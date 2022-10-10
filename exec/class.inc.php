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

class da
{
    private $DA_BIN="/usr/local/directadmin/directadmin";
    private $DACONF_BIN=PLUGIN_EXEC_DIR."/daconf";

    private $_CONF=array();
    private $_CONF_CUSTOM=array();
    private $_DEFAULT_CONF=array();
    private $_LANG=array();
    private $_GET_VARS=array();
    private $_POST_VARS=array();
    private $_ERROR=false;
    private $_ERROR_TEXT="";
    private $_USERNAME="";
    private $_USER_CONF_FILE="";
    private $_USER_CONF=array();
    private $_USER_DOMAINS_FILE="";
    private $_USER_DOMAINS=array();
    private $_EXEC_LEVEL;

    function __construct()
    {
        // Init username and define _USER_CONF_FILE
        $this->_init_user();
        // Load data from user.conf
        $this->_USER_CONF=$this->_load_conf_data($this->_USER_CONF_FILE);
        $this->_LANG=$this->_load_language();
        //$this->_DA_CONF=$this->_load_da_conf();
    }

    public function get_username()
    {
        return $this->_USERNAME;
    }

    public function get_user_domains()
    {
        $domains = array();
        if ($content = $this->get_file($this->_USER_DOMAINS_FILE))
        {
            if ($rows = explode("\n", $content))
            {
                sort($rows);
                foreach ($rows as $row)
                {
                    $row = trim($row);
                    if (!$row) continue;
                    if (!in_array($row, $domains)) $domains[] = $row;
                }
            }
        }
        return $domains;
    }

    public function get_var_post($search, $default=false)
    {
        $var=(isset($this->_POST_VARS[$search])) ? $this->_POST_VARS[$search] : false;
        return ($var) ? $var : (($default) ? $default : false);
    }

    public function get_lang($search)
    {
        return (isset($this->_LANG[$search])) ? $this->_LANG[$search] : $search;
    }

    public function get_file($filename)
    {
        return is_file($filename) ? file_get_contents($filename) : false;
    }

    public function get_conf($search)
    {
        return (isset($this->_CONF_CUSTOM[$search])) ? $this->_CONF_CUSTOM[$search] : $this->_CONF[$search];
    }

    public function get_confs()
    {
        return $this->_CONF;
    }

    public function get_custom_confs()
    {
        return $this->_CONF_CUSTOM;
    }

    public function get_user_data($search)
    {
        return (isset($this->_USER_CONF[$search])) ? $this->_USER_CONF[$search] : NULL;
    }

    public function get_da_conf($search)
    {
        if (!isset($this->_DA_CONF) || !is_array($this->_DA_CONF))
        {
            $this->_DA_CONF=$this->_load_da_conf();
        }
        return (isset($this->_DA_CONF[$search])) ? $this->_DA_CONF[$search] : false;
    }

    public function da_send_message($subject,$message)
    {
        // As of Directadmin version v.1.51.5+ we can message a specific
        // account with the task.queue
        // =================================================================
        $action="notify";
        $user=$this->get_username();
        $subject=urlencode(htmlspecialchars($subject));
        $message=urlencode(htmlspecialchars($message));

        $content = "action=${action}&value=users&users=select1%3D${user}&subject=${subject}&message=${message}\n";
        $file = '/usr/local/directadmin/data/task.queue.cb';
        return file_put_contents($file, $content);
    }

    public function filter_content($content, $filters=array())
    {
        if ($filters)
        {
            foreach ($filters as $key => $val)
            {
                $content = str_replace($key, $val, $content);
            }
        }
        return $content;
    }

    // ===============================
    // Function to parse data
    // ===============================
    private function _load_conf_data($file)
    {
        $data=array();
        if (is_file($file)){$data=parse_ini_file($file,false,INI_SCANNER_RAW);}
        return $data;
    }

    // ===============================
    // Function to read Directadmin configs
    // ===============================
    private function _load_da_conf()
    {
        $_da_conf = array();
        if (function_exists('exec') && is_file($this->DACONF_BIN))
        {
            if (exec($this->DACONF_BIN . " | sort", $out, $res))
            {
                if (($res === 0) && (is_array($out)))
                {
                    foreach($out as $row)
                    {
                        if (strpos($row, "=") !== false)
                        {
                            list($key, $val) = explode("=", $row);
                            $_da_conf[$key] = $val;
                        }
                    }
                    return $_da_conf;
                }
            }
        }
        return false;
    }

    // ===============================
    // Function to init user
    // ===============================
    private function _init_user()
    {
        $this->_USERNAME=(isset($_SERVER['USER']) && $_SERVER['USER']) ? $_SERVER['USER'] : false;
        $this->_USER_CONF_FILE="/usr/local/directadmin/data/users/".$this->_USERNAME."/user.conf";
        $this->_USER_DOMAINS_FILE="/usr/local/directadmin/data/users/".$this->_USERNAME."/domains.list";
        return ($this->_USERNAME) ? true : false;
    }

    // ===============================
    // Function to load default and
    // user language files
    // ===============================
    private function _load_language($force_lang=false)
    {
        $DEFAULT_LANG=array();
        $USER_LANG=array();
        $DEFAULT_LANG=$this->_load_conf_data(PLUGIN_LANG_DIR."/lang_en.php");
        $selected_lang=($force_lang !== false) ? strtolower($force_lang) : strtolower($_SERVER["LANGUAGE"]);
        if ($selected_lang != "en") {
            $USER_LANG=$this->_load_conf_data(PLUGIN_LANG_DIR."/lang_".$selected_lang.".php");
        }
        return array_merge((array)$DEFAULT_LANG, (array)$USER_LANG);
    }
}
// END
