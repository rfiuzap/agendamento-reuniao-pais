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
        $data = $request->validate([
            'school_name' => ['required', 'string', 'max:120'],
            'contact_email' => ['nullable', 'email', 'max:190'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'parent_instructions' => ['nullable', 'string', 'max:1000'],
            // SVG is not accepted: it can carry scripts.
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024', 'dimensions:max_width=2000,max_height=2000'],
        ], [
            'logo.max' => 'O logo deve ter no máximo 1 MB.',
            'logo.mimes' => 'O logo deve ser PNG, JPG ou WEBP.',
        ]);

        $oldLogo = Setting::get('logo');
        unset($data['logo']);

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $name = 'logo-'.Str::random(12).'.'.$file->extension();
            $file->move(public_path('uploads'), $name);
            $data['logo'] = $name;
        } elseif ($request->boolean('remove_logo')) {
            $data['logo'] = '';
        }

        Setting::put($data);

        if (array_key_exists('logo', $data) && $oldLogo && is_file(public_path('uploads/'.$oldLogo))) {
            @unlink(public_path('uploads/'.$oldLogo));
        }

        return back()->with('success', 'Configurações salvas.');
    }
}
