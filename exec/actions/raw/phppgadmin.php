<?php
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

$is_error = true;

$database = (isset($_GET['database']) && $_GET['database']) ? $_GET['database'] : false;
$databases = array();
$dbowner = false;

if ($pg_user_databases = $pg->getDatabasesList($USER))
{
    foreach ($pg_user_databases as $row)
    {
        if (($USER === $row['owner']) || (strpos($row['owner'], $USER."_") === 0))
        {
            $databases[] = $row['name'];
            $dbowner = ($database == $row['name']) ? $row['owner'] : $dbowner;
        }
    }
    $database = ($database === false) ? $databases[0] : $database;
    if (!in_array($database, $databases))
    {
        print "Cache-Control: no-cache, must-revalidate\n";
        print "Content-type: text/html\n\n";

        $is_error = true;
        $error_message = $da->get_lang('ERROR_MESSAGE_FAILED_PHPPGADMIN');
        $error_details = $da->get_lang('ERROR_DETAILS_FAILED_OWNER');
        $error_code = "PHPPGADMIN_100";
        return false;
    }
}


$PHPPGADMIN_URL = 'https://'. $_SERVER['SERVER_NAME'] .'/phppgadmin/';
$PHPPGADMIN_LOGIN_URL = $PHPPGADMIN_URL .'index.php?nc=0.'.uniqid(time());
$PHPPGADMIN_LOGIN_SERVER = PG_HOST . ':'.PG_PORT.':allow';
$TPL_DATA = [
        'FORM_ACTION'    => $PHPPGADMIN_LOGIN_URL,
        'FORM_SUBJECT'   => 'server',
        'FORM_SERVER'    => $PHPPGADMIN_LOGIN_SERVER,
        'LOGIN_SERVER'   => $PHPPGADMIN_LOGIN_SERVER,
        'LOGIN_DATABASE' => $database,
        'LOGIN_USERNAME' => $USER,
        'MD5_HASH'       => md5($PHPPGADMIN_LOGIN_SERVER),
        'LOGIN_PASSWORD' => '',
];

$sso_config_file = PLUGIN_SSO_DIR . '/user.'. $USER .'.pgpass.conf';

// DO WE HAVE AN USER CONFIG FOR PSQL
if (is_file($sso_config_file))
{
    $sso_pgconf = _get_pg_user_credentials($sso_config_file);
    if (isset($sso_pgconf['dbuser']) && $sso_pgconf['dbuser']) $TPL_DATA['LOGIN_USERNAME'] = $session_username = $sso_pgconf['dbuser'];
    if (isset($sso_pgconf['dbpass']) && $sso_pgconf['dbpass']) $TPL_DATA['LOGIN_PASSWORD'] = $session_password = $sso_pgconf['dbpass'];
    if (!$session_username || !$session_password) 
    {
        $is_error = true;
        $error_code = "PHPPGADMIN_110";
    }
    else
    {
        $is_error = false;
    }
}
else
{
    $da_sess_data = array();
    $da_sess_file = '/usr/local/directadmin/data/sessions/da_sess_'. $_SERVER['SESSION_ID'];
    if (is_file($da_sess_file) && ($_da_sess_data = file_get_contents($da_sess_file)))
    {
        $da_sess_data = [];
        if ($lines = explode("\n",$_da_sess_data))
        {
            foreach ($lines as $row)
            {
                if (strpos($row, "username=") === 0) $da_sess_data['username'] = substr($row, 0, strlen("username="));
                if (strpos($row, "passwd=") === 0) $da_sess_data['passwd'] = substr($row, 0, strlen("passwd="));
            }
        }

        $session_username = (isset($da_sess_data['username']) && $da_sess_data['username']) ? $da_sess_data['username'] : false;
        $session_password = (isset($da_sess_data['passwd']) && $da_sess_data['passwd']) ? base64_decode($da_sess_data['passwd']) : false;

        if ($session_username && ($session_username == $USER))
        {
            if ($session_password)
            {
                $is_error = false;
                $TPL_DATA['LOGIN_PASSWORD'] = $session_password;
            }
            else
            {
                // Password is empty - a hacking attempt?
                $is_error = true;
                $error_code = "PHPPGADMIN_120";
            }
        }
        else
        {
            $TPL_DATA['LOGIN_USERNAME'] = $session_username = $USER .'_sso_'. time();
            $TPL_DATA['LOGIN_PASSWORD'] = $session_password = randomPassword();
            if (_save_pg_user_credentials($sso_config_file, [
                'dbhost' => PG_HOST,
                'dbport' => PG_PORT,
                'dbname' => '*',
                'dbuser' => $session_username,
                'dbpass' => $session_password
            ])) {
                $pg->createUser($session_username, $session_password);
                if ($dbowner) $pg->grantRole2Role($dbowner, $session_username); else $pg->grantRole2Role($USER, $session_username);
                $is_error = false;
            }
        }
    }
    else
    {
        // Not authorized in DirectAdmin, or a hacking attempt
        $is_error = true;
        $error_code = "PHPPGADMIN_130";
    }
}

print "Cache-Control: no-cache, must-revalidate\n";
print "Content-type: text/html\n\n";

if ($is_error)
{
    if (!$error_message) $error_message = $da->get_lang('ERROR_MESSAGE_FAILED_PHPPGADMIN');
    if (!$error_details) $error_details = $da->get_lang('ERROR_DETAILS_FAILED_PHPPGADMIN');
    if (isset($error_code) && $error_code) $error_details .= "<br><br>Error code: ". $error_code;
}
else
{
    $HTML_CONTENT = _get_tpl(PLUGIN_TPL_DIR . '/'.PLUGIN_ACTION.'.html', $TPL_DATA);
    do_output($HTML_CONTENT);
}
