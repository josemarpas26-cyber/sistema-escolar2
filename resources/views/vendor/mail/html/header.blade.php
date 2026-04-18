<tr>
<td class="header" style="background: linear-gradient(135deg, #1a3a6b 0%, #1e4d9b 60%, #2563c7 100%); padding: 36px 48px 32px; border-radius: 12px 12px 0 0;">
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">

        {{-- Logo + nome da app --}}
        <tr>
            <td style="padding-bottom: 28px;">
                <a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
                    <table cellpadding="0" cellspacing="0" role="presentation">
                        <tr>
                            <td style="vertical-align: middle; padding-right: 12px;">
                                <img src="{{ asset('images/logo1.png') }}"
                                     alt="{{ config('app.name') }}"
                                     width="40" height="40"
                                     style="display: block; border-radius: 10px; border: 2px solid rgba(255,255,255,0.25);">
                            </td>
                            <td style="vertical-align: middle;">
                                <span style="color: #ffffff; font-size: 15px; font-weight: 700;
                                             font-family: Georgia, 'Times New Roman', serif;
                                             letter-spacing: 0.3px;">
                                    {{ config('app.name') }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </a>
            </td>
        </tr>

        {{-- Separador --}}
        <tr>
            <td style="padding-bottom: 24px;">
                <div style="width: 48px; height: 3px; background: rgba(255,255,255,0.4); border-radius: 2px;"></div>
            </td>
        </tr>

        {{-- Título principal --}}
        <tr>
            <td style="color: #ffffff; font-size: 28px; font-weight: 800; line-height: 1.15;
                        padding-bottom: 10px; font-family: Georgia, 'Times New Roman', serif;
                        letter-spacing: -0.5px;">
                Sistema de<br>Gestão Escolar
            </td>
        </tr>

        {{-- Subtítulo --}}
        <tr>
            <td style="color: rgba(219,234,254,0.85); font-size: 13px; line-height: 1.5;
                        font-family: Arial, Helvetica, sans-serif; font-weight: 400;
                        padding-bottom: 4px;">
                Comunicação oficial da plataforma
            </td>
        </tr>

    </table>
</td>
</tr>