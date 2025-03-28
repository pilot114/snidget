# Contributing to Snidget

Thank you for considering contributing to Snidget! This document outlines the process for contributing to the Snidget PHP microframework.

## Code of Conduct

By participating in this project, you are expected to uphold our Code of Conduct. Please report unacceptable behavior to the project maintainers.

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check the existing issues to see if the problem has already been reported. When you are creating a bug report, please include as many details as possible:

* A clear and descriptive title
* Steps to reproduce the issue
* Expected behavior
* Actual behavior
* PHP version and environment details
* Logs or stack traces if available

### Suggesting Enhancements

Enhancement suggestions are also welcome. Please provide:

* A clear and descriptive title
* A detailed description of the proposed functionality
* An explanation of why this enhancement would be useful to Snidget users

### Pull Requests

* Follow the [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standard
* Include tests for new features or bug fixes when applicable
* Update documentation if necessary
* Add entries to the CHANGELOG.md file for notable changes
* Keep pull requests focused on a single topic

## Development Environment

The recommended way to set up a development environment is using Docker:

1. Clone the repository: `git clone https://github.com/pilot114/snidget.git`
2. Navigate to the project directory: `cd snidget`
3. If you're working with async functionality:
   ```
   cd App/Module/Async/docker
   docker-compose -f serve.yml up -d
   ```

## Project Structure

Snidget follows a modular architecture:

```
App/                    # Application code
  Module/               # Modules (Core, Async, Blockchain, etc.)
  Schema/               # Data schema definitions
  HTTP/                 # HTTP-related controllers
bin/                    # Executable scripts
data/                   # Data storage
public/                 # Public web directory
src/                    # Framework core
  CLI/                  # Command line functionality
  Database/             # Database abstraction
  HTTP/                 # HTTP handling
  Kernel/               # Core framework functionality
  Kernel/PSR/           # PSR implementations
```

## Testing

Run tests with the following command:

```
composer tests
```

### Code Quality

Snidget uses the following tools to ensure code quality:

* PHPStan for static analysis: `composer phpstan`
* Rector for automated code refactoring: `composer rector-lint` (check) or `composer rector-fix` (apply)
* PHPLOC for code metrics: `composer phploc`

## Attributes

Snidget makes extensive use of PHP 8 attributes. Notable attributes include:

* `#[Route]` for HTTP routing
* `#[Command]` for CLI commands
* `#[Bind]` for middleware binding
* `#[Assert]` for validation
* `#[Column]` for database schema

## Documentation

When adding new features, please update the relevant documentation in the `utils/docs/md` directory. Documentation can be built using the `bin/docs.php` script.

## Submitting Changes

1. Create a branch for your changes: `git checkout -b feature/my-feature` or `git checkout -b fix/my-bug-fix`
2. Make your changes
3. Run tests and code quality checks
4. Commit your changes with a descriptive message
5. Push to your branch
6. Submit a pull request

## Release Cycle

Snidget follows semantic versioning (MAJOR.MINOR.PATCH):

* MAJOR version for incompatible API changes
* MINOR version for backwards-compatible functionality additions
* PATCH version for backwards-compatible bug fixes

## License

By contributing to Snidget, you agree that your contributions will be licensed under the project's MIT license.