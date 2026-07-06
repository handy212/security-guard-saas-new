<style>
    @page { margin: 0; }

    body {
        margin: 0;
        padding: 0;
        font-family: DejaVu Sans, sans-serif;
        color: #0f172a;
    }

    table { border-collapse: collapse; }

    .page-break { page-break-before: always; }

    .card {
        width: 153pt;
        height: 243pt;
        border: 0.5pt solid #cbd5e1;
    }

    .header-bg {
        background-color: {{ $brand['brand_color'] }};
        color: #ffffff;
    }

    .header-bg-dark {
        background-color: {{ $brand['brand_color_dark'] }};
        color: #ffffff;
    }

    .company-name {
        font-size: 8pt;
        font-weight: bold;
        letter-spacing: 0.3pt;
    }

    .company-tagline {
        font-size: 5.5pt;
        color: #e2e8f0;
        margin-top: 2pt;
    }

    .staff-name {
        font-size: 11pt;
        font-weight: bold;
        color: #0f172a;
        text-align: center;
    }

    .staff-name-lg {
        font-size: 12pt;
        font-weight: bold;
        text-align: center;
    }

    .staff-role {
        font-size: 7pt;
        font-weight: bold;
        color: {{ $brand['brand_color'] }};
        text-align: center;
        margin-top: 2pt;
    }

    .staff-role-upper {
        font-size: 6.5pt;
        font-weight: bold;
        color: {{ $brand['brand_color'] }};
        text-align: center;
        text-transform: uppercase;
        letter-spacing: 0.8pt;
        margin-top: 3pt;
    }

    .info-label {
        font-size: 6pt;
        color: #64748b;
        font-weight: bold;
    }

    .info-value {
        font-size: 6.5pt;
        font-weight: bold;
        color: #0f172a;
        text-align: right;
    }

    .info-row td {
        padding: 5pt 0;
        border-bottom: 0.5pt solid #e2e8f0;
    }

    .footer-bg {
        background-color: #f8fafc;
        border-top: 0.5pt solid #e2e8f0;
    }

    .footer-dark {
        background-color: #0f172a;
        color: #94a3b8;
    }

    .id-number {
        font-size: 6pt;
        color: #64748b;
        font-weight: bold;
    }

    .id-mono {
        font-family: DejaVu Sans Mono, monospace;
        font-size: 6pt;
    }

    .scan-hint {
        font-size: 5pt;
        color: #94a3b8;
    }

    .initials {
        background: #e2e8f0;
        color: #64748b;
        font-size: 16pt;
        font-weight: bold;
        text-align: center;
        vertical-align: middle;
    }

    .initials-lg {
        background: #cbd5e1;
        color: #64748b;
        font-size: 18pt;
        font-weight: bold;
        text-align: center;
        vertical-align: middle;
    }

    .info-box {
        background: #ffffff;
        border-left: 2pt solid {{ $brand['brand_color'] }};
        padding: 5pt 6pt;
    }

    .info-box-label {
        font-size: 5pt;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.4pt;
    }

    .info-box-value {
        font-size: 6.5pt;
        font-weight: bold;
        color: #0f172a;
        margin-top: 1pt;
    }

    .accent-bar {
        background-color: {{ $brand['brand_color'] }};
        width: 5pt;
    }

    .company-accent {
        font-size: 6pt;
        font-weight: bold;
        color: {{ $brand['brand_color'] }};
        text-transform: uppercase;
        letter-spacing: 0.6pt;
    }

    .creative-name {
        font-size: 10pt;
        font-weight: bold;
        margin-top: 2pt;
    }

    .creative-role {
        font-size: 6.5pt;
        color: #64748b;
        margin-top: 1pt;
    }

    .creative-label {
        font-size: 5pt;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.4pt;
    }

    .creative-value {
        font-size: 7pt;
        font-weight: bold;
        margin-top: 1pt;
    }

    .creative-icon {
        background-color: {{ $brand['brand_color'] }};
        color: #ffffff;
        font-size: 5pt;
        font-weight: bold;
        text-align: center;
        vertical-align: middle;
        width: 18pt;
        height: 18pt;
        opacity: 0.85;
    }

    .notice {
        font-size: 6pt;
        line-height: 1.4;
        padding: 8pt 10pt;
        vertical-align: middle;
    }

    .contact {
        font-size: 6pt;
        line-height: 1.45;
        padding: 10pt;
        vertical-align: top;
    }

    .contact-label { font-weight: bold; color: #334155; }

    .premium-badge-pdf {
        position: absolute;
        top: 6pt;
        left: 8pt;
        background: #ffffff;
        color: {{ $brand['brand_color_dark'] }};
        font-size: 4.5pt;
        font-weight: bold;
        letter-spacing: 0.6pt;
        text-transform: uppercase;
        padding: 2pt 4pt;
    }

    .premium-role-pill-pdf {
        display: inline-block;
        background-color: {{ $brand['brand_color'] }};
        color: #ffffff;
        font-size: 5.5pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.4pt;
        padding: 2pt 6pt;
        margin-top: 3pt;
        text-align: center;
    }

    .premium-chip-pdf {
        background: #f8fafc;
        border: 0.5pt solid #e2e8f0;
        border-top: 1.5pt solid {{ $brand['brand_color'] }};
        padding: 4pt 5pt;
    }

    .premium-chip-label-pdf {
        font-size: 4.5pt;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.3pt;
        font-weight: bold;
    }

    .premium-chip-value-pdf {
        font-size: 6pt;
        font-weight: bold;
        color: #0f172a;
        margin-top: 1pt;
    }

    .footer-premium {
        background-color: #0f172a;
        color: #94a3b8;
    }
</style>
