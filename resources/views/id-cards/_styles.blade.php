@php
    $scope = ($forPdf ?? false) ? '.id-card-scope' : '.id-card-preview';
@endphp
<style>
    {{ $scope }} .id-card {
        background: #fff;
        border-radius: 16px;
        overflow: hidden;
        position: relative;
        box-shadow: 0 25px 50px rgba(0,0,0,0.15);
        font-family: Inter, ui-sans-serif, system-ui, sans-serif;
        color: #0f172a;
    }

    {{ $scope }} .orientation-portrait.id-card {
        width: 280px;
        height: 445px;
    }

    {{ $scope }} .photo-circle {
        width: 118px;
        height: 118px;
        border-radius: 50%;
        border: 4px solid white;
        background: #e2e8f0;
        overflow: hidden;
        object-fit: cover;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        display: block;
    }

    {{ $scope }} .template-modern .card-header {
        height: 140px;
        position: relative;
        background: linear-gradient(135deg, var(--theme-color), var(--theme-color-dark));
    }

    {{ $scope }} .template-modern .card-header-pattern {
        position: absolute;
        inset: 0;
        opacity: 0.1;
        background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0);
        background-size: 20px 20px;
    }

    {{ $scope }} .template-modern .header-brand {
        position: absolute;
        top: 20px;
        left: 20px;
        right: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    {{ $scope }} .template-modern .header-logo {
        height: 28px;
        width: auto;
        max-width: 56px;
        object-fit: contain;
        flex-shrink: 0;
    }

    {{ $scope }} .template-modern .company-name {
        color: white;
        font-weight: 700;
        font-size: 1rem;
        line-height: 1.2;
    }

    {{ $scope }} .template-modern .company-tagline {
        color: rgba(255,255,255,0.8);
        font-size: 0.7rem;
        margin-top: 2px;
    }

    {{ $scope }} .template-modern .photo-wrap {
        position: absolute;
        bottom: -59px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 10;
    }

    {{ $scope }} .template-modern .card-body {
        padding: 68px 24px 88px;
        text-align: center;
    }

    {{ $scope }} .template-modern .staff-name {
        font-size: 1.35rem;
        font-weight: 700;
        margin-bottom: 4px;
    }

    {{ $scope }} .template-modern .staff-role {
        color: var(--theme-color);
        font-weight: 600;
        font-size: 0.9rem;
        margin-bottom: 16px;
    }

    {{ $scope }} .template-modern .info-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #e2e8f0;
        font-size: 0.8rem;
    }

    {{ $scope }} .template-modern .info-label { color: #64748b; font-weight: 500; }
    {{ $scope }} .template-modern .info-value { font-weight: 600; }

    {{ $scope }} .template-modern .card-footer {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 16px;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    {{ $scope }} .template-minimal { background: #f8fafc; }
    {{ $scope }} .template-minimal .card-top {
        height: 180px;
        background: var(--theme-color);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    {{ $scope }} .template-minimal .card-body { padding: 28px 24px 24px; text-align: center; }
    {{ $scope }} .template-minimal .staff-name { font-size: 1.45rem; font-weight: 800; margin-bottom: 6px; }
    {{ $scope }} .template-minimal .staff-role {
        color: var(--theme-color);
        font-weight: 600;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 20px;
    }

    {{ $scope }} .template-minimal .info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        text-align: left;
        margin-bottom: 20px;
    }

    {{ $scope }} .template-minimal .info-item {
        background: white;
        padding: 12px;
        border-radius: 8px;
        border-left: 3px solid var(--theme-color);
    }

    {{ $scope }} .template-minimal .info-item-label {
        font-size: 0.68rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }

    {{ $scope }} .template-minimal .info-item-value { font-size: 0.85rem; font-weight: 700; }

    {{ $scope }} .template-minimal .minimal-bottom {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-top: 8px;
    }

    {{ $scope }} .template-minimal .minimal-bottom-brand {
        text-align: left;
    }

    {{ $scope }} .template-minimal .minimal-bottom-logo {
        height: 22px;
        margin-bottom: 6px;
        display: block;
    }

    {{ $scope }} .template-minimal .minimal-bottom-company {
        font-weight: 700;
        color: #334155;
    }

    {{ $scope }} .template-minimal .minimal-bottom-issued {
        font-size: 0.72rem;
        color: #94a3b8;
        margin-top: 4px;
    }

    {{ $scope }} .template-minimal .minimal-bottom .qr-box {
        padding: 4px;
        border-radius: 8px;
    }

    {{ $scope }} .template-creative .side-accent {
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 8px;
        background: linear-gradient(180deg, var(--theme-color), var(--theme-color-dark));
    }

    {{ $scope }} .template-creative .card-header {
        padding: 28px 24px 18px 32px;
        display: flex;
        align-items: center;
        gap: 14px;
        border-bottom: 2px solid #f1f5f9;
    }

    {{ $scope }} .template-creative .company-name {
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--theme-color);
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 4px;
    }

    {{ $scope }} .template-creative .header-logo { height: 22px; width: auto; margin-bottom: 4px; }

    {{ $scope }} .template-creative .staff-name { font-size: 1.25rem; font-weight: 800; margin-bottom: 2px; }
    {{ $scope }} .template-creative .staff-role { font-size: 0.85rem; color: #64748b; }
    {{ $scope }} .template-creative .card-body { padding: 20px 24px 88px 32px; }

    {{ $scope }} .template-creative .info-list { list-style: none; margin: 0; padding: 0; }
    {{ $scope }} .template-creative .info-list li {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 11px 0;
        border-bottom: 1px solid #f1f5f9;
    }

    {{ $scope }} .template-creative .info-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: color-mix(in srgb, var(--theme-color) 12%, white);
        color: var(--theme-color);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.65rem;
        font-weight: 700;
        flex-shrink: 0;
    }

    {{ $scope }} .template-creative .info-label {
        font-size: 0.68rem;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    {{ $scope }} .template-creative .info-value { font-size: 0.9rem; font-weight: 600; }

    {{ $scope }} .template-creative .card-footer {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 16px 24px 16px 32px;
        background: #0f172a;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: #94a3b8;
        font-family: ui-monospace, monospace;
        font-size: 0.8rem;
    }

    {{ $scope }} .card-back {
        padding: 24px 20px;
        height: 100%;
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
        background: linear-gradient(180deg, var(--theme-color), var(--theme-color-dark));
        color: white;
    }

    {{ $scope }} .card-back .back-header { text-align: center; margin-bottom: 16px; }
    {{ $scope }} .card-back .back-logo {
        height: 32px;
        width: auto;
        max-width: 100%;
        object-fit: contain;
        margin: 0 auto 8px;
        display: block;
    }
    {{ $scope }} .card-back .back-notice {
        background: rgba(0,0,0,0.2);
        border-radius: 8px;
        padding: 12px;
        font-size: 0.75rem;
        line-height: 1.45;
        margin-bottom: 16px;
    }

    {{ $scope }} .card-back .back-contacts {
        background: white;
        color: #0f172a;
        border-radius: 12px;
        padding: 16px;
        font-size: 0.75rem;
        line-height: 1.5;
        flex: 1;
    }

    {{ $scope }} .card-back .back-contacts strong { color: #334155; }

    {{ $scope }} .qr-box {
        background: white;
        padding: 3px;
        border-radius: 6px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        line-height: 0;
    }

    {{ $scope }} .id-number { font-size: 0.75rem; color: #64748b; font-weight: 600; }
    {{ $scope }} .issue-date-footer { font-size: 0.7rem; color: #94a3b8; margin-top: 2px; }
    {{ $scope }} .scan-hint { font-size: 0.65rem; color: #94a3b8; }
    {{ $scope }} .initials {
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: 700;
        color: #94a3b8;
    }

    {{ $scope }} .font-bold { font-weight: 700; }
    {{ $scope }} .text-sm { font-size: 0.875rem; }
    {{ $scope }} .opacity-80 { opacity: 0.8; }
    {{ $scope }} .mt-2 { margin-top: 0.5rem; }

    /* —— Premium (portrait) —— */
    {{ $scope }} .template-premium .premium-header {
        height: 72px;
        position: relative;
        background: linear-gradient(145deg, var(--theme-color), var(--theme-color-dark));
        overflow: hidden;
    }

    {{ $scope }} .template-premium .premium-header-pattern {
        position: absolute;
        inset: 0;
        opacity: 0.12;
        background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0);
        background-size: 18px 18px;
    }

    {{ $scope }} .template-premium .premium-header-lines {
        position: absolute;
        inset: 0;
        opacity: 0.08;
        background: repeating-linear-gradient(
            -35deg,
            transparent,
            transparent 8px,
            rgba(255, 255, 255, 0.5) 8px,
            rgba(255, 255, 255, 0.5) 9px
        );
    }

    {{ $scope }} .template-premium .premium-badge {
        position: absolute;
        top: 14px;
        left: 14px;
        z-index: 2;
        background: rgba(255, 255, 255, 0.92);
        color: var(--theme-color-dark);
        font-size: 0.58rem;
        font-weight: 800;
        letter-spacing: 1.2px;
        text-transform: uppercase;
        padding: 4px 8px;
        border-radius: 4px;
    }

    {{ $scope }} .template-premium .premium-header-brand {
        position: absolute;
        top: 14px;
        right: 14px;
        left: 90px;
        z-index: 2;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;
        text-align: right;
    }

    {{ $scope }} .template-premium .premium-header-brand-text {
        min-width: 0;
    }

    {{ $scope }} .template-premium .premium-header-logo {
        height: 26px;
        width: auto;
        max-width: 52px;
        object-fit: contain;
        flex-shrink: 0;
    }

    {{ $scope }} .template-premium .premium-company-name {
        color: #fff;
        font-weight: 700;
        font-size: 0.8rem;
        line-height: 1.2;
    }

    {{ $scope }} .template-premium .premium-company-tagline {
        color: rgba(255, 255, 255, 0.82);
        font-size: 0.62rem;
        line-height: 1.25;
        margin-top: 2px;
    }

    {{ $scope }} .template-premium .premium-photo-panel {
        padding: 14px 20px 10px;
        background: #fff;
        border-bottom: 1px solid #e2e8f0;
    }

    {{ $scope }} .template-premium .premium-photo-frame {
        width: 100%;
        max-width: 220px;
        aspect-ratio: 4 / 5;
        margin: 0 auto;
        border: 3px solid var(--theme-color);
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        background: #e2e8f0;
    }

    {{ $scope }} .template-premium .premium-photo {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        border-radius: 0;
    }

    {{ $scope }} .template-premium .premium-photo.initials {
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        font-weight: 700;
        color: #94a3b8;
        min-height: 100%;
    }

    {{ $scope }} .template-premium .premium-body {
        position: relative;
        padding: 14px 20px 90px;
        text-align: center;
        overflow: hidden;
    }

    {{ $scope }} .template-premium .premium-watermark-logo {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 58%;
        max-height: 42%;
        object-fit: contain;
        opacity: 0.07;
        pointer-events: none;
        filter: grayscale(20%);
    }

    {{ $scope }} .template-premium .premium-staff-name {
        position: relative;
        font-size: 1.3rem;
        font-weight: 800;
        margin-bottom: 8px;
        line-height: 1.15;
    }

    {{ $scope }} .template-premium .premium-role-pill {
        position: relative;
        display: inline-block;
        background: var(--theme-color);
        color: #fff;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        padding: 5px 12px;
        border-radius: 999px;
        margin-bottom: 16px;
    }

    {{ $scope }} .template-premium .premium-chips {
        position: relative;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        text-align: left;
    }

    {{ $scope }} .template-premium .premium-chip {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 10px 12px;
        border-top: 2px solid var(--theme-color);
    }

    {{ $scope }} .template-premium .premium-chip-label {
        font-size: 0.6rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        font-weight: 600;
        margin-bottom: 3px;
    }

    {{ $scope }} .template-premium .premium-chip-value {
        font-size: 0.78rem;
        font-weight: 700;
        color: #0f172a;
        word-break: break-word;
    }

    {{ $scope }} .template-premium .premium-footer {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 14px 16px;
        background: #0f172a;
        border-top: 3px solid var(--theme-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: #94a3b8;
    }

    {{ $scope }} .template-premium .premium-footer-meta {
        font-size: 0.68rem;
        color: #94a3b8;
    }

    {{ $scope }} .template-premium .premium-back {
        height: 100%;
        display: flex;
        flex-direction: column;
        background: linear-gradient(180deg, var(--theme-color), var(--theme-color-dark));
        color: #fff;
    }

    {{ $scope }} .template-premium .premium-mag-stripe {
        height: 18%;
        min-height: 48px;
        background: #1a1a1a;
        flex-shrink: 0;
    }

    {{ $scope }} .template-premium .premium-sig-strip {
        height: 36px;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        padding: 0 16px;
        flex-shrink: 0;
    }

    {{ $scope }} .template-premium .premium-sig-strip span {
        font-size: 0.62rem;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    {{ $scope }} .template-premium .premium-back-content {
        flex: 1;
        padding: 14px 16px 16px;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }

    {{ $scope }} .template-premium .premium-back-content .back-header {
        text-align: center;
        margin-bottom: 10px;
    }

    {{ $scope }} .template-premium .premium-back-notice {
        background: rgba(0, 0, 0, 0.2);
        border-radius: 8px;
        padding: 10px;
        font-size: 0.7rem;
        line-height: 1.4;
        margin-bottom: 12px;
    }

    {{ $scope }} .template-premium .premium-back-contacts {
        background: #fff;
        color: #0f172a;
        border-radius: 10px;
        padding: 14px;
        font-size: 0.72rem;
        line-height: 1.5;
        flex: 1;
        border: 1px solid rgba(255, 255, 255, 0.3);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
    }

    {{ $scope }} .template-premium .premium-back-contacts strong {
        color: #334155;
    }
</style>
