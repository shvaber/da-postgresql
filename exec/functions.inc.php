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

if (!defined('IN_DA_PLUGIN') || (IN_DA_PLUGIN !==true)){die("You're not allowed to view this page!");}

define("PLUGIN_NAME",        "PostgreSQL Manager for Directadmin");
define("PLUGIN_VERSION",     "0.2.1");
define("PLUGIN_DIR",         "/usr/local/directadmin/plugins/postgresql");
define("PLUGIN_PGCONF_FILE", "/usr/local/directadmin/plugins/postgresql/pgpass.conf");
define("PLUGIN_IMAGES_DIR",  "/usr/local/directadmin/plugins/postgresql/images");
define("PLUGIN_DATA_DIR",    "/usr/local/directadmin/plugins/postgresql/data");
define("PLUGIN_JS_DIR",      "/usr/local/directadmin/plugins/postgresql/data/_js");
define("PLUGIN_CSS_DIR",     "/usr/local/directadmin/plugins/postgresql/data/_css");
define("PLUGIN_TPL_DIR",     "/usr/local/directadmin/plugins/postgresql/data/_tpl");
define("PLUGIN_SSO_DIR",     "/usr/local/directadmin/plugins/postgresql/data/sso");
define("PLUGIN_EXEC_DIR",    "/usr/local/directadmin/plugins/postgresql/exec");
define("PLUGIN_ACTION_DIR",  "/usr/local/directadmin/plugins/postgresql/exec/actions");
define("PLUGIN_TOOLS_DIR",   "/usr/local/directadmin/plugins/postgresql/exec/tools");
define("PLUGIN_LANG_DIR",    "/usr/local/directadmin/plugins/postgresql/lang");
define("PLUGIN_LOGS_DIR",    "/usr/local/directadmin/plugins/postgresql/logs");
define("PLUGIN_UPLOAD_DIR",  "/home/tmp/pgsql_restore");

define("PLUGIN_MOVE_BIN",    "/usr/local/directadmin/plugins/postgresql/exec/move_uploaded_file");
define("PLUGIN_RESTORE_BIN", "/usr/local/directadmin/plugins/postgresql/exec/dbrestore.sh");

define("PLUGIN_TPL_BODY",    "/usr/local/directadmin/plugins/postgresql/data/_tpl/body.html");
define("PLUGIN_TPL_MAIN",    "/usr/local/directadmin/plugins/postgresql/data/_tpl/main.html");
define("PLUGIN_TPL_ERROR",   "/usr/local/directadmin/plugins/postgresql/data/_tpl/error.html");
define("PLUGIN_CSS_FILE",    "/usr/local/directadmin/plugins/postgresql/data/_css/plugins.css");
define("PLUGIN_JS_FILE",     "/usr/local/directadmin/plugins/postgresql/data/_js/plugins.js");

define("TIME_DATE_FORMAT",   "H:i d-m-Y");

$_POST = array();
$_GET = array();

if (isset($_SERVER["SKIN_NAME"]) && strtolower($_SERVER["SKIN_NAME"]) == "evolution") {
    define('EVOLUTION_SKIN', true);
} else {
    define('EVOLUTION_SKIN', false);
}


function parse_input()
{
    global $_POST, $_GET;
    if (isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')
                && isset($_SERVER['POST']) && $_SERVER['REQUEST_METHOD'])
    {
        parse_str($_SERVER['POST'], $_POST);
    }
    if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'])
    {
        parse_str($_SERVER['QUERY_STRING'], $_GET);
    }
    if (get_magic_quotes_gpc())
    {
        $process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
        while (list($key, $val) = each($process))
        {
            foreach ($val as $k => $v)
            {
                unset($process[$key][$k]);
                if (is_array($v))
                {
                    $process[$key][stripslashes($k)] = $v;
                    $process[] = &$process[$key][stripslashes($k)];
                }
                else
                {
                    $process[$key][stripslashes($k)] = stripslashes($v);
                }
            }
        }
        unset($process);
    }
    return true;
}


function gen_uuid()
{
    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
        mt_rand( 0, 0xffff ),
        mt_rand( 0, 0x0fff ) | 0x4000,
        mt_rand( 0, 0x3fff ) | 0x8000,
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );
}

function randomPassword($length=16)
{
    $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    $pass = array();
    $alphaLength = strlen($alphabet) - 1;
    for ($i = 0; $i < $length; $i++)
    {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass);
}

