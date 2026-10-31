<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
</head>
<body style="margin:0;padding:0;background-color:#fff8f5;color:#27272a;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#fff8f5;">
        <tr>
            <td align="center" style="padding:36px 16px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;">
                    <tr>
                        <td align="center" style="padding:0 0 28px;">
                            <a href="{{ config('app.url') }}" style="text-decoration:none;color:#1a5c40;">
                                <img src="{{ $message->embed(public_path('assets/img/pawpaw-email-logo.png')) }}" alt="PawPaw" width="160" style="display:block;width:160px;max-width:100%;height:auto;border:0;">
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px 28px;background-color:#ffffff;border:1px solid #f0e5df;border-radius:16px;">
                            <p style="margin:0 0 12px;color:#1a5c40;font-size:12px;font-weight:bold;letter-spacing:1px;">AKUN MITRA PAWPAW</p>
                            <h1 style="margin:0 0 24px;font-size:26px;line-height:34px;color:#27272a;text-align:center;">Atur ulang password Anda</h1>
                            <p style="margin:0 0 16px;font-size:16px;font-weight:bold;line-height:26px;">{{ $greeting }}</p>
                            @foreach ($introLines as $line)
                                <p style="margin:0 0 16px;font-size:16px;line-height:26px;color:#52525b;">{{ $line }}</p>
                            @endforeach
                            <table role="presentation" align="center" cellspacing="0" cellpadding="0" style="margin:28px auto;">
                                <tr>
                                    <td align="center" bgcolor="#ee6d52" style="border-radius:8px;">
                                        <a href="{{ $actionUrl }}" style="display:inline-block;padding:15px 24px;border:1px solid #ee6d52;border-radius:8px;color:#ffffff;font-size:16px;font-weight:bold;text-decoration:none;">{{ $actionText }}</a>
                                    </td>
                                </tr>
                            </table>
                            @foreach ($outroLines as $line)
                                <p style="margin:0 0 16px;font-size:15px;line-height:25px;color:#52525b;">{{ $line }}</p>
                            @endforeach
                            <p style="margin:24px 0 6px;font-size:15px;line-height:25px;color:#1a5c40;">{{ $salutation }}</p>
                            <img src="{{ $message->embed(public_path('assets/img/pawpaw-team-logo.png')) }}" alt="Tim PawPaw" width="180" height="45" style="display:block;width:180px;max-width:100%;height:auto;border:0;margin:0 0 24px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="border-top:1px solid #f0e5df;padding-top:20px;">
                                        <p style="margin:0 0 10px;font-size:12px;line-height:20px;color:#71717a;">Tombol tidak dapat dibuka? Salin dan tempel tautan berikut ke browser Anda:</p>
                                        <a href="{{ $actionUrl }}" style="font-size:12px;line-height:20px;color:#1a5c40;word-break:break-all;overflow-wrap:anywhere;">{{ $actionUrl }}</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:24px 16px 0;">
                            <p style="margin:0;font-size:12px;line-height:20px;color:#71717a;">&copy; {{ now()->year }} PawPaw. Seluruh hak cipta dilindungi.</p>
                            <p style="margin:6px 0 0;font-size:12px;line-height:20px;color:#71717a;">Email ini dikirim otomatis. Mohon tidak membalas email ini.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

