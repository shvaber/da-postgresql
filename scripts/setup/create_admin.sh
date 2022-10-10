#!/bin/bash
######################################################################################
#
#   Postgresql integration for DirectAdmin $ 0.2
#   ==============================================================================
#          Last modified: Mon Feb 10 12:44:48 +07 2020
#   ==============================================================================
#         Written by Alex Grebenschikov, Poralix, www.poralix.com
#         Copyright 2022 by Alex Grebenschikov, Poralix, www.poralix.com
#   ==============================================================================
#         Distributed under Apache License Version 2.0, January 2004
#                                          http://www.apache.org/licenses/
#
######################################################################################

for ARG in "$@"
do
    case "${ARG}" in
        --strict)
            STRICT=1;
        ;;
        --debug)
            DEBUG=1;
        ;;
    esac;
done;

######################################################################################

genpasswd()
{
    local l=${1:-20};
    tr -dc A-Za-z0-9_ < /dev/urandom | head -c ${l} | xargs;
}

write_conf()
{
    echo "${PSQL_HOST}:${PSQL_PORT}:${PSQL_DB}:${PSQL_ADMIN}:${PSQL_PASS}" > "${1}";
    chown diradmin:diradmin "${1}";
    chmod 600 "${1}";
}

######################################################################################

[ ! -f "/usr/local/directadmin/plugins/postgresql/exec/functions.inc.sh" ] && echo "[ERROR] Corrupted installation" && exit 1;
source /usr/local/directadmin/plugins/postgresql/exec/functions.inc.sh;

######################################################################################

PSQL_HOST="localhost";
PSQL_PORT="5432";
PSQL_DB="*";
PSQL_ADMIN="diradmin";
PSQL_ADMIN_DB="diradmin";
PSQL_PASS=$(genpasswd);
PSQL_CONF="/usr/local/directadmin/plugins/postgresql/pgpass.conf";
PSQL_USER_CONF="/usr/local/directadmin/.pgpass";

PSQL_BIN="/usr/local/bin/psql";
[ -x "${PSQL_BIN}" ] || PSQL_BIN="/usr/bin/psql";
[ -x "${PSQL_BIN}" ] || PSQL_BIN="/bin/psql";
[ -x "${PSQL_BIN}" ] || die "PostgreSQL is not installed: ${PSQL_BIN}!" 20;
PSQL_BIN="sudo -u postgres ${PSQL_BIN}";

PHP_TEST_SRC_FILE="/usr/local/directadmin/plugins/postgresql/scripts/setup/test_php.php.src";
PHP_TEST_TRG_FILE="/usr/local/directadmin/plugins/postgresql/exec/test_php.php";

######################################################################################

UPDATE_PASSWORD=0;

# FIRST CHECK
c=$(${PSQL_BIN} -tAc "SELECT 1 FROM pg_roles WHERE rolname='${PSQL_ADMIN}'" 2>&1 | grep -c 1);
if [ "0" == "${c}" ];
then
    ${PSQL_BIN} -c "CREATE ROLE ${PSQL_ADMIN} WITH SUPERUSER CREATEDB CREATEROLE LOGIN ENCRYPTED PASSWORD '${PSQL_PASS}';" | de;
    ${PSQL_BIN} -c "CREATE DATABASE ${PSQL_ADMIN_DB};" | de;

    # SECOND CHECK AFTER CREATION
    c=$(${PSQL_BIN} -tAc "SELECT 1 FROM pg_roles WHERE rolname='${PSQL_ADMIN}'" 2>&1 | grep -c 1);
    if [ "0" == "${c}" ];
    then
        echo "[ERROR] FAILED TO CREATE USER ROLE ${PSQL_ADMIN}!";
        exit 1;
    else
        echo "[OK] USER ROLE ${PSQL_ADMIN} CREATED FINE!";
        UPDATE_PASSWORD=1;
    fi;
else
    echo "[WARNING] USER ROLE ${PSQL_ADMIN} ALREADY EXISTS!";
    echo "[WARNING] Changing password for ${PSQL_ADMIN} to '${PSQL_PASS}'!";
    ${PSQL_BIN} -c "ALTER ROLE ${PSQL_ADMIN} WITH PASSWORD '${PSQL_PASS}';";
    [ "0" == "$?" ] && UPDATE_PASSWORD=1;
    ${PSQL_BIN} -tc "SELECT 1 FROM pg_database WHERE datname = '${PSQL_ADMIN_DB}'" | grep -q 1 || ${PSQL_BIN} -c "CREATE DATABASE ${PSQL_ADMIN_DB};" | de;
fi;

if [ "1" == "${UPDATE_PASSWORD}" ];
then
    echo "[OK] Writing configs for PostgreSQL superuser access.";
    write_conf "${PSQL_CONF}";
    write_conf "${PSQL_USER_CONF}";

    cat "${PHP_TEST_SRC_FILE}" > "${PHP_TEST_TRG_FILE}";
    perl -pi -e "s#\|DBNAME\|#${PSQL_ADMIN}#" "${PHP_TEST_TRG_FILE}";
    perl -pi -e "s#\|USERNAME\|#${PSQL_ADMIN}#" "${PHP_TEST_TRG_FILE}";
    perl -pi -e "s#\|PASSWORD\|#${PSQL_PASS}#" "${PHP_TEST_TRG_FILE}";
fi;

echo "[OK] Setting a password based authentication in PostgreSQL";

PG_HBA_PEER="/usr/local/directadmin/plugins/postgresql/scripts/setup/pg_hba.conf.peer";

if [ "1" == "${STRICT}" ]; then
    PG_HBA_PASSWORD="/usr/local/directadmin/plugins/postgresql/scripts/setup/pg_hba.conf.password_strict";
else
    PG_HBA_PASSWORD="/usr/local/directadmin/plugins/postgresql/scripts/setup/pg_hba.conf.password";
fi;

for PG_HBA in $(ls -1 /var/lib/pgsql/*/data/pg_hba.conf /var/lib/pgsql/data/pg_hba.conf 2>/dev/null);
do
    if [ -f "${PG_HBA}" ];
    then
        cp -p "${PG_HBA}" "${PG_HBA}.bak$(date +%s)";
        cat "${PG_HBA_PASSWORD}" > "${PG_HBA}";
        echo "[OK] Updating PostgreSQL settings in ${PG_HBA}";
    fi;
done;

echo "[OK] Restarting PostgreSQL server";

if [ -x "/usr/bin/systemctl" ];
then
    /usr/bin/systemctl | grep postgresql*\.service | awk '{system("/usr/bin/systemctl restart " $1)}';
else
    /usr/sbin/service postgresql restart;
fi;

# TEST CONNECTION
chmod 600 ${PHP_TEST_SRC_FILE};
chmod 700 ${PHP_TEST_TRG_FILE};
php -f${PHP_TEST_TRG_FILE};

exit 0;
