<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Laravel;

use Illuminate\Support\ServiceProvider;
use Rubick\RbPdf\Config\RbPdfConfiguration;
use Rubick\RbPdf\Laravel\Console\RbPdfInstallCommand;

final class RbPdfServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/rbpdf.php', 'rbpdf');

        $this->app->singleton(RbPdfConfiguration::class, static function ($app): RbPdfConfiguration {
            /** @var array<string, mixed> $data */
            $data = $app['config']->get('rbpdf', []);

            return RbPdfConfiguration::fromArray($data);
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([RbPdfInstallCommand::class]);
        }

        $this->publishes([
            __DIR__.'/../../resources/stubs/rbpdf.laravel.php' => config_path('rbpdf.php'),
        ], 'rbpdf-config');

        $this->publishes([
            __DIR__.'/../../public/img' => public_path('vendor/rubick/rbpdf/img'),
        ], 'rbpdf-assets');
    }
}
