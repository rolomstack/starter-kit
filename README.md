# Laravel & React Starter Kit

A Laravel + React starter template with multi-tenancy (businesses/teams), authentication, settings, and subscription handling.

## Tech Stack

- **Backend**: Laravel 13, Fortify (Authentication)
- **Frontend**: React 19, Inertia.js v3, Tailwind CSS v4, TypeScript
- **Routing**: Laravel Wayfinder (typed TS routing)
- **Testing**: Pest 4

## Setup

1. **Clone and install dependencies**:
   ```bash
   git clone <repository-url>
   cd amarkit
   ```

2. **Configure environment**:
   Copy `.env.example` to `.env` and set up your database (SQLite by default) and Paystack credentials.
   ```bash
   cp .env.example .env
   ```

3. **Initialize the application**:
   Run the setup script to install all dependencies, generate keys, run migrations, and build assets:
   ```bash
   composer run setup
   ```

4. **Run the dev server**:
   ```bash
   composer run dev
   ```
   This starts both Vite and the Laravel dev server.

## Scripts

- `composer run dev` - Start Vite and the local PHP server.
- `composer run setup` - Run full project installation and setup.
- `composer run test` - Run lints, typechecks, and Pest tests.
- `npm run build` - Build production assets.
- `composer run lint` - Run Laravel Pint to format PHP code.
- `npm run lint` - Run ESLint.
- `npm run format` - Format frontend code with Prettier.

## Testing

Tests are written using Pest. You can run the suite via:
```bash
composer run test
# or just tests alone:
php artisan test
```
