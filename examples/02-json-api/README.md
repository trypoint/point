# JSON API Example

A minimal Point Framework JSON API demonstrating GET/POST requests and HTTP status codes.

## Installation

```bash
cd examples/02-json-api
composer install
```

## Running

### PHP Built-in Server

```bash
php -S localhost:8080 -t public
```

### Test Endpoints

```bash
# List all users
curl http://localhost:8080/api/users

# Get single user
curl http://localhost:8080/api/users/1

# Create user (returns 201)
curl -X POST http://localhost:8080/api/users \
  -H "Content-Type: application/json" \
  -d '{"name": "Charlie", "email": "charlie@example.com"}'

# Delete user (returns 200)
curl -X DELETE http://localhost:8080/api/users/1

# Error cases
curl http://localhost:8080/api/users/999     # 404 Not Found
curl -X POST http://localhost:8080/api/users # 400 Bad Request (no body)
curl -X POST http://localhost:8080/api/users \
  -H "Content-Type: application/json" \
  -d '{"name": ""}'                          # 400 Bad Request (validation)
```

## Features Demonstrated

- Application creation with `AppFactory::create()`
- PSR-17 factory configuration
- Error middleware (development mode)
- Routing middleware
- JSON body parser middleware
- GET routes with dynamic parameters
- POST route with JSON body parsing
- DELETE route
- HTTP exceptions (404, 400, 201)
- JSON responses with proper status codes
- Input validation

## Code Structure

```
examples/02-json-api/
├── composer.json
├── public/
│   └── index.php          # Application entry point
└── README.md
```

## Requirements

- PHP 8.4+
- Composer 2.x
- Extensions: mbstring, json