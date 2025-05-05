# Real-time Log Viewer

A simple, real-time log viewer for development environments. This application provides a web interface to view and search logs in real-time, with support for formatted context data and log level highlighting.

## Features

- 🔄 Real-time log streaming
- 🔍 Full-text search capability
- 📊 Formatted context visualization with expand/collapse
- 🎨 Dark/Light theme support
- 🎯 Log level highlighting
- 🔒 SQLite storage for persistence
- 🚀 Fast and lightweight
- 🐳 Docker support

## Requirements

- [Docker](https://www.docker.com/)

## Quick Start

1. Start the application:
```bash
docker compose up -d
```

2. Access the web interface:
```
http://localhost:8080
```

## Development Setup

1. Build the development docker image:
```bash
make image
```

2. Install dependencies:
```bash
make install
```

3. Start the application in development mode:
```bash
make serve
```

Or with auto-reload on file changes:
```bash
make serve-watch
```

4. Access the PHP container shell:
```bash
make sh
```

## Code Quality Tools

- Run PHP CodeSniffer:
```bash
make phpcs
```

- Run PHPStan static analysis:
```bash
make phpstan
```

- Run unit tests:
```bash
make test
```

## API Documentation

For detailed information about sending logs to the viewer, see the [API Documentation](docs/API.md).

## Environmental Variables

| Variable | Default | Description |
|----------|---------|-------------|
| DATABASE_DSN | :memory: | SQLite database path. Use :memory: for in-memory storage |
| TZ | America/Sao_Paulo | Timezone for log timestamps |

## Architecture

The application is built using:
- PHP 8.3
- ReactPHP for async HTTP server
- SQLite with FTS5 for full-text search
- Server-Sent Events (SSE) for real-time updates
- HTMX for dynamic UI updates
- Hyperscript for client-side interactions

## License

This project is licensed under the MIT License.
