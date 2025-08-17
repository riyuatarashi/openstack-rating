# OpenStack Rating

[![linter](https://github.com/riyuatarashi/openstack-rating/actions/workflows/lint.yml/badge.svg)](https://github.com/riyuatarashi/openstack-rating/actions/workflows/lint.yml)
[![tests](https://github.com/riyuatarashi/openstack-rating/actions/workflows/tests.yml/badge.svg)](https://github.com/riyuatarashi/openstack-rating/actions/workflows/tests.yml)


OpenStack Rating is a Laravel-based application designed to gather and process ratings for OpenStack clouds.
It provides some charts and statistics to help users evaluate the cost of their hosting.

## Features
- Gather ratings for OpenStack clouds.
- Display charts and statistics for cost evaluation.

## Requirements
- PHP 8.4 or higher
- Node 18.x or higher
- Laravel 12.x
- [FluxUI pro](https://fluxui.dev/pricing) licence

## Installation

1. **Clone the repository:**
   ```bash
   git clone <repository-url>
   cd openstack-rating
   ```

2. **Install PHP dependencies:**
   ```bash
   composer config http-basic.composer.fluxui.dev ${FLUX_USERNAME} ${FLUX_LICENSE_KEY}
   composer install
   ```

3. **Install JavaScript dependencies:**
   ```bash
   npm install
   ```

4. **Set up the environment:**
   Copy the `.env.example` file to `.env` and configure the necessary environment variables:
   ```bash
   cp .env.example .env
   ```

5. **Generate the application key:**
   ```bash
   php artisan key:generate
   ```

6. **Run database migrations:**
   ```bash
   php artisan migrate
   ```

## Hosting

### Local Development
If you have parallel installed, you just need to run the following command to start the local development server:
```bash
composer run serve
```

This will execute this command concurrently:
```bash
## Start the octane server with FrankenPHP
php artisan octane:start --server frankenphp --watch

## Start the queue listener
php artisan queue:listen --tries=1

## Start the Pail worker
php artisan pail --timeout=0

## Start the Vite development server
npm run dev
```

### Production
I highly recommend using [Laravel cloud](https://laravel.com/docs/12.x/deployment#deploying-with-cloud-or-forge) for production deployment.
This is the solution that I use, and I do not really have tried other solutions.

## Code type and cleaner helper
For simplifying the code type and cleaner helper, this project uses [Rector](https://getrector.com).
To run Laravel rector, execute:
```bash
./vendor/bin/rector
```

## Code Style
This project uses the [Laravel pint](https://laravel.com/docs/12.x/pint) code style.
To format the code, run:
```bash
composer pint
```

## Code Type analysis
[Larastan](https://github.com/larastan/larastan) is used for static analysis.
To run Larastan, execute:
```bash
composer larastan
```

## Testing

Run the test suite using [Pest](https://pestphp.com):
```bash
./vendor/bin/pest
```

## License
This project is licensed under the MIT License.
