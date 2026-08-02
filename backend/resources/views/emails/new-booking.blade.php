<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:40px 16px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.08);">
                    <!-- Header -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#9333ea,#7e22ce);padding:28px 32px;text-align:center;">
                            <h1 style="margin:0;color:#ffffff;font-size:20px;font-weight:800;">Nouvelle réservation</h1>
                            <p style="margin:6px 0 0;color:rgba(255,255,255,0.8);font-size:14px;">{{ $business->name }}</p>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 20px;color:#374151;font-size:15px;line-height:1.5;">
                                Vous avez reçu une nouvelle demande de réservation.
                            </p>

                            <!-- Booking details card -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f9fafb;border-radius:12px;border:1px solid #e5e7eb;margin-bottom:24px;">
                                <tr>
                                    <td style="padding:20px;">
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding:6px 0;color:#6b7280;font-size:13px;width:110px;">Client</td>
                                                <td style="padding:6px 0;color:#111827;font-size:14px;font-weight:600;">{{ $booking->customer_name }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:6px 0;color:#6b7280;font-size:13px;">Téléphone</td>
                                                <td style="padding:6px 0;color:#111827;font-size:14px;font-weight:600;">{{ $booking->customer_phone }}</td>
                                            </tr>
                                            @if($booking->customer_email)
                                            <tr>
                                                <td style="padding:6px 0;color:#6b7280;font-size:13px;">Email</td>
                                                <td style="padding:6px 0;color:#111827;font-size:14px;font-weight:600;">{{ $booking->customer_email }}</td>
                                            </tr>
                                            @endif
                                            <tr>
                                                <td style="padding:6px 0;color:#6b7280;font-size:13px;">Service</td>
                                                <td style="padding:6px 0;color:#111827;font-size:14px;font-weight:600;">{{ $booking->service->name ?? '—' }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:6px 0;color:#6b7280;font-size:13px;">Date</td>
                                                <td style="padding:6px 0;color:#111827;font-size:14px;font-weight:600;">{{ \Carbon\Carbon::parse($booking->date)->locale('fr')->isoFormat('dddd D MMMM YYYY') }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:6px 0;color:#6b7280;font-size:13px;">Heure</td>
                                                <td style="padding:6px 0;color:#111827;font-size:14px;font-weight:600;">{{ $booking->time_slot }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:6px 0;color:#6b7280;font-size:13px;">Référence</td>
                                                <td style="padding:6px 0;color:#9333ea;font-size:14px;font-weight:700;font-family:monospace;">{{ $booking->reference }}</td>
                                            </tr>
                                            @if($booking->notes)
                                            <tr>
                                                <td style="padding:6px 0;color:#6b7280;font-size:13px;vertical-align:top;">Notes</td>
                                                <td style="padding:6px 0;color:#6b7280;font-size:13px;font-style:italic;">{{ $booking->notes }}</td>
                                            </tr>
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA -->
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        <a href="{{ config('app.frontend_url', 'http://localhost:5173') }}/dashboard/bookings"
                                           style="display:inline-block;background-color:#111827;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;padding:12px 32px;border-radius:12px;">
                                            Voir dans le tableau de bord
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding:20px 32px;border-top:1px solid #e5e7eb;text-align:center;">
                            <p style="margin:0;color:#9ca3af;font-size:12px;">
                                Cet email a été envoyé automatiquement par Nuvo.<br>
                                Vous pouvez désactiver ces notifications dans vos <a href="{{ config('app.frontend_url', 'http://localhost:5173') }}/dashboard/settings" style="color:#9333ea;text-decoration:none;">paramètres</a>.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
