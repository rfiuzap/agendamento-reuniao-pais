<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SettingController extends Controller
{
    /** Uploaded images: setting key => file name prefix. "logo" is the square icon (kept for compatibility). */
    private const IMAGES = ['logo' => 'icone', 'brand_logo' => 'logo'];

    public function edit(): View
    {
        return view('staff.settings', [
            'settings' => Setting::allValues(),
            'mail' => [
                'mailer' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'from' => config('mail.from.address'),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        // SVG is not accepted: it can carry scripts.
        $image = ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024', 'dimensions:max_width=3000,max_height=3000'];

        $data = $request->validate([
            'school_name' => ['required', 'string', 'max:120'],
            'contact_email' => ['nullable', 'email', 'max:190'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'parent_instructions' => ['nullable', 'string', 'max:1000'],
            'logo' => $image,
            'brand_logo' => $image,
        ], [
            'logo.max' => 'O ícone deve ter no máximo 1 MB.',
            'logo.mimes' => 'O ícone deve ser PNG, JPG ou WEBP.',
            'brand_logo.max' => 'O logo deve ter no máximo 1 MB.',
            'brand_logo.mimes' => 'O logo deve ser PNG, JPG ou WEBP.',
        ]);

        $replaced = [];
        foreach (self::IMAGES as $key => $prefix) {
            unset($data[$key]);
            if ($request->hasFile($key)) {
                $file = $request->file($key);
                $name = $prefix.'-'.Str::random(12).'.'.$file->extension();
                $file->move(public_path('uploads'), $name);
                $data[$key] = $name;
            } elseif ($request->boolean('remove_'.$key)) {
                $data[$key] = '';
            }
            if (array_key_exists($key, $data)) {
                $replaced[] = Setting::get($key);
            }
        }

        Setting::put($data);

        foreach (array_filter($replaced) as $old) {
            if (is_file(public_path('uploads/'.$old))) {
                @unlink(public_path('uploads/'.$old));
            }
        }

        return back()->with('success', 'Configurações salvas.');
    }
}
