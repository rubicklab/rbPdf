<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Laravel\Console;

use Illuminate\Console\Command;

final class RbPdfInstallCommand extends Command
{
    protected $signature = 'rbpdf:install
                            {--force : Sobrescreve arquivos já publicados}
                            {--no-assets : Não copia logos para public/vendor/rubick/rbpdf/img}';

    protected $description = 'Publica config/rbpdf.php no app Laravel e, por padrão, copia logos para public/vendor (opcional)';

    public function handle(): int
    {
        $this->call('vendor:publish', [
            '--tag' => 'rbpdf-config',
            '--force' => (bool) $this->option('force'),
        ]);

        if (! $this->option('no-assets')) {
            $this->call('vendor:publish', [
                '--tag' => 'rbpdf-assets',
                '--force' => (bool) $this->option('force'),
            ]);
        }

        $this->newLine();
        $this->info('RbPdf: config em config/rbpdf.php (sobreponha valores ou use .env conforme o docblock do pacote).');
        if (! $this->option('no-assets')) {
            $this->comment('Assets em public/vendor/rubick/rbpdf/img — opcional; o PDF já usa os arquivos dentro de vendor por padrão.');
        }

        return self::SUCCESS;
    }
}
