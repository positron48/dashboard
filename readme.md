# dashboard backend

## Install & Setup

edit DATABASE_URL and REDMINE_URL in .env.local

```
composer install
php bin/console doctrine:database:create
doctrine:migrations:migrate
```