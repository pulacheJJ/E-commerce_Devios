<?php

namespace Tests\Unit;

use Tests\TestCase;

class DeploymentPackagingTest extends TestCase
{
    public function test_docker_image_excludes_secrets_and_uses_runtime_configuration(): void
    {
        $dockerignore = file_get_contents(base_path('.dockerignore'));
        $dockerfile = file_get_contents(base_path('Dockerfile'));
        $compose = file_get_contents(base_path('docker-compose.yml'));

        $this->assertStringContainsString(".env\n", str_replace("\r\n", "\n", $dockerignore));
        $this->assertStringNotContainsString('COPY .env', $dockerfile);
        $this->assertStringContainsString('ENTRYPOINT ["devioz-entrypoint"]', $dockerfile);
        $this->assertStringContainsString('RUN_DEMO_SEEDERS: ${RUN_DEMO_SEEDERS:-false}', $compose);
        $this->assertStringContainsString('RUN_SCHEDULER: ${RUN_SCHEDULER:-false}', $compose);
        $this->assertStringContainsString('SEED_ADMIN_PASSWORD: ${SEED_ADMIN_PASSWORD:-}', $compose);
        $this->assertStringContainsString('MERCADOPAGO_ALLOW_LIVE: ${MERCADOPAGO_ALLOW_LIVE:-false}', $compose);
        $this->assertStringContainsString('command: ["php", "artisan", "schedule:work"]', $compose);
        $this->assertStringContainsString('DB_HOST: ${DB_HOST:-db}', $compose);
    }

    public function test_production_example_is_safe_for_a_test_payment_demo(): void
    {
        $environment = file_get_contents(base_path('.env.production.example'));

        $this->assertStringContainsString('APP_DEBUG=false', $environment);
        $this->assertStringContainsString('ADMIN_DOMAIN=', $environment);
        $this->assertStringContainsString('MERCADOPAGO_MODE=test', $environment);
        $this->assertStringContainsString('MERCADOPAGO_ALLOW_LIVE=false', $environment);
        $this->assertStringContainsString('FORCE_HTTPS=true', $environment);
        $this->assertStringContainsString('SESSION_SECURE_COOKIE=true', $environment);
        $this->assertStringContainsString('MEDIA_DISK=public', $environment);
        $this->assertStringContainsString('AWS_DEFAULT_REGION=auto', $environment);
        $this->assertStringContainsString('CLOUDINARY_CLOUD_NAME=', $environment);
        $this->assertStringContainsString('CLOUDINARY_API_SECRET=', $environment);
        $this->assertStringContainsString('RUN_MIGRATIONS=false', $environment);
        $this->assertStringContainsString('RUN_DEMO_SEEDERS=false', $environment);
        $this->assertStringNotContainsString('APP_KEY=base64:', $environment);
    }

    public function test_render_blueprint_keeps_secrets_external_and_enforces_https(): void
    {
        $blueprint = file_get_contents(base_path('render.yaml'));

        $this->assertStringContainsString('runtime: docker', $blueprint);
        $this->assertStringContainsString('healthCheckPath: /health', $blueprint);
        $this->assertStringContainsString("- key: FORCE_HTTPS\n        value: \"true\"", str_replace("\r\n", "\n", $blueprint));
        $this->assertStringContainsString("- key: APP_KEY\n        sync: false", str_replace("\r\n", "\n", $blueprint));
        $this->assertStringContainsString("- key: MERCADOPAGO_ALLOW_LIVE\n        value: \"false\"", str_replace("\r\n", "\n", $blueprint));
        $this->assertStringContainsString("- key: MEDIA_DISK\n        value: cloudinary", str_replace("\r\n", "\n", $blueprint));
        $this->assertStringNotContainsString('base64:', $blueprint);
        $this->assertStringNotContainsString('TEST-', $blueprint);
    }

    public function test_https_is_proxy_aware_and_enabled_by_runtime_configuration(): void
    {
        $kernel = file_get_contents(base_path('app/Http/Kernel.php'));
        $proxies = file_get_contents(base_path('app/Http/Middleware/TrustProxies.php'));
        $https = file_get_contents(base_path('app/Http/Middleware/EnforceHttps.php'));

        $this->assertStringContainsString('TrustProxies::class', $kernel);
        $this->assertStringContainsString('TrustHosts::class', $kernel);
        $this->assertStringContainsString('EnforceHttps::class', $kernel);
        $this->assertStringContainsString("protected \$proxies = '*'", $proxies);
        $this->assertStringContainsString('HEADER_X_FORWARDED_PROTO', $proxies);
        $this->assertStringContainsString("config('app.force_https')", $https);
        $this->assertStringContainsString('Strict-Transport-Security', $https);
    }

    public function test_docker_hub_workflow_builds_the_render_architecture_with_current_actions(): void
    {
        $workflow = file_get_contents(base_path('.github/workflows/docker-publish.yml'));

        $this->assertStringContainsString('actions/checkout@v4', $workflow);
        $this->assertStringContainsString('docker/setup-buildx-action@v3', $workflow);
        $this->assertStringContainsString('docker/login-action@v3', $workflow);
        $this->assertStringContainsString('docker/build-push-action@v6', $workflow);
        $this->assertStringContainsString('platforms: linux/amd64', $workflow);
        $this->assertStringContainsString('devioz-ecommerce:${{ github.sha }}', $workflow);
    }

    public function test_admin_domain_is_optional_and_routes_keep_the_admin_fallback(): void
    {
        $config = file_get_contents(base_path('config/app.php'));
        $routes = file_get_contents(base_path('routes/web.php'));

        $this->assertStringContainsString("'admin_domain' => env('ADMIN_DOMAIN')", $config);
        $this->assertStringContainsString('Route::domain($adminDomain)', $routes);
        $this->assertStringContainsString("Route::prefix('admin')", $routes);
        $this->assertStringContainsString('Route::domain($storeDomain)', $routes);
    }

    public function test_uploads_share_the_persistent_public_disk(): void
    {
        $sliderService = file_get_contents(base_path('app/Services/SliderService.php'));
        $compose = file_get_contents(base_path('docker-compose.yml'));

        $this->assertStringContainsString("store('sliders', MediaStorage::diskName())", $sliderService);
        $this->assertStringNotContainsString("move(public_path('img/sliders')", $sliderService);
        $this->assertStringContainsString('app_storage:/var/www/html/storage/app/public', $compose);
        $this->assertStringContainsString('MEDIA_DISK: ${MEDIA_DISK:-public}', $compose);
        $this->assertStringContainsString('CLOUDINARY_API_SECRET: ${CLOUDINARY_API_SECRET:-}', $compose);
        $this->assertStringNotContainsString('slider_data:', $compose);
    }
}
