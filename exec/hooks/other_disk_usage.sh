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


USERNAME="${1}";

if [ -n "${USERNAME}" ];
then
    result=$(/usr/local/directadmin/plugins/postgresql/exec/dbsize.sh ${USERNAME});
    c=$(echo "${result}" | grep -m1 -c -i "ERROR");
    if [ "1" == "${c}" ];
    then
        # Error
        echo "other_quota=0";
    else
        # OK
        echo "other_quota=${result}";
    fi;
else
    # Error
    echo "other_quota=0";
fi;

exit 0;
