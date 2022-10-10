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

$MAIN_CONTENT = [
    'ERROR_MESSAGE'    => $error_message,
    'ERROR_DETAILS'    => $error_details,
];

$TPL_DATA = [
    'PLUGIN_HOME_TITLE'            => $da->get_lang('PLUGIN_HOME_TITLE'),
    'PLUGIN_HOME_DESCRIPTION'      => $da->get_lang('PLUGIN_HOME_DESCRIPTION'),
    'PLUGIN_FOOTER_TITLE'          => sprintf($da->get_lang('PLUGIN_FOOTER_TITLE'), PLUGIN_VERSION),
    'PLUGIN_FOOTER_CLASS'          => ' sr-only',
    'PLUGIN_PHPPGADMIN_CLASS'      => ' sr-only',
    'PLUGIN_PHPPGADMIN_LINK'       => PHPPGADMIN_LINK,
    'PLUGIN_SELECTED_DATABASE'     => (isset($database) && $database) ? $database : ((isset($dbname) && $dbname) ? $dbname : false),
    'PLUGIN_PHPPGADMIN_CONNECT'    => $da->get_lang('PLUGIN_CONNECT_PHPPGADMIN'),
    'ERROR_HTML'                   => '',
    'MAIN_CONTENT'                 => _get_tpl(PLUGIN_TPL_DIR . '/error.html', $MAIN_CONTENT),
    'SERVER_TIME'                  => date(TIME_DATE_FORMAT),
    'UUID'                         => gen_uuid(),
];

do_output(generate_page($TPL_DATA));

// terminate here
exit;
