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
$completed = false;

if (isset($_POST) && $_POST)
{
    $dbname = (isset($_POST['dbname']) && $_POST['dbname']) ? $_POST['dbname'] : false;
    $dbusers = (isset($_POST['dbusers']) && $_POST['dbusers']) ? $_POST['dbusers'] : false;
    $userselected = (isset($_POST['userselected']) && $_POST['userselected']) ? $_POST['userselected'] : false;
    $dbusers_selected = array();
    $dbuser = false;

    $dbusers_removed = array();
    $pg_user_databases = array();

    // Databases of an user
    if ($_pg_user_databases = $pg->getDatabasesList($USER))
    {
        // Check Database owner
        foreach ($_pg_user_databases as $row)
        {
            if (($USER === $row['owner']) || (strpos($row['owner'], $USER."_") === 0))
            {
                $pg_user_databases[] = $row['name'];
                $dbowner = ($dbname == $row['name']) ? $row['owner'] : $dbowner;
            }
        }
        if (!in_array($dbname, $pg_user_databases))
        {
            $is_error = true;
            $error_message = $da->get_lang('ERROR_MESSAGE_FAILED_ACTION_ON_DB');
            $error_details = $da->get_lang('ERROR_DETAILS_FAILED_OWNER');
            return false;
        }
    }

    // Validate data and prepare list of selected users
    foreach($dbusers as $key => $val)
    {
        $id = $key;// + 1;
        if (is_array($userselected) && in_array($id, $userselected))
        {
            if (($USER === $val) || (strpos($val, $USER."_") === 0))
            {
                if (!in_array($dbuser, $dbusers_selected)) $dbusers_selected[] = $val;
            }
        }
    }

    // Go through the list of selected users
    if ($dbusers_selected)
    {
        $pg = new postgresql([
            'user' => PG_USER,
            'password' => PG_PASSWORD,
            'host' => PG_HOST,
            'port' => PG_PORT,
            'dbname' => $dbname,
        ]);

        foreach ($dbusers_selected as $dbuser)
        {
            // Revoke membership in role $USER
            $pg->revokeRoleFromRole($USER, $dbuser);

            if ($dbowner !== $dbuser)
            {
                // Revoke membership in role $dbowner
                $pg->revokeRoleFromRole($dbowner, $dbuser);
            }

            // Revoke privileges from role to database
            $pg->revokeRoleFromDatabase($dbuser, $dbname);

            // get list of DBs to which the user has grants
            $databases = $pg->getGrantedDatabasesList($dbuser);
            if (sizeof($databases) == 0)
            {
                $pg->removeUser($dbuser);
            }

            $is_error = false;
            if (!in_array($dbuser, $dbusers_removed)) $dbusers_removed[] = $dbuser;
        }
    }
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
    $message_ok = sprintf($da->get_lang('OK_MESSAGE_COMPLETED_ACTION_ON_USERS'), PLUGIN_ACTION, implode(', ', $dbusers_removed), $dbname);
}
