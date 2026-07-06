@php
    $scope = ($forPdf ?? false) ? '.id-card-scope' : '.id-card-preview';
    $previewScale = ($forPreview ?? false) ? (float) ($previewScale ?? 1.0) : 1.0;
    $ls = static function (float $px) use ($previewScale): string {
        if ($previewScale === 1.0) {
            return (floor($px) === $px) ? (string) (int) $px : (string) $px;
        }

        return rtrim(rtrim(sprintf('%.2f', $px * $previewScale), '0'), '.');
    };
@endphp
<style>
    /* Landscape CR80 — 445 × 280 design canvas */
    {{ $scope }} .orientation-landscape.id-card {
        width: {{ $ls(445) }}px;
        height: {{ $ls(280) }}px;
        font-size: {{ $ls(16) }}px;
        display: flex;
        flex-direction: column;
        border-radius: {{ $ls(12) }}px;
    }

    {{ $scope }} .ls-shell {
        display: flex;
        flex: 1;
        min-height: 0;
        width: 100%;
        height: 100%;
    }

    {{ $scope }} .ls-shell--row {
        flex-direction: row;
    }

    {{ $scope }} .ls-shell--col {
        flex-direction: column;
    }

    /* —— Modern —— */
    {{ $scope }} .ls-modern-brand {
        width: 42%;
        flex-shrink: 0;
        background: linear-gradient(160deg, var(--theme-color), var(--theme-color-dark));
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: {{ $ls(10) }}px {{ $ls(8) }}px;
        gap: {{ $ls(6) }}px;
        position: relative;
        overflow: hidden;
    }

    {{ $scope }} .ls-modern-brand::before {
        content: '';
        position: absolute;
        inset: 0;
        opacity: 0.1;
        background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0);
        background-size: {{ $ls(16) }}px {{ $ls(16) }}px;
    }

    {{ $scope }} .ls-modern-brand > * {
        position: relative;
        z-index: 1;
    }

    {{ $scope }} .ls-modern-logo {
        height: {{ $ls(26) }}px;
        width: auto;
        max-width: {{ $ls(52) }}px;
        object-fit: contain;
    }

    {{ $scope }} .ls-modern-company {
        color: #fff;
        font-weight: 700;
        font-size: 0.75em;
        line-height: 1.15;
        text-align: center;
        max-width: 100%;
        word-wrap: break-word;
        hyphens: auto;
    }

    {{ $scope }} .ls-modern-tagline {
        color: rgba(255, 255, 255, 0.82);
        font-size: 0.58em;
        text-align: center;
        line-height: 1.25;
        max-width: 100%;
    }

    {{ $scope }} .ls-modern-photo {
        width: {{ $ls(76) }}px;
        height: {{ $ls(76) }}px;
        border-radius: 50%;
        border: {{ $ls(3) }}px solid #fff;
        object-fit: cover;
        box-shadow: 0 {{ $ls(4) }}px {{ $ls(10) }}px rgba(0, 0, 0, 0.18);
        background: #e2e8f0;
    }

    {{ $scope }} .ls-modern-photo.initials {
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6em;
        font-weight: 700;
        color: #94a3b8;
    }

    {{ $scope }} .ls-modern-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-width: 0;
        background: #fff;
    }

    {{ $scope }} .ls-modern-details {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: {{ $ls(12) }}px {{ $ls(14) }}px {{ $ls(6) }}px;
        min-width: 0;
    }

    {{ $scope }} .ls-modern-name {
        font-size: 1.05em;
        font-weight: 700;
        line-height: 1.15;
        margin-bottom: {{ $ls(2) }}px;
        word-wrap: break-word;
    }

    {{ $scope }} .ls-modern-role {
        color: var(--theme-color);
        font-weight: 600;
        font-size: 0.78em;
        margin-bottom: {{ $ls(10) }}px;
    }

    {{ $scope }} .ls-info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: {{ $ls(8) }}px 0;
        border-top: 1px solid #e2e8f0;
        font-size: 0.75em;
    }

    {{ $scope }} .ls-info-label {
        color: #64748b;
        font-weight: 500;
    }

    {{ $scope }} .ls-info-value {
        font-weight: 600;
    }

    {{ $scope }} .ls-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: {{ $ls(10) }}px {{ $ls(14) }}px;
        border-top: 1px solid #e2e8f0;
        background: #f8fafc;
        flex-shrink: 0;
    }

    {{ $scope }} .ls-footer--dark {
        background: #0f172a;
        border-top: 0;
        color: #94a3b8;
        font-family: ui-monospace, monospace;
        font-size: 0.72em;
    }

    {{ $scope }} .ls-footer-meta {
        font-size: 0.68em;
        color: #94a3b8;
        line-height: 1.35;
    }

    {{ $scope }} .ls-footer--dark .ls-footer-meta {
        color: #64748b;
    }

    {{ $scope }} .ls-footer-id {
        font-weight: 600;
        color: #64748b;
        font-size: 0.72em;
    }

    {{ $scope }} .ls-qr svg {
        display: block;
        width: {{ $ls(48) }}px !important;
        height: {{ $ls(48) }}px !important;
    }

    {{ $scope }} .ls-qr img {
        display: block;
        width: {{ $ls(48) }}px;
        height: {{ $ls(48) }}px;
    }

    /* —— Minimal —— */
    {{ $scope }} .ls-minimal-photo-panel {
        width: 34%;
        flex-shrink: 0;
        background: var(--theme-color);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: {{ $ls(10) }}px;
    }

    {{ $scope }} .ls-minimal-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: {{ $ls(12) }}px {{ $ls(14) }}px;
        background: #f8fafc;
        min-width: 0;
    }

    {{ $scope }} .ls-minimal-name {
        font-size: 1.05em;
        font-weight: 800;
        margin-bottom: {{ $ls(2) }}px;
    }

    {{ $scope }} .ls-minimal-role {
        color: var(--theme-color);
        font-weight: 600;
        font-size: 0.68em;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        margin-bottom: {{ $ls(10) }}px;
    }

    {{ $scope }} .ls-minimal-id-box {
        background: #fff;
        border-left: {{ $ls(3) }}px solid var(--theme-color);
        border-radius: {{ $ls(6) }}px;
        padding: {{ $ls(8) }}px {{ $ls(10) }}px;
        margin-bottom: {{ $ls(10) }}px;
    }

    {{ $scope }} .ls-minimal-id-label {
        font-size: 0.62em;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-bottom: {{ $ls(2) }}px;
    }

    {{ $scope }} .ls-minimal-id-value {
        font-size: 0.8em;
        font-weight: 700;
    }

    {{ $scope }} .ls-minimal-bottom {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: {{ $ls(8) }}px;
    }

    {{ $scope }} .ls-minimal-bottom-logo {
        height: {{ $ls(18) }}px;
        margin-bottom: {{ $ls(4) }}px;
        display: block;
    }

    {{ $scope }} .ls-minimal-bottom-company {
        font-weight: 700;
        font-size: 0.72em;
        color: #334155;
        line-height: 1.2;
    }

    {{ $scope }} .ls-minimal-issued {
        font-size: 0.62em;
        color: #94a3b8;
        margin-top: {{ $ls(2) }}px;
    }

    /* —— Creative —— */
    {{ $scope }} .ls-creative-accent {
        height: {{ $ls(5) }}px;
        flex-shrink: 0;
        background: linear-gradient(90deg, var(--theme-color), var(--theme-color-dark));
    }

    {{ $scope }} .ls-creative-header {
        display: flex;
        align-items: center;
        gap: {{ $ls(12) }}px;
        padding: {{ $ls(10) }}px {{ $ls(14) }}px;
        border-bottom: 1px solid #f1f5f9;
        flex-shrink: 0;
    }

    {{ $scope }} .ls-creative-photo {
        width: {{ $ls(68) }}px;
        height: {{ $ls(68) }}px;
        border-radius: 50%;
        border: {{ $ls(3) }}px solid #fff;
        box-shadow: 0 {{ $ls(2) }}px {{ $ls(8) }}px rgba(0, 0, 0, 0.12);
        object-fit: cover;
        flex-shrink: 0;
        background: #e2e8f0;
    }

    {{ $scope }} .ls-creative-photo.initials {
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35em;
        font-weight: 700;
        color: #94a3b8;
    }

    {{ $scope }} .ls-creative-header-logo {
        height: {{ $ls(18) }}px;
        margin-bottom: {{ $ls(3) }}px;
        display: block;
    }

    {{ $scope }} .ls-creative-company {
        font-size: 0.65em;
        font-weight: 700;
        color: var(--theme-color);
        text-transform: uppercase;
        letter-spacing: 0.8px;
        margin-bottom: {{ $ls(2) }}px;
    }

    {{ $scope }} .ls-creative-name {
        font-size: 0.95em;
        font-weight: 800;
        line-height: 1.15;
    }

    {{ $scope }} .ls-creative-role {
        font-size: 0.72em;
        color: #64748b;
    }

    {{ $scope }} .ls-creative-body {
        flex: 1;
        display: flex;
        align-items: center;
        padding: {{ $ls(6) }}px {{ $ls(14) }}px;
        min-height: 0;
    }

    {{ $scope }} .ls-creative-id-chip {
        display: flex;
        align-items: center;
        gap: {{ $ls(10) }}px;
        width: 100%;
    }

    {{ $scope }} .ls-creative-id-icon {
        width: {{ $ls(28) }}px;
        height: {{ $ls(28) }}px;
        border-radius: {{ $ls(6) }}px;
        background: color-mix(in srgb, var(--theme-color) 12%, white);
        color: var(--theme-color);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.58em;
        font-weight: 700;
        flex-shrink: 0;
    }

    /* —— Back —— */
    {{ $scope }} .ls-back {
        display: flex;
        flex-direction: row;
        height: 100%;
        background: linear-gradient(135deg, var(--theme-color), var(--theme-color-dark));
        color: #fff;
    }

    {{ $scope }} .ls-back-left {
        width: 44%;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: {{ $ls(14) }}px {{ $ls(12) }}px;
        border-right: 1px solid rgba(255, 255, 255, 0.15);
        text-align: center;
    }

    {{ $scope }} .ls-back-logo {
        height: {{ $ls(26) }}px;
        width: auto;
        max-width: 100%;
        object-fit: contain;
        object-position: left center;
        margin-bottom: {{ $ls(6) }}px;
        display: block;
        flex-shrink: 0;
        align-self: center;
    }

    {{ $scope }} .ls-back-company {
        font-weight: 700;
        font-size: 0.82em;
        line-height: 1.2;
        margin-bottom: {{ $ls(2) }}px;
    }

    {{ $scope }} .ls-back-tagline {
        font-size: 0.65em;
        opacity: 0.85;
        margin-bottom: {{ $ls(10) }}px;
    }

    {{ $scope }} .ls-back-notice {
        background: rgba(0, 0, 0, 0.2);
        border-radius: {{ $ls(6) }}px;
        padding: {{ $ls(8) }}px;
        font-size: 0.62em;
        line-height: 1.4;
    }

    {{ $scope }} .ls-back-right {
        flex: 1;
        display: flex;
        align-items: center;
        padding: {{ $ls(12) }}px;
        min-width: 0;
    }

    {{ $scope }} .ls-back-contacts {
        background: #fff;
        color: #0f172a;
        border-radius: {{ $ls(8) }}px;
        padding: {{ $ls(10) }}px {{ $ls(12) }}px;
        font-size: 0.65em;
        line-height: 1.45;
        width: 100%;
    }

    {{ $scope }} .ls-back-contacts strong {
        color: #334155;
    }

    /* —— Premium (landscape) —— */
    {{ $scope }} .ls-premium-brand {
        width: 44%;
        flex-shrink: 0;
        background: linear-gradient(160deg, var(--theme-color), var(--theme-color-dark));
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: {{ $ls(8) }}px {{ $ls(8) }}px {{ $ls(10) }}px;
        gap: {{ $ls(4) }}px;
        position: relative;
        overflow: hidden;
        min-height: 0;
    }

    {{ $scope }} .ls-premium-brand-pattern {
        position: absolute;
        inset: 0;
        opacity: 0.12;
        background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0);
        background-size: {{ $ls(14) }}px {{ $ls(14) }}px;
    }

    {{ $scope }} .ls-premium-brand-lines {
        position: absolute;
        inset: 0;
        opacity: 0.08;
        background: repeating-linear-gradient(
            -35deg,
            transparent,
            transparent {{ $ls(6) }}px,
            rgba(255, 255, 255, 0.5) {{ $ls(6) }}px,
            rgba(255, 255, 255, 0.5) {{ $ls(7) }}px
        );
    }

    {{ $scope }} .ls-premium-brand > *:not(.ls-premium-brand-pattern):not(.ls-premium-brand-lines) {
        position: relative;
        z-index: 1;
    }

    {{ $scope }} .ls-premium-badge {
        align-self: flex-start;
        background: rgba(255, 255, 255, 0.92);
        color: var(--theme-color-dark);
        font-size: 0.5em;
        font-weight: 800;
        letter-spacing: 0.8px;
        text-transform: uppercase;
        padding: {{ $ls(2) }}px {{ $ls(6) }}px;
        border-radius: {{ $ls(3) }}px;
        margin-bottom: {{ $ls(4) }}px;
    }

    {{ $scope }} .ls-premium-header-brand {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: {{ $ls(4) }}px;
        padding: {{ $ls(8) }}px {{ $ls(12) }}px {{ $ls(4) }}px;
        text-align: center;
        border-bottom: 1px solid #e2e8f0;
        flex-shrink: 0;
    }

    {{ $scope }} .ls-premium-header-brand-text {
        min-width: 0;
        width: 100%;
    }

    {{ $scope }} .ls-premium-header-logo {
        height: {{ $ls(24) }}px;
        width: auto;
        max-width: {{ $ls(56) }}px;
        object-fit: contain;
        flex-shrink: 0;
    }

    {{ $scope }} .ls-premium-company {
        font-weight: 700;
        font-size: 0.74em;
        line-height: 1.15;
        color: #0f172a;
    }

    {{ $scope }} .ls-premium-tagline {
        font-size: 0.58em;
        color: #64748b;
        line-height: 1.25;
        margin-top: {{ $ls(1) }}px;
    }

    {{ $scope }} .ls-premium-photo-frame {
        flex: 1;
        width: 90%;
        min-height: 0;
        margin-top: {{ $ls(4) }}px;
        border: {{ $ls(2) }}px solid rgba(255, 255, 255, 0.85);
        border-radius: {{ $ls(8) }}px;
        overflow: hidden;
        background: #e2e8f0;
        box-shadow: 0 {{ $ls(4) }}px {{ $ls(12) }}px rgba(0, 0, 0, 0.18);
    }

    {{ $scope }} .ls-premium-photo {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        border-radius: 0;
    }

    {{ $scope }} .ls-premium-photo.initials {
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2em;
        font-weight: 700;
        color: #94a3b8;
        min-height: 100%;
    }

    {{ $scope }} .ls-premium-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-width: 0;
        background: #fff;
    }

    {{ $scope }} .ls-premium-details {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        padding: {{ $ls(10) }}px {{ $ls(12) }}px {{ $ls(4) }}px;
        min-width: 0;
        position: relative;
        overflow: hidden;
    }

    {{ $scope }} .ls-premium-watermark-logo {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 82%;
        max-height: 72%;
        object-fit: contain;
        opacity: 0.1;
        pointer-events: none;
        filter: grayscale(15%);
    }

    {{ $scope }} .ls-premium-role-pill {
        display: inline-block;
        align-self: center;
        background: var(--theme-color);
        color: #fff;
        font-size: 0.62em;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: {{ $ls(3) }}px {{ $ls(8) }}px;
        border-radius: 999px;
        margin-bottom: {{ $ls(8) }}px;
    }

    {{ $scope }} .ls-premium-chips {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: {{ $ls(6) }}px;
        width: 100%;
    }

    {{ $scope }} .ls-premium-chip {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: {{ $ls(5) }}px;
        padding: {{ $ls(6) }}px {{ $ls(8) }}px;
        border-top: {{ $ls(2) }}px solid var(--theme-color);
    }

    {{ $scope }} .ls-premium-chip-label {
        font-size: 0.55em;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        font-weight: 600;
        margin-bottom: {{ $ls(1) }}px;
    }

    {{ $scope }} .ls-premium-chip-value {
        font-size: 0.68em;
        font-weight: 700;
        color: #0f172a;
        word-break: break-word;
    }

    {{ $scope }} .ls-footer--premium {
        background: #0f172a;
        border-top: {{ $ls(2) }}px solid var(--theme-color);
        color: #94a3b8;
        font-family: ui-monospace, monospace;
    }

    {{ $scope }} .ls-footer--premium .ls-footer-meta {
        color: #94a3b8;
        font-size: 0.68em;
        font-family: inherit;
    }

    {{ $scope }} .ls-back--premium {
        display: flex;
        flex-direction: column;
        height: 100%;
        background: linear-gradient(160deg, var(--theme-color), var(--theme-color-dark));
        color: #fff;
        overflow: hidden;
    }

    {{ $scope }} .ls-back-premium-stripe {
        height: {{ $ls(22) }}px;
        background: #111827;
        flex-shrink: 0;
        border-bottom: {{ $ls(2) }}px solid rgba(255, 255, 255, 0.12);
    }

    {{ $scope }} .ls-back-premium-grid {
        display: flex;
        flex: 1;
        min-height: 0;
        padding: {{ $ls(10) }}px {{ $ls(12) }}px {{ $ls(6) }}px;
        gap: {{ $ls(10) }}px;
    }

    {{ $scope }} .ls-back-premium-brand {
        width: 36%;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: {{ $ls(4) }}px;
    }

    {{ $scope }} .ls-back-premium-logo {
        height: {{ $ls(34) }}px;
        width: auto;
        max-width: 100%;
        object-fit: contain;
        margin-bottom: {{ $ls(6) }}px;
        filter: brightness(0) invert(1);
    }

    {{ $scope }} .ls-back-premium-company {
        font-weight: 700;
        font-size: 0.72em;
        line-height: 1.15;
    }

    {{ $scope }} .ls-back-premium-tagline {
        font-size: 0.56em;
        opacity: 0.85;
        margin-top: {{ $ls(2) }}px;
        line-height: 1.25;
    }

    {{ $scope }} .ls-back-premium-panel {
        flex: 1;
        background: #fff;
        color: #0f172a;
        border-radius: {{ $ls(8) }}px;
        padding: {{ $ls(8) }}px {{ $ls(10) }}px;
        box-shadow: 0 {{ $ls(4) }}px {{ $ls(14) }}px rgba(0, 0, 0, 0.14);
        min-width: 0;
    }

    {{ $scope }} .ls-back-premium-panel-title {
        font-size: 0.52em;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: var(--theme-color-dark);
        margin-bottom: {{ $ls(6) }}px;
        padding-bottom: {{ $ls(4) }}px;
        border-bottom: 1px solid #e2e8f0;
    }

    {{ $scope }} .ls-back-premium-contacts {
        display: flex;
        flex-direction: column;
        gap: {{ $ls(4) }}px;
    }

    {{ $scope }} .ls-back-premium-contact-row {
        display: flex;
        justify-content: space-between;
        gap: {{ $ls(6) }}px;
        font-size: 0.56em;
        line-height: 1.35;
    }

    {{ $scope }} .ls-back-premium-contact-row span {
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        font-weight: 600;
        flex-shrink: 0;
    }

    {{ $scope }} .ls-back-premium-contact-row strong {
        color: #0f172a;
        font-weight: 700;
        text-align: right;
        word-break: break-word;
    }

    {{ $scope }} .ls-back-premium-contact-row--stack {
        flex-direction: column;
        align-items: flex-start;
    }

    {{ $scope }} .ls-back-premium-contact-row--stack strong {
        text-align: left;
        margin-top: {{ $ls(1) }}px;
    }

    {{ $scope }} .ls-back-premium-notice {
        margin: 0 {{ $ls(12) }}px {{ $ls(6) }}px;
        background: rgba(0, 0, 0, 0.22);
        border-radius: {{ $ls(6) }}px;
        padding: {{ $ls(6) }}px {{ $ls(8) }}px;
        font-size: 0.54em;
        line-height: 1.4;
        flex-shrink: 0;
    }

    {{ $scope }} .ls-back-premium-sig {
        height: {{ $ls(20) }}px;
        margin: 0 {{ $ls(12) }}px {{ $ls(8) }}px;
        background: #f8fafc;
        border-radius: {{ $ls(4) }}px;
        display: flex;
        align-items: center;
        padding: 0 {{ $ls(8) }}px;
        flex-shrink: 0;
    }

    {{ $scope }} .ls-back-premium-sig span {
        font-size: 0.5em;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }
</style>
