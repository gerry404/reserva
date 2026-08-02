<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:40px 16px;">
        <tr><td align="center">
            <table width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.08);">
                <tr>
                    <td style="background:linear-gradient(135deg,#9333ea,#7e22ce);padding:36px 32px;text-align:center;">
                        <div style="font-size:48px;margin-bottom:12px;">🎉</div>
                        <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:800;">Bienvenue sur Nuvo !</h1>
                        <p style="margin:8px 0 0;color:rgba(255,255,255,0.8);font-size:14px;">Votre commerce en ligne en quelques clics</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:32px;">
                        <p style="margin:0 0 20px;color:#374151;font-size:15px;line-height:1.6;">
                            Bonjour <strong>{{ $user->name }}</strong>,<br><br>
                            Merci d'avoir rejoint Nuvo ! Votre compte a été créé avec succès.
                            @if($user->plan !== 'free' && $user->plan_expires_at)
                            <br><br>🎁 Vous bénéficiez d'un <strong>essai Pro gratuit de 14 jours</strong>. Profitez de toutes les fonctionnalités premium !
                            @endif
                        </p>

                        <p style="margin:0 0 16px;color:#374151;font-size:15px;font-weight:700;">Pour bien démarrer :</p>

                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                            <tr>
                                <td style="padding:12px 16px;background-color:#faf5ff;border-radius:12px;margin-bottom:8px;">
                                    <p style="margin:0;color:#7e22ce;font-size:14px;font-weight:700;">1️⃣ Ajoutez vos services</p>
                                    <p style="margin:4px 0 0;color:#6b7280;font-size:13px;">Coiffure, massage, consultation… Configurez vos prestations avec durée et prix.</p>
                                </td>
                            </tr>
                            <tr><td style="height:8px;"></td></tr>
                            <tr>
                                <td style="padding:12px 16px;background-color:#f0fdf4;border-radius:12px;">
                                    <p style="margin:0;color:#15803d;font-size:14px;font-weight:700;">2️⃣ Partagez votre lien</p>
                                    <p style="margin:4px 0 0;color:#6b7280;font-size:13px;">Envoyez votre page de réservation sur WhatsApp, Instagram, Facebook…</p>
                                </td>
                            </tr>
                            <tr><td style="height:8px;"></td></tr>
                            <tr>
                                <td style="padding:12px 16px;background-color:#eff6ff;border-radius:12px;">
                                    <p style="margin:0;color:#1d4ed8;font-size:14px;font-weight:700;">3️⃣ Recevez vos réservations</p>
                                    <p style="margin:4px 0 0;color:#6b7280;font-size:13px;">Vous serez notifié par email et WhatsApp à chaque nouvelle réservation.</p>
                                </td>
                            </tr>
                        </table>

                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr><td align="center">
                                <a href="{{ config('app.frontend_url', 'http://localhost:5173') }}/dashboard"
                                   style="display:inline-block;background:linear-gradient(135deg,#9333ea,#7e22ce);color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;padding:14px 36px;border-radius:12px;">
                                    Accéder à mon tableau de bord →
                                </a>
                            </td></tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding:20px 32px;border-top:1px solid #e5e7eb;text-align:center;">
                        <p style="margin:0;color:#9ca3af;font-size:12px;">
                            Besoin d'aide ? Répondez à cet email ou consultez notre <a href="{{ config('app.frontend_url') }}/help" style="color:#9333ea;text-decoration:none;">centre d'aide</a>.
                        </p>
                    </td>
                </tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
