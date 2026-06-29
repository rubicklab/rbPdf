<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Features\Concurrency;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class RbPdfConcurrentGenerationTest extends TestCase
{
    private string $workDir = '';

    protected function setUp(): void
    {
        parent::setUp();
        $repoRoot = dirname(__DIR__, 3);
        $baseTmp = $repoRoot.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'tmp';
        if (! is_dir($baseTmp)) {
            mkdir($baseTmp, 0775, true);
        }
        $this->workDir = $baseTmp.DIRECTORY_SEPARATOR.'rbpdf_conc_'.uniqid('', true);
        self::assertNotFalse(mkdir($this->workDir, 0700, true));
    }

    protected function tearDown(): void
    {
        if ($this->workDir !== '' && is_dir($this->workDir)) {
            $this->removeTree($this->workDir);
        }
        parent::tearDown();
    }

    public function test_multiple_worker_processes_write_valid_distinct_pdfs(): void
    {
        $repoRoot = dirname(__DIR__, 3);
        $worker = $repoRoot.'/tests/Fixtures/Concurrency/generate_minimal_pdf_worker.php';
        self::assertFileExists($worker);

        $processCount = 4;
        $processes = [];

        for ($i = 0; $i < $processCount; $i++) {
            $outFile = $this->workDir.DIRECTORY_SEPARATOR.'out_'.$i.'.pdf';
            $cacheDir = $this->workDir.DIRECTORY_SEPARATOR.'cache_'.$i;
            self::assertNotFalse(mkdir($cacheDir, 0700, true));
            $token = 'w'.$i.'_'.uniqid('', true);

            $env = $this->buildProcessEnvironment([
                'RBPDF_REPO_ROOT' => $repoRoot,
                'RBPDF_OUT_FILE' => $outFile,
                'RBPDF_CACHE_DIR' => $cacheDir,
                'TEST_TOKEN' => $token,
            ]);

            $cmd = [
                PHP_BINARY,
                '-d',
                'display_errors=1',
                $worker,
            ];

            $pipes = [];
            $processes[] = [
                'proc' => proc_open(
                    $cmd,
                    [
                        0 => ['pipe', 'r'],
                        1 => ['pipe', 'w'],
                        2 => ['pipe', 'w'],
                    ],
                    $pipes,
                    $repoRoot,
                    $env,
                    ['bypass_shell' => true],
                ),
                'out' => $outFile,
                'pipes' => $pipes,
            ];
        }

        $exitCodes = [];
        foreach ($processes as $item) {
            if (is_resource($item['pipes'][0])) {
                fclose($item['pipes'][0]);
            }
            $stderr = stream_get_contents($item['pipes'][2]);
            $stdout = stream_get_contents($item['pipes'][1]);
            fclose($item['pipes'][1]);
            fclose($item['pipes'][2]);
            $exitCodes[] = proc_close($item['proc']);
            $residualStderr = $this->filterIgnorableWorkerStderr($stderr);
            if ($residualStderr !== '') {
                self::fail('Worker stderr: '.$stderr.' stdout: '.$stdout);
            }
        }

        foreach ($exitCodes as $code) {
            self::assertSame(0, $code);
        }

        $hashes = [];
        foreach ($processes as $item) {
            $path = $item['out'];
            self::assertFileExists($path);
            $size = filesize($path);
            self::assertIsInt($size);
            self::assertGreaterThan(64, $size, 'PDF mínimo não deve ser truncado');

            $content = file_get_contents($path);
            self::assertNotFalse($content);
            self::assertStringStartsWith('%PDF', $content);

            $hashes[sha1($content)] = true;
        }

        self::assertCount($processCount, $hashes, 'Cada worker deve produzir conteúdo distinto (rótulo com token).');
    }

    /**
     * Xdebug escreve avisos (ex.: cliente de Step Debug indisponível) no stderr de cada
     * processo PHP filho; o teste exige stderr “limpo” para detetar falhas reais do worker.
     */
    private function filterIgnorableWorkerStderr(string|false $stderr): string
    {
        if ($stderr === false || $stderr === '') {
            return '';
        }

        $lines = preg_split('/\R/', $stderr) ?: [];
        $kept = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }
            if (str_starts_with($trimmed, 'Xdebug:')) {
                continue;
            }
            $kept[] = $trimmed;
        }

        return implode("\n", $kept);
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function buildProcessEnvironment(array $overrides): array
    {
        $env = [];
        foreach (array_merge($_ENV, $_SERVER) as $key => $value) {
            if (! is_string($key)) {
                continue;
            }
            if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
                continue;
            }
            if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key)) {
                continue;
            }
            $env[$key] = (string) $value;
        }

        foreach ($overrides as $key => $value) {
            $env[$key] = $value;
        }

        return $env;
    }

    private function removeTree(string $dir): void
    {
        $items = scandir($dir);
        if ($items === false) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir.DIRECTORY_SEPARATOR.$item;
            if (is_dir($path)) {
                $this->removeTree($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
