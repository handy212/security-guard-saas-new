<style>
    @page { margin: 0; }

    body {
        margin: 0;
        padding: 0;
        font-family: DejaVu Sans, sans-serif;
        color: #18181b;
    }

    table { border-collapse: collapse; }

    .page-break { page-break-before: always; }

    .card {
        width: 153pt; /* 2.125" */
        height: 243pt; /* 3.375" */
        border: 0.5pt solid #d4d4d8;
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
        letter-spacing: 0.2pt;
        line-height: 1.2;
        color: #ffffff;
    }

    .company-tagline {
        font-size: 5.5pt;
        color: rgba(255, 255, 255, 0.82);
        margin-top: 2pt;
        line-height: 1.25;
    }

    .staff-name {
        font-size: 11pt;
        font-weight: bold;
        color: #18181b;
        text-align: center;
        line-height: 1.2;
    }

    .staff-name-lg {
        font-size: 12pt;
        font-weight: bold;
        text-align: center;
        color: #18181b;
        line-height: 1.2;
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
        color: #71717a;
        font-weight: bold;
    }

    .info-value {
        font-size: 6.5pt;
        font-weight: bold;
        color: #18181b;
        text-align: right;
    }

    .info-row td {
        padding: 5pt 0;
        border-bottom: 0.5pt solid #e4e4e7;
    }

    .footer-bg {
        background-color: #fafafa;
        border-top: 0.5pt solid #e4e4e7;
    }

    .footer-dark {
        background-color: #18181b;
        color: #a1a1aa;
    }

    .id-number {
        font-size: 6pt;
        color: #71717a;
        font-weight: bold;
    }

    .id-mono {
        font-family: DejaVu Sans Mono, monospace;
        font-size: 6pt;
        color: #e4e4e7;
    }

    .scan-hint {
        font-size: 5pt;
        color: #a1a1aa;
        line-height: 1.3;
    }

    .initials {
        background: #e4e4e7;
        color: #71717a;
        font-size: 16pt;
        font-weight: bold;
        text-align: center;
        vertical-align: middle;
    }

    .initials-lg {
        background: #d4d4d8;
        color: #71717a;
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
        color: #71717a;
        text-transform: uppercase;
        letter-spacing: 0.4pt;
    }

    .info-box-value {
        font-size: 6.5pt;
        font-weight: bold;
        color: #18181b;
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
        color: #71717a;
        margin-top: 1pt;
    }

    .creative-label {
        font-size: 5pt;
        color: #a1a1aa;
        text-transform: uppercase;
        letter-spacing: 0.4pt;
    }

    .creative-value {
        font-size: 7pt;
        font-weight: bold;
        color: #18181b;
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

    .contact-label { font-weight: bold; color: #3f3f46; }

    .premium-badge-pdf {
        position: absolute;
        top: 6pt;
        left: 8pt;
        background: #ffffff;
        color: {{ $brand['brand_color_dark'] }};
        font-size: 4.5pt;
        font-weight: bold;
        letter-spacing: 0.5pt;
        text-transform: uppercase;
        padding: 2pt 5pt;
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
        background: #fafafa;
        border: 0.5pt solid #e4e4e7;
        border-top: 1.5pt solid {{ $brand['brand_color'] }};
        padding: 4pt 5pt;
    }

    .premium-chip-label-pdf {
        font-size: 4.5pt;
        color: #71717a;
        text-transform: uppercase;
        letter-spacing: 0.3pt;
        font-weight: bold;
    }

    .premium-chip-value-pdf {
        font-size: 6pt;
        font-weight: bold;
        color: #18181b;
        margin-top: 1pt;
    }

    .footer-premium {
        background-color: #18181b;
        color: #a1a1aa;
    }
</style>
