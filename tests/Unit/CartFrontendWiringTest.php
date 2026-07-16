<?php

namespace Tests\Unit;

use Tests\TestCase;

class CartFrontendWiringTest extends TestCase
{
    public function test_public_layout_renders_the_page_script_slot_once(): void
    {
        $layout = file_get_contents(resource_path('views/components/layouts/app.blade.php'));

        $this->assertSame(1, substr_count($layout, '@isset($js)'));
        $this->assertSame(1, substr_count($layout, '{{ $js }}'));
    }

    public function test_product_cart_action_has_one_guarded_click_listener(): void
    {
        $script = file_get_contents(public_path('js/tienda/producto.js'));

        $this->assertSame(1, substr_count($script, "btnAgregarCarrito.addEventListener('click'"));
        $this->assertStringContainsString('event.preventDefault();', $script);
        $this->assertStringContainsString('dataset.cartListenerBound', $script);
        $this->assertStringContainsString('btnElement.disabled = true;', $script);
        $this->assertStringContainsString('btnElement.disabled = false;', $script);
    }
}
