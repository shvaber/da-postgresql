# SECURITY NOTES

When importing backups as user, whenever someone tries to change a password or gain higher privileges will get the following errors (in logs):

- Change superuser password: ALTER ROLE diradmin WITH LOGIN PASSWORD 'md5dc42ad975d9d738e1bcb99c7d43047af';

```
psql:/home/tmp/pgsql_restore/5e258345a0748.hack.sql:1: ERROR:  must be superuser to alter superusers
```

- Change their own user's password: ALTER ROLE poralix WITH LOGIN PASSWORD 'md5dc42ad975d9d738e1bcb99c7d43047af';

```
psql:/home/tmp/pgsql_restore/5e258345a0748.hack.sql:2: ERROR:  permission denied
```

- Change other user's password: ALTER ROLE admin WITH LOGIN PASSWORD 'md5dc42ad975d9d738e1bcb99c7d43047af';

```
psql:/home/tmp/pgsql_restore/5e258345a0748.hack.sql:3: ERROR:  permission denied
```

- Gain higher privileges: ALTER ROLE poralix WITH SUPERUSER LOGIN PASSWORD 'md5dc42ad975d9d738e1bcb99c7d43047af';

```
psql:/home/tmp/pgsql_restore/5e258345a0748.hack.sql:4: ERROR:  must be superuser to alter superusers
```

last updated: Mon Jan 20 17:43:40 +07 2020
