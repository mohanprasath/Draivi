# Draivi Backend Test

## Requirements:
- PHP 7.x or higher
- MySQL
- Composer (for PHP dependencies like PHPExcel)
- JavaScript (with jQuery)

## Setup:
1. Clone this repository.
2. Run `composer install` to install PHP dependencies.
3. Create a MySQL database `draivi` and import the `01_create_table.sql` file. Edit the `Database.php` with proper credentials.
4. Run the `fetch_data.php` script to download the Alko price list and update the database.
5. Set up a cron job to run the `fetch_data.php` script every day. You can edit the existing command in `crontab.txt`.
6. Set up a local server - run the command `php -S localhost:8000 -t public` to start a PHP server.
7. Open the `public/index.html` file in your browser. You can now view and edit the data. You can clear one product at a time or all products.

## Note:
- The `fetch_data.php` script will download the Alko price list and upsert the database. It will also upsert existing product data. By default the price of items without a default price is set to 0.

## Part 1:
1. Run the `fetch_data.php` script to download the Alko price list and update the database.

## Part 2:
1. Open the `public/index.html` file in your browser, and click "List" to load data.
2. Click "Add" to increase the order amount, and "Clear" to reset it.
