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

/usr/local/directadmin/plugins/postgresql/scripts/uninstall.sh >/dev/null 2>&1;
/usr/local/directadmin/plugins/postgresql/scripts/install.sh >/dev/null 2>&1;

echo "Plugin has been updated!";
exit 0;
