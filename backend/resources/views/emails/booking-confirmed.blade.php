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
                        <td style="background:linear-gradient(135deg,#10b981,#059669);padding:28px 32px;text-align:center;">
                            <div style="font-size:40px;margin-bottom:8px;">✅</div>
                            <h1 style="margin:0;color:#ffffff;font-size:20px;font-weight:800;">Réservation confirmée !</h1>
                            <p style="margin:6px 0 0;color:rgba(255,255,255,0.8);font-size:14px;">{{ $business->name }}</p>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 20px;color:#374151;font-size:15px;line-height:1.5;">
                                Bonjour <strong>{{ $booking->customer_name }}</strong>,<br>
                                Votre réservation a été confirmée par <strong>{{ $business->name }}</strong>. Voici le récapitulatif :
                            </p>

                            <!-- Booking details card -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f0fdf4;border-radius:12px;border:1px solid #bbf7d0;margin-bottom:24px;">
                                <tr>
                                    <td style="padding:20px;">
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding:6px 0;color:#6b7280;font-size:13px;width:110px;">Service</td>
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
                                                <td style="padding:6px 0;color:#10b981;font-size:14px;font-weight:700;font-family:monospace;">{{ $booking->reference }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            @if($business->address || $business->city)
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f9fafb;border-radius:12px;border:1px solid #e5e7eb;margin-bottom:24px;">
                                <tr>
                                    <td style="padding:16px 20px;">
                                        <p style="margin:0;color:#6b7280;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">📍 Adresse</p>
                                        <p style="margin:6px 0 0;color:#111827;font-size:14px;">
                                            {{ $business->address ?? '' }}{{ $business->address && $business->city ? ', ' : '' }}{{ $business->city ?? '' }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            @endif

                            @if($business->phone)
                            <p style="margin:0 0 4px;color:#6b7280;font-size:13px;text-align:center;">
                                En cas de besoin, contactez <strong>{{ $business->name }}</strong> au <a href="tel:{{ $business->phone }}" style="color:#9333ea;text-decoration:none;font-weight:600;">{{ $business->phone }}</a>
                            </p>
                            @endif
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding:20px 32px;border-top:1px solid #e5e7eb;text-align:center;">
                            <p style="margin:0;color:#9ca3af;font-size:12px;">
                                Cet email a été envoyé automatiquement par Réserva.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
