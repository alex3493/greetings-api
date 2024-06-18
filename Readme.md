# 🐳 Docker + PHP 8.3 + MySQL + Nginx + Symfony 7 Boilerplate

## Description

This is a complete stack for running Symfony 7.0 in Docker containers using docker-compose tool.

It is composed of 6 containers:

- `nginx` - acting as the webserver.
- `php` - the PHP-FPM container with the 8.3 version of PHP.
- `db` - MySQL database container with MySQL 8.0 image.
- `mercure-hub` - Mercure Hub.
- `mailer` - Mailpit testing mail server (currently not used).
- `swagger-ui` - OpenAPI documentation

This project follows Hexagonal architecture principles.

We implement a super-basic CRUD for *Greeting* entity with user authentication and authorization. *Greeting* has the following properties:
- `id` - unique ID
- `text` - greeting text
- `variant` - greeting "mood", *primary*, *secondary*, *success* or *warning*
- `author` - the user who created the greeting
- `updatedBy` - the user who updated the greeting
- `creeatedAt` - creation time
- `updatedAt` - update time or NULL if greeting was never updated

## Project structure

All controller entry points are located in `src/EntryPoint/Http/Controllers` folder.

Modules:
- `Shared` - classes designed for general use.
- `User` - classes related to user and authentication.
- `Greeting` - classes related to greeting management.

This is a pure **API** application. You can browse Open API docs (see below) to explore and test API responses.

We support two authentication methods:
- Mobile application, providing user authentication via **auth tokens** (similar to Laravel Sanctum auth tokens).
  A new token is generated after each successful registration or login. Client app should store this token in secure area and use it for all subsequent requests.
- Single page web application, providing user authentication via **JWT** with **token refresh** support. Client browser should store provided token in local storage (after successful login) and use it for all subsequent requests.

Two route patterns are created for these auth methods:
- `^/api/web` - JWT (browser)
- `^/api/app` - Auth token (mobile app)

**Account actions (auth token):**
- User can register. If registration is successful user is automatically logged in on a device used for registration.
- User can log in on multiple devices. Each registered device provides its own token that can be used to access protected pages.
- User can log out from a given device.
- User can log out from all devices.
- User can change password.
- User can update profile (firstname and lastname).
- User can delete his account.

**Account actions (JWT):**
- User can register.
- User can log in. Each login generates token (JWT) and refresh token. JWT can be used to access protected pages. Refresh token will authorize new JWT generation when the current one expires.
- User can log out.
- User can change password.
- User can update profile (firstname and lastname).
- User can delete his account.

We support two types of users: **admin** user and **regular** user. 

**Greeting actions:**
- User can create a greeting.
- User can edit own greeting.
- User can delete own greeting.
- Admin user can edit any greeting.
- Admin user can delete any greeting.

We leverage Symfony Mercure (SSE) to implement real-time updates for all connected UI clients. Whenever a greeting is created,
updated or deleted we publish a Mercure update, so that all clients see updates without the need of page reload.

SSE updates also provide an easy way to solve conflicts when multiple users are editing the same greeting at the same time.
For example, if a greeting is open in edit form by the first user and another user has updated this greeting in the meanwhile,
we show an alert in edit form and require that the first user accepts recent changes before he can save the greeting.

If a greeting was deleted by another user we also show an alert in edit form and this time the only action available is to close form.

We have also added Pusher support for demo purposes. Admins can send *Admin greetings* that will show up as toast alert
for all connected UI clients. These greetings are not persisted anywhere.

*Note: For Pusher feature you have to configure service using your own Pusher account data, see step 8 in installation instructions*

## Installation

1. Clone this repo.
2. Go inside `./docker` folder and run `docker compose up -d` to start containers.

**Next commands should be executed inside `php` container.**

3. `docker exec -it php bash` or use your favourite Docker desktop application `php` container Exec tab.
4. Install dependencies: `#composer install`
5. Generate SSL keys: `#php bin/console lexik:jwt:generate-keypair`
6. Migrate database: `#php bin/console doctrine:migrations:migrate`
7. Run tests: `#php ./vendor/bin/phpunit`. Current project setup uses in-memory Sqlite database for testing, so migrations are done automatically before each test.
8. Add your Pusher account settings: create `.env.local` file in project root and fill in you data.
```
###> pusher/pusher-php-server ###
PUSHER_APP_ID=app_id
PUSHER_APP_KEY=app_key
PUSHER_APP_SECRET=app_secret
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1
###< pusher/pusher-php-server ###
```
9. Add symfony.test to your operating system *hosts* file.
10. Open https://symfony.test in browser and accept security warning about self-signed certificates.
11. Browse OpenAPI docs: http://localhost:8888

## How to test

We have a console command that allows to set up some users. Remember that console commands should be run inside docker `php` container.
`#php bin/console app:add-user`

You can use Swagger UI at http://localhost:8888 for testing selected API endpoints. Most endpoints require authorization,
so you will have to run registration / login endpoints first and then copy token from response to authorize subsequent requests.

There is also a frontend counterpart [project](https://github.com/alex3493/symfony-ui) that consumes this project's API.
It is preconfigured to work with default docker API installation. This application doesn't support registration, so make sure that you
create some users with `#php bin/console app:add-user` console command in order to be able to log in.

**Important: we are using HTTPS with self-signed SSL certificate for local development!** Even if you are going to test using UI
installation, do not forget step 10 from installation instructions, otherwise all requests to API with result in certificate error.

## What's next

You can change the name, user and password of the database in the `env` file at the root of the project. Make sure that you update `.docker/.env` settings accordingly.

.env:
```
DATABASE_URL=mysql://app_user:secret@db:3306/symfony?serverVersion=8.0.33
```
.docker/.env:
```
MYSQL_ROOT_PASSWORD=root
MYSQL_DATABASE=symfony
MYSQL_USER=app_user
MYSQL_PASSWORD=secret
```

Make sure that you rebuild containers after database setting are changed. In local project folder cd to `.docker`, then:
- `docker compose down --remove-orphans`
- `docker compose build --no-cache` (optional, just to make sure we have fresh images)
- `docker compose up --pull always --wait -d`

## Adding more features

If you need to install more Symfony packages you have to do it inside docker `php` container.

Symfony messenger queue worker is started automatically when docker container is started.

If you need more tasks, e.g. cron jobs, etc., you will have to launch background processes in `php` container.
E.g. [Run multiple processes in a container](https://docs.docker.com/config/containers/multi-service_container/)






