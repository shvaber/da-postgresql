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
# - filename=/path/to/the/user.tar.gz

USERNAME="${1}";
RESELLER="${2}";
FILE="${3}";
SCRIPT="/usr/local/directadmin/plugins/postgresql/exec/dbimportuser.sh";
LOG_DIR="/usr/local/directadmin/plugins/postgresql/logs";

do_restore()
{
    if [ -n "${USERNAME}" ] && [ -n "${FILE}" ] && [ -f "${FILE}" ];
    then
    {
        "${SCRIPT}" "${USERNAME}" "${FILE}" > ${LOG_DIR}/restore.user.${USERNAME}.$(date +%Y%m%d.%s).log 2>&1;
    }
    else
    {
        # Error
        echo "No user selected....";
    }
    fi;
}

do_restore;

exit 0;
