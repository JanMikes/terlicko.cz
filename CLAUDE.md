# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Architecture

This is a modern web application for the municipality of Těrlicko (Czech Republic) built with a decoupled architecture:

- **Strapi v5** - Headless CMS backend providing content API
- **Symfony 7** - Frontend application consuming Strapi API
- **PostgreSQL** - Database for both Strapi and Symfony
- **Docker Compose** - Development environment orchestration

The Symfony frontend serves as a public-facing website while Strapi provides a content management interface for administrators.

## Development Environment

### Starting the Application
```bash
docker compose up
```

This starts all services:
- Frontend (Symfony): http://localhost:8080
- Strapi CMS: http://localhost:1337
- Adminer (DB admin): http://localhost:8000

### Key Directories

**Frontend (Symfony)**:
- `frontend/src/Controller/` - Page controllers
- `frontend/src/Components/` - Live components for dynamic content
- `frontend/templates/` - Twig templates
- `frontend/assets/` - Frontend assets (CSS, JS, images)
- `frontend/src/Services/` - Business logic and Strapi API integration

**Strapi CMS**:
- `strapi/src/` - Strapi application code
- `strapi/config/` - Strapi configuration
- `strapi/public/uploads/` - Media uploads (shared with frontend)

## Common Development Commands

**IMPORTANT: All commands must be executed inside Docker containers using `docker compose exec`. Never run commands directly on the host machine.**

### Frontend (Symfony)

```bash
# Run PHPStan static analysis
docker compose exec frontend composer phpstan

# Run tests
docker compose exec frontend vendor/bin/phpunit

# Symfony console commands
docker compose exec frontend bin/console cache:clear
docker compose exec frontend bin/console doctrine:migrations:migrate

# Install dependencies
docker compose exec frontend composer install
```

### Strapi

```bash
# Start development server (usually runs automatically)
docker compose exec strapi npm run develop

# Build for production
docker compose exec strapi npm run build

# Start production server
docker compose exec strapi npm run start

# Install dependencies
docker compose exec strapi npm install
```

## Key Architecture Patterns

### Content Fetching
The Symfony frontend fetches content from Strapi through service classes in `frontend/src/Services/Strapi/`. The main service is `StrapiContent` which provides methods like:
- `getAktualityData()` - News/announcements
- `getUredniDeskyData()` - Official notices
- `getHomepageData()` - Homepage content

### Live Components
The application uses Symfony UX Live Components for dynamic content updates without full page reloads. Components are in `frontend/src/Components/` with corresponding Twig templates in `frontend/templates/components/`.

### Content Types
Main content types managed in Strapi:
- Aktuality (News/Announcements)
- Uredni Deska (Official Board)
- Kalendar Akci (Event Calendar)
- Homepage sections and components

## Testing

PHPUnit is configured for the Symfony frontend with:
- Test directory: `frontend/tests/`
- Bootstrap file: `frontend/tests/bootstrap.php`
- Database fixtures for testing
- DAMA Doctrine Test Bundle for transaction isolation

## Static Analysis

PHPStan is configured with Symfony, Doctrine, and PHPUnit rules. Run with memory limit disabled due to codebase size.