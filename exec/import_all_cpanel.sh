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

[ ! -f "/usr/local/directadmin/plugins/postgresql/exec/functions.inc.sh" ] && echo "[ERROR] Corrupted installation" && exit 1;
source /usr/local/directadmin/plugins/postgresql/exec/functions.inc.sh;

SOURCE_DIR="${1:-/home/admin/admin_backups}";
LOG_DIR="/usr/local/directadmin/plugins/postgresql/logs";

if [ -d "${SOURCE_DIR}" ];
then
    for FILE in $(find "${SOURCE_DIR}" -name cpmove-\*.tar.gz -type f);
    do
        if [ -s "${FILE}" ];
        then
            tmp=$(basename "${FILE}" .tar.gz);
            username=${tmp/cpmove-/};
            LOG_FILE="${LOG_DIR}/cpmove-import.${username}.log";
            if [ -d "/usr/local/directadmin/data/users/${username}" ] && [ -f "/usr/local/directadmin/data/users/${username}/user.conf" ];
            then
                echo "[OK] Found a cPanel backup for user ${username} in ${FILE}. Going to import PostgreSQL data from it.";
                DEBUG=1 /usr/local/directadmin/plugins/postgresql/exec/dbimportuser.sh "${username}" "${FILE}" > ${LOG_FILE} 2>&1;
            else
                echo "[WARNING] User ${username} does not exist in DirectAdmin! You should first create or import the user in DirectAdmin!";
                continue;
            fi;
        else
            echo "File ${FILE} is empty...";
            continue;
        fi;
    done;
else
    echo "Directory ${SOURCE_DIR} does not exist!";
    exit 1;
fi;

exit 0;
