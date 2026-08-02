<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande de réservation reçue</title>
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:40px 16px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.08);">

                    <tr>
                        <td style="background:linear-gradient(135deg,#6366f1,#4f46e5);padding:28px 32px;text-align:center;">
                            <div style="font-size:40px;margin-bottom:8px;">📩</div>
                            <h1 style="margin:0;color:#ffffff;font-size:20px;font-weight:800;">Demande bien reçue</h1>
                            <p style="margin:6px 0 0;color:rgba(255,255,255,0.8);font-size:14px;">{{ $business->name }}</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 20px;color:#374151;font-size:15px;line-height:1.5;">
                                Bonjour <strong>{{ $booking->customer_name }}</strong>,<br>
                                Nous avons transmis votre demande à <strong>{{ $business->name }}</strong>.
                                Elle sera confirmée sous peu — vous recevrez un second email à ce moment-là.
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#eef2ff;border-radius:12px;border:1px solid #c7d2fe;margin-bottom:24px;">
                                <tr>
                                    <td style="padding:20px;">
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding:6px 0;color:#6b7280;font-size:13px;width:110px;">Service</td>
                                                <td style="padding:6px 0;color:#111827;font-size:14px;font-weight:600;">{{ $booking->service->name ?? '—' }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:6px 0;color:#6b7280;font-size:13px;">Date</td>
                                                <td style="padding:6px 0;color:#111827;font-size:14px;font-weight:600;">{{ $booking->date->locale('fr')->isoFormat('dddd D MMMM YYYY') }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:6px 0;color:#6b7280;font-size:13px;">Heure</td>
                                                <td style="padding:6px 0;color:#111827;font-size:14px;font-weight:600;">{{ $booking->time_slot }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:6px 0;color:#6b7280;font-size:13px;">Référence</td>
                                                <td style="padding:6px 0;color:#4f46e5;font-size:14px;font-weight:700;font-family:monospace;">{{ $booking->reference }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding-bottom:8px;">
                                        <a href="{{ $trackUrl }}"
                                           style="display:inline-block;background-color:#4f46e5;color:#ffffff;text-decoration:none;padding:12px 28px;border-radius:10px;font-size:14px;font-weight:700;">
                                            Suivre ou annuler ma réservation
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:16px 0 0;color:#9ca3af;font-size:12px;line-height:1.6;text-align:center;">
                                Gardez cette référence : elle vous permet de retrouver votre réservation
                                avec le numéro de téléphone que vous avez indiqué.
                            </p>

                            @if ($business->whatsapp || $business->phone)
                                <p style="margin:20px 0 0;color:#6b7280;font-size:13px;line-height:1.6;text-align:center;">
                                    Une question ? Contactez {{ $business->name }} au
                                    <strong>{{ $business->whatsapp ?: $business->phone }}</strong>.
                                </p>
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:20px 32px;background-color:#f9fafb;text-align:center;">
                            <p style="margin:0;color:#9ca3af;font-size:12px;">
                                Réservation effectuée via <strong style="color:#6b7280;">Nuvo</strong>
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
