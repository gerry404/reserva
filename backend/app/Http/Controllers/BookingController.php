<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookingResource;
use App\Mail\BookingConfirmedNotification;
use App\Models\Booking;
use App\Support\WhatsAppLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The merchant's own bookings. Every route runs behind auth:sanctum and
 * EnsureBusinessExists, and every single-row action goes through BookingPolicy.
 */
class BookingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status'   => ['nullable', 'string', 'in:' . implode(',', Booking::STATUSES)],
            'date'     => ['nullable', 'date_format:Y-m-d'],
            'from'     => ['nullable', 'date_format:Y-m-d'],
            'to'       => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'search'   => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        // Tout sauf le statut. Les compteurs par statut se lisent sur cette
        // base : filtrer sur « confirmé » ne doit pas faire tomber à zéro le
        // nombre de réservations en attente, qui est précisément ce que le
        // commerçant regarde pour décider de changer de filtre.
        $base = Booking::query()
            ->forBusiness($request->user()->business->id)
            ->when($validated['date'] ?? null, fn ($q, $date) => $q->whereDate('date', $date))
            ->when($validated['from'] ?? null, fn ($q, $from) => $q->whereDate('date', '>=', $from))
            ->when($validated['to'] ?? null, fn ($q, $to) => $q->whereDate('date', '<=', $to))
            ->when($validated['search'] ?? null, fn ($q, $term) => $q->where(function ($q) use ($term) {
                // escapeLike: a customer legitimately named "100%" must not turn
                // into a wildcard that matches the whole table.
                $like = '%' . $this->escapeLike($term) . '%';
                $q->where('customer_name', 'like', $like)
                  ->orWhere('customer_phone', 'like', $like)
                  ->orWhere('reference', 'like', $like);
            }));

        $bookings = $base->clone()
            // Prices are printed in the business currency, at both levels of
            // the payload, so the service's own parent has to come along too,
            // or it is one extra query per row.
            ->with('service.business', 'business')
            ->when($validated['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('starts_at')
            ->paginate($validated['per_page'] ?? 20)
            ->withQueryString();

        return BookingResource::collection($bookings)
            ->additional(['meta' => ['counts' => $this->countsByStatus($base)]])
            ->response();
    }

    /**
     * Le nombre de réservations par statut, tous statuts présents.
     *
     * Une seule requête agrégée plutôt qu'un compte par statut, et les statuts
     * absents sont ramenés à zéro : sans cela l'interface reçoit une clé
     * manquante là où elle attend un nombre, et affiche du vide au lieu d'un 0.
     *
     * @return array<string, int>
     */
    private function countsByStatus(\Illuminate\Database\Eloquent\Builder $base): array
    {
        $comptes = $base->clone()
            ->reorder()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $tous = array_fill_keys(Booking::STATUSES, 0);

        return ['all' => array_sum($comptes)] + array_map('intval', array_merge($tous, $comptes));
    }

    public function show(Request $request, Booking $booking): BookingResource
    {
        $this->authorize('view', $booking);

        return new BookingResource($booking->load('service.business', 'business'));
    }

    /**
     * Move a booking along its lifecycle.
     *
     * Confirming is the one transition with side effects: the customer gets an
     * email if we have one, and the merchant always gets a ready-to-send
     * WhatsApp link, which for most of them is the notification that matters.
     */
    public function updateStatus(Request $request, Booking $booking): JsonResponse
    {
        $this->authorize('update', $booking);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', Booking::STATUSES)],
        ]);

        $wasConfirmed = $booking->status === Booking::STATUS_CONFIRMED;

        $booking->update(['status' => $validated['status']]);
        $booking->load('service.business', 'business');

        $payload = ['booking' => new BookingResource($booking)];

        if ($validated['status'] === Booking::STATUS_CONFIRMED) {
            // Only on the transition into confirmed; re-saving a confirmed
            // booking must not email the customer a second time.
            if (! $wasConfirmed) {
                $this->emailCustomer($booking);
            }

            $payload['whatsapp_link'] = WhatsAppLink::confirmation($booking);
        }

        return response()->json($payload);
    }

    /**
     * Cancelling, not deleting: the row is the merchant's record of what
     * happened, and the freed slot has to become bookable again.
     */
    public function destroy(Request $request, Booking $booking): JsonResponse
    {
        $this->authorize('delete', $booking);

        $booking->update(['status' => Booking::STATUS_CANCELLED]);

        return response()->json([
            'message' => 'Réservation annulée.',
            'booking' => new BookingResource($booking->fresh()->load('service.business', 'business')),
        ]);
    }

    /**
     * Streamed so a busy salon exporting two years of history does not have to
     * fit all of it in memory at once.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $businessId = $request->user()->business->id;
        $filename   = 'reservations-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($businessId) {
            $handle = fopen('php://output', 'w');

            // BOM so Excel opens the accented French headers correctly.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Référence', 'Client', 'Téléphone', 'Email', 'Service',
                'Date', 'Début', 'Fin', 'Durée (min)', 'Prix', 'Statut', 'Notes',
            ], ';');

            Booking::query()
                ->forBusiness($businessId)
                ->with('service')
                ->orderBy('starts_at')
                ->chunk(500, function ($bookings) use ($handle) {
                    foreach ($bookings as $booking) {
                        fputcsv($handle, [
                            $booking->reference,
                            $booking->customer_name,
                            $booking->customer_phone,
                            $booking->customer_email ?? '',
                            $booking->service?->name ?? '',
                            $booking->date->format('d/m/Y'),
                            $booking->time_slot,
                            $booking->ends_at?->format('H:i') ?? '',
                            $booking->duration,
                            (int) $booking->price,
                            $booking->status_label,
                            $booking->notes ?? '',
                        ], ';');
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    // ─── Internals ───────────────────────────────────────────────────────

    private function emailCustomer(Booking $booking): void
    {
        if (! $booking->customer_email) {
            return;
        }

        try {
            Mail::to($booking->customer_email)
                ->queue(new BookingConfirmedNotification($booking, $booking->business));
        } catch (\Throwable $e) {
            // The status change is what matters; a mail outage must not undo it.
            Log::error('Confirmation email could not be queued', [
                'booking' => $booking->reference,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    private function escapeLike(string $term): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $term);
    }
}
