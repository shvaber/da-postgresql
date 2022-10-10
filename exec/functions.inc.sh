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

de()
{
    if [ "1" == "${DEBUG}" ];
    then
        if [ -n "${1}" ]; then
            echo "[DEBUG] ${1}";
            return;
        else
            while read data; do echo "[DEBUG] ${data}"; done;
        fi;
    fi;
}

die()
{
    echo "[ERROR] ${1}";
    [ -n "${LOG_FILE}" ] && log "[ERROR] ${1}";
    exit "${2}";
}

