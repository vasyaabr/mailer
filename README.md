# Test mailer project

This is example async mailer. 
Projected is implemented as 2 separate tasks, running in parallel.
First task constantly checking new user emails, second task sending emails.
If nothing left, tasks stop but restart every minute using cron.

PHP 8.2+ required.

Set enough MySQL max_connections = MAX_PARALLEL_PROCESSES_COUNT/50 (200 connections for current config).

### Usage

Add correct database credentials to **config.php**, run 2 processes using console commands:
```
php mailer.php validate
php mailer.php send
```

### Development

Project built with the docker support, for the ease of debugging.

To start mysql server, execute:
`docker compose up`

To drop images (for re-seed, for example):
`docker-compose down --rmi all`

Fill database with test data:
`php mailer.php seed`

Config values are stored in **config.php**.

### Production

Add cron tasks from crontab file on selected server / container.
