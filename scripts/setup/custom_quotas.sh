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

action="${1}";

DA_BIN="/usr/local/directadmin/directadmin";
HOOK_FILE="/usr/local/directadmin/scripts/custom/other_disk_usage.sh";
SRC_FILE="/usr/local/directadmin/plugins/postgresql/scripts/setup/other_disk_usage.sh.src";

do_enable()
{
    if [ -x "${HOOK_FILE}" ];
    then
        f1=$(md5sum "${HOOK_FILE}" | awk '{print $1}');
        f2=$(md5sum "${SRC_FILE}" | awk '{print $1}');
        if [ "${f1}" == "${f2}" ]; 
        then
            echo "[OK] The script ${HOOK_FILE} is already installed! No action is required.";
        else
            echo "[WARNING] The script ${HOOK_FILE} already exists!";
            echo "[WARNING] It contains the following lines probably installed by the plugin:"
            echo "==========================================================================="
            grep PostgreSQL "${HOOK_FILE}";
            echo "==========================================================================="
            echo "[WARNING] You might need to compare it manually with ${SRC_FILE}";
        fi;
    else
        touch "${HOOK_FILE}";
        chmod 700 "${HOOK_FILE}";
        chown diradmin:diradmin "${HOOK_FILE}";
        cat "${SRC_FILE}" > "${HOOK_FILE}";
        echo "[OK] Installing script ${HOOK_FILE}";
    fi;
    echo "[OK] Updating settings in DirectAdmin: $(${DA_BIN} set count_other_disk_usage 1 restart)";
}

do_disable()
{
    if [ -x "${HOOK_FILE}" ];
    then
        f1=$(md5sum "${HOOK_FILE}" | awk '{print $1}');
        f2=$(md5sum "${SRC_FILE}" | awk '{print $1}');
        if [ "${f1}" == "${f2}" ]; 
        then
            echo "[OK] The script ${HOOK_FILE} has been removed! No action is required.";
            rm -f "${HOOK_FILE}";
        else
            echo "[WARNING] The script ${HOOK_FILE} exists!";
            echo "[WARNING] It contains the following lines probably installed by the plugin:"
            echo "==========================================================================="
            grep PostgreSQL "${HOOK_FILE}";
            echo "==========================================================================="
            echo "[WARNING] You might need to review and clean it manually";
        fi;
    else
        echo "[OK] The script ${HOOK_FILE} is not installed! No action is required.";
    fi;
    echo "[OK] Updating settings in DirectAdmin: $(${DA_BIN} set count_other_disk_usage 0 restart)";
}

if [ ! -x "${DA_BIN}" ];
then
    echo "Error: DirectAdmin not found!";
    exit 1;
fi;

case "${action}" in
    enable)
        do_enable;
    ;;
    disable)
        do_disable;
    ;;
    *)
        echo "Usage: $0 <enable|disable>";
    ;;
esac;

exit 0;
