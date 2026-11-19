# Hello World Example

A minimal Point Framework application demonstrating basic routing and text responses.

## Installation

```bash
cd examples/01-hello-world
composer install
```

## Running

### PHP Built-in Server

```bash
php -S localhost:8080 -t public
```

Visit:
- `http://localhost:8080/` → "Hello, Point Framework!"
- `http://localhost:8080/hello/World` → "Hello, World!"
- `http://localhost:8080/hello/Point` → "Hello, Point!"

### Apache/Nginx

Configure document root to `public/` directory.

## Features Demonstrated

- Application creation with `AppFactory::create()`
- PSR-17 factory configuration
- Error middleware (development mode)
- Routing middleware
- GET routes with dynamic parameters
- Text responses

## Code Structure

```
examples/01-hello-world/
├── composer.json
├── public/
│   └── index.php          # Application entry point
└── README.md
```

## Requirements

- PHP 8.4+
- Composer 2.x
- Extensions: mbstring, json