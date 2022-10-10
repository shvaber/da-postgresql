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
    $mode   = (isset($_POST['mode']) && $_POST['mode']) ? $_POST['mode'] : false;
    $dbuser_new = (isset($_POST['dbuser_new']) && $_POST['dbuser_new']) ? $_POST['dbuser_new'] : false;
    $dbuser_existing = (isset($_POST['dbuser_existing']) && $_POST['dbuser_existing']) ? $_POST['dbuser_existing'] : false;
    $dbuser = (isset($_POST['dbuser']) && $_POST['dbuser']) ? $_POST['dbuser'] : false;
    $dbname = (isset($_POST['dbname']) && $_POST['dbname']) ? $_POST['dbname'] : false;
    $dbpass = (isset($_POST['dbpass']) && $_POST['dbpass']) ? $_POST['dbpass'] : false;
    $dbowner = false;

    $pg_user_databases = array();
    $pg_db_users = array();

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

        // List all users who has access to the selected database
        if ($_pg_all_privileges = $pg->getPrivilegesList($dbname))
        {
            foreach ($_pg_all_privileges as $row)
            {
                $pg_db_users[] = $row['user'];
            }
        }

        // CREATE NEW USER
        if ($dbuser_new && $dbpass && $dbname && ($mode === 'create'))
        {
            $dbuser = $USER ."_". $dbuser_new;

            if (in_array($dbuser, $pg_db_users))
            {
                $is_error = true;
                $error_message = $da->get_lang('ERROR_MESSAGE_FAILED_ACTION_ON_DB');
                $error_details = $da->get_lang('ERROR_DETAILS_FAILED_DBUSER_EXISTS');
                return false;
            }
            $pg = new postgresql([
                'user'     => PG_USER,
                'password' => PG_PASSWORD,
                'host'     => PG_HOST,
                'port'     => PG_PORT,
                'dbname'   => $dbname,
            ]);
            if ($completed = $pg->createNewUser($dbuser, $dbname, $dbpass))
            {
                $is_error = false;
                if ($dbowner) $pg->grantRole2Role($dbowner, $dbuser);
                $message_ok = sprintf($da->get_lang('OK_MESSAGE_USER_CREATED'), $dbuser, $dbpass, $dbname, PG_HOST, PG_PORT, $dbname);
                return true;
            }
            else
            {
                $is_error = true;
            }
        }

        // CHANGE PASSWORD
        if ($dbuser && $dbpass && ($mode === 'password'))
        {
            // Check if an user $dbuser has access to the database $dbname
            if (!in_array($dbuser, $pg_db_users))
            {
                $is_error = true;
                $error_message = $da->get_lang('ERROR_MESSAGE_FAILED_ACTION_ON_DB');
                $error_details = $da->get_lang('ERROR_DETAILS_FAILED_DBUSER');
                return false;
            }

            // Does ?
            if (($USER !== $dbuser) && (strpos($dbuser, $USER."_")!==0))
            {
                $is_error = true;
                $error_message = $da->get_lang('ERROR_MESSAGE_FAILED_ACTION_ON_DB');
                $error_details = $da->get_lang('ERROR_DETAILS_FAILED_DBUSER');
                return false;
            }

            if ($completed = $pg->changeUserPassword($dbuser, $dbpass))
            {
                $is_error = false;
                $message_ok = sprintf($da->get_lang('OK_MESSAGE_PASSWORD_CHANGED'), $dbuser, $dbpass, $dbname, PG_HOST, PG_PORT, $dbname);
                return true;
            }
            else
            {
                $is_error = true;
            }
        }

        // ADD EXISTING USER
        if ($dbuser_existing && $dbname && ($mode === 'existing'))
        {
            // DOES USER EXIST?
            $pg_db_users = $pg->getUsersList($USER);
            if (in_array($dbuser_existing, $pg_db_users))
            {
                // ALREADY HAS GRANTS ON THE DATABSE?
                if (!in_array($dbuser_existing, $pg_db_users))
                {
                    $is_error = true;
                    $error_message = $da->get_lang('ERROR_MESSAGE_FAILED_ACTION_ON_DB');
                    $error_details = $da->get_lang('ERROR_DETAILS_FAILED_ALREADY_GRANTED');
                    return false;
                }

                // LET'S GRANT ACCESS TO THE DATABASE
                $pg = new postgresql([
                    'user'     => PG_USER,
                    'password' => PG_PASSWORD,
                    'host'     => PG_HOST,
                    'port'     => PG_PORT,
                    'dbname'   => $dbname,
                ]);
                if ($dbowner) $pg->grantRole2Role($dbowner, $dbuser_existing);
                if ($completed = $pg->grantUserOnDB($dbuser_existing, $dbname))
                {
                    $is_error = false;
                    $message_ok = sprintf($da->get_lang('OK_MESSAGE_USER_GRANTED'), $dbuser_existing, '', $dbname, PG_HOST, PG_PORT, $dbname);
                    return true;
                }
                else
                {
                    $is_error = true;
                    $error_message = $da->get_lang('ERROR_MESSAGE_FAILED_ACTION_ON_DB');
                    $error_details = $da->get_lang('ERROR_DETAILS_FAILED_GRANT');
                    return false;
                }
            }
            else
            {
                $is_error = true;
                $error_message = $da->get_lang('ERROR_MESSAGE_FAILED_ACTION_ON_DB');
                $error_details = $da->get_lang('ERROR_DETAILS_FAILED_DBUSER');
                return false;
            }
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
    $message_ok = sprintf($da->get_lang('OK_MESSAGE_COMPLETED_ACTION_ON_DB'), PLUGIN_ACTION, implode(', ', $user_processed_databases));
}
