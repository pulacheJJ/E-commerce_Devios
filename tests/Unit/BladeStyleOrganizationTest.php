<?php

namespace Tests\Unit;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class BladeStyleOrganizationTest extends TestCase
{
    public function test_blade_views_do_not_contain_embedded_css(): void
    {
        $violations = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views')));

        foreach ($files as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            if (preg_match('/<style\b|\sstyle\s*=|\.style\.|cssText/i', $contents)) {
                $violations[] = str_replace(resource_path('views').DIRECTORY_SEPARATOR, '', $file->getPathname());
            }
        }

        $this->assertSame([], $violations, 'Vistas Blade con CSS incrustado: '.implode(', ', $violations));
    }
}
