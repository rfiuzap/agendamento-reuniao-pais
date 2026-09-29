<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;

/** Web app manifest: lets parents install the site on the phone's home screen. */
class ManifestController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $logo = Setting::logoUrl();
        $type = match (strtolower(pathinfo(parse_url($logo, PHP_URL_PATH), PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'image/svg+xml',
        };

        return response()->json([
            'name' => 'Reunião de Pais · '.Setting::get('school_name'),
            'short_name' => 'Reunião de Pais',
            'start_url' => route('parent.login'),
            'scope' => url('/').'/',
            'display' => 'standalone',
            'background_color' => '#f1f5f9',
            'theme_color' => '#1e3a8a',
            'lang' => 'pt-BR',
            'icons' => [
                ['src' => $logo, 'sizes' => 'any', 'type' => $type, 'purpose' => 'any'],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
