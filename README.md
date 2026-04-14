<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# Pageturner Bookstore

Pageturner Bookstore is a Laravel-based web application designed to manage an online bookstore. It includes features for managing books, categories, orders, and user reviews. The application uses a modern tech stack, including Laravel for the backend and Tailwind CSS for styling. It supports user authentication and provides an admin panel for managing the store's content. The project is structured to be easily set up and extended, with clear instructions for installation and configuration.

## Setup Instructions

1. **Extract the ZIP File**:
   Extract the contents of `Montecillo_JopurJay_Lab3_Bookstore.zip` to your desired directory.

2. **Install Dependencies**:
   ```bash
   composer install
   npm install
   ```

3. **Set Up Environment**:
   Update the `.env` file with your database credentials.

4. **Run Migrations and Seeders**:
   ```bash
   php artisan migrate --seed
   ```

5. **Start the Development Server**:
   ```bash
   php artisan serve
   ```

## Login Credentials for Test Accounts

- **Admin Account**:
  - Email: `admin@pageturner.com`
  - Password: `password`

- **User Account**:
  - `you can proceed by register to create a user account`

## Additional Notes

- Ensure writable permissions for `storage/` and `bootstrap/cache/` directories.
