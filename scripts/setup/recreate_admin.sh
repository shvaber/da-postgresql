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

for ARG in "$@"
do
    case "${ARG}" in
        --strict)
            STRICT="--strict";
        ;;
        --debug)
            DEBUG="--debug";
        ;;
    esac;
done;

/usr/local/directadmin/plugins/postgresql/scripts/setup/drop_admin.sh "${STRICT}" "${DEBUG}";
/usr/local/directadmin/plugins/postgresql/scripts/setup/create_admin.sh "${STRICT}" "${DEBUG}";

exit 0;
