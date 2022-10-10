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

PLUGIN_DIR="/usr/local/directadmin/plugins/postgresql";
CPIF="/usr/local/directadmin/data/admin/custom_package_items.conf";

if [ -f "${CPIF}" ];
then
    c=$(grep -m1 -c "^postgresql=" "${CPIF}");
    if [ "${c}" -gt "0" ];
    then
        cat "${CPIF}" | grep -v "^postgresql=" > "${CPIF}.new";
        cat "${CPIF}.new" > "${CPIF}";
    fi;
fi;

PL_CONF="${PLUGIN_DIR}/plugin.conf";
if [ -f "${PL_CONF}" ]; then
    perl -pi -e "s#active=.*#active=no#" "${PL_CONF}";
    perl -pi -e "s#installed=.*#installed=no#" "${PL_CONF}";
fi;

echo "Plugin has been removed!";
exit 0;
