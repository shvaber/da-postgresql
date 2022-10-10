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

$database = (isset($_POST["database"]) && $_POST["database"]) ? $_POST["database"] : false;
$dbpass = (isset($_POST["dbpass"]) && $_POST["dbpass"]) ? $_POST["dbpass"] : false;
$dbuser = (isset($_POST["dbuser"]) && $_POST["dbuser"]) ? $_POST["dbuser"] : false;
$dbdump = (isset($_POST["dump"]) && $_POST["dump"]) ? $_POST["dump"] : false;
$fname = (isset($_POST["fname"]) && $_POST["fname"]) ? $_POST["fname"] : false;

$pg_user_databases = array();
$message_ok = false;

if (is_file($dbdump) && $database)
{
    if ($_pg_user_databases = $pg->getDatabasesList($USER))
    {
        foreach ($_pg_user_databases as $row)
        {
            if (($USER === $row['owner']) || (strpos($row['owner'], $USER."_") === 0))
            {
                $pg_user_databases[] = $row['name'];
            }
        }
        if (!in_array($database, $pg_user_databases))
        {
            $is_error = true;
            $error_message = $da->get_lang('ERROR_MESSAGE_FAILED_ACTION_ON_DB');
            $error_details = $da->get_lang('ERROR_DETAILS_FAILED_OWNER');
            return false;
        }
        $pg = new postgresql([
            'user'     => $dbuser,
            'password' => $dbpass,
            'host'     => PG_HOST,
            'port'     => PG_PORT,
            'dbname'   => $database
        ]);
        if ($pg->testServer())
        {
            $tmpdir = (defined('PLUGIN_UPLOAD_DIR') && PLUGIN_UPLOAD_DIR) ? PLUGIN_UPLOAD_DIR : "/home/tmp/pgsql_restore";

            // TRYING TO CREATE TEMPDIR AND CONTROL ITS CREATION
            @mkdir($tmpdir, 0700);
            if (is_dir($tmpdir) && is_writable($tmpdir))
            {
                // FILENAME SENT FROM FORM
                $fname = basename($fname);

                // FILENAME OF UPLOADED FILE FROM DirectAdmin
                $fdump = basename($dbdump);
                if (strpos($fname,'\\') !== false) //'
                {
                    $tmp = preg_split("[\\\]",$fname);
                    $fname = $tmp[count($tmp) - 1];
                }

                // CONTROL FILENAME, THAT IT IS NOT OVERWRITTEN BY USER
                if (!$fname || (strpos($fdump, $fname) !== 0))
                {
                    $fname = substr($fdump,0,-6);
                }

                // CREATING UNIQUE NAME FOR THE FILE
                $uid = uniqid();
                $fname = $uid.'.'.str_replace(" ", "", $fname);

                // MOVING UPLOADED FILE TO A TEMPDIR
                $cmd = sprintf("%s %s %s", PLUGIN_MOVE_BIN, escapeshellarg($fdump), escapeshellarg($fname));
                @exec($cmd, $out, $rtval);

                // RESTORING UPLOADED FILE TO THE SERVER
                $dbdump = $tmpdir.'/'.$fname;
                if ($dbdump && is_file($dbdump))
                {
                    $dbuserconf = $tmpdir.'/.'.$database.'.pgpass.conf';
                    $content = sprintf("%s:%d:%s:%s:%s",PG_HOST,PG_PORT,$database,$dbuser,$dbpass);
                    file_put_contents($dbuserconf, $content);
                    chmod($dbuserconf, 0600);

                    unset($out);
                    $cmd = sprintf("%s %s %s %d", PLUGIN_RESTORE_BIN, escapeshellarg($dbdump), escapeshellarg($database), 1);
                    @exec($cmd, $out, $rtval);

                    if ($rtval === 0)
                    {
                        $is_error = false;
                    }
                    else
                    {
                        $is_error = true;
                        $error_message = $da->get_lang('ERROR_MESSAGE_FAILED_RESTORE_DB');
                        $error_details = sprintf($da->get_lang('ERROR_DETAILS_ERROR_CODE'), PLUGIN_ACTION, PLUGIN_ACTION, 301);
                        return false;
                    }
                    if (is_file($dbdump)) unlink($dbdump);
                    if (is_file($dbuserconf)) unlink($dbuserconf);
                }
                else
                {
                    $is_error = true;
                    $error_message = $da->get_lang('ERROR_MESSAGE_FAILED_RESTORE_DB');
                    $error_details = sprintf($da->get_lang('ERROR_DETAILS_ERROR_CODE'), PLUGIN_ACTION, PLUGIN_ACTION, 302);
                    return false;
                }
            }
            else
            {
                $is_error = true;
                $error_message = $da->get_lang('ERROR_MESSAGE_FAILED_RESTORE_DB');
                $error_details = sprintf($da->get_lang('ERROR_DETAILS_ERROR_CODE'), PLUGIN_ACTION, PLUGIN_ACTION, 303);
                return false;
            }
        }
        else
        {
            $is_error = true;
            $error_message = $da->get_lang('ERROR_MESSAGE_FAILED_RESTORE_DB');
            $error_details = $da->get_lang('ERROR_DETAILS_FAILED_CONNECT_DB');
            return false;
        }
    }
    else
    {
        $is_error = true;
        $error_message = $da->get_lang('ERROR_MESSAGE_FAILED_RESTORE_DB');
        $error_details = $da->get_lang('ERROR_DETAILS_FAILED_LIST_DATABASES');
        return false;
    }
}
else
{
    $is_error = true;
}

if ($is_error)
{
    if (!$error_message) $error_message = $da->get_lang('ERROR_MESSAGE_FAILED_RESTORE_DB');
    if (!$error_details) $error_details = $da->get_lang('ERROR_DETAILS_FAILED_RESTORE_DB');
    $message_ok = false;
}
else
{
    $is_error = false;
    $error_message = false;
    $error_details = false;
    $message_ok = sprintf($da->get_lang('OK_MESSAGE_COMPLETED_ACTION_ON_DB'), PLUGIN_ACTION, $database);
}
