# Chronos

SOEN 343 - Fall 2016

Name | Student ID
--- | ---
Angelo Pengue | 26674151
Bobak Ansari | 27484062
Christopher Paslawski | 27445164
Jhayzle Arevalo | 27344333
Richard Puth | 27407726
Rikil Desai | 27534841
Steve Ferreira | 27477546

## Installing

### System Requirements

To install Chronos, you will need an Internet-connected server running a PHP-capable web server and database software. The recommended tested requirements are listed below.

 -	Apache 2.4 or nginx 1.6
    - Configured to serve files from a `public` directory under the installation directory
 -	PHP 7.0
    - Required extensions: OpenSSL, PDO, Mbstring, Tokenizer
 -	MySQL 5.6

For this installation manual, an Ubuntu Linux 15.10 installation will be assumed. We will also assume the following paths:

 -	Installation path is /var/www/chronos
 -	Document root of web server /var/www/chronos/public
 -	URL of website is http://chronos.chrs.pw
 -	Web server user is www-data
 -	MySQL database address is localhost

For local development, any easy-install _AMP_ server such as _MAMP_, _WampServer_, _XAMPP_ (or others) may be used instead of a manual server setup. 

1. Please run the following commands to check your environment:

  ```
  dev:~$ php --version
  PHP 7.0.5-2+deb.sury.org~wily+1 (cli) ( NTS )
  Copyright (c) 1997-2016 The PHP Group
  Zend Engine v3.0.0, Copyright (c) 1998-2016 Zend Technologies
      with Zend OPcache v7.0.6-dev, Copyright (c) 1999-2016, by Zend Technologies

  dev:~$ mysql -uroot -p -e "SELECT VERSION();"
  Enter password:
  +-------------------------+
  | VERSION()               |
  +-------------------------+
  | 5.6.28-0ubuntu0.15.10.1 |
  +-------------------------+
  ```

2. Ensure that your web server and PHP are enabled on your server by creating a sample `index.php` file in the document root and load the website in your web browser.

  ```
  dev:~$ cd /var/www/chronos/public
  dev:/var/www/chronos/public$ echo "<?php phpinfo(); ?>" > index.php
  ```

### Downloading Chronos

Obtain a Chronos release by downloading it from the official repository, and extract it into your installation directory.

1. Change your directory to the installation path:

  ```
  dev:~$ cd /var/www/chronos
  ```

2. Download the Chronos release:

  ```
  dev:/var/www/chronos$ wget \
    https://github.com/Shmeve/soen343-emu/releases/download/v0.0.1/chronos.tar.gz
  ```

3. Extract the Chronos system files to the installation path:

  ```
  dev:/var/www/chronos$ tar --strip-components=1 -zxvf chronos.tar.gz
  ```

### Setting up the Database

After extracting the Chronos system files, it is necessary to create a database and a user for Chronos.

1. Log into your MySQL database as an administrative user, and issue the following commands:

  ```
  dev:~$ mysql -uroot -p
  Enter password:
  Welcome to the MySQL monitor.  Commands end with ; or \g.
  Your MySQL connection id is 3
  Server version: 5.6.28-0ubuntu0.15.10.1 (Ubuntu)

  Copyright (c) 2000, 2015, Oracle and/or its affiliates. All rights reserved.

  Oracle is a registered trademark of Oracle Corporation and/or its
  affiliates. Other names may be trademarks of their respective
  owners.

  Type 'help;' or '\h' for help. Type '\c' to clear the current input statement.

  mysql> CREATE DATABASE chronos;
  Query OK, 1 row affected (0.00 sec)

  mysql> CREATE USER 'chronos'@'localhost' IDENTIFIED BY 'password';
  Query OK, 0 rows affected (0.00 sec)

  mysql> GRANT ALL PRIVILEGES ON chronos.* TO 'chronos'@'localhost';
  Query OK, 0 rows affected (0.00 sec)

  mysql> FLUSH PRIVILEGES;
  Query OK, 0 rows affected (0.00 sec)
  ```

  This has created a database `chronos` and a user `chronos` with password `password` with privileges on that database, ready for use with Chronos.

2. Now, you must configure Chronos with this database information. First, copy the example ".env.example" configuration file:

  ```
  dev:/var/www/chronos$ cp .env.example .env
  ```

3. Edit the `.env` file and change the DB variables to match the appropriate values:

  ```
  DB_HOST=localhost
  DB_DATABASE=chronos
  DB_USERNAME=chronos
  DB_PASSWORD=chronos
  ```

### Install Chronos

Most of the Chronos installation is done via Composer, PHP’s package manager. An install script, `install.sh` is included to accelerate the process, which will create all necessary database tables and install all dependencies.

1. Execute `install.sh`:

  ```
  dev@:/var/www/chronos$ ./install.sh
  All settings correct for using Composer
  Downloading 1.2.0...
  
  Composer successfully installed to: /var/www/chronos/composer.phar
  Use it: php composer.phar 
  Application is now down.
  Loading composer repositories with package information
  Installing dependencies from lock file
    - Installing symfony/finder (v3.1.7)
    Loading from cache
  
  [...]
  
  Generating autoload files
  > php artisan clear-compiled
  > php artisan optimize
  Generating optimized class loader
  Compiling common classes
  Migration table created successfully.
  Migrated: 2016_11_16_000000_create_users_table
  Migrated: 2016_11_17_045934_create_rooms_table
  Migrated: 2016_11_17_051557_create_reservations_table
  Generating optimized class loader
  Compiling common classes
  Application cache cleared!
  Application is now live.
  ```

2. [Optional] Seed the database with some default data

  Seeding the database will populate it with some default test users and rooms. The test users will have IDs 10000001 through 10000009, with passwords set to "password". Rooms will be H-901 through H-909.
  
  ```
   dev@:/var/www/chronos$ php artisan db:seed
   Seeded: UsersTableSeeder
   Seeded: RoomsTableSeeder
  ```