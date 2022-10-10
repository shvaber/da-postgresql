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

ignore_user_abort(true);
set_time_limit(0);
error_reporting(E_ALL);

if (!defined('IN_DA_PLUGIN') || (IN_DA_PLUGIN !==true)){die("You're not allowed to view this page!");}
if (!defined('PLUGIN_ACTION')) {define('PLUGIN_ACTION','home');}
if ( defined('IN_JSON_OUTPUT') && (IN_JSON_OUTPUT == true))
{
    print "HTTP/1.1 200 OK\n";
    print "Cache-Control: no-cache, must-revalidate\n";
    print "Content-type: application/json\n\n";
    define('FILE_TYPE', 'json');
    $append_go_back_link_on_error = false;
}
else if ( defined('IN_RAW_OUTPUT') && (IN_RAW_OUTPUT == true))
{
    print "HTTP/1.1 200 OK\n";
    print "Cache-Control: no-cache, must-revalidate\n";
    define('FILE_TYPE', 'raw');
    $append_go_back_link_on_error = true;
    // ALL THE OTHER HEADERS WILL BE SENT LATER
}
else if ( defined('IN_DOWNLOAD_OUTPUT') && (IN_DOWNLOAD_OUTPUT == true))
{
    print "HTTP/1.1 200 OK\n";
    define('FILE_TYPE', 'raw');
    $append_go_back_link_on_error = false;
    // ALL THE OTHER HEADERS WILL BE SENT LATER
}
else
{
    define('IN_HTML_OUTPUT', true);
    define('IN_JSON_OUTPUT', false);
    define('IN_RAW_OUTPUT', false);
    define('FILE_TYPE', 'html');
    $append_go_back_link_on_error = true;
}


require_once('functions.inc.php');
require_once(PLUGIN_EXEC_DIR . '/class.inc.php');
require_once(PLUGIN_EXEC_DIR . '/class.postgresql.inc.php');
if  (is_file(PLUGIN_EXEC_DIR . '/settings.local.inc.php')) {require_once(PLUGIN_EXEC_DIR . '/settings.local.inc.php');}
require_once(PLUGIN_EXEC_DIR . '/settings.inc.php');

parse_input();
_get_pg_credentials();

$is_error = false;
$message_ok = false;
$error_message = false;
$error_details = false;

$USER = isset($_SERVER['USER']) ? $_SERVER['USER'] : '';
$HOME = isset($_SERVER['HOME']) ? $_SERVER['HOME'] : '';
$SESSION_DOMAIN = isset($_SERVER['SESSION_SELECTED_DOMAIN']) ? $_SERVER['SESSION_SELECTED_DOMAIN'] : '';


$da = new da();
$pg = new postgresql([
    'user' => PG_USER,
    'password' => PG_PASSWORD,
    'host' => PG_HOST,
    'port' => PG_PORT,
    'dbname' => false,
]);

// PROCESS ACTION
$action_file = sprintf("%s/%s/%s.php", PLUGIN_ACTION_DIR, FILE_TYPE, basename(PLUGIN_ACTION));

$PGSQL_USER_LIMIT = $da->get_user_data('postgresql');
$PGSQL_USER_USAGE = $pg->getDatabasesCount($USER);
$PGSQL_USER_USAGE_SIZE = $pg->getDatabasesSize($USER);

if (is_null($PGSQL_USER_LIMIT) || ($PGSQL_USER_LIMIT == 0) || !$PGSQL_USER_LIMIT)
{
    $is_error = true;
    $error_message = $da->get_lang('ERROR_MESSAGE_PGSQL_NOT_ALLOWED');
    $error_details = $da->get_lang('ERROR_DETAILS_PGSQL_NOT_ALLOWED');
    $action_file = sprintf("%s/%s/%s.php", PLUGIN_ACTION_DIR, FILE_TYPE, 'error');
}
else
{
    if (is_file($action_file))
    {
        require_once($action_file);
    }
    else
    {
        $is_error = true;
        $error_message = $da->get_lang('ERROR_MESSAGE_UNKNOWN_ACTION');
        $error_details = $da->get_lang('ERROR_DETAILS_UKNOWN_DETAILS');
    }
}

// HTML pages should not go here
// This case is expected to be for JSON
// to avoid duplicating lines of code
if ($is_error == true)
{
    $append_go_back_link = $append_go_back_link_on_error ?  $da->get_lang('PLUGIN_GO_BACK_LINK') : '';

    $FINAL_CONTENT = generate_page([
        'MESSAGE_HTML'            => false,
        'ERROR_HTML'              => $error_message .': '. $error_details."<br><br>". $append_go_back_link,
        'SERVER_TIME'             => date(TIME_DATE_FORMAT),
        'OUTPUT_DATA'             => ['action' => PLUGIN_ACTION],
        'UUID'                    => gen_uuid(),
        'MAIN_CONTENT'            => '',
        'PLUGIN_FOOTER_TITLE'     => '',
        'PLUGIN_HOME_TITLE'            => $da->get_lang('PLUGIN_HOME_TITLE'),
        'PLUGIN_HOME_DESCRIPTION'      => $da->get_lang('PLUGIN_HOME_DESCRIPTION'),
        'PLUGIN_PHPPGADMIN_CLASS'      => ' sr-only',
        'PLUGIN_PHPPGADMIN_LINK'       => PHPPGADMIN_LINK,
        'PLUGIN_SELECTED_DATABASE'     => (isset($database) && $database) ? $database : ((isset($dbname) && $dbname) ? $dbname : false),
        'PLUGIN_PHPPGADMIN_CONNECT'    => $da->get_lang('PLUGIN_CONNECT_PHPPGADMIN'),
    ]);

    if (defined('IN_RAW_OUTPUT') && (IN_RAW_OUTPUT == true))
    {
        $FINAL_CONTENT = _get_tpl(PLUGIN_TPL_BODY, [
            'LANG'  => strtolower($_SERVER["LANGUAGE"]),
            'TITLE' => PLUGIN_NAME,
            'BODY'  => $FINAL_CONTENT
        ]);
    }

    do_output($FINAL_CONTENT);
}
else
{
    do_output(generate_page([
        'MESSAGE_HTML'         => $message_ok,
        'ERROR_HTML'           => false,
        'SERVER_TIME'          => date(TIME_DATE_FORMAT),
        'OUTPUT_DATA'          => ['action' => PLUGIN_ACTION],
        'UUID'                 => gen_uuid(),
        'MAIN_CONTENT'         => '',
        'PLUGIN_FOOTER_TITLE'  => '',
        'PLUGIN_HOME_TITLE'            => $da->get_lang('PLUGIN_HOME_TITLE'),
        'PLUGIN_HOME_DESCRIPTION'      => $da->get_lang('PLUGIN_HOME_DESCRIPTION'),
        'PLUGIN_PHPPGADMIN_CLASS'      => '',
        'PLUGIN_PHPPGADMIN_LINK'       => PHPPGADMIN_LINK,
        'PLUGIN_SELECTED_DATABASE'     => (isset($database) && $database) ? $database : ((isset($dbname) && $dbname) ? $dbname : false),
        'PLUGIN_PHPPGADMIN_CONNECT'    => $da->get_lang('PLUGIN_CONNECT_PHPPGADMIN'),
        ])
    );
}

exit;
