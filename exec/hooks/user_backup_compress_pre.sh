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

# DirectAdmin variables:
# - username=joe
# - reseller=resellername
# - file=/path/to/the/user.tar.gz

#set -x

USERNAME="${1}";
RESELLER="${2}";
FILE="${3}";
SCRIPT="/usr/local/directadmin/plugins/postgresql/exec/dbbackupuser.sh";
LOG_DIR="/usr/local/directadmin/plugins/postgresql/logs";

do_backup()
{
    WHOAMI="$(whoami)";

    if [ -n "${USERNAME}" ] && [ -n "${RESELLER}" ] && [ -n "${FILE}" ];
    then
    {
        USER_HOMEDIR="$(grep "^${USERNAME}:" /etc/passwd | cut -d: -f6)";
        RESELLER_HOMEDIR="$(grep "^${RESELLER}:" /etc/passwd | cut -d: -f6)";

        # USER LEVEL BACKUPS: =/home/${USERNAME}/backups
        # RESELLER LEVEL BACKUPS: =/home/${RESELLER}/user_backups
        # ADMIN LEVEL BACKUPS: =/home/???/admin_backups/???
        BACKUP_DIR="$(dirname ${FILE})";

        if [ "${BACKUP_DIR}" == "${USER_HOMEDIR}/backups" ];
        then
            # USER_LEVEL BACKUPS
            BACKUP_DIR="$(dirname ${FILE})/backup";
        elif [ "${BACKUP_DIR}" == "${RESELLER_HOMEDIR}/user_backups" ];
        then
            # RESELLER LEVEL BACKUPS
            BACKUP_DIR="$(dirname ${FILE})/${USERNAME}/backup";
        else
            # ADMIN LEVEL BACKUPS
            BACKUP_DIR="$(dirname ${FILE})/${USERNAME}/backup";
        fi;

        PSQL_BACKUP_DIR="${BACKUP_DIR}/psql/";
        echo "DirectAdmin is backuping user ${USERNAME} into temporary folder ${BACKUP_DIR}";
        if [ -d "${BACKUP_DIR}" ];
        then
            echo "PostgreSQL will be backupped to ${PSQL_BACKUP_DIR} and then compressed";
            mkdir -v "${PSQL_BACKUP_DIR}";
            chmod -v 700 "${PSQL_BACKUP_DIR}";
            "${SCRIPT}" "${USERNAME}" "${PSQL_BACKUP_DIR}";
            chown -v -R "${USERNAME}:${USERNAME}" "${PSQL_BACKUP_DIR}";
            echo "PostgreSQL backup completed:";
            ls -la "${PSQL_BACKUP_DIR}";
        fi;
    }
    else
    {
        # Error
        echo "No user selected....";
    }
    fi;
}

do_backup > ${LOG_DIR}/backup.user.${USERNAME}.$(date +%Y%m%d.%s).log 2>&1;

exit 0;