function set_task_custom($task)
{
    global $USER, $is_error;
    if ($is_error) return false;
    if (defined('PLUGIN_TASKQ_FILE') && $task)
    {
        $new_content = "${task}\n";
        $task_file = sprintf(PLUGIN_TASKQ_FILE, $USER);
        if (!is_file($task_file.'.lock') && !is_file($task_file))
        {
            return file_put_contents($task_file, $new_content, LOCK_EX);
        }
        else
        {
            return false;
        }
    }
    return false;
}


function array2options($input, $selected, $values_only = true)
{
    $HTML_code = '';
    if ($input && is_array($input))
    {
        if ($values_only == true)
        {
            foreach ($input as $value)
            {
                $HTML_selected = ($value == $selected) ? ' selected' : '';
                $HTML_code .= '<option'.$HTML_selected.'>'.htmlspecialchars(trim($value)).'</option>';
            }
        }
        else
        {
            foreach ($input as $key => $value)
            {
                $HTML_selected = ($key == $selected) ? ' selected' : '';
                $HTML_code .= '<option value="'.addslashes($key).'"'.$HTML_selected.'>'.htmlspecialchars(trim($value)).'</option>';
            }
        }
    }
    return $HTML_code;
}


function array2checkboxes($input, $name)
{
    $HTML_code = '';
    $n = 0;
    if ($input)
    {
        foreach ($input as $value)
        {
            $n++;
            $bg = ($n%2) ? 'bg-light' : '';
            $name = addslashes($name);
            $value = addslashes($value);
            $HTML_code .= '<div class="text-dark '.$bg.'"><label class="my-3 form-check-label"><input class="form-check-input" type="checkbox" value="'.$value.'" id="'.$name.'_'.$n.'" name="'.$name.'[]">&nbsp;'.htmlspecialchars($value).'</label></div>';
        }
    }
    else
    {
        $HTML_code = '<div class="text-dark bg-light p-3">Nothing to restore yet</div>';
    }
    return $HTML_code;
}


function do_output($HTML, $display=true)
{
    if ($display) print $HTML."\n";
            else return $HTML."\n";
}


function generate_page($data)
{
    global $is_error;
    $output = '';

    if ( defined('IN_JSON_OUTPUT') && (IN_JSON_OUTPUT == true))
    {
        $is_ok = ($is_error) ? false : true;
        $is_error = ($is_error) ? true : false;
        $is_cached = false;
        $message_text = isset($data['MESSAGE_HTML']) ? nl2br(trim($data['MESSAGE_HTML'])) : '';
        $error_text = isset($data['ERROR_HTML']) ? nl2br(trim($data['ERROR_HTML'])) : '';
        $data = isset($data['OUTPUT_DATA']) ? $data['OUTPUT_DATA'] : [];
        $timestamp = date(TIME_DATE_FORMAT);
        $output = json_encode([
            'is_ok'      => $is_ok,
            'error'      => $is_error,
            'is_cached'  => $is_cached,
            'message'    => ($is_error) ? $error_text : $message_text,
            'data'       => $data,
            'timestamp'  => $timestamp
        ]);
    }
    else
    {
        $data['PLUGIN_BASE_URL'] = (strpos($_SERVER["REQUEST_URI"], "CMD_PLUGINS_ADMIN") === false) ? "/CMD_PLUGINS/postgresql" : "/CMD_PLUGINS_ADMIN/postgresql";
        $data['CSS_PLUGIN_CODE'] = _get_css(PLUGIN_CSS_FILE);
        $data['JS_PLUGIN_CODE'] = _get_js(PLUGIN_JS_FILE);
        $data['USERNAME'] = isset($_SERVER['USER']) ? $_SERVER['USER'] : '';
        $output = _get_tpl(PLUGIN_TPL_MAIN, $data);
    }
    return $output;
}


// Prepare a formated error-message table
function error_message($title, $details)
{
    $HTML_code = _get_tpl(PLUGIN_TPL_ERROR, [
            'TITLE'   => $title,
            'DETAILS' => $details,
        ]);
    return $HTML_code;
}


function _get_css($file)
{
    $content = false;
    if (is_file($file))
    {
        $content = file_get_contents($file);
    }
    return $content;
}


function _get_js($file)
{
    $content = false;
    if (is_file($file))
    {
        $content = file_get_contents($file);
    }
    return $content;
}


