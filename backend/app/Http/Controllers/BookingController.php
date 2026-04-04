<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Mail\BookingConfirmedNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

class BookingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $business = $request->user()->business;

        $query = Booking::forBusiness($business->id)
            ->with('service')
            ->orderBy('date', 'desc')
            ->orderBy('time_slot', 'desc');

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date')) {
            $query->where('date', $request->date);
        }

        if ($request->filled('month')) {
            $query->whereMonth('date', $request->month)
                  ->whereYear('date', $request->year ?? now()->year);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('customer_name', 'like', '%' . $request->search . '%')
                  ->orWhere('customer_phone', 'like', '%' . $request->search . '%')
                  ->orWhere('reference', 'like', '%' . $request->search . '%');
            });
        }

        $bookings = $query->paginate($request->get('per_page', 20));

        return response()->json($bookings);
    }

    public function show(Request $request, Booking $booking): JsonResponse
    {
        abort_unless($booking->business_id === $request->user()->business->id, 403);
        return response()->json($booking->load('service'));
    }

    public function updateStatus(Request $request, Booking $booking): JsonResponse
    {
        abort_unless($booking->business_id === $request->user()->business->id, 403);

        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,cancelled,completed',
        ]);

        $booking->update(['status' => $validated['status']]);
        $booking->load('business', 'service');

        $response = $booking->fresh()->load('service')->toArray();

        // Send notifications when confirmed
        if ($validated['status'] === 'confirmed') {
            // Email the customer if they provided an email
            if ($booking->customer_email) {
                try {
                    Mail::to($booking->customer_email)
                        ->queue(new BookingConfirmedNotification($booking, $booking->business));
                } catch (\Exception $e) {
                    \Log::error('Confirmation email failed: ' . $e->getMessage());
                }
            }

            // Build WhatsApp link for the merchant to notify the customer
            $phone = preg_replace('/[^0-9]/', '', $booking->customer_phone);
            $date = $booking->date->locale('fr')->isoFormat('dddd D MMMM YYYY');
            $msg = "Bonjour {$booking->customer_name},\n\n"
                 . "✅ Votre réservation chez *{$booking->business->name}* est confirmée !\n\n"
                 . "📋 *Détails :*\n"
                 . "• Service : {$booking->service?->name}\n"
                 . "• Date : {$date}\n"
                 . "• Heure : {$booking->time_slot}\n"
                 . "• Réf : {$booking->reference}\n\n"
                 . "À bientôt ! 🙏";

            $response['whatsapp_link'] = "https://wa.me/{$phone}?text=" . urlencode($msg);
        }

        return response()->json($response);
    }

    public function destroy(Request $request, Booking $booking): JsonResponse
    {
        abort_unless($booking->business_id === $request->user()->business->id, 403);
        $booking->update(['status' => 'cancelled']);
        return response()->json(['message' => 'Réservation annulée.']);
    }

    public function exportCsv(Request $request)
    {
        $business = $request->user()->business;
        $bookings = Booking::forBusiness($business->id)
            ->with('service')
            ->orderBy('date')
            ->orderBy('time_slot')
            ->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="reservations-' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($bookings) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            fputcsv($file, ['Référence', 'Client', 'Téléphone', 'Email', 'Service', 'Date', 'Heure', 'Statut', 'Notes'], ';');

            foreach ($bookings as $b) {
                fputcsv($file, [
                    $b->reference,
                    $b->customer_name,
                    $b->customer_phone,
                    $b->customer_email ?? '',
                    $b->service?->name ?? '',
                    $b->date->format('d/m/Y'),
                    $b->time_slot,
                    $b->status_label,
                    $b->notes ?? '',
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
