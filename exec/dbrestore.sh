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

dbdump=${1};
dbname=${2};
remove=${3:-0};

# ======================================================================================================== #

SUPERUSER=${SUPERUSER:-0};
DEBUG=${DEBUG:-0};

# ======================================================================================================== #

[ ! -f "/usr/local/directadmin/plugins/postgresql/exec/functions.inc.sh" ] && echo "[ERROR] Corrupted installation" && exit 1;
source /usr/local/directadmin/plugins/postgresql/exec/functions.inc.sh;

log()
{
    echo "[$(date)] ${1}" >> "${LOG_FILE}";
}

usage()
{
    echo "
=======================================================================
 A script to import/restore a PostgreSQL database from a backup
=======================================================================
 
Usage:

$0 <dump> <dbname> [<remove>]

    <dump>    - a full path to a dump file
    <dbname>  - a database name to which the dump will be restored
    <remove>  - 1 - remove backup file after import, 0 - do not remove (default is 0)
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
    [ -d "${TMP_DIR}" ] && rm -rf "${TMP_DIR}";
    if [ "1" == "${remove}" ] && [ -f "${DBDUMP_ORIG}" ];
    then
        rm -f "${DBDUMP_ORIG}";
    fi;
}

set_permissions()
{
    WHOAMI=$(whoami);
    [ -n "${1}" ] || return 0;
    if [ "root" == "${WHOAMI}" ] && [ -n "${dbadmin}" ];
    then
        de "Changing owner of ${1} to ${dbadmin}";
        if [ -d "${1}" ]; then
            chown -v "${dbadmin}:" ${1}/*;
            chmod 755 ${1};
            chmod 644 ${1}/*;
        else
            chown -v "${dbadmin}:" "${1}";
            chmod 644 "${1}";
        fi;
    fi;
}

do_detect_mime()
{
    file -i "${1}" | awk '{print $2}' | cut -d\; -f1;
}

do_process_file()
{
    let TRY++;
    if [ "${TRY}" -gt "${MAX_TRIES}" ];
    then
        die "Too many archives inside.... Terminating..." 100;
        exit 1;
    fi;
    filemime=$(do_detect_mime "${1}");
    de "TRY ${TRY}: ${1} with mime ${filemime}";
    case "${filemime}" in
        "text/plain")
            do_import_plain "${1}";
            ;;
        "application/x-tar")
            do_import_tar "${1}";
            ;;
        "application/x-gzip")
            do_import_gzip "${1}";
            ;;
        "application/zip")
            do_import_zip "${1}";
            ;;
        "inode/directory")
            do_import_directory "${1}";
            ;;
        *)
            die "Unsupported format ${filemime} of ${1}" 40;
            ;;
    esac;
}

do_import_gzip()
{
    de "Processing ${1}";
    cp -p "${1}" "${TMP_DIR}";
    local loc_fname="${TMP_DIR}/$(basename ${1})";
    gunzip -f "${loc_fname}";
    loc_fname="${loc_fname%.*}";
    do_process_file "${loc_fname}";
    gzip -f "${loc_fname}";
}

do_import_zip()
{
    de "Processing ${1}";
    out=$(${UNZIP_BIN} -l "${1}" | grep '1 file' -c);
    if [ "${out}" == "1" ];
    then
        out=$(${UNZIP_BIN} -o "${1}" -d "${TMP_DIR}" | grep inflating: | awk '{print $NF}');
        if [ -f "${out}" ];
        then
            set_permissions "${out}";
            do_process_file "${out}";
        else
            die "Failed to import ZIP, corrupted file?" 50;
        fi;
    else
        die "Directory found inside ZIP..." 60;
    fi;
}

do_import_tar()
{
    de "Processing ${1}";
    tar -xvf "${1}" -C "${TMP_DIR}" --no-same-owner;
    set_permissions "${TMP_DIR}";
    perl -pi -e 's#\$\$PATH\$\$#'${TMP_DIR}'#' ${TMP_DIR}/*.sql;
    do_process_file "${TMP_DIR}";
}

do_import_plain()
{
    de "Processing ${1}";
    export PGPASSWORD="${dbpass}";
    export PGUSER="${dbuser}";
    export PGHOST="${dbhost}";
    export PGPORT="${dbport}";
    export PGDATABASE="${dbname}";
    log "======================================= SQL IMPORT // =======================================";
    ${PSQL_BIN} -f "${1}" >> "${LOG_FILE}" 2>&1;
    RETVAL=$?;
    log "======================================= // SQL IMPORT =======================================";
    if [ "0" == "${RETVAL}" ];
    then
        de "SUCCESS: Import ended with exit code=${RETVAL}";
    else
        de "ERROR: Import failed with exit code=${RETVAL}";
        die "Import failed..." "${RETVAL}";
    fi;
}

do_import_directory()
{
    de "Processing ${1}";
    for FILE in $(find "${1}" -name \*.sql)
    do
        de "Do not encrease tries on processing numerous *.sql files"
        let TRY--; # DO NOT ENCREASE TRIES ON FILES *.sql
        do_process_file "${FILE}";
    done;
}

# ======================================================================================================== #

LOG_DIR="/usr/local/directadmin/plugins/postgresql/logs";
LOG_FILE="${LOG_DIR}/restore.db.${dbname}.$(date +%Y%m%d.%s).log";
MAX_TRIES=3
CUR_TRY=0
TEMP_DIR="${TEMP_DIR:-/home/tmp/pgsql_restore}";
DBCONF="${TEMP_DIR}/.${dbname}.pgpass.conf";
DBCONF_SU="/usr/local/directadmin/plugins/postgresql/pgpass.conf";

dbadmin="";

if [ "1" == "${SUPERUSER}" ] && [ -f "${DBCONF_SU}" ];
then
    de "IMPORTANT: superuser mode!";
    DBCONF="${DBCONF}.superuser";
    cat "${DBCONF_SU}" > "${DBCONF}";
    chmod 400 "${DBCONF}";
    dbadmin=$(awk -F: '{print $4}' "${DBCONF}");
fi;

de "TEMP_DIR is set to ${TEMP_DIR}";
de "Reading user conf from ${DBCONF}";

# ======================================================================================================== #

[ -n "${dbname}" ] || usage;
[ -f "${DBCONF}" ] || die "Could not find user conf" 1;
[ -e "${dbdump}" ] || die "File ${dbdump} does not exist" 2;

dbhost=$(awk -F: '{print $1}' "${DBCONF}");
dbport=$(awk -F: '{print $2}' "${DBCONF}");
dbuser=$(awk -F: '{print $4}' "${DBCONF}");
dbpass=$(awk -F: '{print $5}' "${DBCONF}");

[ -n "${dbhost}" ] || die "Could not find dbhost in user conf ${DBCONF}" 3;
[ -n "${dbport}" ] || die "Could not find dbport in user conf ${DBCONF}" 4;
[ -n "${dbuser}" ] || die "Could not find dbuser in user conf ${DBCONF}" 5;
[ -n "${dbpass}" ] || die "Could not find dbpass in user conf ${DBCONF}" 6;

if [ "1" == "${SUPERUSER}" ] && [ -f "${DBCONF}" ];
then
    rm -f "${DBCONF}";
    DBCONF="${TEMP_DIR}/.${dbname}.pgpass.conf";
fi;

RESTORE_BIN="/usr/local/bin/pg_restore";
[ -x "${RESTORE_BIN}" ] || RESTORE_BIN="/usr/bin/pg_restore";
[ -x "${RESTORE_BIN}" ] || RESTORE_BIN="/bin/pg_restore";
[ -x "${RESTORE_BIN}" ] || die "Could not find ${RESTORE_BIN}" 10;

PSQL_BIN="/usr/local/bin/psql";
[ -x "${PSQL_BIN}" ] || PSQL_BIN="/usr/bin/psql";
[ -x "${PSQL_BIN}" ] || PSQL_BIN="/bin/psql";
[ -x "${PSQL_BIN}" ] || die "PostgreSQL is not installed: ${PSQL_BIN}!" 20;

UNZIP_BIN="/usr/local/bin/unzip";
[ -x "${UNZIP_BIN}" ] || UNZIP_BIN="/usr/bin/unzip";
[ -x "${UNZIP_BIN}" ] || UNZIP_BIN="/bin/unzip";
[ -x "${UNZIP_BIN}" ] || die "ZIP is not installed: ${UNZIP_BIN}!" 30;

# ======================================================================================================== #

TMP_DIR=$(mktemp -d "${TEMP_DIR}.XXXXXXXXXX");
DBDUMP_ORIG="${dbdump}";
trap egress EXIT;

de "Writing LOGS to ${LOG_FILE}";

do_process_file "${dbdump}";

de "Ended...bye";

exit 0;