function _get_tpl($file, $tokens=array())
{
    $HTML_code = "";
    if (is_file($file))
    {
        $HTML_code = file_get_contents($file);
        if ($tokens && is_array($tokens))
        {
            foreach ($tokens as $key => $val)
            {
                $HTML_code = str_replace('|'.strtoupper($key).'|', $val, $HTML_code);
            }
        }
    }
    else
    {
        $HTML_code = "Error: Template not found...";
    }
    return $HTML_code;
}

function _get_pg_credentials()
{
    if (defined('PLUGIN_PGCONF_FILE') && PLUGIN_PGCONF_FILE && is_file(PLUGIN_PGCONF_FILE))
    {
        $conf = _get_pg_user_credentials(PLUGIN_PGCONF_FILE);
        if (isset($conf['dbhost']) && $conf['dbhost']) define('PG_HOST', $conf['dbhost']); else define('PG_HOST', false);
        if (isset($conf['dbport']) && $conf['dbport']) define('PG_PORT', $conf['dbport']); else define('PG_PORT', false);
        if (isset($conf['dbname']) && $conf['dbname']) define('PG_DB', $conf['dbname']); else define('PG_DB', false);
        if (isset($conf['dbuser']) && $conf['dbuser']) define('PG_USER', $conf['dbuser']); else define('PG_USER', false);
        if (isset($conf['dbpass']) && $conf['dbpass']) define('PG_PASSWORD', $conf['dbpass']); else define('PG_PASSWORD', false);
        return true;
    }
    return false;
}

function _get_pg_user_credentials($file)
{
    $return = false;
    if ($contents = file_get_contents($file))
    {
        if ($content = explode("\n", $contents))
        {
            list($host,$port,$db,$user,$password) = explode(":",$content[0]);
            $return = [
                'dbhost' => $host,
                'dbport' => $port,
                'dbname' => $db,
                'dbuser' => $user,
                'dbpass' => $password
            ];
        }
    }
    return ($return) ? $return : false;
}

function _save_pg_user_credentials($file, $input)
{
    // localhost:5432:*:diradmin:sEcrEt
    $content = '';
    $content .= (isset($input['dbhost']) && $input['dbhost']) ? $input['dbhost'] : '*';
    $content .= (isset($input['dbport']) && $input['dbport']) ? ':'.$input['dbport'] : ':*';
    $content .= (isset($input['dbname']) && $input['dbname']) ? ':'.$input['dbname'] : ':*';
    if (isset($input['dbuser']) && $input['dbuser']) $content .= ':'.$input['dbuser']; else return false;
    if (isset($input['dbpass']) && $input['dbpass']) $content .= ':'.$input['dbpass']; else return false;
    if (@file_put_contents($file, $content, LOCK_EX))
    {
        @chmod($file, 0600);
        return true;
    }
    return false;
}

function format_table_list($input, $template, $transform=false)
{
    global $da;
    $HTML_result = '';
    foreach ($input as $row)
    {
        $CONTENT = array();
        $id = 0;
        $prop = $row;
        foreach($row as $key => $val)
        {
            $original_val = htmlspecialchars($val);
            if ($val !== false)
            {
                if ($transform && isset($transform[$key]))
                {
                    $val = str_replace("|VAL|", htmlspecialchars($val), $transform[$key]);
                    foreach($prop as $p_key => $p_val)
                    {
                        $val = str_replace("|VAL_".strtoupper($p_key)."|", htmlspecialchars($p_val), $val);
                    }
                }
                else
                {
                    $val = htmlspecialchars($val);
                }
            }
            if ($id === 0)
            {
                $CONTENT["TH1_INPUT"] = $val;
                $CONTENT["TH1_INPUT_ORIGINAL"] = $original_val;
                $CONTENT["TH1_INPUT_CLASS"] = "px_th_". strtolower($key);
            }
            else
            {
                $CONTENT["TD".$id."_INPUT"] = $val;
                $CONTENT["TD".$id."_INPUT_ORIGINAL"] = $original_val;
                $CONTENT["TD".$id."_INPUT_CLASS"] = "px_td_". strtolower($key);
            }
            $id++;
        }
        $HTML_result .= _get_tpl(PLUGIN_TPL_DIR . '/'.$template.'.html', $CONTENT);
    }
    return $HTML_result;
}
// END
