# ta.ecomail

## Requirements

- Docker
- Docker Compose
- Git

## Quick Start

Clone the repository, install and initialize the full environment (build, start, dependencies, migrations):

```bash
git clone git@github.com:taranovegor/ta.ecomail.git
cd ecomail
make install
```

### Import visuals

**Import processing scheme**  
![Import processing scheme](docs/import-scheme.png)

**100k run resource usage**  
![import-orchestrator-1.png](docs/import-orchestrator-1.png)
![100k run resource usage](docs/queue-import-chunks-1.png)

**Import results**  
![Import results](docs/import-results.png)
`\App\Services\Import\ChunkProcessor::validate`
```php
        // Laravel Validator is too slow for this case.
        // Tests showed that replacing Validator::make with simple native PHP checks
        // makes the process about 3 times faster and uses 15% less memory.
        //
        // We use filter_var and strlen for basic validation,
        // BUT, the email check is not exactly the same as Laravel 'email:rfc' rule.
        //
        // This is a topic for discussion: what is matters more to us.
        $validator = Validator::make($record, [
            'email' => ['required', 'string', 'max:255', 'email:rfc'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
        ]);
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

## Дополнительные команды

Сборка образов (без запуска контейнеров):

```bash
make build
```

Открыть bash внутри контейнера workspace:

```bash
make bash
```

### Проверки и форматирование

Статический анализ PHPStan:

```bash
make phpstan
```

Форматирование кода (Pint):

```bash
make pint
```

Проверка форматирования без изменений:

```bash
make pint-check
```

Единая команда форматирования:

```bash
make format
```

Все проверки разом:

```bash
make lint
```

### Тесты

Запуск тестов:

```bash
make test
```
