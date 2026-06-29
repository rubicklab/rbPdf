# rubick/rbpdf (RbPdf)

Biblioteca PHP **RbPdf**: API fluente para PDF com **FPDF** (`fawno/fpdf`), pensada para o ecossistema Rubick. Cada documento deve ser criado via fábrica para **isolamento** entre requisições, jobs  e workers.

## Requisitos

- PHP **8.1+**
- Laravel **10+** (opcional, para auto-discovery, config e comando `rbpdf:install`)
- Extensão **`mbstring`**
- [Composer](https://getcomposer.org/)

## Instalação no Laravel

Use sempre o branch **`production`** para instalar o pacote num projeto Laravel. **Não** use `master` para essa instalação — o código estável para consumo fica em `production`.

### Passo a passo

1. **Abrir o `composer.json` do projeto Laravel** e adicionar o repositório VCS (ajuste a URL ao repositório real):

   ```json
   {
       "repositories": [
           {
               "type": "vcs",
               "url": "https://github.com/SUA_ORG/RbPdf.git"
           }
       ]
   }
   ```

2. **Instalar a versão do branch `production`**. No Composer, branches têm o prefixo `dev-`; para `production` use `dev-production`:

   ```bash
   composer require rubick/rbpdf:dev-production
   ```

   Alternativa equivalente: declarar no `composer.json` em `require` e correr `composer update`:

   ```json
   "require": {
       "rubick/rbpdf": "dev-production"
   }
   ```

3. **Publicar config e assets** no Laravel:

   ```bash
   php artisan rbpdf:install
   ```

O pacote regista automaticamente o `RbPdfServiceProvider` (via `extra.laravel` no Composer). O comando `rbpdf:install` publica `config/rbpdf.php` e, por defeito, copia logos para `public/vendor/rubick/rbpdf/img` (opcional para URLs públicas; o PDF usa os ficheiros em `vendor` por defeito).

Opções:

- `php artisan rbpdf:install --no-assets` — só o config
- `php artisan rbpdf:install --force` — sobrescreve ficheiros já publicados

### Resolver `RbPdfConfiguration` no container

Após o provider estar ativo:

```php
use Rubick\RbPdf\Config\RbPdfConfiguration;

$config = app(RbPdfConfiguration::class);
```

## Uso rápido (qualquer PHP)

```php
<?php

declare(strict_types=1);

use Rubick\RbPdf\Enum\RbPdfFontStyle;
use Rubick\RbPdf\RbPdf;

$pdf = new RbPdf;

$binary = $pdf
    ->addPage()
    ->font('Helvetica', RbPdfFontStyle::Regular, 12)
    ->cell(0, 10, 'Olá, RbPdf', 0, 1)
    ->toString();
```

## Configuração (`config/rbpdf.php`)

O ficheiro do pacote define defaults com caminhos absolutos para `public/img` **dentro do vendor** (logos incluídas). Variáveis de ambiente listadas no docblock do ficheiro continuam a poder sobrepor paths, fontes e locale.

Em Laravel, o stub publicado mescla esses defaults com `paths.public` = `public_path()`.

## Documentação HTML

Na raiz do repositório:

```bash
composer install
php -S localhost:8765 -t docs
```

Abrir [http://localhost:8765/index.html](http://localhost:8765/index.html).

## Cache, concorrência e testes paralelos

- **`RBPDF_CACHE_DIR`**: diretório base para cache (ex.: TTF). Em vários workers no mesmo disco, use diretório por worker ou token de teste.
- **`TEST_TOKEN`** / ParaTest: a suíte isola subpastas de cache em testes paralelos.

## Testes (PHPUnit)

Na raiz do repositório, instale as dependências (inclui o PHPUnit em `vendor/`):

```bash
composer install
```

**Forma direta de rodar a suíte** (não depende do comando `composer` no PATH depois do install — use o executável do PHPUnit):

```bash
./vendor/bin/phpunit -c phpunit.xml.dist
```

- `vendor/bin` é uma **pasta**; o programa certo é `vendor/bin/phpunit` (como acima).
- Se o shebang do binário não for usado no seu ambiente, equivale a: `php vendor/bin/phpunit -c phpunit.xml.dist`.

**Atalhos via Composer** (se `composer` estiver instalado):

```bash
composer test              # mesmo que phpunit -c phpunit.xml.dist
composer test:parallel     # Paratest (vários processos)
```

## Estrutura

- `src/` — biblioteca (`Rubick\RbPdf\…`), incluindo `Laravel/` (provider e comando)
- `config/rbpdf.php` — defaults do pacote
- `resources/stubs/rbpdf.laravel.php` — stub publicado no app Laravel
- `public/img/` — logos padrão (referenciadas nos defaults)
- `docs/` — documentação estática
- `tests/` — PHPUnit

## Licença

Proprietária (Rubick).
