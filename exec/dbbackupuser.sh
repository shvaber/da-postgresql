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
dbdump_dir="${2}";

# ======================================================================================================== #

DEBUG=${DEBUG:-0};

# ======================================================================================================== #

[ ! -f "/usr/local/directadmin/plugins/postgresql/exec/functions.inc.sh" ] && echo "[ERROR] Corrupted installation" && exit 1;
source /usr/local/directadmin/plugins/postgresql/exec/functions.inc.sh;

usage()
{
    echo "
=======================================================================
 A script to dump PostgreSQL data: Roles, Grants, Databases
=======================================================================

Usage:

$0 <da_user> <dir>

    <da_user>  - a directadmin user to backup
    <dir>      - a directory where to save dumps
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
    [ -f "${TEMP_FILE}" ] && rm -rf "${TEMP_FILE}";
}

# ======================================================================================================== #

DBCONF="/usr/local/directadmin/plugins/postgresql/pgpass.conf";

# ======================================================================================================== #

[ -n "${da_user}" ] || usage;
[ -n "${dbdump_dir}" ] || usage;
[ -f "${DBCONF}" ] || die "Could not find user conf" 1;

dbhost=$(awk -F: '{print $1}' "${DBCONF}");
dbport=$(awk -F: '{print $2}' "${DBCONF}");
dbuser=$(awk -F: '{print $4}' "${DBCONF}");
dbpass=$(awk -F: '{print $5}' "${DBCONF}");

[ -n "${dbhost}" ] || die "Could not find dbhost in user conf ${DBCONF}" 3;
[ -n "${dbport}" ] || die "Could not find dbport in user conf ${DBCONF}" 4;
[ -n "${dbuser}" ] || die "Could not find dbuser in user conf ${DBCONF}" 5;
[ -n "${dbpass}" ] || die "Could not find dbpass in user conf ${DBCONF}" 6;

PDUMPALL_BIN="/usr/local/bin/pg_dumpall";
[ -x "${PDUMPALL_BIN}" ] || PDUMPALL_BIN="/usr/bin/pg_dumpall";
[ -x "${PDUMPALL_BIN}" ] || PDUMPALL_BIN="/bin/pg_dumpall";
[ -x "${PDUMPALL_BIN}" ] || die "PostgreSQL is not installed: ${PDUMPALL_BIN}!" 10;

PSQL_BIN="/usr/local/bin/psql";
[ -x "${PSQL_BIN}" ] || PSQL_BIN="/usr/bin/psql";
[ -x "${PSQL_BIN}" ] || PSQL_BIN="/bin/psql";
[ -x "${PSQL_BIN}" ] || die "PostgreSQL is not installed: ${PSQL_BIN}!" 20;


# ======================================================================================================== #

[ -d "/usr/local/directadmin/data/users/${da_user}/" ] || die "User ${da_user} does not exist in DirectAdmin" 30;
[ -d "${dbdump_dir}" ] || die "Directory ${dbdump_dir} does not exist on the server. Terminating..." 40;

export PGPASSWORD="${dbpass}";
export PGUSER="${dbuser}";
export PGHOST="${dbhost}";
export PGPORT="${dbport}";
export GZIP=0;

TEMP_FILE=$(mktemp "${dbdump_dir}/pg.roles.XXXXX");
chmod 600 "${TEMP_FILE}";
trap egress EXIT;

"${PDUMPALL_BIN}" --roles-only > "${TEMP_FILE}";

DB_GRANTS_FILE="${dbdump_dir}/psql_grants.sql";
DB_USERS_FILE="${dbdump_dir}/psql_users.sql";

[ -f "${DB_GRANTS_FILE}" ] && rm -f "${DB_GRANTS_FILE}";
[ -f "${DB_USERS_FILE}" ] && rm -f "${DB_USERS_FILE}";

touch "${DB_GRANTS_FILE}" "${DB_USERS_FILE}";
chmod 400 "${DB_GRANTS_FILE}" "${DB_USERS_FILE}";

# GET LIST OF ALL DATABASES OWNED BY DIRECTADMIN USER
for DB in $(${PSQL_BIN} -tc "SELECT datname FROM pg_catalog.pg_database WHERE datname = '${da_user}' OR datname LIKE '${da_user}_%';" 2>/dev/null | xargs);
do
{
    # CREATE A DUMP OF A DATABASE
    de "[DB] Dumping ${DB} in ${dbdump_dir}";
    /usr/local/directadmin/plugins/postgresql/exec/dbdump.sh "${DB}" "${dbdump_dir}";

    DB_CONF_FILE="${dbdump_dir}/${DB}.conf";
    [ -f "${DB_CONF_FILE}" ] && rm -f "${DB_CONF_FILE}";

    touch "${DB_CONF_FILE}";
    chmod 400 "${DB_CONF_FILE}";

    # GET THE DATABASE OWNER
    OWNER=$(${PSQL_BIN} -tc "SELECT pg_catalog.pg_get_userbyid(d.datdba) as "Owner" FROM pg_catalog.pg_database d WHERE d.datname='${DB}';" 2>/dev/null | xargs); #"
    echo "owner=${OWNER}" >> "${DB_CONF_FILE}";

    # GET LIST OF USERS ALLOWED TO CONNECT TO THE DATABASE
    for ROW in $("${PSQL_BIN}" -tc "SELECT datacl AS acl FROM pg_catalog.pg_database WHERE datname='"${DB}"';" | xargs | cut -d{ -f2 | cut -d} -f1);
    do
    {
        OIFS="${IFS}";
        IFS=',';
        read -ra PRIV <<< "${ROW}";
        for i in "${PRIV[@]}";
        do
        {
            ROLE=$(echo "${i}" | cut -d= -f1);
            if [ -n "${ROLE}" ];
            then
            {
                de "[ROLE] Found role ${ROLE} to have priveleges on database ${DB}. Dumping...";
                egrep " ${ROLE}( |;)" "${TEMP_FILE}" | egrep "^(CREATE|ALTER)" >> "${DB_USERS_FILE}";
                echo "GRANT ALL PRIVILEGES ON DATABASE ${DB} TO ${ROLE};" >> "${DB_GRANTS_FILE}";
                egrep " ${ROLE}( |;)" "${TEMP_FILE}" | grep "^GRANT" >> "${DB_GRANTS_FILE}";
            }
            fi;
        }
        done
        IFS="${OIFS}";
    }
    done;
}
done;

cat "${DB_GRANTS_FILE}" | sort | uniq > "${DB_GRANTS_FILE}.tmp"; 
cat "${DB_USERS_FILE}" | sort -r | uniq > "${DB_USERS_FILE}.tmp";

cat "${DB_GRANTS_FILE}.tmp" > "${DB_GRANTS_FILE}";
cat "${DB_USERS_FILE}.tmp" > "${DB_USERS_FILE}";

rm -f "${DB_GRANTS_FILE}.tmp";
rm -f "${DB_USERS_FILE}.tmp";

exit 0;
