<?php

namespace Tests\Unit;

use Tests\TestCase;

class ProductSkuFeedbackViewTest extends TestCase
{
    public function test_product_forms_render_general_and_indexed_sku_errors(): void
    {
        foreach (['create.blade.php', 'edit.blade.php'] as $view) {
            $contents = file_get_contents(resource_path('views/admin/productos/'.$view));

            $this->assertStringContainsString('$errors->any()', $contents);
            $this->assertStringContainsString('Existen campos por corregir.', $contents);
            $this->assertStringContainsString('@error("variantes.$index.sku")', $contents);
            $this->assertStringContainsString('old(\'variantes\'', $contents);
        }
    }
}
