@php
    $template = $brand['template'] ?? 'modern';
    $color = $brand['brand_color'];
    $colorDark = $brand['brand_color_dark'];
    $orientation = $brand['orientation'] ?? 'portrait';
    $forPdf = $forPdf ?? false;
    $logo = $forPdf ? ($logoSrc ?? null) : ($logoUrl ?? ($brand['logo_url'] ?? null));
    $backLogo = $forPdf
        ? ($backLogoSrc ?? $logoSrc ?? null)
        : ($backLogoUrl ?? ($brand['back_logo_url'] ?? $logoUrl ?? ($brand['logo_url'] ?? null)));
    $signature = $forPdf
        ? ($signatureSrc ?? null)
        : ($signatureUrl ?? ($brand['signature_url'] ?? null));
    $photo = $forPdf ? ($photoSrc ?? null) : ($photoUrl ?? null);
    $isLandscape = $orientation === 'landscape' || $template === 'premium';
@endphp

<div
    class="id-card template-{{ $template }} orientation-{{ $orientation }}"
    style="--theme-color: {{ $color }}; --theme-color-dark: {{ $colorDark }};"
>
    @if ($side === 'back')
        @if ($isLandscape)
            @if ($template === 'premium')
                <div class="ls-back ls-back--premium">
                    <div class="ls-back-premium-stripe"></div>
                    <div class="ls-back-premium-grid">
                        <div class="ls-back-premium-brand">
                            @if ($backLogo)
                                <img src="{{ $backLogo }}" alt="" class="ls-back-premium-logo">
                            @endif
                            <div class="ls-back-premium-company">{{ $brand['company_name'] }}</div>
                            <div class="ls-back-premium-tagline">{{ $brand['tagline'] }}</div>
                        </div>
                        <div class="ls-back-premium-panel">
                            <div class="ls-back-premium-panel-title">Emergency contact</div>
                            <div class="ls-back-premium-contacts">
                                @if ($brand['phone'] ?? null)
                                    <div class="ls-back-premium-contact-row"><span>Tel</span><strong>{{ $brand['phone'] }}</strong></div>
                                @endif
                                @if ($brand['phone_secondary'] ?? null)
                                    <div class="ls-back-premium-contact-row"><span>Alt</span><strong>{{ $brand['phone_secondary'] }}</strong></div>
                                @endif
                                @if ($brand['email'] ?? null)
                                    <div class="ls-back-premium-contact-row"><span>Email</span><strong>{{ $brand['email'] }}</strong></div>
                                @endif
                                @if ($brand['website'] ?? null)
                                    <div class="ls-back-premium-contact-row"><span>Web</span><strong>{{ $brand['website'] }}</strong></div>
                                @endif
                                @if ($brand['address'] ?? null)
                                    <div class="ls-back-premium-contact-row ls-back-premium-contact-row--stack"><span>Address</span><strong>{{ $brand['address'] }}</strong></div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="ls-back-premium-notice">{{ $brand['emergency_text'] ?? 'In case of emergency, please contact the company.' }}</div>
                    <div class="ls-back-premium-sig @if ($signature) ls-back-premium-sig--has-image @endif">
                        <div class="ls-back-premium-sig-pad">
                            @if ($signature)
                                <img src="{{ $signature }}" alt="" class="ls-back-premium-sig-img">
                            @endif
                        </div>
                        <div class="ls-back-premium-sig-meta">
                            <span>Authorized signature</span>
                        </div>
                    </div>
                </div>
            @else
            <div class="ls-back">
                <div class="ls-back-left">
                    @if ($backLogo)
                        <img src="{{ $backLogo }}" alt="" class="ls-back-logo">
                    @endif
                    <div class="ls-back-company">{{ $brand['company_name'] }}</div>
                    <div class="ls-back-tagline">{{ $brand['tagline'] }}</div>
                    <div class="ls-back-notice">{{ $brand['emergency_text'] ?? 'In case of emergency, please contact the company.' }}</div>
                </div>
                <div class="ls-back-right">
                    <div class="ls-back-contacts">
                        @if ($brand['phone'] ?? null)
                            <div><strong>Tel:</strong> {{ $brand['phone'] }}</div>
                        @endif
                        @if ($brand['phone_secondary'] ?? null)
                            <div><strong>Alt:</strong> {{ $brand['phone_secondary'] }}</div>
                        @endif
                        @if ($brand['address'] ?? null)
                            <div class="mt-2"><strong>Address:</strong> {{ $brand['address'] }}</div>
                        @endif
                        @if ($brand['website'] ?? null)
                            <div><strong>Web:</strong> {{ $brand['website'] }}</div>
                        @endif
                        @if ($brand['email'] ?? null)
                            <div><strong>Email:</strong> {{ $brand['email'] }}</div>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        @elseif ($template === 'premium')
            <div class="premium-back">
                <div class="premium-mag-stripe"></div>
                <div class="premium-sig-strip @if ($signature) premium-sig-strip--has-image @endif">
                    <div class="premium-sig-pad">
                        @if ($signature)
                            <img src="{{ $signature }}" alt="" class="premium-sig-img">
                        @endif
                    </div>
                    <div class="premium-sig-meta">
                        <span>Authorized signature</span>
                    </div>
                </div>
                <div class="premium-back-content">
                    <div class="back-header">
                        @if ($logo)
                            <img src="{{ $logo }}" alt="" class="back-logo">
                        @endif
                        <div class="font-bold">{{ $brand['company_name'] }}</div>
                        <div class="text-sm opacity-80">{{ $brand['tagline'] }}</div>
                    </div>
                    <div class="back-notice premium-back-notice">{{ $brand['emergency_text'] ?? 'In case of emergency, please contact the company.' }}</div>
                    <div class="back-contacts premium-back-contacts">
                        @if ($brand['phone'] ?? null)
                            <div><strong>Tel:</strong> {{ $brand['phone'] }}</div>
                        @endif
                        @if ($brand['phone_secondary'] ?? null)
                            <div><strong>Alt:</strong> {{ $brand['phone_secondary'] }}</div>
                        @endif
                        @if ($brand['address'] ?? null)
                            <div class="mt-2"><strong>Address:</strong> {{ $brand['address'] }}</div>
                        @endif
                        @if ($brand['website'] ?? null)
                            <div><strong>Web:</strong> {{ $brand['website'] }}</div>
                        @endif
                        @if ($brand['email'] ?? null)
                            <div><strong>Email:</strong> {{ $brand['email'] }}</div>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <div class="card-back">
                <div class="back-header">
                    @if ($backLogo)
                        <img src="{{ $backLogo }}" alt="" class="back-logo">
                    @endif
                    <div class="font-bold">{{ $brand['company_name'] }}</div>
                    <div class="text-sm opacity-80">{{ $brand['tagline'] }}</div>
                </div>
                <div class="back-notice">{{ $brand['emergency_text'] ?? 'In case of emergency, please contact the company.' }}</div>
                <div class="back-contacts">
                    @if ($brand['phone'] ?? null)
                        <div><strong>Tel:</strong> {{ $brand['phone'] }}</div>
                    @endif
                    @if ($brand['phone_secondary'] ?? null)
                        <div><strong>Alt:</strong> {{ $brand['phone_secondary'] }}</div>
                    @endif
                    @if ($brand['address'] ?? null)
                        <div class="mt-2"><strong>Address:</strong> {{ $brand['address'] }}</div>
                    @endif
                    @if ($brand['website'] ?? null)
                        <div><strong>Web:</strong> {{ $brand['website'] }}</div>
                    @endif
                    @if ($brand['email'] ?? null)
                        <div><strong>Email:</strong> {{ $brand['email'] }}</div>
                    @endif
                </div>
            </div>
        @endif
    @elseif ($template === 'modern')
        @if ($isLandscape)
            <div class="ls-shell ls-shell--row">
                <div class="ls-modern-brand">
                    @if ($logo)
                        <img src="{{ $logo }}" alt="" class="ls-modern-logo">
                    @endif
                    <div class="ls-modern-company">{{ $brand['company_name'] }}</div>
                    <div class="ls-modern-tagline">{{ $brand['tagline'] }}</div>
                    @if ($photo)
                        <img src="{{ $photo }}" alt="" class="ls-modern-photo">
                    @else
                        <div class="ls-modern-photo initials">{{ $card['initial'] }}</div>
                    @endif
                </div>
                <div class="ls-modern-main">
                    <div class="ls-modern-details">
                        <div class="ls-modern-name">{{ $card['name'] }}</div>
                        <div class="ls-modern-role">{{ $card['role'] }}</div>
                        <div class="ls-info-row">
                            <span class="ls-info-label">ID</span>
                            <span class="ls-info-value">{{ $card['employee_id'] }}</span>
                        </div>
                    </div>
                    <div class="ls-footer">
                        <div>
                            <div class="ls-footer-meta">Issued {{ $card['issue_date'] }}</div>
                            <div class="ls-footer-meta">Scan to verify</div>
                        </div>
                        @if ($forPdf && ($qrPng ?? null))
                            <div class="ls-qr qr-box"><img src="data:image/png;base64,{{ $qrPng }}" width="48" height="48" alt=""></div>
                        @elseif ($qrSvg ?? null)
                            <div class="ls-qr qr-box">{!! $qrSvg !!}</div>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <div class="card-header">
                <div class="card-header-pattern"></div>
                <div class="header-brand">
                    @if ($logo)
                        <img src="{{ $logo }}" alt="" class="header-logo">
                    @endif
                    <div>
                        <div class="company-name">{{ $brand['company_name'] }}</div>
                        <div class="company-tagline">{{ $brand['tagline'] }}</div>
                    </div>
                </div>
                <div class="photo-wrap">
                    @if ($photo)
                        <img src="{{ $photo }}" alt="" class="photo-circle">
                    @else
                        <div class="photo-circle initials">{{ $card['initial'] }}</div>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="staff-name">{{ $card['name'] }}</div>
                <div class="staff-role">{{ $card['role'] }}</div>
                <div class="info-row"><span class="info-label">ID</span><span class="info-value">{{ $card['employee_id'] }}</span></div>
            </div>
            <div class="card-footer">
                <div>
                    <div class="issue-date-footer">Issued {{ $card['issue_date'] }}</div>
                    <div class="scan-hint">Scan to verify</div>
                </div>
                @if ($forPdf && ($qrPng ?? null))
                    <div class="qr-box"><img src="data:image/png;base64,{{ $qrPng }}" width="56" height="56" alt=""></div>
                @elseif ($qrSvg ?? null)
                    <div class="qr-box">{!! $qrSvg !!}</div>
                @endif
            </div>
        @endif
    @elseif ($template === 'minimal')
        @if ($isLandscape)
            <div class="ls-shell ls-shell--row">
                <div class="ls-minimal-photo-panel">
                    @if ($photo)
                        <img src="{{ $photo }}" alt="" class="ls-modern-photo">
                    @else
                        <div class="ls-modern-photo initials">{{ $card['initial'] }}</div>
                    @endif
                </div>
                <div class="ls-minimal-main">
                    <div class="ls-minimal-name">{{ $card['name'] }}</div>
                    <div class="ls-minimal-role">{{ $card['role'] }}</div>
                    <div class="ls-minimal-id-box">
                        <div class="ls-minimal-id-label">ID</div>
                        <div class="ls-minimal-id-value">{{ $card['employee_id'] }}</div>
                    </div>
                    <div class="ls-minimal-bottom">
                        <div>
                            @if ($logo)
                                <img src="{{ $logo }}" alt="" class="ls-minimal-bottom-logo">
                            @endif
                            <div class="ls-minimal-bottom-company">{{ $brand['company_name'] }}</div>
                            <div class="ls-minimal-issued">Issued {{ $card['issue_date'] }}</div>
                        </div>
                        @if ($forPdf && ($qrPng ?? null))
                            <div class="ls-qr qr-box"><img src="data:image/png;base64,{{ $qrPng }}" width="48" height="48" alt=""></div>
                        @elseif ($qrSvg ?? null)
                            <div class="ls-qr qr-box">{!! $qrSvg !!}</div>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <div class="card-top">
                @if ($photo)
                    <img src="{{ $photo }}" alt="" class="photo-circle">
                @else
                    <div class="photo-circle initials">{{ $card['initial'] }}</div>
                @endif
            </div>
            <div class="card-body">
                <div class="staff-name">{{ $card['name'] }}</div>
                <div class="staff-role">{{ $card['role'] }}</div>
                <div class="info-grid">
                    <div class="info-item" style="grid-column: span 2;">
                        <div class="info-item-label">ID</div>
                        <div class="info-item-value">{{ $card['employee_id'] }}</div>
                    </div>
                </div>
                <div class="minimal-bottom">
                    <div class="minimal-bottom-brand">
                        @if ($logo)
                            <img src="{{ $logo }}" alt="" class="minimal-bottom-logo">
                        @endif
                        <div class="minimal-bottom-company">{{ $brand['company_name'] }}</div>
                        <div class="minimal-bottom-issued">Issued {{ $card['issue_date'] }}</div>
                    </div>
                    @if ($forPdf && ($qrPng ?? null))
                        <div class="qr-box"><img src="data:image/png;base64,{{ $qrPng }}" width="56" height="56" alt=""></div>
                    @elseif ($qrSvg ?? null)
                        <div class="qr-box">{!! $qrSvg !!}</div>
                    @endif
                </div>
            </div>
        @endif
    @elseif ($template === 'premium')
        <div class="ls-shell ls-shell--row">
            <div class="ls-premium-brand">
                <div class="ls-premium-brand-pattern"></div>
                <div class="ls-premium-brand-lines"></div>
                <div class="ls-premium-badge">SECURITY ID</div>
                <div class="ls-premium-photo-frame">
                    @if ($photo)
                        <img src="{{ $photo }}" alt="" class="ls-premium-photo">
                    @else
                        <div class="ls-premium-photo initials">{{ $card['initial'] }}</div>
                    @endif
                </div>
            </div>
            <div class="ls-premium-main">
                <div class="ls-premium-header-brand">
                    @if ($logo)
                        <img src="{{ $logo }}" alt="" class="ls-premium-header-logo">
                    @endif
                    <div class="ls-premium-header-brand-text">
                        <div class="ls-premium-company">{{ $brand['company_name'] }}</div>
                        <div class="ls-premium-tagline">{{ $brand['tagline'] }}</div>
                    </div>
                </div>
                <div class="ls-premium-details">
                    @if ($logo)
                        <img src="{{ $logo }}" alt="" class="ls-premium-watermark-logo" aria-hidden="true">
                    @endif
                    <div class="ls-modern-name">{{ $card['name'] }}</div>
                    <div class="ls-premium-role-pill">{{ $card['role'] }}</div>
                    <div class="ls-premium-chips">
                        <div class="ls-premium-chip">
                            <div class="ls-premium-chip-label">Guard ID</div>
                            <div class="ls-premium-chip-value">{{ $card['employee_id'] }}</div>
                        </div>
                        <div class="ls-premium-chip">
                            <div class="ls-premium-chip-label">Issued</div>
                            <div class="ls-premium-chip-value">{{ $card['issue_date'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="ls-footer ls-footer--premium">
                    <div class="ls-footer-meta">Scan to verify (KYG)</div>
                    @if ($forPdf && ($qrPng ?? null))
                        <div class="ls-qr qr-box"><img src="data:image/png;base64,{{ $qrPng }}" width="48" height="48" alt=""></div>
                    @elseif ($qrSvg ?? null)
                        <div class="ls-qr qr-box">{!! $qrSvg !!}</div>
                    @endif
                </div>
            </div>
        </div>
    @elseif ($template === 'creative')
        @if ($isLandscape)
            <div class="ls-shell ls-shell--col">
                <div class="ls-creative-accent"></div>
                <div class="ls-creative-header">
                    @if ($photo)
                        <img src="{{ $photo }}" alt="" class="ls-creative-photo">
                    @else
                        <div class="ls-creative-photo initials">{{ $card['initial'] }}</div>
                    @endif
                    <div>
                        @if ($logo)
                            <img src="{{ $logo }}" alt="" class="ls-creative-header-logo">
                        @endif
                        <div class="ls-creative-company">{{ $brand['company_name'] }}</div>
                        <div class="ls-creative-name">{{ $card['name'] }}</div>
                        <div class="ls-creative-role">{{ $card['role'] }}</div>
                    </div>
                </div>
                <div class="ls-creative-body">
                    <div class="ls-creative-id-chip">
                        <div class="ls-creative-id-icon">ID</div>
                        <div>
                            <div class="ls-info-label">ID</div>
                            <div class="ls-info-value">{{ $card['employee_id'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="ls-footer ls-footer--dark">
                    <div>
                        <div class="ls-footer-meta">Issued {{ $card['issue_date'] }}</div>
                        <div class="ls-footer-meta">Scan to verify</div>
                    </div>
                    @if ($forPdf && ($qrPng ?? null))
                        <div class="ls-qr qr-box"><img src="data:image/png;base64,{{ $qrPng }}" width="48" height="48" alt=""></div>
                    @elseif ($qrSvg ?? null)
                        <div class="ls-qr qr-box">{!! $qrSvg !!}</div>
                    @endif
                </div>
            </div>
        @else
            <div class="side-accent"></div>
            <div class="card-header">
                @if ($photo)
                    <img src="{{ $photo }}" alt="" class="photo-circle">
                @else
                    <div class="photo-circle initials">{{ $card['initial'] }}</div>
                @endif
                <div>
                    @if ($logo)
                        <img src="{{ $logo }}" alt="" class="header-logo">
                    @endif
                    <div class="company-name">{{ $brand['company_name'] }}</div>
                    <div class="staff-name">{{ $card['name'] }}</div>
                    <div class="staff-role">{{ $card['role'] }}</div>
                </div>
            </div>
            <div class="card-body">
                <ul class="info-list">
                    <li>
                        <div class="info-icon">ID</div>
                        <div>
                            <div class="info-label">ID</div>
                            <div class="info-value">{{ $card['employee_id'] }}</div>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="card-footer">
                <div>
                    <div class="issue-date-footer" style="color:#64748b;">Issued {{ $card['issue_date'] }}</div>
                </div>
                @if ($forPdf && ($qrPng ?? null))
                    <div class="qr-box"><img src="data:image/png;base64,{{ $qrPng }}" width="56" height="56" alt=""></div>
                @elseif ($qrSvg ?? null)
                    <div class="qr-box">{!! $qrSvg !!}</div>
                @endif
            </div>
        @endif
    @endif
</div>
