@php
    switch($color ?? 'primary') {
        case 'success': $bg = '#16a34a'; break;
        case 'error':   $bg = '#dc2626'; break;
        default:        $bg = '#1266d3';
    }
@endphp
<table width="100%" cellpadding="0" cellspacing="0" style="margin:28px 0">
    <tbody>
        <tr>
            <td align="center">
                <!--[if mso]>
                <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word"
                    href="{{ $url }}"
                    style="height:46px;v-text-anchor:middle;width:220px;" arcsize="22%" strokecolor="{{ $bg }}" fillcolor="{{ $bg }}">
                    <w:anchorlock/>
                    <center style="color:#ffffff;font-family:sans-serif;font-size:14px;font-weight:600;">{{ $slot }}</center>
                </v:roundrect>
                <![endif]-->
                <!--[if !mso]><!-->
                <a href="{{ $url }}"
                   style="background-color:{{ $bg }};border-radius:10px;color:#ffffff;display:inline-block;font-size:14px;font-weight:600;line-height:1;padding:14px 32px;text-decoration:none;-webkit-text-size-adjust:none;">
                    {{ $slot }}
                </a>
                <!--<![endif]-->
            </td>
        </tr>
    </tbody>
</table>
