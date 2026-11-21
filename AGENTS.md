# snidget — для агентов



## Project

Snidget is a PHP 8.6 educational microframework demonstrating DDD + CQRS patterns, PSR compatibility, and modern PHP features (attributes, enums, fibers). Comments are short, in simple English; error messages are in Russian; class/method names follow English conventions.

## Development Environment

The project runs inside Docker. All composer commands assume `/app` working directory inside the container.

```bash
# Docker (from repo root; compose file — dev/docker/serve.yml)
make build    # Build images (php 8.6 + xdebug, nginx)
make install  # composer install in container
make dev      # nginx + PHP-FPM on port 8099, Ctrl+C to stop
make docs     # Build dev/docs/index.html, served at http://localhost:8099/docs/
make enter    # Shell into PHP container

# Inside container
composer test       # PHPUnit with coverage (HTML → dev/tests/coverage/)
composer check      # PHPStan (max, без baseline) + Rector dry-run + PHPCS
composer fix        # Rector + PHPCBF
```

### Running individual tests

```bash
phpunit /app/dev/tests/ContainerTest.php --bootstrap /app/dev/tests/bootstrap.php
phpunit --filter testHas /app/dev/tests/ContainerTest.php --bootstrap /app/dev/tests/bootstrap.php
```

### Pre-commit hooks

`composer hook-install` sets git hooks path to `dev/git-hooks/`. The pre-commit hook runs phpstan, tests, phploc, and lint.

## Architecture

### Two namespaces

- `Snidget\` → `src/` — Framework core (HTTP, CLI, Database, Kernel, PSR implementations)
- `App\` → `App/` — Application code using the framework

### Attribute-driven configuration

Everything is configured via PHP 8 attributes instead of YAML/XML:
- `#[Route]` — HTTP routing (regex-based, class-level prefix supported)
- `#[Command]` — CLI command registration
- `#[Bind]` — Middleware binding (with priority, controller/action targeting)
- `#[Listen]` — Event listener registration
- `#[Assert]` — Validation rules on DTO properties
- `#[Column]` — Database schema definition
- `#[Arg]` — CLI argument definition

### Modular structure

Application modules live under `App/Module/`. Each module can contain `Command/`, `HTTP/Controller/`, `HTTP/Middleware/`, `Schema/`, and `Domain/` directories. The framework auto-discovers these via `AppPaths::getPathsByType()` glob patterns.

### Entry points

- **HTTP**: `public/index.php` → `Kernel::run()` (routes, middleware onion, controller dispatch, events)
- **CLI**: `bin/app` → `CommandHandler` resolves `Class:method` pattern (e.g., `Test:run`)
- **Async HTTP**: Same entry point with `ASYNC=1` env → Fiber-based server via `AsyncKernel`

### DI Container

`Container.php` implements PSR-11 with auto-wiring: resolves constructor params by type, supports interface→implementation linking, caches singletons. Service definitions in `App/container.php`.

### PSR implementations

Custom implementations of PSR-3 (Logger), PSR-11 (Container), PSR-14 (EventDispatcher with `#[Listen]`), PSR-16 (MemoryCache).

### Kernel lifecycle events

START → REQUEST → RESPONSE → FINISH (or ERROR). Listeners registered via `#[Listen(KernelEvent::X)]`.

## Code Standards

- PSR-12 coding standard
- PHPStan level max (config in `dev/phpstan.neon`)
- Rector for automated refactoring (dead code, code quality, type declarations, early returns)
- Heavy use of typed properties, return types, enums, constructor property promotion

## Команды

Обслуживание — как в pretty-php: `composer test`, `composer check`, `composer fix`.

- `check` — phpstan, rector --dry-run, phpcs; ничего не меняет
- `fix` — rector, phpcbf
- `test` — phpunit с покрытием

Цели Makefile одинаковые во всех проектах `~/projects` (`make help` — список):

- `make install` — поставить зависимости
- `make dev` — запустить для разработки
- `make build` — сборка
- `make audit` — уязвимости в зависимостях
- `make outdated` — устаревшие зависимости
