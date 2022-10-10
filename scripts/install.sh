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

set_permissions()
{
    if [ -n "${3}" ] && [ -e "${3}" ]; then
        chown ${1} ${3};
        chmod ${2} ${3};
    fi;
}

PLUGIN_DIR="/usr/local/directadmin/plugins/postgresql";
set_permissions diradmin:diradmin 755 "${PLUGIN_DIR}";
set_permissions diradmin:diradmin 700 "${PLUGIN_DIR}/admin";
set_permissions diradmin:diradmin 700 "${PLUGIN_DIR}/admin/index.html";
set_permissions diradmin:diradmin 700 "${PLUGIN_DIR}/user";
set_permissions diradmin:diradmin 700 "${PLUGIN_DIR}/create.html";
set_permissions diradmin:diradmin 700 "${PLUGIN_DIR}/create.raw";
set_permissions diradmin:diradmin 700 "${PLUGIN_DIR}/database.html";
set_permissions diradmin:diradmin 700 "${PLUGIN_DIR}/download.raw";
set_permissions diradmin:diradmin 700 "${PLUGIN_DIR}/index.html";
set_permissions diradmin:diradmin 700 "${PLUGIN_DIR}/restore.html";
set_permissions diradmin:diradmin 700 "${PLUGIN_DIR}/data";
set_permissions diradmin:diradmin 700 "${PLUGIN_DIR}/data/_css";
set_permissions diradmin:diradmin 700 "${PLUGIN_DIR}/data/_js";
set_permissions diradmin:diradmin 700 "${PLUGIN_DIR}/data/_tpl";
set_permissions diradmin:diradmin 700 "${PLUGIN_DIR}/data/sso";
set_permissions diradmin:diradmin 700 "${PLUGIN_DIR}/exec";
set_permissions diradmin:diradmin 700 "${PLUGIN_DIR}/exec/actions";
set_permissions diradmin:diradmin 700 "${PLUGIN_DIR}/exec/actions/html";
set_permissions diradmin:diradmin 700 "${PLUGIN_DIR}/exec/actions/shell";
set_permissions diradmin:diradmin 700 "${PLUGIN_DIR}/exec/actions/html/admin";

[ -d "${PLUGIN_DIR}/logs" ] || mkdir "${PLUGIN_DIR}/logs";
set_permissions diradmin:diradmin 700 "${PLUGIN_DIR}/logs";

# CUSTOM PACKAGE ITEMS
CPIF="/usr/local/directadmin/data/admin/custom_package_items.conf";
CPIL="postgresql=type=text&string=PostgreSQL Databases&desc=Allow to create PostgreSQL databases&default=5";

if [ -f "${CPIF}" ];
then
    INSTALL_CPIF=0;
    c=$(grep -m1 -c "^postgresql=" "${CPIF}");
    if [ "${c}" -eq "0" ];
    then
        INSTALL_CPIF=1;
    fi;
else
    INSTALL_CPIF=1;
fi;

if [ "$INSTALL_CPIF" -eq "1" ];
then
    echo "${CPIL}" >> "${CPIF}";
    set_permissions diradmin:diradmin 640 "${CPIF}";
fi;

# WRAPPER
GCC="/usr/bin/gcc";
[ -x "${GCC}" ] || GCC="/usr/local/bin/gcc";
[ -x "${GCC}" ] || GCC="/bin/gcc";
${GCC} -std=gnu99 -B/usr/bin -o "${PLUGIN_DIR}/exec/move_uploaded_file" "${PLUGIN_DIR}/exec/move_uploaded_file.c" >> /dev/null 2>&1;
set_permissions root:diradmin 4550 "${PLUGIN_DIR}/exec/move_uploaded_file";

# ACTIVATE PLUGIN
PL_CONF="${PLUGIN_DIR}/plugin.conf";
perl -pi -e "s#active=no#active=yes#" ${PL_CONF};
perl -pi -e "s#installed=no#installed=yes#" ${PL_CONF};

echo "Plugin has been installed and activated!<br>Make sure to update your users packages details!";
exit 0;
