<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Codigo de recuperacion</title>
</head>

<body style="margin:0; padding:0; background-color:#f7faf8; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color:#3f3f3f;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f7faf8; padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px; background-color:#ffffff; border:1px solid rgba(63,63,63,0.12); border-radius:20px; box-shadow:0 16px 40px rgba(40,116,82,0.08); overflow:hidden;">
                    <tr>
                        <td style="padding:28px 32px 16px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="vertical-align:middle;">
                                        <div style="display:inline-flex; align-items:center; gap:12px;">
                                            <div style="font-size:14px; font-weight:700; letter-spacing:0.04em; color:#287452;">GIACOMAZZI GLASS</div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 8px;">
                            <h1 style="margin:0 0 8px; font-size:22px; font-weight:700; color:#3f3f3f;">Codigo de recuperacion</h1>
                            <p style="margin:0; font-size:14px; color:rgba(63,63,63,0.72);">Usa el siguiente codigo para restablecer tu contrasena. Si no solicitaste este cambio, puedes ignorar este mensaje.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 32px;">
                            <div style="background:rgba(40,116,82,0.08); border:1px dashed rgba(40,116,82,0.35); border-radius:14px; padding:18px; text-align:center;">
                                <div style="font-size:12px; color:#287452; letter-spacing:0.16em; text-transform:uppercase; font-weight:700;">Tu codigo</div>
                                <div style="font-size:28px; font-weight:800; letter-spacing:0.2em; color:#1f5a40; margin-top:6px;">{{ $verificationCode }}</div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 28px;">
                            <p style="margin:0; font-size:13px; color:rgba(63,63,63,0.68);">Este codigo tiene una validez limitada. Te recomendamos completar el proceso lo antes posible.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 32px; background-color:#ffffff; border-top:1px solid rgba(63,63,63,0.08); font-size:12px; color:rgba(63,63,63,0.6); text-align:center;">
                            <div style="margin-bottom:4px;">© {{ date('Y') }} Giacomazzi Glass</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>