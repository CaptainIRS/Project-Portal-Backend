# Project Portal Backend

## Features
- Add project and specify its type, stack, deadline and repository link
- Add developers and maintainers to a project
- Filter based on project type, stack and members
- Give general comments on a project as a review
- Dashboard showing user projects and open projects

## Setup

### Prerequisites

1. Install PHP (Preferably >= 7.0). Install [LAMP](https://www.digitalocean.com/community/tutorials/how-to-install-linux-apache-mysql-php-lamp-stack-ubuntu-18-04) and [phpmyadmin](https://www.digitalocean.com/community/tutorials/how-to-install-and-secure-phpmyadmin-on-ubuntu-18-04).
2. Install [Composer](https://getcomposer.org/download/)
3. Install PHP Extensions. 

### Project Installation

1. Clone the repo
2. Install dependencies - `composer install`
3. Copy contents of `.env.example` to a new file `.env` : ` cp .env.example .env`. 
4. Set DB_USERNAME and DB_PASSWORD to your localhost mysql credentials
5. Create API Key - `php artisan key:generate`
6. Create a DB `project_portal`.
7. Run Migrations - `php artisan migrate`
8. Run - `php artisan passport:install`
9. Start the Server - `php artisan serve`