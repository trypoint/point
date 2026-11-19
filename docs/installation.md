# Installation

## Requirements

- **PHP** >= 8.4 (8.5 supported)
- **Composer** 2.x
- Required extensions: `mbstring`, `json` (bundled with PHP 8.4+)

## Composer Installation

```bash
composer require trypoint/point
```

## PSR-7 Implementation (Required)

Point requires a PSR-7/PSR-17 implementation. Choose one:

### Nyholm (Recommended)

Fast, lightweight, minimal dependencies.

```bash
composer require nyholm/psr7 nyholm/psr7-server
```

### Laminas Diactoros

```bash
composer require laminas/laminas-diactoros
```

### Guzzle PSR-7

```bash
composer require guzzlehttp/psr7
```

### HttpSoft

```bash
composer require httpsoft/http-message httpsoft/http-server-request
```

## Development Dependencies

For testing and code quality:

```bash
composer install --dev
```

This installs:
- PHPUnit 13
- PHPStan (level 8)
- PHP-CS-Fixer
- All PSR-7 implementations for integration testing

## Verification

Create `test.php`:

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use Point\Factory\AppFactory;

$app = AppFactory::create();
echo "Point Framework " . Point\App::VERSION . " installed successfully!\n";
```

Run:

```bash
php test.php
```

Expected output:
```
Point Framework 0.1.0-alpha installed successfully!
```

## Project Structure

```
my-project/
├── composer.json
├── composer.lock
├── vendor/
├── public/
│   └── index.php          # Application entry point
├── src/                   # Your application code
└── tests/                 # Your tests
```

---

## Next Steps

- [Quick Start](application.md#quick-start)
- [Routing](routing.md)
- [Middleware](middleware.md)
- [Container](container.md)