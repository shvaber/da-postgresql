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

$type = (isset($_GET['type']) && $_GET['type']) ? $_GET['type'] : false;
$value = (isset($_GET['value']) && $_GET['value']) ? $_GET['value'] : false;

if (($type == "username") && $value)
{
    $dbuser = $USER."_".$value;
    $pg_dbusers = $pg->getUsersList($USER);
    if (in_array($dbuser, $pg_dbusers))
    {
        $is_error = true;
        $error_message = 'User exists';
        $error_details = '';
        return false;
    }
}

if ($is_error)
{
    $error_message = false;
    $error_details = false;
    $message_ok = false;
}
else
{
    $is_error = false;
    $error_message = false;
    $error_details = false;
    $message_ok = 'OK';
}
