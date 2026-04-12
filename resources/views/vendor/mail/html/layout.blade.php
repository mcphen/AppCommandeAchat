<!DOCTYPE html>
<html lang="fr" xmlns:v="urn:schemas-microsoft-com:vml">
<head>
    <meta charset="utf-8">
    <meta name="x-apple-disable-message-reformatting">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no">
    <!--[if mso]>
    <noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
    <![endif]-->
    <style>
        *,::after,::before { box-sizing: border-box; }
        body, html { -webkit-text-size-adjust: 100%; margin: 0; padding: 0; }
        body { background-color: #eef2f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e2a3a; }
        a { color: #1266d3; text-decoration: none; }
        a:hover { text-decoration: underline; }
        img { border: 0; display: block; max-width: 100%; }
        table { border-collapse: collapse; }
        p { font-size: 15px; line-height: 1.7; color: #374151; margin: 0 0 16px; }
        h1 { font-size: 20px; font-weight: 700; color: #1e2a3a; margin: 0 0 20px; }
        strong, b { font-weight: 600; color: #1e2a3a; }
        hr { border: none; border-top: 1px solid #e5eaf2; margin: 24px 0; }
        @media only screen and (max-width: 600px) {
            .wrapper { padding: 16px 8px !important; }
            .header  { padding: 20px 24px !important; border-radius: 10px 10px 0 0 !important; }
            .body    { padding: 28px 20px !important; }
        }
    </style>
</head>
<body>

<table width="100%" cellpadding="0" cellspacing="0"
    class="wrapper"
    style="background-color:#eef2f7; padding:32px 16px;">
    <tbody>
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0"
                    style="max-width:600px; width:100%;">
                    <tbody>

                        {{-- ── Logo / En-tête ── --}}
                        <tr>
                            <td class="header"
                                style="background-color:#1266d3; border-radius:12px 12px 0 0; padding:28px 40px; text-align:center;">
                                @php
                                    $logoPath = \App\Models\AppSetting::get('company_logo');
                                    $logoUrl  = $logoPath ? asset('storage/' . $logoPath) : null;
                                    $appName  = config('app.name', 'Application');
                                @endphp
                                @if($logoUrl)
                                    <img src="{{ $logoUrl }}"
                                         alt="{{ $appName }}"
                                         style="max-height:56px; max-width:220px; display:block; margin:0 auto; object-fit:contain;">
                                @else
                                    <span style="color:#ffffff; font-size:20px; font-weight:700; letter-spacing:0.3px;">
                                        {{ $appName }}
                                    </span>
                                @endif
                            </td>
                        </tr>

                        {{-- ── Contenu ── --}}
                        <tr>
                            <td class="body"
                                style="background-color:#ffffff; padding:40px; border-radius:0 0 12px 12px;">
                                {{ $slot }}

                                @isset($subcopy)
                                    <hr style="border:none; border-top:1px solid #e5eaf2; margin:28px 0;">
                                    {{ $subcopy }}
                                @endisset
                            </td>
                        </tr>

                        {{-- ── Pied de page ── --}}
                        <tr>
                            <td style="padding:24px 0; text-align:center;">
                                <p style="color:#94a3b8; font-size:12px; line-height:1.6; margin:0;">
                                    © {{ date('Y') }} {{ $appName }} &nbsp;·&nbsp;
                                    Email envoyé automatiquement, merci de ne pas y répondre.
                                </p>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </td>
        </tr>
    </tbody>
</table>

</body>
</html>
