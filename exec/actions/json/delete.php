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

$dbnames = (isset($_POST["dbnames"]) && $_POST["dbnames"]) ? $_POST["dbnames"] : false;
$dbselected = (isset($_POST["dbselected"]) && $_POST["dbselected"]) ? $_POST["dbselected"] : false;
$pg_user_databases = array();
$_pg_user_databases = array();
$user_selected_databases = array();
$user_processed_databases = array();

if ($_pg_user_databases = $pg->getDatabasesList($USER))
{
    foreach ($_pg_user_databases as $row)
    {
        if (($USER === $row['owner']) || (strpos($row['owner'], $USER."_") === 0))
        {
            $pg_user_databases[] = $row['name'];
        }
    }
    foreach ($dbnames as $key => $val)
    {
        $id = $key + 1;
        if (is_array($dbselected) && in_array($id, $dbselected))
        {
            $user_selected_databases[] = $val;
        }
    }
    foreach ($user_selected_databases as $database)
    {
        if (!in_array($database, $pg_user_databases))
        {
            $is_error = true;
            $error_message = $da->get_lang('ERROR_MESSAGE_FAILED_ACTION_ON_DB');
            $error_details = $da->get_lang('ERROR_DETAILS_FAILED_OWNER');
            return false;
        }
        if ($pg->doDeleteDB($database))
        {
            $user_processed_databases[] = $database;
        }
    }
}
else
{
    $is_error = true;
    $error_message = $da->get_lang('ERROR_MESSAGE_FAILED_ACTION_ON_DB');
    $error_details = $da->get_lang('ERROR_DETAILS_FAILED_LIST_DATABASES');
}

if ($is_error)
{
    if (!$error_message) $error_message = $da->get_lang('ERROR_MESSAGE_FAILED_ACTION_ON_DB');
    if (!$error_details) $error_details = $da->get_lang('ERROR_DETAILS_FAILED_ACTION_ON_DB');
    $message_ok = false;
}
else
{
    $is_error = false;
    $error_message = false;
    $error_details = false;
    $message_ok = sprintf($da->get_lang('OK_MESSAGE_COMPLETED_ACTION_ON_DB'), PLUGIN_ACTION, implode(', ', $user_processed_databases));
}
