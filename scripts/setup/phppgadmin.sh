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

PSQL_ADMIN="diradmin";
PSQL_CONF="/usr/local/directadmin/plugins/postgresql/pgpass.conf";
PSQL_USER_CONF="/usr/local/directadmin/.pgpass";

PSQL_BIN="/usr/local/bin/psql";
[ ! -x "${PSQL_BIN}" ] && PSQL_BIN="/usr/bin/psql";
[ ! -x "${PSQL_BIN}" ] && echo "[ERROR] PostgreSQL is not installed! You should first install PostgreSQL." && exit 1;

GIT_BIN="/usr/local/bin/git";
[ ! -x "${GIT_BIN}" ] && GIT_BIN="/usr/bin/git";
[ ! -x "${GIT_BIN}" ] && echo "[ERROR] Git is not installed! You should first install git." && exit 2;

RSYNC_BIN="/usr/local/bin/rsync";
[ ! -x "${RSYNC_BIN}" ] && RSYNC_BIN="/usr/bin/rsync";
[ ! -x "${RSYNC_BIN}" ] && echo "[ERROR] Rsync is not installed! You should first install rsync." && exit 3;

cd /usr/local/src;
rm -rf ./phppgadmin/;
${GIT_BIN} clone https://github.com/phppgadmin/phppgadmin.git
cd ./phppgadmin/ && VER=$(${GIT_BIN} tag | sort -rn | grep -m1 ^REL_ | cut -d_ -f2);
echo "";

if [ -z "${VER}" ]; then
    echo "[ERROR] Failed to identify version of phpPgAdmin";
    exit 10;
fi;

VER=${VER//-/.};

CONF_DIST="/usr/local/directadmin/plugins/postgresql/scripts/setup/phpPgAdmin-${VER}/config.inc.php-dist";
CONF_FILE="/var/www/html/phpPgAdmin/conf/config.inc.php";

if [ ! -f "${CONF_DIST}" ]; then
    echo "[ERROR] Not supported verision of phpPgAdmin-${VER}";
    exit 20;
fi;

# ==========================================================================
echo "[OK] Going to install phpPgAdmin-${VER}";
# ==========================================================================
rm -rf /var/www/html/phpPgAdmin* 2>/dev/null;
${RSYNC_BIN} -avz ./ "/var/www/html/phpPgAdmin-${VER}/";
ln -s "/var/www/html/phpPgAdmin-${VER}" "/var/www/html/phpPgAdmin";
chown -R webapps:webapps "/var/www/html/phpPgAdmin-${VER}";
chown -h webapps:webapps "/var/www/html/phpPgAdmin";

# ==========================================================================
echo "[OK] Updating config file of phpPgAdmin-${VER}";
# ==========================================================================
cp -p "${CONF_DIST}" "${CONF_FILE}";
chmod 600 "${CONF_FILE}";
chown webapps:webapps "${CONF_FILE}";

# ==========================================================================
echo "[OK] Rewriting web-server configs";
# ==========================================================================
cd /usr/local/directadmin/custombuild;
mkdir -p custom;
c=$(grep -m1 -c "^phppgadmin=" custom/webapps.list 2>/dev/null);
if [ "0" == "${c}" ]; then
    echo "phppgadmin=phpPgAdmin" >> custom/webapps.list;
    ./build rewrite_confs
fi;

# ==========================================================================
echo "[OK] Patching phpPgAdmin-${VER}";
# ==========================================================================
PATCH_SRC="/usr/local/directadmin/plugins/postgresql/scripts/setup/phpPgAdmin-${VER}/Postgres.php.patch";
PATCH_TGT="/var/www/html/phpPgAdmin/classes/database/Postgres.php";
if [ -e "${PATCH_SRC}" ]; then
    cd $(dirname "${PATCH_TGT}") && patch --ignore-whitespace -p1 < "${PATCH_SRC}";
    cd -;
else
    echo "[ERROR] Could not find patch for phpPgAdmin-${VER}";
fi;

# ==========================================================================
echo "[OK] Completed...";
# ==========================================================================

exit 0;
