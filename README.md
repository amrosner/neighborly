# Neighborly
A volunteer matching platform for PR residents

## Overiew

Neighborly is a platform designed to connect volunteers with community organizations across Puerto Rico. Volunteers can browse events, manage skills and profiles, sign up or cancel their participation, and communicate through comments. Organizers can create, edit, and manage volunteer events, view participating volunteers, and respond to questions or comments. Administrators oversee the platform by reviewing and approving events, moderating comments, managing users, and ensuring the platform’s safety.

## Documentation

Detailed documentation for users, programmers and system administrators is available in the folder `docs/`

## Installation

To set up Neighborly in a machine it requires [PHP 8](https://www.php.net/) with mysqli (usually installed by default), and [MySQL 8](https://www.mysql.com/). To beter work with MySQL you can also install [MySQL Workbench](https://www.mysql.com/products/workbench/) and [MySQL Community Server](https://dev.mysql.com/downloads/mysql/).

Neighborly requires a database to function. By default Neighborly expects a database called `neighborly`, and a MySQL user, with full permissions over the database, called `root@localhost`. To change the database, the MySQL user, or the user's password than Neighborly will use, change the information within the file `config/database.php`, specifically the variables `$dbname`, `$username` or `$password`.

The project provides an SQL file with the database schema and dummy values. This file is located in `database/neighborly_db.sql`. Make sure the MySQL server is running, then execute the file to populate the database.

Once done Neighborly is ready use. <!-- To run it locally you can execute the command `php -S localhost:8000` in a terminal, inside the folder `/neighborly`. -->

More detailed instructions are available in `/docs`
