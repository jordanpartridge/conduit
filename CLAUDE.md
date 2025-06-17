# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Conduit is a Laravel Zero CLI application that serves as a personal developer API gateway and MCP (Model Context Protocol) integration engine. It focuses on GitHub operations while being extensible to other services.

## Development Commands

```bash
# Install dependencies
composer install

# Run tests
./vendor/bin/pest

# Code formatting
./vendor/bin/pint

# Build PHAR executable
php -d phar.readonly=off vendor/bin/box compile

# Run application locally
php conduit [command]
```

## Architecture

### Laravel Zero Framework
- Built on Laravel Zero 11.x for CLI applications
- Uses standard Laravel conventions with simplified structure
- Commands are located in `app/Commands/`
- Configuration in `config/` follows Laravel patterns

### Core Components
- **Commands**: All CLI commands extend `LaravelZero\Framework\Commands\Command`
- **Service Providers**: Located in `app/Providers/` for dependency injection
- **GitHub Integration**: Uses `jordanpartridge/github-client` package
- **MCP Integration**: Planned integration with Model Context Protocol servers

### Command Structure
Commands follow Laravel Zero patterns:
- Signature and description properties
- `handle()` method for execution
- Optional `schedule()` method for scheduling
- Uses Termwind for terminal styling

### Testing
- Uses Pest testing framework
- Feature tests for command execution
- Unit tests for isolated components
- Run with `./vendor/bin/pest`

### Build Process
- Uses Box for PHAR compilation
- Configuration in `box.json`
- Produces single executable `conduit` file
- Includes app, bootstrap, config, and vendor directories

## Key Dependencies
- `laravel-zero/framework`: Core CLI framework
- `jordanpartridge/github-client`: GitHub API operations
- `guzzlehttp/guzzle`: HTTP client
- `symfony/process`: Process execution
- `pestphp/pest`: Testing framework
- `laravel/pint`: Code formatting