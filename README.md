<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# Pageturner Bookstore

Pageturner Bookstore is a Laravel 12 application for browsing books, placing orders, writing reviews, and administering store operations. The system includes customer and admin dashboards, data transfer tools, audit logging, scheduled maintenance, API optimization, and database performance tuning.

## Overview

The application is built around three primary experiences:

- Customer storefront for book browsing, cart checkout, orders, reviews, and account management.
- Admin workspace for catalog management, order handling, transfer jobs, audit logs, and operational monitoring.
- Support utilities for import/export, backups, scheduled maintenance, API responses, and data portability.

## Key Features

- Book, category, order, and review management.
- Customer and admin dashboards.
- Self-service data portability from the customer dashboard.
- Import/export workflows for books, orders, and users.
- Audit logging with integrity checks and export/archive tools.
- Scheduled backup, cleanup, and maintenance tasks.
- Optimized JSON API responses for book data.
- Query performance indexes for the main transactional tables.

## Setup

1. Install dependencies.

```bash
composer install
npm install
```

2. Configure environment variables in `.env`.

3. Run migrations and seeders.

```bash
php artisan migrate --seed
```

4. Start the app.

```bash
php artisan serve
```

## Default Access

- Admin email: `admin@pageturner.com`
- Admin password: `password`

Customers can register from the sign-up page.

## Important Runtime Commands

```bash
php artisan queue:work
php artisan schedule:work
```

These keep queued imports/exports and scheduled maintenance running.

## Main Routes

- `/dashboard` customer dashboard
- `/dashboard/export-data` personal data portability download
- `/admin/dashboard` admin dashboard
- `/books`, `/orders`, `/categories` storefront and account flows
- `/api/books` optimized API endpoint

## Documentation

- `docs/LAB6_REQUIREMENTS_MATRIX.md` for requirement coverage
- `docs/LAB6_RUNBOOK.md` for operational verification

## Notes

- Ensure `storage/` and `bootstrap/cache/` are writable.
- Run the pending migrations before using the integrity/archive and indexing enhancements.
