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

da_user="${1}";

# ======================================================================================================== #

DEBUG=${DEBUG:-0};

# ======================================================================================================== #

[ ! -f "/usr/local/directadmin/plugins/postgresql/exec/functions.inc.sh" ] && echo "[ERROR] Corrupted installation" && exit 1;
source /usr/local/directadmin/plugins/postgresql/exec/functions.inc.sh;

usage()
{
    echo "
=======================================================================
 A script to get size of all PostgreSQL databases owned by an user
=======================================================================

Usage:

$0 <da_user>

    <da_user>  - a directadmin user for which to calculate PostgreSQL size
";
    exit 0;
}

# ======================================================================================================== #

DBCONF="/usr/local/directadmin/plugins/postgresql/pgpass.conf";

# ======================================================================================================== #

[ -n "${da_user}" ] || usage;
[ -f "${DBCONF}" ] || die "Could not find user conf" 1;

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

[ -d "/usr/local/directadmin/data/users/${da_user}/" ] || die "User ${da_user} does not exist in DirectAdmin" 30;

export PGPASSWORD="${dbpass}";
export PGUSER="${dbuser}";
export PGHOST="${dbhost}";
export PGPORT="${dbport}";
dbsize=$(${PSQL_BIN} -tc "SELECT SUM(pg_database_size(datname)) AS size FROM pg_catalog.pg_database WHERE datname = '${da_user}' OR datname LIKE '${da_user}_%';" 2>/dev/null | xargs); #"
if [ -n "${dbsize}" ]; then
    echo "${dbsize}";
else
    echo 0;
fi;

unset PGPASSWORD;
unset PGUSER;
unset PGHOST;
unset PGPORT;
unset PGDATABASE;
exit 0;
