@props(['title' => null, 'description' => null])

<div {{ $attributes->merge(['class' => 'sub-sidebar-layout']) }}>
    <div class="sub-sidebar-layout-grid">
        @if (isset($sidebar))
            <aside class="sub-sidebar-layout-aside">
                <div class="sub-sidebar-layout-aside-inner">
                    {{ $sidebar }}
                </div>
            </aside>
        @endif

        <div class="sub-sidebar-layout-main">
            @if ($title || $description)
                <header class="profile-tab-header">
                    <div>
                        @if ($title)
                            <h2 class="profile-tab-title">{{ $title }}</h2>
                        @endif
                        @if ($description)
                            <p class="profile-tab-description">{{ $description }}</p>
                        @endif
                    </div>
                    @if (isset($headerActions))
                        <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $headerActions }}</div>
                    @endif
                </header>
            @endif

            <div class="profile-tab-content">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
