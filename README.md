# UIPulse AI Boilerplate

A high-performance boilerplate for building AI-driven design analysis tools. 
Integrated with Laravel 12, Filament v3, and ready for Figma plugin interaction.

## Features
- **Filament Admin**: Manage projects, designs, and AI results.
- **Figma API**: Ingest design nodes directly into your database.
- **AI Engine**: Asynchronous processing with Redis and OpenAI/Anthropic.
- **Docker Ready**: Pre-configured with Laravel Sail (MySQL, Redis, Meilisearch).
- **Tested**: Full feature coverage with Pest.

## Quick Start (Local / Nixpacks)

1. **Clone & Install**
   ```bash
   composer install
   cp .env.example .env
   php artisan key:generate
   ```

2. **Setup Database**
   Ensure you have a local MySQL database named `uipulse` or change `DB_CONNECTION` to `sqlite` in `.env`.

3. **Migrate & Seed**
   ```bash
   php artisan migrate:fresh --seed
   ```
   - *Default Admin*: `admin@uipulse.ai` / `password`

4. **Start Development Server**
   ```bash
   php artisan serve
   ```

## Quick Start (Docker with Sail)
...

## Figma API Integration

- **Base URL**: `http://uipulse.test/api/v1`
- **Auth**: Bearer Token (Sanctum)

### Endpoints
- `GET /projects`: List available projects.
- `POST /projects/{project}/designs`: Import a design frame.

## Local Development
- **Tailwind/Vite**: `npm run dev`
- **Queue Worker**: `php artisan queue:work`
- **Meilisearch**: `http://localhost:7700`

## Deployment
- Use `composer install --no-dev --optimize-autoloader`.
- Recommended runtime: **Laravel Octane (FrankenPHP)**.
