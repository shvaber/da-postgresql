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

dbname="${1}";
dbdump_dir="${2}";

# ======================================================================================================== #

DEBUG=${DEBUG:-0};
GZIP=${GZIP:-1};

# ======================================================================================================== #

[ ! -f "/usr/local/directadmin/plugins/postgresql/exec/functions.inc.sh" ] && echo "[ERROR] Corrupted installation" && exit 1;
source /usr/local/directadmin/plugins/postgresql/exec/functions.inc.sh;

usage()
{
    echo "
=======================================================================
 A script to dump a PostgreSQL database to a .sql and/or sql.gz file
=======================================================================

Usage:

$0 <dbname> <dir>

    <dbname>  - a database name to dump
    <dir>     - a directory to save dump
";
    exit 0;
}

egress()
{
    unset PGPASSWORD;
    unset PGUSER;
    unset PGHOST;
    unset PGPORT;
    unset PGDATABASE;
    [ -d "${TMPDIR}" ] && rm -rf "${TMPDIR}";
}

do_dump()
{
    de "Processing ${1}";
    [ -d "${2}" ] || die "Directory ${2} does not exist...." 2;
    export PGPASSWORD="${dbpass}";
    export PGUSER="${dbuser}";
    export PGHOST="${dbhost}";
    export PGPORT="${dbport}";
    export PGDATABASE="${1}";
    ${DUMP_BIN} --inserts -c -f "${TMPDIR}/${1}.sql";
    if [ -f "${TMPDIR}/${1}.sql" ];
    then
        if [ "0" == "${GZIP}" ]; 
        then
            mv "${TMPDIR}/${1}.sql" "${2}/${1}.sql";
        else
            gzip "${TMPDIR}/${1}.sql";
            mv "${TMPDIR}/${1}.sql.gz" "${2}/${1}.sql.gz";
        fi;
    fi;
}

# ======================================================================================================== #

DBCONF="/usr/local/directadmin/plugins/postgresql/pgpass.conf";

# ======================================================================================================== #

[ -n "${dbname}" ] || usage;
[ -f "${DBCONF}" ] || die "Could not find user conf" 1;

dbhost=$(awk -F: '{print $1}' "${DBCONF}");
dbport=$(awk -F: '{print $2}' "${DBCONF}");
dbuser=$(awk -F: '{print $4}' "${DBCONF}");
dbpass=$(awk -F: '{print $5}' "${DBCONF}");

[ -n "${dbhost}" ] || die "Could not find dbhost in user conf ${DBCONF}" 3;
[ -n "${dbport}" ] || die "Could not find dbport in user conf ${DBCONF}" 4;
[ -n "${dbuser}" ] || die "Could not find dbuser in user conf ${DBCONF}" 5;
[ -n "${dbpass}" ] || die "Could not find dbpass in user conf ${DBCONF}" 6;

DUMP_BIN="/usr/local/bin/pg_dump";
[ -x "${DUMP_BIN}" ] || DUMP_BIN="/usr/bin/pg_dump";
[ -x "${DUMP_BIN}" ] || DUMP_BIN="/bin/pg_dump";
[ -x "${DUMP_BIN}" ] || die "Could not find ${DUMP_BIN}" 10;

PSQL_BIN="/usr/local/bin/psql";
[ -x "${PSQL_BIN}" ] || PSQL_BIN="/usr/bin/psql";
[ -x "${PSQL_BIN}" ] || PSQL_BIN="/bin/psql";
[ -x "${PSQL_BIN}" ] || die "PostgreSQL is not installed: ${PSQL_BIN}!" 20;

# ======================================================================================================== #

TMPDIR=$(mktemp -d "/home/tmp/pgsql_dump.XXXXXXXXXX");
trap egress EXIT;

do_dump "${dbname}" "${dbdump_dir}";

exit 0;
