# Laravel React Starter Kit

A premium, production-ready starter kit built with Laravel 13, React 19, Inertia.js v3, Tailwind CSS v4, and TypeScript. It includes ready-to-use business/team management, subscription handling, and a modern dashboard UI.

---

## 🚀 Features

- **Authentication & Security:** Powered by [Laravel Fortify](https://laravel.com/docs/fortify) and custom React components (login, registration, password reset, and profile settings).
- **Multi-Tenancy & Business Management:** Built-in team/business invitations, member roles, and member management.
- **Subscription Management:** Pre-configured subscription settings and integration routes (e.g., Paystack billing gateway).
- **Modern UI Components:** Built using [Tailwind CSS v4](https://tailwindcss.com), TypeScript, and Radix UI primitives.
- **Developer Experience:**
  - **Laravel Wayfinder:** Auto-generated typed TS routes for effortless routing.
  - **Pest PHP:** Next-generation testing framework for comprehensive unit/feature testing.
  - **Laravel Pint & ESLint/Prettier:** Configured styling, linting, and formatting tools.

---

## 🛠️ Requirements

- **PHP:** `^8.3`
- **Composer:** For dependency management
- **Node.js & NPM:** For building frontend assets

---

## 🏁 Getting Started

Follow these steps to set up the project locally:

### 1. Clone the Repository
```bash
git clone <repository-url>
cd amarkit
```

### 2. Configure Environment Variables
Copy the example environment file and configure your database and third-party integrations:
```bash
cp .env.example .env
```
Open `.env` and configure:
- **Database:** Defaults to SQLite (`DB_CONNECTION=sqlite`).
- **Paystack Credentials:** Set `PAYSTACK_PUBLIC_KEY` and `PAYSTACK_SECRET_KEY` for billing.

### 3. Run the Setup Script
A convenience composer script is available to install dependencies, generate application keys, and run migrations:
```bash
composer run setup
```
> **Note:** Under the hood, this script runs:
> - `composer install`
> - `php artisan key:generate`
> - `php artisan migrate --force`
> - `npm install`
> - `npm run build`

### 4. Seed the Database (Optional)
If you have seeders set up, you can run:
```bash
php artisan db:seed
```

### 5. Run the Development Server
To launch both the Laravel backend and the Vite frontend compiler:
```bash
composer run dev
```
Alternatively, in separate terminal tabs, you can run:
- Backend: `php artisan serve`
- Frontend: `npm run dev`

---

## 📖 Scripts & Commands

Below are the most common commands you will use:

| Command | Description |
| :--- | :--- |
| `composer run dev` | Starts the local dev environment (Vite + Laravel) |
| `composer run test` | Runs the Pest test suite and code quality checks |
| `composer run lint` | Runs Laravel Pint to format backend code |
| `npm run build` | Compiles the production assets |
| `npm run lint` | Runs ESLint and fixes frontend syntax/issues |
| `npm run format` | Runs Prettier to format frontend code |
| `npm run types:check` | Run PHPStan and TypeScript type checks |

---

## 🧪 Testing

The codebase uses **Pest PHP** for feature and unit tests. Run the tests with:
```bash
composer run test
# Or specifically for Pest test runner
php artisan test
```
