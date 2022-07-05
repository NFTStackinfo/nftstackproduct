# NFTStack Product

## Official Lumen Documentation

Documentation for the framework can be found on the [Lumen website](https://lumen.laravel.com/docs).

Middleware [documentation](https://lumen.laravel.com/docs/9.x/middleware)

Routing [documentation](https://lumen.laravel.com/docs/9.x/routing)

Controllers [documentation](https://lumen.laravel.com/docs/9.x/controllers)

## Database Migrations

### Create tables base on migrations file
````
php artisan migrate
````

### Create Migration file
````
php artisan make:migration create_{table_name}_table
````

## Run server on port
````
php -S localhost:8000 public/index.php
````

## Swagger Documentation

to publish configs (config/swagger-lume.php)
````
php artisan swagger-lume:publish-config 
````

Make configuration changes if needed, to publish everything
````
php artisan swagger:generate
````
````
php artisan swagger-lume:publish
````
