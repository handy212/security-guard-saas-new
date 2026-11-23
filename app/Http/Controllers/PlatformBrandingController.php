<?php

namespace App\Http\Controllers;

use App\Services\PlatformBrandingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PlatformBrandingController extends Controller
{
    public function edit(PlatformBrandingService $branding): View
    {
        return view('platform-branding', [
            'identity' => $branding->current(),
            'logoUrl' => $branding->logoUrl(),
            'faviconUrl' => $branding->faviconUrl(),
            'backgroundUrl' => $branding->backgroundUrl(),
        ]);
    }

    public function update(Request $request, PlatformBrandingService $branding): RedirectResponse
    {
        $favicon = $request->file('favicon');
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'sign_in_message' => ['nullable', 'string', 'max:180'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'light_accent' => ['nullable', 'string', 'regex:/\A#[0-9a-f]{6}\z/i'],
            'dark_accent' => ['nullable', 'string', 'regex:/\A#[0-9a-f]{6}\z/i'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048', 'dimensions:max_width=3000,max_height=3000'],
            'favicon' => [
                'nullable', 'file', 'mimes:png,ico', 'max:2048',
                Rule::when($favicon instanceof UploadedFile && $favicon->isValid() && $favicon->getMimeType() === 'image/png', 'dimensions:max_width=3000,max_height=3000'),
            ],
            'background' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
            'remove_background' => ['sometimes', 'accepted'],
            'remove_logo' => ['sometimes', 'accepted'],
            'remove_favicon' => ['sometimes', 'accepted'],
        ]);

        $branding->save([
            'name' => $data['name'] ?? null,
            'sign_in_message' => $data['sign_in_message'] ?? null,
            'support_email' => $data['support_email'] ?? null,
            'light_accent' => $data['light_accent'] ?? null,
            'dark_accent' => $data['dark_accent'] ?? null,
        ], [
            'logo' => $data['logo'] ?? null,
            'favicon' => $data['favicon'] ?? null,
            'background' => $data['background'] ?? null,
        ], [
            'logo' => $request->boolean('remove_logo'),
            'favicon' => $request->boolean('remove_favicon'),
            'background' => $request->boolean('remove_background'),
        ]);

        return redirect()->route('saas.branding')->with('branding_status', 'App branding saved.');
    }
}
