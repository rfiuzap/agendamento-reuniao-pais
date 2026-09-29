<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\CreatesSchoolData;
use Tests\TestCase;

class SettingsLogoTest extends TestCase
{
    use CreatesSchoolData, RefreshDatabase;

    private array $created = [];

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget('settings');
        $this->actingAs($this->makeUser('admin', 'chefe'));
    }

    protected function tearDown(): void
    {
        foreach ($this->created as $file) {
            @unlink(public_path('uploads/'.$file));
        }
        Cache::forget('settings');
        parent::tearDown();
    }

    private function save(array $extra = [])
    {
        return $this->put(route('staff.settings.update'), array_merge([
            'school_name' => 'Colégio Morumbi',
        ], $extra));
    }

    public function test_admin_uploads_logo_used_as_favicon_and_can_restore_default(): void
    {
        $this->save(['logo' => UploadedFile::fake()->image('logo.png', 256, 256)])->assertSessionHasNoErrors();

        $logo = Setting::get('logo');
        $this->created[] = $logo;
        $this->assertMatchesRegularExpression('/^logo-\w{12}\.png$/', $logo);
        $this->assertFileExists(public_path('uploads/'.$logo));

        $this->get(route('parent.login'))->assertSee('<link rel="icon" href="'.asset('uploads/'.$logo).'">', false);
        $this->get(route('manifest'))->assertOk()->assertJsonPath('icons.0.type', 'image/png');

        $this->save(['remove_logo' => '1'])->assertSessionHasNoErrors();
        $this->assertSame('', Setting::get('logo'));
        $this->assertFileDoesNotExist(public_path('uploads/'.$logo));
        $this->assertSame(asset('img/logo.svg'), Setting::logoUrl());
    }

    public function test_rejects_svg_and_non_images(): void
    {
        $svg = UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
        $this->save(['logo' => $svg])->assertSessionHasErrors('logo');
        $this->save(['logo' => UploadedFile::fake()->create('logo.pdf', 10, 'application/pdf')])->assertSessionHasErrors('logo');
        $this->assertSame('', Setting::get('logo'));
    }

    public function test_home_page_has_install_button_and_manifest(): void
    {
        $this->get(route('parent.login'))
            ->assertSee('Salvar no celular')
            ->assertSee('rel="manifest"', false);

        $this->get(route('manifest'))->assertOk()
            ->assertJsonPath('display', 'standalone')
            ->assertJsonPath('short_name', 'Reunião de Pais');
    }
}
