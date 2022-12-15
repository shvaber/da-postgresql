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
user_backup="${2}";

# ======================================================================================================== #

DEBUG=${DEBUG:-0};

# ======================================================================================================== #

[ ! -f "/usr/local/directadmin/plugins/postgresql/exec/functions.inc.sh" ] && echo "[ERROR] Corrupted installation" && exit 1;
source /usr/local/directadmin/plugins/postgresql/exec/functions.inc.sh;

usage()
{
    echo "
=======================================================================
 A script to recover PostgreSQL data from DirectAdmin/cPanel archives
=======================================================================

Usage:

$0 <da_user> <file>

    <da_user>       - a directadmin user to restore (should exist on a server)
    <file>          - a full path to a tar.gz file with DirectAdmin/cPanel backup
";
    exit 0;
}

egress()
{
    unset_psql_credentials;
    [ -d "${TEMP_DIR}" ] && rm -rf "${TEMP_DIR}";
    [ -f "${TEMP_FILE}" ] && rm -f "${TEMP_FILE}";
    de "Removing temp files/directories and exiting...";
}

genpasswd()
{
    local length;
    length=${1:-20};
    tr -dc A-Za-z0-9_ < /dev/urandom | head -c ${length} | xargs;
}

set_psql_credentials()
{
    export PGPASSWORD="${dbpass}";
    export PGUSER="${dbuser}";
    export PGHOST="${dbhost}";
    export PGPORT="${dbport}";
}

unset_psql_credentials()
{
    unset PGPASSWORD;
    unset PGUSER;
    unset PGHOST;
    unset PGPORT;
    unset PGDATABASE;
}

validate_filename()
{
    c=$(echo "${1}" | grep -c '\.tar\.gz$\|\.tar\.zst$');
    [ "0" == "${c}" ] && die "Filename ${1} should end on tar.gz or tar.zst. Terminating..." 30;
    de "Filename validated: ${1} - OK";

    c=$(file -i "${1}" | grep -c ' application/x-gzip\|x-zstd;');
    [ "0" == "${c}" ] && die "Filename ${1} should be of a tar.gz format (mime application/x-gzip) or a tar.zst format (mime application/x-zstd). Terminating..." 31;
    de "File mime type validated: ${1} - OK";
}

detect_backup_type()
{
    filename=$(basename "${1}");
    c=$(echo "${filename}" | grep -c '^cpmove-');
    if [ "1" == "${c}" ]; 
    then
    {
        de "File ${filename} seems to be a cPanel move archive - OK";
        IS_DIRECTADMIN_TYPE=0;
        IS_CPANEL_TYPE=1;
        IS_UNSUPPORTED_TYPE=0;
        #echo 'cpanel';
        return;
    }
    fi;

    c=$(echo "${filename}" | egrep -c '^(admin|user)\.[A-Za-z0-9_-]*\.[A-Za-z0-9_-]*\.(tar.gz|tar.zst)'); #'
    if [ "1" == "${c}" ];
    then
    {
        de "File ${filename} seems to be a DirectAdmin archive - OK";
        IS_DIRECTADMIN_TYPE=1;
        IS_CPANEL_TYPE=0;
        IS_UNSUPPORTED_TYPE=0;
        #echo 'directadmin';
        return;
    }
    fi;

    de "File ${filename} seems to be of an unknown format - NOT OK";
    IS_DIRECTADMIN_TYPE=0;
    IS_CPANEL_TYPE=0;
    IS_UNSUPPORTED_TYPE=1;
    #echo 'unsupported';
    return;
}

# $1 - dbname
# $2 - username
validate_db_owner()
{
    echo "${1}" | grep -c -m1 "^${2}_";
}

