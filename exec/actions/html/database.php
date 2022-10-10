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

$database = (isset($_GET['database']) && $_GET['database']) ? $_GET['database'] : false;
$databases = array();
$error_message = '';
$error_details = '';

$TABLE_LIST = false;
$SELECT_DB_USER = false;

// Define the custom sort function
function sort_privileges($a,$b)
{
    return $a['user']>$b['user'];
}

if ($pg_user_databases = $pg->getDatabasesList($USER))
{
    foreach ($pg_user_databases as $row)
    {
        if (($USER === $row['owner']) || (strpos($row['owner'], $USER."_") === 0))
        {
            $databases[] = $row['name'];
        }
    }
    if (in_array($database, $databases))
    {
        $is_error = false;
        $pg_db_users = $pg->getUsersList($USER);
        if ($pg_all_privileges = $pg->getPrivilegesList($database))
        {
            foreach ($pg_all_privileges as $key => $row)
            {
                if (($key = array_search($row['user'], $pg_db_users)) !== false)
                {
                    unset($pg_db_users[$key]);
                }
                else if (($USER !== $row['user']) && (strpos($row['user'], $USER."_")!==0))
                {
                    $pg_all_privileges[$key]['id'] = false;
                    $pg_all_privileges[$key]['password'] = false;
                    $pg_all_privileges[$key]['privileges'] = false;
                }
            }
            reset($pg_all_privileges);
            usort($pg_all_privileges, "sort_privileges");
            $TABLE_LIST = format_table_list($pg_all_privileges, 'table_list', [
                        'id'         => '<div class="form-check"><input type="checkbox" name="userselected[]" id="user_selected_|VAL|" class="form-check-input px_plugin_select_user" value="|VAL|" /></div>',
                        'user'       => '<input type="hidden" name="dbusers[]" value="|VAL|" />|VAL|',
                        'password'   => '<a href="/CMD_PLUGINS/postgresql/database.html?dbname='.$database.'&mode=password&dbuser=|VAL_USER|" class="text-dark px_plugin_change_password" data-toggle="modal" data-target="#px_ModalCenter">'.$da->get_lang('PLUGIN_CHANGE_PASSWORD').'</a>',
                        'privileges' => '&nbsp;', //$da->get_lang('PLUGIN_MODIFY_PRIVILEGES'),
                    ]);
        }
        else
        {
            $TABLE_LIST = '<tr><td colspan="5" class="text-center">'.$da->get_lang('PLUGIN_USERS_NOT_FOUND').'</td></tr>';
        }
        sort($pg_db_users);
        array_unshift($pg_db_users, '');
    }
    else
    {
        $is_error = true;
        $error_message = $da->get_lang('ERROR_MESSAGE_FAILED_DETAILS');
        $error_details = $da->get_lang('ERROR_DETAILS_FAILED_OWNER');
        return false;
    }
}
else
{
    $is_error = true;
    $error_message = $da->get_lang('ERROR_MESSAGE_FAILED_DETAILS');
    $error_details = $da->get_lang('ERROR_DETAILS_FAILED_LIST_DATABASES');
}

$MAIN_CONTENT = [
    'PLUGIN_DB_DETAILS'           => $da->get_lang('PLUGIN_DB_DETAILS'),
    'PLUGIN_GO_BACK'              => $da->get_lang('PLUGIN_GO_BACK'),
    'PLUGIN_TH_USERNAME'          => $da->get_lang('PLUGIN_TH_USERNAME'),
    'PLUGIN_TH_CHANGE_PASSWORD'   => $da->get_lang('PLUGIN_TH_CHANGE_PASSWORD'),
    'PLUGIN_TH_MODIFY_PRIVILEGES' => '', //$da->get_lang('PLUGIN_TH_MODIFY_PRIVILEGES'),
    'PLUGIN_CHANGE_PASSWORD'      => $da->get_lang('PLUGIN_CHANGE_PASSWORD'),
    'PLUGIN_MODIFY_PRIVILEGES'    => '', //$da->get_lang('PLUGIN_MODIFY_PRIVILEGES'),
    'PLUGIN_USER_DELETE'          => $da->get_lang('PLUGIN_USER_DELETE'),
    'PLUGIN_USER_SELECTED'        => $da->get_lang('PLUGIN_USER_SELECTED'),
    'PLUGIN_USER_CREATE'          => $da->get_lang('PLUGIN_USER_CREATE'),
    'PLUGIN_USER_EXISTING_ADD'    => $da->get_lang('PLUGIN_USER_EXISTING_ADD'),
    'PLUGIN_MODAL_CLOSE'          => $da->get_lang('PLUGIN_MODAL_CLOSE'),
    'PLUGIN_SAVE_CHANGES'         => $da->get_lang('PLUGIN_SAVE_CHANGES'),
    'PLUGIN_USER_PASSWORD'        => $da->get_lang('PLUGIN_USER_PASSWORD'),
    'PLUGIN_USER_CREATE'          => $da->get_lang('PLUGIN_USER_CREATE'),
    'PLUGIN_USER_ADD'             => $da->get_lang('PLUGIN_USER_ADD'),
    'PLUGIN_DB_USER'              => $da->get_lang('PLUGIN_DB_USER'),
    'PLUGIN_SELECTED_DATABASE'    => addslashes($database),
    'PLUGIN_SELECTED_USERNAME'    => '',
    'PLUGIN_TABLE_LIST'           => $TABLE_LIST,
    'PLUGIN_SELECT_DB_USER'       => ($pg_db_users) ? array2options($pg_db_users, false) : '',
    'DISABLED'                    => '',
];

$TPL_DATA = [
    'PLUGIN_HOME_TITLE'            => $da->get_lang('PLUGIN_HOME_TITLE'),
    'PLUGIN_HOME_DESCRIPTION'      => $da->get_lang('PLUGIN_HOME_DESCRIPTION'),
    'PLUGIN_FOOTER_TITLE'          => sprintf($da->get_lang('PLUGIN_FOOTER_TITLE'), PLUGIN_VERSION),
    'PLUGIN_FOOTER_CLASS'          => ' sr-only',
    'PLUGIN_PHPPGADMIN_CLASS'      => '',
    'PLUGIN_PHPPGADMIN_LINK'       => PHPPGADMIN_LINK,
    'PLUGIN_SELECTED_DATABASE'     => (isset($database) && $database) ? $database : ((isset($dbname) && $dbname) ? $dbname : false),
    'PLUGIN_PHPPGADMIN_CONNECT'    => $da->get_lang('PLUGIN_CONNECT_PHPPGADMIN'),
    'MAIN_CONTENT'                 => _get_tpl(PLUGIN_TPL_DIR . '/database.html', $MAIN_CONTENT),
    'SERVER_TIME'                  => date(TIME_DATE_FORMAT),
    'UUID'                         => gen_uuid(),
];

if ($is_error)
{

    if (!$error_message) $error_message = $da->get_lang('ERROR_MESSAGE_FAILED_DETAILS');
    if (!$error_details) $error_details = $da->get_lang('ERROR_DETAILS_FAILED_DETAILS');

    $TPL_DATA['DISABLED']             = ' disabled="disabled"';
    $TPL_DATA['MESSAGE_HTML']         = '';
    $TPL_DATA['ERROR_HTML']           = $error_message .' '. ($error_details ? $error_details : $da->get_lang('TRY_AGAIN_LATER'));
}
else
{
    $TPL_DATA['MESSAGE_HTML']         = $message_ok;
    $TPL_DATA['ERROR_HTML']           = '';
}

do_output(generate_page($TPL_DATA));

// terminate here
exit;
