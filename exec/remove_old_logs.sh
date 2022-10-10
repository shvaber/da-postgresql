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

DAYS="${1:-14}";
LOG_DIR="/usr/local/directadmin/plugins/postgresql/logs";
NUM='^[0-9]+$';

if [ -d "${LOG_DIR}" ];
then
    if [[ ${DAYS} =~ ${NUM} ]];
    then
        echo "[OK] Removing logs from ${LOG_DIR} older than ${DAYS} days";
        find "${LOG_DIR}" -name \*.log -type f -mtime +${DAYS} -exec rm -fv {} \;
    else
        echo "[OK] Removing all logs from ${LOG_DIR}";
        find "${LOG_DIR}" -name \*.log -type f -exec rm -fv {} \;
    fi;
fi;

exit 0;
