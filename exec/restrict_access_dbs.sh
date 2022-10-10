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

DEBUG="${DEBUG:-0}";

# ======================================================================================================== #

[ ! -f "/usr/local/directadmin/plugins/postgresql/exec/functions.inc.sh" ] && echo "[ERROR] Corrupted installation" && exit 1;
source /usr/local/directadmin/plugins/postgresql/exec/functions.inc.sh;

# ======================================================================================================== #

usage()
{
    echo "
=======================================================================
 (BETA) A script to restrict access to PostgreSQL databases
=======================================================================
 ! NOT MUCH TESTED ! DO NOT RUN IT ! UNLESS YOU ARE SURE WHAT YOU DO !
=======================================================================

Usage:

$0 [--run|--help] [--debug] [--user=<user>]

    --help  - print this help article
    --run   - run the script
    --debug - print debug information
    --user  - username
";
    exit 0;
}

# We modify template1 to revoke all rights from "PUBLIC" to the public schema,
# to prevent access to the public schema of indiviudial customer databases
# by other customers. Also we add support for PL/PGSQL.
revoke_default()
{
    ${PSQL_BIN} template1 -f - << EOT
REVOKE ALL ON DATABASE template1 FROM public;
REVOKE ALL ON SCHEMA public FROM public;
GRANT ALL ON SCHEMA public TO postgres;
GRANT ALL ON SCHEMA public TO diradmin;
CREATE LANGUAGE plpgsql;
EOT
}

# ======================================================================================================== #

DBCONF="/usr/local/directadmin/plugins/postgresql/pgpass.conf";

# ======================================================================================================== #

[ -f "${DBCONF}" ] || die "Could not find user conf" 1;

for ARG in "$@"
do
{
    case ${ARG} in
        --run)
            RUN=1;
        ;;
        --debug)
            DEBUG=1;
        ;;
        --help)
            usage;
        ;;
        --user=*)
            selected_user=$(echo "${1}" | cut -d= -f2);
        ;;
    esac;
}
done;

selected_user="${selected_user:-PUBLIC}";

dbhost=$(awk -F: '{print $1}' "${DBCONF}");
dbport=$(awk -F: '{print $2}' "${DBCONF}");
dbuser=$(awk -F: '{print $4}' "${DBCONF}");
dbpass=$(awk -F: '{print $5}' "${DBCONF}");

[ -n "${dbhost}" ] || die "Could not find dbhost in user conf ${DBCONF}" 3;
[ -n "${dbport}" ] || die "Could not find dbport in user conf ${DBCONF}" 4;
[ -n "${dbuser}" ] || die "Could not find dbuser in user conf ${DBCONF}" 5;
[ -n "${dbpass}" ] || die "Could not find dbpass in user conf ${DBCONF}" 6;

PSQL_BIN="/usr/local/bin/psql";
[ -x "${PSQL_BIN}" ] || PSQL_BIN="/usr/bin/psql";
[ -x "${PSQL_BIN}" ] || PSQL_BIN="/bin/psql";
[ -x "${PSQL_BIN}" ] || die "PostgreSQL is not installed: ${PSQL_BIN}!" 20;

# ======================================================================================================== #

if [ "1" == "${RUN}" ];
then
{
    export PGPASSWORD="${dbpass}";
    export PGUSER="${dbuser}";
    export PGHOST="${dbhost}";
    export PGPORT="${dbport}";

    revoke_default | de;

    DATABASES=$(${PSQL_BIN} -tAc "SELECT datname FROM pg_catalog.pg_database ORDER BY datname ASC;" 2>/dev/null | xargs);

    unset PGPASSWORD;
    unset PGUSER;
    unset PGHOST;
    unset PGPORT;
    unset PGDATABASE;

    for dbname in ${DATABASES};
    do
    {
        echo "[OK] Restricting access to DB ${dbname} for ${selected_user}";
        export PGPASSWORD="${dbpass}";
        export PGUSER="${dbuser}";
        export PGHOST="${dbhost}";
        export PGPORT="${dbport}";
        export PGDATABASE="${dbname}";
        ${PSQL_BIN} -tc "REVOKE ALL ON ALL TABLES IN SCHEMA pg_catalog FROM ${selected_user};" 2>&1 | de;
        ${PSQL_BIN} -tc "REVOKE ALL ON DATABASE ${dbname} FROM ${selected_user};" 2>&1 | de;
        ${PSQL_BIN} -tc "REVOKE ALL ON ALL TABLES IN SCHEMA public FROM ${selected_user};" 2>&1 | de;
        ${PSQL_BIN} -tc "REVOKE ALL ON ALL SEQUENCES IN SCHEMA public FROM ${selected_user};" 2>&1 | de;
        unset PGPASSWORD;
        unset PGUSER;
        unset PGHOST;
        unset PGPORT;
        unset PGDATABASE;
    }
    done;
}
else
{
    usage;
}
fi;

exit 0;
