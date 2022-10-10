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

if ($pg_all_databases = $pg->getDatabasesList($USER))
{
    foreach ($pg_all_databases as $key => $row)
    {
        if (($USER !== $row['owner']) && (strpos($row['owner'], $USER."_") !== 0))
        {
            $pg_all_databases[$key]['id'] = false;
            $pg_all_databases[$key]['download'] = false;
        }
        else
        {
            $pg_all_databases[$key]['download'] = true;
        }
    }
    $TABLE_LIST = format_table_list($pg_all_databases, 'table_list5', [
                    'id'       => '<div class="form-check"><input type="checkbox" name="dbselected[]" id="db_selected_|VAL|" class="form-check-input px_plugin_select_db" value="|VAL|" /></div>',
                    'name'     => '<a href="/CMD_PLUGINS/postgresql/database.html?database=|VAL|" class="text-dark">|VAL|</a><input type="hidden" name="dbnames[]" value="|VAL|" />',
                    'download' => '<a href="/CMD_PLUGINS/postgresql/download.raw?database=|VAL_NAME|" class="btn btn-sm btn-light px_plugin_download">'.$da->get_lang('PLUGIN_DB_DOWNLOAD').'</a>',
                ]);
}
else
{
    $TABLE_LIST = '<tr><td colspan="5" class="text-center">'.$da->get_lang('PLUGIN_DATABASES_NOT_FOUND').'</td></tr>';
}

$MAIN_CONTENT = [
    'PHP_PGSQL_EXTENSION'    => ($is_error == false) ? $HTML_ADMIN_TEXT_OK : $HTML_ADMIN_TEXT_ERROR,
    'ALERT_CLASS'            => ($is_error == false) ? 'alert-success' : 'alert-danger',
    'PLUGIN_DB_CREATE'       => $da->get_lang('PLUGIN_DB_CREATE'),
    'PLUGIN_DB_CREATE_NEW'   => $da->get_lang('PLUGIN_DB_CREATE_NEW'),
    'PLUGIN_DB_RESTORE'      => $da->get_lang('PLUGIN_DB_RESTORE'),
    'PLUGIN_RELOAD'          => $da->get_lang('PLUGIN_RELOAD'),
    'PLUGIN_TH_DATABASE'     => $da->get_lang('PLUGIN_TH_DATABASE'),
    'PLUGIN_TH_DBOWNER'      => $da->get_lang('PLUGIN_TH_DBOWNER'),
    'PLUGIN_TH_DBSIZE'       => $da->get_lang('PLUGIN_TH_DBSIZE'),
    'PLUGIN_DB_COUNT'        => $da->get_lang('PLUGIN_DB_COUNT'),
    'PLUGIN_DB_SIZE'         => $da->get_lang('PLUGIN_DB_SIZE'),
    'PLUGIN_DB_VACUUM'       => $da->get_lang('PLUGIN_DB_VACUUM'),
    'PLUGIN_DB_REINDEX'      => $da->get_lang('PLUGIN_DB_REINDEX'),
    'PLUGIN_DB_DELETE'       => $da->get_lang('PLUGIN_DB_DELETE'),
    'PLUGIN_DB_SELECTED'     => $da->get_lang('PLUGIN_DB_SELECTED'),
    'PLUGIN_TABLE_LIST'      => $TABLE_LIST,
    'PLUGIN_DB_USAGE'        => $PGSQL_USER_USAGE ? $PGSQL_USER_USAGE : 0,
    'PLUGIN_DB_USAGE_SIZE'   => $PGSQL_USER_USAGE_SIZE ? $PGSQL_USER_USAGE_SIZE : 0,
    'PLUGIN_DB_LIMIT'        => $PGSQL_USER_LIMIT,
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
    'MAIN_CONTENT'                 => _get_tpl(PLUGIN_TPL_DIR . '/list.html', $MAIN_CONTENT),
    'SERVER_TIME'                  => date(TIME_DATE_FORMAT),
    'UUID'                         => gen_uuid(),
];

if ($is_error)
{
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
