# ta.ecomail

## Requirements

- Docker
- Docker Compose
- Git

## Quick Start

Clone the repository and build the project:

```bash
git clone git@github.com:taranovegor/ta.ecomail.git
cd ecomail
cp .env.example .env
make build
make up
```

## Container Management

Start the application in background mode:

```bash
make up
```

Stop and remove containers:

```bash
make down
```

Check the status of all services:

```bash
make ps
```

View logs in real time (press Ctrl+C to exit):

```bash
make logs
```

## Database

Apply pending migrations:

```bash
make migrate
```

Reset the database and load test data:

```bash
make fresh
```

Populate the database using seeders:

```bash
make seed
```

## Dependencies

Install PHP dependencies:

```bash
make composer ARGS='install'
```

Install JavaScript dependencies:

```bash
make npm ARGS='install'
```

Build frontend assets:

```bash
make npm ARGS='run build'
```

## Application

Execute Laravel artisan commands:

```bash
make artisan ARGS='command:name'
```

Launch the interactive REPL:

```bash
make artisan ARGS='tinker'
```

## Architecture

The project uses Docker Compose to orchestrate multiple services: web application, database, cache layer, and queue worker. Separate configurations are provided for development (`compose.dev.yaml`) and production (`compose.prod.yaml`) environments.

## Environment

Switch between environments using the ENV variable:

```bash
ENV=prod make build
```

```bash
ENV=dev make up
```

Development mode is used by default.

## Troubleshooting

If errors occur, check the service logs. For a complete reinstall, perform cleanup and rebuild:

```bash
make down
```

```bash
make rebuild
```

```bash
make up
```

The `rebuild` command rebuilds images without using cache, which is useful when dealing with stale dependencies.

View the complete list of available commands:

```bash
make help
```
