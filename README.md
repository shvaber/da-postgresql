# PostgreSQL module

1. requirements:

- PHP functions: `exec`
- PHP extension `pgsql`
- `unzip` installed


# phpPgAdmin

1. requirements:

- PHP functions: `exec`, `passthru`
- PHP extension `pgsql`


# Scripts:

- **exec/dbbackupuser.sh** - a script to backup PostgreSQL DBs and users, used by DirectAdmin
- **exec/dbdump.sh** - a script to dump a single PostgreSQL DB
- **exec/dbrestore.sh** - a script to restore a single PostgreSQL DB
- **exec/dbsize.sh** - a script to get size of all DBs created by user in DirectAdmin
- **exec/dbusage.sh** - a script to get count of all DBs created by user in DirectAdmin

# It is REQUIRE 
- to re-save User Package or the user's account and set up how much databases it can handle. Including the user "admin"
- plugin will work with **phpPgAdmin-7.13.0**
- needs to change /var/lib/pgsql/14/data/postgresql.conf:
   from:
        password_encryption = scram-sha-256
   to:
        password_encryption = md5
        
- needs to install pgsql module for PHP to run phpPgAdmin and plugin using standart DA way. Like this one: https://www.interserver.net/tips/kb/custom-php-modules-directadmin/
