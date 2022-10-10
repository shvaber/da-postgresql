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
if (!defined('PLUGIN_IS_ADMIN') || (PLUGIN_IS_ADMIN !==true)){die("You're not allowed to view this page!");}
if (!defined('PLUGIN_ACTION')) {define('PLUGIN_ACTION','admin');}
if ( defined('IN_JSON_OUTPUT') && (IN_JSON_OUTPUT == true))
{
    print "HTTP/1.1 200 OK\n";
    print "Cache-Control: no-cache, must-revalidate\n";
    print "Content-type: application/json\n\n";
    define('FILE_TYPE', 'json');
}
else if ( defined('IN_RAW_OUTPUT') && (IN_RAW_OUTPUT == true))
{
    print "HTTP/1.1 200 OK\n";
    print "Cache-Control: no-cache, must-revalidate\n";
    print "Content-type: text/html\n\n";
    define('FILE_TYPE', 'raw');
}
else
{
    define('IN_HTML_OUTPUT', true);
    define('IN_JSON_OUTPUT', false);
    define('IN_RAW_OUTPUT', false);
    define('FILE_TYPE', 'html');
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

$da = new da();
$pg = new postgresql([
    'user' => PG_USER,
    'password' => PG_PASSWORD,
    'host' => PG_HOST,
    'port' => PG_PORT,
    'dbname' => false,
]);

// PROCESS ACTION
$action_file = sprintf("%s/%s/admin/%s.php", PLUGIN_ACTION_DIR, FILE_TYPE, basename(PLUGIN_ACTION));

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

// HTML pages should not go here
// This case is expected to be for JSON
// to avoid duplicating lines of code
if ($is_error == true)
{
    do_output(generate_page([
        'MESSAGE_HTML'         => false,
        'ERROR_HTML'           => $error_message .' '. $error_details,
        'SERVER_TIME'          => date(TIME_DATE_FORMAT),
        'OUTPUT_DATA'          => ['action' => PLUGIN_ACTION],
        'UUID'                 => gen_uuid(),
        ])
    );
}
else
{
    do_output(generate_page([
        'MESSAGE_HTML'         => $message_ok,
        'ERROR_HTML'           => false,
        'SERVER_TIME'          => date(TIME_DATE_FORMAT),
        'OUTPUT_DATA'          => ['action' => PLUGIN_ACTION],
        'UUID'                 => gen_uuid(),
        ])
    );
}

exit;
