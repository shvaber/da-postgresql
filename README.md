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
