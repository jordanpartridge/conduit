# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Conduit is a Laravel Zero CLI application that serves as a personal developer API gateway and MCP (Model Context Protocol) integration engine. It provides a microkernel architecture for integrating multiple developer tools through a component system.

## Development Commands (Conduit-First Approach)

```bash
# Install dependencies
composer install

# Use Conduit for development workflow
php conduit components list                    # See installed development tools
php conduit components discover               # Find new development components  
php conduit components install github        # Install GitHub component for repo management

# Core development tasks
./vendor/bin/pest                            # Run tests
./vendor/bin/pint                           # Code formatting

# Build and distribution
php -d phar.readonly=off vendor/bin/box compile  # Build PHAR executable
php conduit [command]                        # Run application locally
```

## Conduit-Powered Development Workflow

### Component Management
- **Components**: Modular functionality via `conduit components`
- **Discovery**: Find new tools via GitHub topics and Packagist
- **Installation**: Automated setup with `conduit components install`
- **Registry**: Curated vs community components

### Future Development Commands
Once component ecosystem is built, development will use:
```bash
# Version control (via github-zero component)
conduit github repos                        # List repositories
conduit github clone <repo>                 # Clone repositories
conduit github pr create                    # Create pull requests

# Package management (via conduit-composer component)  
conduit composer require <package>          # Smart package installation
conduit composer audit                      # Security and dependency analysis

# Testing and quality (via conduit-laravel component)
conduit laravel test                        # Run Laravel tests
conduit laravel migrate                     # Database migrations
```

## Architecture

### Microkernel Design
- **Core**: Minimal framework with component system only
- **Components**: All functionality via installable components
- **Discovery**: Automated component finding via GitHub/Packagist
- **Registry**: Tiered components (core/certified/community)

### Component Structure
All components extend base `ConduitComponent` class:
- Standard installation/uninstallation
- Self-validation capabilities
- Metadata provision (commands, env vars, etc.)
- MCP integration hooks

### Development Philosophy
- **Conduit builds Conduit**: Use Conduit itself for development tasks
- **Component-first**: Everything is a discoverable, installable component
- **AI-ready**: MCP integration for AI tool collaboration
- **Microkernel**: Core remains minimal and focused

## Testing Strategy
- **Unit Tests**: Individual component testing
- **Integration Tests**: Component installation and interaction
- **End-to-end**: Full workflow testing with multiple components
- **Self-validation**: Components can test their own health

## Key Dependencies
- `laravel-zero/framework`: CLI framework foundation
- `jordanpartridge/conduit-component`: Base component interface (planned)
- `jordanpartridge/packagist-client`: Component discovery
- `jordanpartridge/github-client`: GitHub integration foundation