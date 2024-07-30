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

We implement a super-basic CRUD for *Greeting* entity with user authentication and authorization. *Greeting* has the
following properties:

- `id` - unique ID
- `text` - greeting text
- `variant` - greeting "mood", *primary*, *secondary*, *success* or *warning*
- `author` - the user who created the greeting
- `updatedBy` - the user who updated the greeting (or NULL if greeting was never updated)
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
  A new token is generated after each successful registration or login. Client app should store this token in secure
  area and use it for all subsequent requests.
- Single page web application, providing user authentication via **JWT** with **token refresh** support. Client browser
  should store provided tokens in local storage and use them for all subsequent requests.

Depending on the authentication mode all subsequent requests should use one of these route patterns:

- `^/api/app/` - Auth token (mobile app)
- `^/api/web/` - JWT (browser)

**Account actions (auth token):**

- User can register. If registration is successful user is automatically logged in on a device used for registration.
- User can log in on multiple devices. Each registered device provides its own token that can be used to access
  protected pages.
- User can log out from a given device.
- User can log out from all devices.
- User can change password.
- User can update profile (firstname and lastname).
- User can delete his account.

**Account actions (JWT):**

- User can register.
- User can log in. Each login generates token (JWT) and refresh token. JWT can be used to access protected pages.
  Refresh token will authorize new JWT generation when the current one expires.
- User can log out.
- User can change password.
- User can update profile (firstname and lastname).
- User can delete his account.

We support two types of users: **admin** user and **regular** user.

**Greeting actions:**

- User can list greetings. For demo purposes we keep it as simple as possible:
    - Greetings are always ordered by creation date descending.
    - We only support `limit`, `offset` and `beforeId` query parameters, the latter is prepared for infinite scroll (
      e.g. mobile app list). If both `offset` and `beforeId` are present, `beforeId` always takes precedence
      and `offset` is ignored.
- User can create a greeting.
- User can edit own greeting.
- User can delete own greeting.
- Admin user can edit any greeting.
- Admin user can delete any greeting.

We leverage Symfony Mercure (SSE) to implement real-time updates for all connected UI clients. Whenever a greeting is
created,
updated or deleted we publish a Mercure update, so that all clients see updates without the need of page reload.

SSE updates also provide an easy way to solve conflicts when multiple users are editing the same greeting at the same
time.
For example, if a greeting is open in edit form by the first user and another user has updated this greeting in the
meanwhile,
we show an alert in edit form and require that the first user accepts recent changes before he can save the greeting.

If a greeting was deleted by another user we also show an alert in edit form and this time the only action available is
to close form.

We have also added Pusher support for demo purposes. Admins can send *Admin greetings* that will show up as toast alert
for all connected UI clients. These greetings are not persisted anywhere.

*Note: For Pusher feature you have to configure service using your own Pusher account data, see step 3 in installation
instructions*

## Installation

1. Clone this repo.
2. Go inside `./docker` folder and run `docker compose up -d` to start containers.
3. Add your Pusher account settings: create `.env.local` file in project root and fill in you data.

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

4. Add symfony.test to your operating system *hosts* file.
5. Open https://symfony.test in browser and accept security warning (self-signed certificates).
6. Browse OpenAPI docs: http://localhost:8888

*Note: `docker compose up -d` command executes entry point script (composer install, migrations if need be, etc.) that
may take some time on slow systems. If you get 502 bad gateway response when you open https://symfony.test shortly after
starting docker containers, please, wait for a while or check `php` container logs to make sure that all start up tasks
are finished.*

## How to test

**Console commands should be executed inside `php` container.**

Run `docker exec -it php bash` or use your favourite Docker desktop application `php` container Exec tab.

Run tests: `#php ./vendor/bin/phpunit`.

A default Admin user is created when you run docker containers for the first time.
You can use the following credentials right away:

- email: admin@greetings.com
- password: password

You can create more users running console command: `#php bin/console app:add-user`. Remember that console
commands should be executed inside docker `php` container.

You can use Swagger UI at http://localhost:8888 for testing selected API endpoints. Most endpoints require
authorization, so you will have to run registration / login first and then copy token from response to authorize
subsequent requests.

You can also use one of frontend counterpart projects that consume this API:

- [Vue](https://github.com/alex3493/greetings-ui)
- [React](https://github.com/alex3493/greetings-react-ui)

These projects are preconfigured to work with default docker API installation.

You can log in as admin using default credentials (admin@greetings.com / password) and/or use UI registration form to
create new users.

Keep in mind that all users registered in UI are **regular** users (role USER). Some features testing require **admin**
role users, if you need more admin users you can create them with `#php bin/console app:add-user` console command.

**Important: we are using HTTPS with self-signed SSL certificate for local development!** Even if you are going to test
using UI installation, do not forget step 5 from installation instructions, otherwise all requests to API with result in
certificate error.

## What's next

You can change the name, user and password of the database in the `env` file at the root of the project. Make sure that
you update `.docker/.env` settings accordingly.

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






