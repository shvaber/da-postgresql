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

PSQL_ADMIN="diradmin";
PSQL_CONF="/usr/local/directadmin/plugins/postgresql/pgpass.conf";
PSQL_USER_CONF="/usr/local/directadmin/.pgpass";
PSQL_BIN="/usr/local/bin/psql";
[ -x "${PSQL_BIN}" ] || PSQL_BIN="/usr/bin/psql";
[ -x "${PSQL_BIN}" ] || PSQL_BIN="/bin/psql";
[ -x "${PSQL_BIN}" ] || die "PostgreSQL is not installed: ${PSQL_BIN}!" 20;
PSQL_BIN="sudo -u postgres ${PSQL_BIN}";

######################################################################################

[ ! -f "/usr/local/directadmin/plugins/postgresql/exec/functions.inc.sh" ] && echo "[ERROR] Corrupted installation" && exit 1;
source /usr/local/directadmin/plugins/postgresql/exec/functions.inc.sh;

######################################################################################

revoke_privileges()
{
    dbname="${1}";
    dbuser="${2}";
    ${PSQL_BIN} ${dbname} -c "REVOKE ALL ON ALL TABLES IN SCHEMA pg_catalog FROM ${dbuser};" 2>&1;
    ${PSQL_BIN} ${dbname} -c "REVOKE ALL PRIVILEGES ON DATABASE ${dbname} FROM ${dbuser};" 2>&1;
    ${PSQL_BIN} ${dbname} -c "REVOKE ALL ON SCHEMA public FROM ${dbuser};" 2>&1;
    ${PSQL_BIN} ${dbname} -c "REVOKE ALL PRIVILEGES ON ALL TABLES IN SCHEMA public FROM ${dbuser};" 2>&1;
    ${PSQL_BIN} ${dbname} -c "REVOKE ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public FROM ${dbuser};" 2>&1;
}

######################################################################################

echo "[OK] Disabling a password based authentication in PostgreSQL";

PG_HBA_PEER="/usr/local/directadmin/plugins/postgresql/scripts/setup/pg_hba.conf.peer";
PG_HBA_PASSWORD="/usr/local/directadmin/plugins/postgresql/scripts/setup/pg_hba.conf.password";

for PG_HBA in $(ls -1 /var/lib/pgsql/*/data/pg_hba.conf /var/lib/pgsql/data/pg_hba.conf 2>/dev/null);
do
    if [ -f "${PG_HBA}" ];
    then
        cp -p "${PG_HBA}" "${PG_HBA}.bak$(date +%s)";
        cat "${PG_HBA_PEER}" > ${PG_HBA};
    fi;
done;

if [ -x "/usr/bin/systemctl" ];
then
    /usr/bin/systemctl | grep postgresql*\.service | awk '{system("/usr/bin/systemctl restart " $1)}';
else
    /usr/sbin/service postgresql restart;
fi;

c=$(${PSQL_BIN} -tAc "SELECT 1 FROM pg_roles WHERE rolname='${PSQL_ADMIN}'" 2>&1 | grep -c 1);
if [ "1" == "${c}" ];
then
    revoke_privileges "${PSQL_ADMIN}" "${PSQL_ADMIN}" | de;
    revoke_privileges "template1" "${PSQL_ADMIN}" | de;
    ${PSQL_BIN} -c "DROP DATABASE IF EXISTS ${PSQL_ADMIN};" | de;
    ${PSQL_BIN} -c "DROP ROLE IF EXISTS ${PSQL_ADMIN};";
    RETVAL=$?;
    if [ "0" == "${RETVAL}" ];
    then
        test -f "${PSQL_CONF}" && rm -f "${PSQL_CONF}";
        test -f "${PSQL_USER_CONF}" && rm -f "${PSQL_USER_CONF}";
    fi;
else
    echo "[WARNING] ROLE ${PSQL_ADMIN} DOES NOT EXIST";
fi;