get_pguser_epassword()
{
    local loc_pgpasword;
    set_psql_credentials;
    loc_pgpassword=$("${PDUMPALL_BIN}" --roles-only | egrep " ${1}( |;)" | grep PASSWORD | cut -d\' -f2); #"
    unset_psql_credentials;
    echo "${loc_pgpassword}";
}

# $1 - username
# $2 - password
# $3 - is encrypted password
set_pguser_password()
{
    local loc_pguser;
    local loc_pgpassword;
    local loc_is_encrypted;

    [ -n "${1}" ] && log_pguser="${1}";
    [ -n "${2}" ] && loc_pgpassword="${2}";
    loc_is_encrypted="${3:-0}";

    set_psql_credentials;

    ${PSQL_BIN} -c "DO \$\$
BEGIN
CREATE USER ${1};
EXCEPTION WHEN duplicate_object THEN RAISE NOTICE '%, skipping', SQLERRM USING ERRCODE = SQLSTATE;
END
\$\$;" 1>/dev/null 2>&1;
    de "Setting user ${1}'s password to the value of '${2}'";
    if [ "0" == "${loc_is_encrypted}" ]; 
    then
        ${PSQL_BIN} -c "ALTER USER ${1} WITH PASSWORD '${2}';" 1>/dev/null 2>&1;
    else
        ${PSQL_BIN} -c "ALTER USER ${1} WITH PASSWORD E'${2}';" 1>/dev/null 2>&1;
    fi;
    unset_psql_credentials;
}

# $1 - dbname
_get_db_owner()
{
    ${PSQL_BIN} -tAc "SELECT pg_catalog.pg_get_userbyid(d.datdba) as "Owner" FROM pg_catalog.pg_database d WHERE d.datname='${1}';" 2>/dev/null;
}

# $1 - group
# $2 - role
grant_role_to_role()
{
    local loc_group;
    local loc_dbuser;

    loc_group="${1}";
    loc_dbuser="${2}";

    set_psql_credentials;

    de "Granting membership in ${loc_group} to ${loc_dbuser}";
    ${PSQL_BIN} -c "
REVOKE ${loc_group} FROM ${loc_dbuser};
GRANT ${loc_group} TO ${loc_dbuser};
" >/dev/null 2>&1;
    unset_psql_credentials;
}

# $1 - username
# $2 - dbname
# $3 - 0|1 - advanced
set_pguser_privileges()
{
    local loc_dbuser;
    local loc_dbname;
    local loc_advanced;

    loc_dbuser="${1}";
    loc_dbname="${2}";
    loc_advanced="${3:-0}";

    set_psql_credentials;

    # SET DBNAME
    export PGDATABASE="${loc_dbname}";

    if [ "1" == "${loc_advanced}" ];
    then
        de "Granting all privileges on database ${loc_dbname} to ${loc_dbuser} (advanced mode ON)";
        ${PSQL_BIN} -c "
REVOKE ALL PRIVILEGES ON DATABASE ${loc_dbname} FROM ${loc_dbuser};
REVOKE ALL PRIVILEGES ON ALL TABLES IN SCHEMA public FROM ${loc_dbuser};
REVOKE ALL PRIVILEGES ON ALL TABLES IN SCHEMA pg_catalog FROM ${loc_dbuser};
REVOKE ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public FROM ${loc_dbuser};
GRANT ALL PRIVILEGES ON DATABASE ${loc_dbname} TO ${loc_dbuser};
GRANT SELECT ON ALL TABLES IN SCHEMA public TO ${loc_dbuser};
GRANT SELECT ON ALL TABLES IN SCHEMA pg_catalog TO ${loc_dbuser};
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO ${loc_dbuser};
" >/dev/null 2>&1;
    else
        de "Granting all privileges on database ${loc_dbname} to ${loc_dbuser} (advanced mode OFF)";
        ${PSQL_BIN} -c "
REVOKE ALL PRIVILEGES ON DATABASE ${loc_dbname} FROM ${loc_dbuser};
GRANT ALL PRIVILEGES ON DATABASE ${loc_dbname} TO ${loc_dbuser};
" >/dev/null 2>&1;
    fi;
    unset_psql_credentials;
}

write_user_pgconf()
{
    echo -n "${1}" > "${2}";
    chmod 600 "${2}";
}

create_user_if_missing()
{
    set_psql_credentials;
    de "Check and create user ${1} if it does not exist";
    c=$(${PSQL_BIN} -tAc "SELECT 1 FROM pg_roles WHERE rolname='${1}'");
    [ "1" == "${c}" ] || ${PSQL_BIN} -c "CREATE USER ${1};";
    unset_psql_credentials;
}

create_db_if_missing()
{
    set_psql_credentials;
    de "Check and create database ${1} owned by ${2} if it does not exist";
    c=$(${PSQL_BIN} -tAc "SELECT 1 FROM pg_database WHERE datname='${1}'");
    [ "1" == "${c}" ] || ${PSQL_BIN} -c "CREATE DATABASE ${1} OWNER ${2};" >/dev/null 2>&1;
    unset_psql_credentials;
}

import_raw_sql()
{
    set_psql_credentials;
    de "Importing raq SQL file ${1} into PostgreSQL server";
    ${PSQL_BIN} -f "${1}" 2>&1;
    unset_psql_credentials;
}

import_users_from_file()
{
    local loc_line;
    local loc_username;
    local loc_password;
    local loc_cp;

    loc_cp="${3:-directadmin}";

    while read loc_line;
    do
    {
        if [ "directadmin" == "${loc_cp}" ];
        then
        {
            loc_username=$(echo "${loc_line}" | egrep "^CREATE (ROLE|USER) ${1}(\ |\_|\;)" | awk '{print $3}' | cut -d\; -f1;); #"
            if [ -n "${loc_username}" ];
            then
                loc_password=$(egrep "^ALTER (ROLE|USER) ${loc_username}\ " "${2}" | egrep -o "PASSWORD (E|)'[^\']*" | cut -d\' -f2); #"
            else
                loc_password="";
            fi;
        }
        else
        {
            loc_username=$(echo "${loc_line}" | egrep "^CREATE (ROLE|USER) (\"|\'|)${1}(\ |\_|\;|\"|\').* WITH PASSWORD" | awk '{print $3}'); #"
            loc_username=${loc_username//\"/};
            loc_username=${loc_username//\'/};
            loc_password=$(echo "${loc_line}" | egrep "^CREATE (ROLE|USER) (\"|\'|)${1}(\ |\_|\;|\"|\').* WITH PASSWORD" | egrep -o "PASSWORD (E|)'[^\']*" | cut -d\' -f2); #"
        }
        fi;

        [ -n "${loc_username}" ] || continue;

        if [ -n "${loc_password}" ];
        then
        {
            echo "[OK] Found username ${loc_username} with encrypted password ${loc_password}";
            set_pguser_password "${loc_username}" "${loc_password}" 1;
            [ "${da_user}" == "${loc_username}" ] && USER_PGPASSWORD_RECOVERED=1;
        }
        else
        {
            echo "[WARNING] Found username ${loc_username} without password";
            create_user_if_missing "${loc_username}";
        }
        fi;
    }
    done < "${2}";
}

import_grants_from_file()
{
    local loc_line;
    local loc_dbname;
    local loc_username;
    local loc_cp;

    loc_cp="${3:-directadmin}";

    while read loc_line;
    do
    {
        if [ "directadmin" == "${loc_cp}" ];
        then
        {
            loc_dbname=$(echo "${loc_line}" | grep "^GRANT " | egrep 'DATABASE '${1}'(\_|).* TO '${1}'(\_|).*;' | grep -o 'ON DATABASE .*' | awk '{print $3}'); #'
            loc_username=$(echo "${loc_line}" | grep "^GRANT " | egrep 'DATABASE '${1}'(\_|).* TO '${1}'(\_|).*;' | egrep -o 'ON DATABASE .*[^\;]+' | awk '{print $5}'); #'
        }
        else
        {
            loc_dbname=$(echo "${loc_line}" | grep "^GRANT " | egrep " (\"|\'|)${1}(\_|).*(\"|\'|) TO (\"|\'|)${1}(\_|).*(\"|\'|);" | awk '{print $2}'); #"
            loc_dbname=${loc_dbname//\"/};
            loc_dbname=${loc_dbname//\'/};
            loc_username=$(echo "${loc_line}" | grep "^GRANT " | egrep " (\"|\'|)${1}(\_|).*(\"|\'|) TO (\"|\'|)${1}(\_|).*(\"|\'|);" | awk '{print $4}'); #"
            loc_username=${loc_username//\"/};
            loc_username=${loc_username//\'/};
            loc_username=${loc_username//\;/};
        }
        fi;

        if [ -n "${loc_dbname}" ] && [ -n "${loc_username}" ];
        then
            echo "[OK] Found grant access request on database ${loc_dbname} to ${loc_username}";
            #set_pguser_privileges "${loc_username}" "${loc_dbname}";
            grant_role_to_role "${loc_dbname}" "${loc_username}";
        fi;
    }
    done < "${2}";
}

#
# IMPORT DATA FROM DIRECTADMIN TYPE BACKUP
#
import_directadmin_data()
{
    local loc_dbname;
    local loc_dbowner;
    local loc_owner_ok;
    local loc_dbdump;
    local loc_dbuser;

    local loc_dbgrants_file;
    local loc_dbusers_file;
    local loc_dbconf_file;

    local loc_grants_imported;
    local loc_users_imported;

    de "Unpacking DirectAdmin archive ${1} to ${2}";
    
    c=$(echo "${1}" | grep -c '\.tar\.gz$');
    [ "1" == "${c}" ] && tar -zxf "${1}" -C "${2}" "backup/psql/" --strip-components=1;
    
    c=$(echo "${1}" | grep -c '\.tar\.zst$');
    [ "1" == "${c}" ] && tar --use-compress-program=unzstd -xf "${1}" -C "${2}" "backup/psql/" --strip-components=1;

    #tar -zxf "${1}" -C "${2}" "backup/psql/" --strip-components=1;
    IMPORT_DIR="${2}/psql";
    if [ -d "${IMPORT_DIR}" ];
    then
    {
        loc_dbgrants_file="${IMPORT_DIR}/psql_grants.sql";
        loc_dbusers_file="${IMPORT_DIR}/psql_users.sql";

        loc_grants_imported=0;
        loc_users_imported=0;

        # Processing DataBases
        for FILE in $(ls -1 "${IMPORT_DIR}"/*.conf);
        do
        {
            # GET DBNAME FROM FILENAME
            loc_dbname=$(basename ${FILE} .conf);
            de "Found ${FILE} in ${1}, dump for ${loc_dbname}";

            # A DUMP FILE
            loc_dbdump="${IMPORT_DIR}/${loc_dbname}.sql";

            # A CONF FILE
            loc_dbconf_file="${IMPORT_DIR}/${loc_dbname}.conf";

            # CHECK DIRECTADMIN USER SHOULD MATCH THE DBNAME
            loc_owner_ok=$(validate_db_owner "${loc_dbname}" "${da_user}");

            # RESTORE IF OWNER IS OK
            if [ "0" == "${loc_owner_ok}" ];
            then
            {
                echo "[WARNING] Database ${loc_dbname} can not be restored to user ${da_user}! Wrong owner!";
            }
            else
            {
                #--[STEP 1]--# CREATE DB USERS: SYS USER AND DB OWNER

                # FIRST WE TRY AND GET DB OWNER FROM CONF FILE
                [ -f "${loc_dbconf_file}" ] && loc_dbowner=$(grep ^owner= "${loc_dbconf_file}" | cut -d= -f2);

                # THEN WE SET DB OWNER TO THE SIMILAR USERNAME (IF THE FIRST IS EMPTY)
                [ -z "${loc_dbowner}" ] && loc_dbowner="${loc_dbname}";

                # CREATE SYS USER
                create_user_if_missing "${da_user}";

                if [ "${loc_dbowner}" == "${loc_dbname}" ];
                then
                    # CREATE DBOWNER IF IT DOES NOT EXIST
                    loc_dbuser="";
                    create_user_if_missing "${loc_dbowner}";
                else
                    # CREATE USER ROLE WITH A NAME SIMILAR TO DBNAME (DBOWNER IS A DIFFERENT NAME)
                    loc_dbuser="${loc_dbowner}";
                    loc_dbowner="${loc_dbname}";
                    create_user_if_missing "${loc_dbuser}";
                    create_user_if_missing "${loc_dbowner}";
                fi;


                #--[STEP 2]--# CREATE DATABASE OWNED BY DB OWNER

                # CREATE DB IF IT IS MISSING
                create_db_if_missing "${loc_dbname}" "${loc_dbowner}";


                #--[STEP 3]--# SET PRIVILEGES FOR DATABASE

                # GRANT MEMBERSHIP IN ROLE DBOWNER TO SYS USER
                echo "[OK] Grant membership in role ${loc_dbowner} to ${da_user}";
                grant_role_to_role "${loc_dbowner}" "${da_user}";

                if [ -n "${loc_dbuser}" ];
                then
                    # GRANT MEMBERSHIP IN ROLE DBOWNER TO DBUSER
                    echo "[OK] Grant membership in role ${loc_dbowner} to ${loc_dbuser}";
                    grant_role_to_role "${loc_dbowner}" "${loc_dbuser}";

                    # SET PRIVILEGES FOR THE SYSTEM USER TO THE DBNAME
                    echo "[OK] Updating access privileges for user ${loc_dbuser} to database ${loc_dbname}";
                    set_pguser_privileges "${loc_dbuser}" "${loc_dbname}";
                fi;

                # IMPORT USERS
                if [ -s "${loc_dbusers_file}" ] && [ "0" = "${loc_users_imported}" ];
                then
                    de "Going to import users from ${loc_dbusers_file}";
                    import_users_from_file "${da_user}" "${loc_dbusers_file}" directadmin;
                    loc_users_imported=1;
                fi;

                # IMPORT GRANTS
                if [ -s "${loc_dbgrants_file}" ] && [ "0" = "${loc_grants_imported}" ];
                then
                    de "Going to import users grants ${loc_dbgrants_file}";
                    import_grants_from_file "${da_user}" "${loc_dbgrants_file}" directadmin;
                    loc_grants_imported=1;
                fi;

                # UNSET SUPERUSER PRIVILEGES
                unset_psql_credentials;

                export SUPERUSER=0;

                # PREPARE USER PGCONF FOR CONNECTING TO THE PSQL SERVER
                write_user_pgconf "${dbhost}:${dbport}:${loc_dbname}:${da_user}:${USER_PGPASSWORD_TEMP}" "${TEMP_DIR}/.${loc_dbname}.pgpass.conf";

                echo "[OK] Going to import database ${loc_dbname} for user ${da_user}";
                "${SCRIPT}" "${loc_dbdump}" "${loc_dbname}" 1;
                RETVAL=$?;
                if [ "0" == "${RETVAL}" ]; then
                    echo "[OK] Task to import database ${loc_dbname} ended with success, with exit_code=${RETVAL}";
                else
                    echo "[ERROR] Task to import database ${loc_dbname} failed, exit_code=${RETVAL}";
                fi;

                [ -f "${TEMP_DIR}/.${loc_dbname}.pgpass.conf" ] && rm -f "${TEMP_DIR}/.${loc_dbname}.pgpass.conf";
            }
            fi;
        }
        done;
    }
    else
    {
        die "PostgreSQL data not found in the archive ${1}" 100;
    }
    fi;
}


#
# IMPORT DATA FROM CPANEL TYPE BACKUP
#
import_cpanel_data()
{
    local loc_dbname;
    local loc_dbowner;
    local loc_owner_ok;
    local loc_dbdump;
    local loc_dbuser;

    local loc_dbgrants_file;
    local loc_dbusers_file;
    local loc_dbconf_file;

    local loc_grants_imported;
    local loc_users_imported;

    de "Unpacking cPanel archive ${1} to ${2}";
    tar -zxf "${1}" -C "${2}" "*/psql/" "*/psql_*.sql" --strip-components=1;
    IMPORT_DIR="${2}/psql";
    if [ -d "${IMPORT_DIR}" ];
    then
    {
        [ -f "${2}/psql_grants.sql" ] && mv "${2}/psql_grants.sql" "${IMPORT_DIR}";
        [ -f "${2}/psql_users.sql" ] && mv "${2}/psql_users.sql" "${IMPORT_DIR}";

        loc_dbgrants_file="${IMPORT_DIR}/psql_grants.sql";
        loc_dbusers_file="${IMPORT_DIR}/psql_users.sql";

        loc_grants_imported=0;
        loc_users_imported=0;

        # Processing DataBases
        for FILE in $(ls -1 "${IMPORT_DIR}"/*.tar);
        do
        {
            # GET DBNAME FROM FILENAME
            loc_dbname=$(basename ${FILE} .tar);
            de "Found ${FILE} in ${1}, dump for ${loc_dbname}";

            # A DUMP FILE
            loc_dbdump="${IMPORT_DIR}/${loc_dbname}.tar";

            # A CONF FILE
            loc_dbconf_file="";

            # CHECK DIRECTADMIN USER SHOULD MATCH THE DBNAME
            loc_owner_ok=$(validate_db_owner "${loc_dbname}" "${da_user}");

            # RESTORE IF OWNER IS OK
            if [ "0" == "${loc_owner_ok}" ];
            then
            {
                echo "[WARNING] Database ${loc_dbname} can not be restored to user ${da_user}! Wrong owner!";
            }
            else
            {
                #--[STEP 1]--# CREATE DB USERS: SYS USER AND DB OWNER

                # WE SET DB OWNER TO SIMILAR USERNAME
                loc_dbowner="${loc_dbname}";
                loc_dbuser="";

                # CREATE SYS USER
                create_user_if_missing "${da_user}";

                # CREATE DBOWNER IF IT DOES NOT EXIST
                create_user_if_missing "${loc_dbowner}";


                #--[STEP 2]--# CREATE DATABASE OWNED BY DB OWNER

                # CREATE DB IF IT IS MISSING
                create_db_if_missing "${loc_dbname}" "${loc_dbowner}";


                #--[STEP 3]--# SET PRIVILEGES FOR DATABASE

                # GRANT MEMBERSHIP IN ROLE DBOWNER TO SYS USER
                echo "[OK] Grant membership in role ${loc_dbowner} to ${da_user}";
                grant_role_to_role "${loc_dbowner}" "${da_user}";

                # SET PRIVILEGES FOR THE SYSTEM USER TO THE DBNAME
                echo "[OK] Updating access privileges for user ${da_user} to import database ${loc_dbname}";
                set_pguser_privileges "${da_user}" "${loc_dbname}";

                # IMPORT USERS
                if [ -s "${loc_dbusers_file}" ] && [ "0" = "${loc_users_imported}" ];
                then
                    de "Going to import users from ${loc_dbusers_file} (should run only once)";
                    import_users_from_file "${da_user}" "${loc_dbusers_file}" cpanel;
                    loc_users_imported=1;
                fi;

                # IMPORT GRANTS
                if [ -s "${loc_dbgrants_file}" ] && [ "0" = "${loc_grants_imported}" ];
                then
                    de "Going to import users grants ${loc_dbgrants_file} (should run only once)";
                    import_grants_from_file "${da_user}" "${loc_dbgrants_file}" cpanel;
                    loc_grants_imported=1;
                fi;

                # UNSET SUPERUSER PRIVILEGES
                unset_psql_credentials;

                export SUPERUSER=1;

                # PREPARE USER PGCONF FOR CONNECTING TO THE PSQL SERVER
                write_user_pgconf "${dbhost}:${dbport}:${loc_dbname}:${da_user}:${USER_PGPASSWORD_TEMP}" "${TEMP_DIR}/.${loc_dbname}.pgpass.conf";

                echo "[OK] Going to import database ${loc_dbname} for user ${da_user}";
                "${SCRIPT}" "${loc_dbdump}" "${loc_dbname}" 1;
                RETVAL=$?;
                if [ "0" == "${RETVAL}" ]; then
                    echo "[OK] Task to import database ${loc_dbname} ended with success, with exit_code=${RETVAL}";
                else
                    echo "[ERROR] Task to import database ${loc_dbname} failed, exit_code=${RETVAL}";
                fi;

                [ -f "${TEMP_DIR}/.${loc_dbname}.pgpass.conf" ] && rm -f "${TEMP_DIR}/.${loc_dbname}.pgpass.conf";
            }
            fi;
        }
        done;
    }
    else
    {
        die "PostgreSQL data not found in the archive ${1}" 100;
    }
    fi;
}

# ======================================================================================================== #

DBCONF_SU="/usr/local/directadmin/plugins/postgresql/pgpass.conf";

# ======================================================================================================== #

[ -n "${da_user}" ] || usage;
[ -n "${user_backup}" ] || usage;
[ -f "${DBCONF_SU}" ] || die "Could not find superuser conf for connecting to PSQL" 1;

dbhost=$(awk -F: '{print $1}' "${DBCONF_SU}");
dbport=$(awk -F: '{print $2}' "${DBCONF_SU}");
dbuser=$(awk -F: '{print $4}' "${DBCONF_SU}");
dbpass=$(awk -F: '{print $5}' "${DBCONF_SU}");

[ -n "${dbhost}" ] || die "Could not find dbhost in user conf ${DBCONF_SU}" 3;
[ -n "${dbport}" ] || die "Could not find dbport in user conf ${DBCONF_SU}" 4;
[ -n "${dbuser}" ] || die "Could not find dbuser in user conf ${DBCONF_SU}" 5;
[ -n "${dbpass}" ] || die "Could not find dbpass in user conf ${DBCONF_SU}" 6;

RESTORE_BIN="/usr/local/bin/pg_restore";
[ -x "${RESTORE_BIN}" ] || RESTORE_BIN="/usr/bin/pg_restore";
[ -x "${RESTORE_BIN}" ] || RESTORE_BIN="/bin/pg_restore";
[ -x "${RESTORE_BIN}" ] || die "Could not find ${RESTORE_BIN}" 10;

PSQL_BIN="/usr/local/bin/psql";
[ -x "${PSQL_BIN}" ] || PSQL_BIN="/usr/bin/psql";
[ -x "${PSQL_BIN}" ] || PSQL_BIN="/bin/psql";
[ -x "${PSQL_BIN}" ] || die "PostgreSQL is not installed: ${PSQL_BIN}!" 20;

PDUMPALL_BIN="/usr/local/bin/pg_dumpall";
[ -x "${PDUMPALL_BIN}" ] || PDUMPALL_BIN="/usr/bin/pg_dumpall";
[ -x "${PDUMPALL_BIN}" ] || PDUMPALL_BIN="/bin/pg_dumpall";
[ -x "${PDUMPALL_BIN}" ] || die "PostgreSQL is not installed: ${PDUMPALL_BIN}!" 10;
# ======================================================================================================== #

trap egress EXIT;

[ -d "/usr/local/directadmin/data/users/${da_user}/" ] || die "User ${da_user} does not exist in DirectAdmin" 30;
[ -f "${user_backup}" ] || die "File ${user_backup} does not exist on the server. Terminating..." 40;

IS_DIRECTADMIN_TYPE=0;
IS_CPANEL_TYPE=0;
IS_UNSUPPORTED_TYPE=1;

# TEMP PASSWORD FOR $da_user TO CONNECT TO PSQL SERVER
USER_PGPASSWORD_TEMP="$(genpasswd 24)";
USER_PGPASSWORD_RECOVERED=0;

# DATABASE RESTORE SCRIPT
SCRIPT="/usr/local/directadmin/plugins/postgresql/exec/dbrestore.sh";

export TEMP_DIR=$(mktemp --directory /home/tmp/.${da_user}.XXXXXXXX);

# VALIDATE FILENAME AND MIME-TYPE
validate_filename "${user_backup}";

# DETET ARCHIVE TYPE
detect_backup_type "${user_backup}";
[ "1" == "${IS_UNSUPPORTED_TYPE}" ] && die "Unsupported format. Only DirectAdmin and cPanel backups are supported. Terminating..." 50;

# GET THE CURRENT PGPASSWORD OF THE USER
USER_PGPASSWORD_STORED=$(get_pguser_epassword "${da_user}");

# SET A TEMP PASSWORD FOR THE USER
set_pguser_password "${da_user}" "${USER_PGPASSWORD_TEMP}";

if [ "1" == "${IS_DIRECTADMIN_TYPE}" ];
then
    # DirectAdmin
    echo "[OK] Starting with DirectAdmin backup for user ${da_user}";
    import_directadmin_data "${user_backup}" "${TEMP_DIR}";
    echo "[OK] Completed with DirectAdmin backup for user ${da_user}";
elif [ "1" == "${IS_CPANEL_TYPE}" ];
then
    # cPanel
    echo "[OK] Starting with cPanel backup for user ${da_user}";
    import_cpanel_data "${user_backup}" "${TEMP_DIR}";
    echo "[OK] Completed with cPanel backup for user ${da_user}";
fi;

# REVERTING THE PASSWORD FOR THE USER
if [ -n "${USER_PGPASSWORD_STORED}" ] && [ "0" == "${USER_PGPASSWORD_RECOVERED}" ];
then
    de "Changing the password for ${da_user} to its original value...";
    set_pguser_password "${da_user}" "${USER_PGPASSWORD_STORED}" 1;
else
    de "Skipping to change the password for ${da_user} to its original value. Recovered from backup.";
fi;

exit 0;
