# Conduit

> Your personal developer API & MCP integration engine - AI-ready GitHub CLI and beyond

Conduit is a Laravel Zero-powered CLI that acts as your personal API gateway, starting with GitHub mastery but extensible to any service. It's designed to work seamlessly with both human developers and AI agents through the Model Context Protocol (MCP).

## Vision

Conduit transforms how developers interact with their tools:
- **GitHub Superpowers**: GitKraken-style visualizations and smart operations in your terminal
- **MCP Integration Engine**: Spawn and manage MCP servers for any integration
- **Personal API Gateway**: Register and unify all your services under one interface
- **AI-Ready**: Built for both human and AI agent interaction from day one

## Features (Planned)

### GitHub Excellence
- Interactive branch visualization
- Smart conflict resolution
- AI-powered commit messages
- Visual diff tools in terminal
- Workflow designer for GitHub Actions

### MCP Integration
- Auto-discovery of available MCPs
- Health monitoring and auto-restart
- Unified authentication
- Rate limiting and caching

### Personal API Platform
- Register any API endpoint
- Natural language queries across all services
- Unified search and commands
- Extensible plugin system

## Installation

```bash
composer global require jordanpartridge/conduit
```

## Usage

```bash
# GitHub operations
conduit pr create --interactive
conduit branch visualize
conduit conflict resolve --smart

# MCP server management
conduit serve github
conduit serve --all

# Personal API
conduit register myapp --url=api.myapp.com
conduit query "show me all critical issues across all projects"
```

## Development

This project is built with Laravel Zero and uses the `jordanpartridge/github-client` package for GitHub operations.

## License

MIT
