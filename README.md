# 🚀 Conduit v1.0.0

> Your personal developer API & MCP integration engine - AI-ready GitHub CLI and beyond

[![Latest Version](https://img.shields.io/packagist/v/jordanpartridge/conduit.svg?style=flat-square)](https://packagist.org/packages/jordanpartridge/conduit)
[![Total Downloads](https://img.shields.io/packagist/dt/jordanpartridge/conduit.svg?style=flat-square)](https://packagist.org/packages/jordanpartridge/conduit)
[![License](https://img.shields.io/packagist/l/jordanpartridge/conduit.svg?style=flat-square)](https://packagist.org/packages/jordanpartridge/conduit)

Conduit is a **modular, extensible CLI platform** built with Laravel Zero that transforms your development workflow. Starting with powerful GitHub integration, it features a revolutionary component system that makes adding new tools as simple as running `conduit install:service`.

## ✨ What's New in v1.0.0

### 🧩 **Modular Component System**
- **Dynamic installation**: Add integrations on-demand without rebuilding
- **GitHub discovery**: Auto-discover components via topics
- **Clean lifecycle**: Install, configure, and remove components seamlessly
- **Config-driven**: No database dependencies, pure configuration

### 🐙 **GitHub Zero Integration** 
- **Interactive workflows**: Rich Laravel Prompts UI for all operations
- **Smart repository management**: Browse, clone, and manage repos with ease
- **Environment automation**: Automatic `.env` setup with token validation
- **Service provider magic**: Seamless Laravel Zero integration

## 🚀 Installation

### Via Composer (Recommended)
```bash
composer global require jordanpartridge/conduit
```

### Via GitHub Releases
```bash
# Download latest PHAR
curl -L https://github.com/jordanpartridge/conduit/releases/latest/download/conduit.phar -o conduit
chmod +x conduit
sudo mv conduit /usr/local/bin/conduit
```

### Development Setup
```bash
git clone https://github.com/jordanpartridge/conduit.git
cd conduit
composer install
```

## 🎯 Quick Start

```bash
# Install GitHub integration
conduit install:github

# Browse your repositories interactively
conduit repos --interactive

# Clone a repository with smart selection  
conduit clone --interactive

# Manage installed components
conduit components

# List all available commands
conduit list
```

## 🧩 Component Architecture

Conduit's revolutionary component system allows you to:

```bash
# Discover available integrations
conduit components

# Install new integrations dynamically
conduit install:github
conduit install:docker    # Coming soon
conduit install:aws       # Coming soon

# Remove integrations cleanly
conduit uninstall:github
```

### Available Components
- **🐙 GitHub Zero**: Repository management, cloning, and exploration
- **🐳 Docker** *(planned)*: Container management and orchestration
- **☁️ AWS Toolkit** *(planned)*: Cloud infrastructure helpers
- **🗄️ Database Tools** *(planned)*: Migration and seeding utilities

## 🤖 AI-Ready Architecture

Conduit is built from the ground up for AI integration:
- **Structured commands**: Perfect for AI tool integration
- **Rich metadata**: Commands expose detailed help and options  
- **Context-aware**: Smart defaults based on project detection
- **MCP Protocol ready**: Foundation for Model Context Protocol servers

## Development

This project is built with Laravel Zero and uses the `jordanpartridge/github-client` package for GitHub operations.

## License

MIT
