<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaCopyCommandTest extends TestCase
{
    public function test_it_copies_media_without_deleting_the_source(): void
    {
        Storage::fake('media_source');
        Storage::fake('media_destination');
        Storage::disk('media_source')->put('products/foto.webp', 'contenido-original');

        $this->artisan('media:copy', [
            '--from' => 'media_source',
            '--to' => 'media_destination',
        ])->assertSuccessful();

        Storage::disk('media_source')->assertExists('products/foto.webp');
        Storage::disk('media_destination')->assertExists('products/foto.webp');
        $this->assertSame(
            'contenido-original',
            Storage::disk('media_destination')->get('products/foto.webp')
        );
    }

    public function test_it_does_not_replace_an_existing_object_without_force(): void
    {
        Storage::fake('media_source');
        Storage::fake('media_destination');
        Storage::disk('media_source')->put('products/foto.webp', 'nuevo');
        Storage::disk('media_destination')->put('products/foto.webp', 'existente');

        $this->artisan('media:copy', [
            '--from' => 'media_source',
            '--to' => 'media_destination',
        ])->assertSuccessful();

        $this->assertSame('existente', Storage::disk('media_destination')->get('products/foto.webp'));
    }
}
