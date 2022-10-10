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

class postgresql
{
    private $_ERROR=false;
    private $_ERROR_TEXT;

    private $_PG_HOST;
    private $_PG_PORT;
    private $_PG_DB;
    private $_PG_USER;
    private $_PG_PASSWORD;

    private $_PG_CONN;
    private $_PG_LAST_ERROR;
    private $_PG_QUERIES;

    private $_CONNECT_DB;
    private $_PG_PERSISTENT = false;

    function __construct($input)
    {
        $this->_PG_QUERIES = array();
        if ($this->_PG_CONN) $this->_disconnect();

        $user = (isset($input['user']) && $input['user']) ? $input['user'] : false;
        $password = (isset($input['password']) && $input['password']) ? $input['password'] : false;
        $dbname = (isset($input['dbname']) && $input['dbname']) ? $input['dbname'] : false;
        $host = (isset($input['host']) && $input['host']) ? $input['host'] : 'localhost';
        $port = (isset($input['port']) && intval($input['port'])) ? intval($input['port']) : 5432;

        $this->setDBuser($user);
        $this->setDBpassword($password);
        $this->setDBhost($host);
        $this->setDBport($port);
        $this->setDBname($dbname);
    }

    function testServer()
    {
        $conn = $this->_connect();
        $this->_disconnect();
        return $conn;
    }

    function getConnectedDBname()
    {
        $conn = $this->_connect();
        $dbname = pg_dbname($conn);
        $this->_disconnect();
        return $dbname;
    }

    //
    // DELETE DATABASE BY NAME
    // ========================================
    function doDeleteDB($dbname)
    {
        $conn = $this->_connect();
        if ($conn)
        {

            $this->setQuery("DROP DATABASE IF EXISTS ".addslashes($dbname).";");
            if ($result = $this->runQuery())
            {
                $this->setQuery("DROP USER IF EXISTS ".addslashes($dbname).";");
                $this->runQuery();
                return $result;
            }
            else
            {
                $this->_disconnect();
                $this->_ERROR = true;
                $this->_ERROR_TEXT[] = 'Failed to delete database: '. pg_dbname($conn);
                return false;
            }
        }
        $this->_ERROR = true;
        $this->_ERROR_TEXT[] = 'Failed to connect to PostgreSQL server';
        return false;
    }

    function doReindexDB($dbname)
    {
        $conn = $this->_connect();
        if ($conn)
        {
            $this->setQuery('REINDEX DATABASE '.addslashes($dbname).';');
            if ($result = $this->runQuery())
            {
                return $result;
            }
            else
            {
                $this->_disconnect();
                $this->_ERROR = true;
                $this->_ERROR_TEXT[] = 'Failed to reindex database: '. pg_dbname($conn);
                return false;
            }
        }
        $this->_ERROR = true;
        $this->_ERROR_TEXT[] = 'Failed to connect to PostgreSQL server';
        return false;
    }

    function doVacuumDB()
    {
        $conn = $this->_connect();
        if ($conn)
        {
            $this->setQuery('VACUUM;');
            if ($result = $this->runQuery())
            {
                return $result;
            }
            else
            {
                $this->_disconnect();
                $this->_ERROR = true;
                $this->_ERROR_TEXT[] = 'Failed to vaccum database: '. pg_dbname($conn);
                return false;
            }
        }
        $this->_ERROR = true;
        $this->_ERROR_TEXT[] = 'Failed to connect to PostgreSQL server';
        return false;
    }

    //
    // COUNT DATABASES:
    // - for all users, when $owner=false
    // - for a specified owner
    // ========================================
    function getDatabasesCount($owner=false)
    {
        $conn = $this->_connect();
        if ($conn)
        {
            if ($owner)
            {
                $this->setQuery('SELECT COUNT(datname) AS "Count" FROM pg_catalog.pg_database WHERE datname = \''.addslashes($owner).'\' OR datname LIKE \''.addslashes($owner).'_%\';');
            }
            else
            {
                $this->setQuery('SELECT COUNT(datname) AS "Count" FROM pg_catalog.pg_database;');
            }
            if ($result = $this->runQuery())
            {
                if ($row = pg_fetch_array($result, NULL, PGSQL_ASSOC))
                {
                    return $row['Count'];
                }
                else
                {
                    $this->_disconnect();
                    $this->_ERROR = true;
                    $this->_ERROR_TEXT[] = 'Failed to count databases';
                    return false;
                }
            }
            else
            {
                $this->_disconnect();
                $this->_ERROR = true;
                $this->_ERROR_TEXT[] = 'Failed to get list of databases';
                return false;
            }
        }
        $this->_ERROR = true;
        $this->_ERROR_TEXT[] = 'Failed to connect to PostgreSQL server';
        return false;
    }


    //
    // COUNT SIZE OF DATABASES:
    // - for all users, when $owner=false
    // - for a specified owner
    // ========================================
    function getDatabasesSize($owner=false)
    {
        $conn = $this->_connect();
        if ($conn)
        {
            if ($owner)
            {
                $this->setQuery('SELECT pg_size_pretty(SUM(pg_database_size(datname))) AS "Size" FROM pg_catalog.pg_database WHERE datname = \''.addslashes($owner).'\' OR datname LIKE \''.addslashes($owner).'_%\';');
            }
            else
            {
                $this->setQuery('SELECT pg_size_pretty(SUM(pg_database_size(datname))) AS "Size" FROM pg_catalog.pg_database;');
            }
            if ($result = $this->runQuery())
            {
                if ($row = pg_fetch_array($result, NULL, PGSQL_ASSOC))
                {
                    return $row['Size'];
                }
                else
                {
                    $this->_disconnect();
                    $this->_ERROR = true;
                    $this->_ERROR_TEXT[] = 'Failed to count size of databases';
                    return false;
                }
            }
            else
            {
                $this->_disconnect();
                $this->_ERROR = true;
                $this->_ERROR_TEXT[] = 'Failed to get list of databases';
                return false;
            }
        }
        $this->_ERROR = true;
        $this->_ERROR_TEXT[] = 'Failed to connect to PostgreSQL server';
        return false;
    }


    //
    // LIST USERS:
    // - for all users, when $owner=false
    // - for a specified owner
    // ========================================
    function getUsersList($user=false)
    {
        $conn = $this->_connect();
        if ($conn)
        {
            if ($user)
            {
                $this->setQuery('SELECT u.usename AS "User" FROM pg_catalog.pg_user u WHERE u.usename NOT LIKE \''.addslashes($user).'_sso_%\'  AND (u.usename = \''.addslashes($user).'\' OR u.usename LIKE \''.addslashes($user).'_%\');');
            }
            else
            {
                $this->setQuery('SELECT u.usename AS "User" FROM pg_catalog.pg_user u;');
            }
            if ($result = $this->runQuery())
            {
                $data = false;
                while ($row = pg_fetch_array($result, NULL, PGSQL_ASSOC))
                {
                    $data[] = $row['User'];
                }
                return $data;
            }
            else
            {
                $this->_disconnect();
                $this->_ERROR = true;
                $this->_ERROR_TEXT[] = 'Failed to get list of users';
                return false;
            }
        }
        $this->_ERROR = true;
        $this->_ERROR_TEXT[] = 'Failed to connect to PostgreSQL server';
        return false;
    }


    function getPrivilegesList($dbase)
    {
        $conn = $this->_connect();
        if ($conn)
        {
            $this->setQuery("SELECT datacl AS acl FROM pg_catalog.pg_database WHERE datname='".addslashes($dbase)."';");
            if ($result = $this->runQuery())
            {
                $data = false;
                if ($row = pg_fetch_row($result,0))
                {
                    $tmp = explode(",",substr(substr($row[0],1),0,-1));
                    sort($tmp);
                    $id=0;
                    foreach ($tmp as $_row)
                    {
                        list($user, $other) = @explode("=",$_row);
                        if ($user)
                        {
                            $data[] = [
                                'id'             => $id,
                                'user'           => $user,
                                'password'       => true,
                                'privileges'     => '',
                            ];
                            $id++;
                        }
                    }
                }
                return $data;
            }
            else
            {
                $this->_disconnect();
                $this->_ERROR = true;
                $this->_ERROR_TEXT[] = 'Failed to get list of privileges';
                return false;
            }
        }
        $this->_ERROR = true;
        $this->_ERROR_TEXT[] = 'Failed to connect to PostgreSQL server';
        return false;
    }


    //
    // LIST DATABASES:
    // - for all users, when $owner=false
    // - for a specified owner
    // ========================================
    function getDatabasesList($owner=false)
    {
        $conn = $this->_connect();
        if ($conn)
        {
            if ($owner)
            {
                $this->setQuery('SELECT d.datname as "Name", pg_catalog.pg_get_userbyid(d.datdba) as "Owner", pg_size_pretty(pg_database_size(d.datname)) as "Size" FROM pg_catalog.pg_database d WHERE d.datname = \''.addslashes($owner).'\' OR d.datname LIKE \''.addslashes($owner).'_%\' ORDER BY d.datname ASC;');
            }
            else
            {
                $this->setQuery('SELECT d.datname as "Name", pg_catalog.pg_get_userbyid(d.datdba) as "Owner", pg_size_pretty(pg_database_size(d.datname)) as "Size" FROM pg_catalog.pg_database d ORDER BY d.datname ASC;');
            }
            if ($result = $this->runQuery())
            {
                $data = false;
                $id = 1;
                while ($row = pg_fetch_array($result, NULL, PGSQL_ASSOC))
                {
                    $data[] = [
                        'id'    => $id,
                        'name'  => $row['Name'],
                        'owner' => $row['Owner'],
                        'size'  => $row['Size'],
                        ];
                    $id++;
                }
                return $data;
            }
            else
            {
                $this->_disconnect();
                $this->_ERROR = true;
                $this->_ERROR_TEXT[] = 'Failed to get list of databases';
                return false;
            }
        }
        $this->_ERROR = true;
        $this->_ERROR_TEXT[] = 'Failed to connect to PostgreSQL server';
        return false;
    }

    // 
    // List databases an user has privilege to connect to
    // =====================================================
    function getGrantedDatabasesList($dbuser)
    {
        $conn = $this->_connect();
        if ($conn)
        {
            if (strpos($dbuser, "_") !== false)
            {
                list($sysuser, $other) = explode("_", $dbuser);
            }
            else
            {
                $sysuser = $dbuser;
            }
            $this->setQuery('SELECT datname as "Name" FROM pg_database WHERE has_database_privilege(\''.addslashes($dbuser).'\', datname, \'CONNECT\') and datistemplate = false and datname like \''.addslashes($sysuser).'_%\'');
            if ($result = $this->runQuery())
            {
                $data = false;
                while ($row = pg_fetch_array($result, NULL, PGSQL_ASSOC))
                {
                    $data[] = $row['Name'];
                }
                return $data;
            }
            else
            {
                $this->_disconnect();
                $this->_ERROR = true;
                $this->_ERROR_TEXT[] = 'Failed to get list of databases';
                return false;
            }
        }
        $this->_ERROR = true;
        $this->_ERROR_TEXT[] = 'Failed to connect to PostgreSQL server';
        return false;
    }


    //
    // CHANGE USER'S PASSWORD
    // ========================================
    function changeUserPassword($dbuser, $dbpassword)
    {
        $conn = $this->_connect();
        if ($conn)
        {
            $this->setQuery("ALTER USER ".addslashes($dbuser)." WITH LOGIN PASSWORD '".addslashes($dbpassword)."';");
            if ($this->runQuery())
            {
                $this->_disconnect();
                return true;
            }
            else
            {
                $this->_disconnect();
                $this->_ERROR = true;
                $this->_ERROR_TEXT[] = 'Query error: Failed to change a password for role';
                return false;
            }
        }
        $this->_ERROR = true;
        $this->_ERROR_TEXT[] = 'Connection error: Failed to change a password for role';
        return false;
    }


    //
    // GRANT ROLE TO ROLE
    // ========================================
    // Grant membership in role admins to user joe:
    // e.g:    GRANT admins TO joe;
    function grantRole2Role($role, $dbuser)
    {
        $conn = $this->_connect();
        if ($conn)
        {
            $this->setQuery("GRANT ".addslashes($role)." TO ".addslashes($dbuser).";");
            if ($this->runQuery())
            {
                $this->_disconnect();
                return true;
            }
            else
            {
                $this->_disconnect();
                $this->_ERROR = true;
                $this->_ERROR_TEXT[] = 'Query error: Failed to grant role';
                return false;
            }
        }
        $this->_ERROR = true;
        $this->_ERROR_TEXT[] = 'Connection error: Failed to grant role';
        return false;
    }

    //
    // REVOKE ROLE FROM ROLE
    // ========================================
    // Revoke membership in role admins from user joe:
    // e.g.   REVOKE admins FROM joe;
    function revokeRoleFromRole($role, $dbuser)
    {
        $conn = $this->_connect();
        if ($conn)
        {
            $this->setQuery("REVOKE ".addslashes($role)." FROM ".addslashes($dbuser).";");
            if ($this->runQuery())
            {
                $this->_disconnect();
                return true;
            }
            else
            {
                $this->_disconnect();
                $this->_ERROR = true;
                $this->_ERROR_TEXT[] = 'Query error: Failed to revoke role';
                return false;
            }
        }
        $this->_ERROR = true;
        $this->_ERROR_TEXT[] = 'Connection error: Failed to revoke role';
        return false;
    }

    //
    // GRANT ROLE TO DATABASE
    // ========================================
    function grantRole2Database($dbuser, $dbname)
    {
        $conn = $this->_connect();
        if ($conn)
        {
            $this->setQuery("GRANT ALL ON DATABASE ".addslashes($dbname)." TO ".addslashes($dbuser).";");
            if ($this->runQuery())
            {
                $this->_disconnect();
                return true;
            }
            else
            {
                $this->_disconnect();
                $this->_ERROR = true;
                $this->_ERROR_TEXT[] = 'Query error: Failed to grant role';
                return false;
            }
        }
        $this->_ERROR = true;
        $this->_ERROR_TEXT[] = 'Connection error: Failed to grant role';
        return false;
    }

    //
    // REVOKE GRANTS FROM ROLE TO DATABASE
    // ========================================
    function revokeRoleFromDatabase($dbuser, $dbname)
    {
        $this->setPersistent(true);
        $conn = $this->_connect();
        if ($conn)
        {
            $dbconnected = $this->getConnectedDBname();
            if ($dbname == $dbconnected)
            {
                $this->setQuery("REVOKE ALL ON DATABASE ".addslashes($dbname)." FROM ".addslashes($dbuser).";");
                $this->runQuery();
                $this->setQuery("REVOKE SELECT ON ALL TABLES IN SCHEMA public FROM ".addslashes($dbuser).";");
                $this->runQuery();
                $this->setQuery("REVOKE SELECT ON ALL TABLES IN SCHEMA pg_catalog FROM ".addslashes($dbuser).";");
                $this->runQuery();
                $this->setQuery("REVOKE ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public FROM ".addslashes($dbuser).";");
                $this->runQuery();
                $this->_disconnect();
                return true;
            }
            else
            {
                $this->_disconnect();
                $this->_ERROR = true;
                $this->_ERROR_TEXT[] = 'Failed to revoke permissions';
                return false;
            }
        }
        $this->_ERROR = true;
        $this->_ERROR_TEXT[] = 'Connection error: Failed to revoke privileges role';
        return false;
    }

    //
    // DROP USER/ROLE
    // ========================================
    function removeUser($dbuser)
    {
        $conn = $this->_connect();
        if ($conn)
        {
            $this->setQuery("DROP USER ".addslashes($dbuser).";");
            if ($this->runQuery())
            {
                $this->_disconnect();
                return true;
            }
            else
            {
                $this->_disconnect();
                $this->_ERROR = true;
                $this->_ERROR_TEXT[] = 'Query error: Failed to drop role';
                return false;
            }
        }
        $this->_ERROR = true;
        $this->_ERROR_TEXT[] = 'Connection error: Failed to drop role';
        return false;
    }

    //
    // CREATE USER
    // ========================================
    function createUser($dbuser, $dbpassword=false)
    {
        $conn = $this->_connect();
        if ($conn)
        {
            if ($dbpassword !== false)
            {
                $this->setQuery("CREATE USER ".addslashes($dbuser)." WITH ENCRYPTED PASSWORD '".addslashes($dbpassword)."';");
            }
            else
            {
                $this->setQuery("CREATE ROLE ".addslashes($dbuser)." WITH NOLOGIN;");
            }

            if ($this->runQuery())
            {
                $this->_disconnect();
                return true;
            }
            else
            {
                $this->_disconnect();
                $this->_ERROR = true;
                $this->_ERROR_TEXT[] = 'Query error: Failed to create role';
                return false;
            }
        }
        $this->_ERROR = true;
        $this->_ERROR_TEXT[] = 'Connection error: Failed to create role';
        return false;
    }


    //
    // GRANT PERMISSIONS
    // ========================================
    function createGrants($dbname, $dbuser)
    {
        $conn = $this->_connect();
        if ($conn)
        {
            $dbconnected = $this->getConnectedDBname();
            if ($dbname == $dbconnected)
            {
                $this->setQuery("GRANT SELECT ON ALL TABLES IN SCHEMA public TO ".addslashes($dbuser).";");
                if (!$this->runQuery())
                {
                    $this->_disconnect();
                    $this->_ERROR = true;
                    $this->_ERROR_TEXT[] = 'Failed to grant permissions on tables in schema public';
                    return false;
                }
                $this->setQuery("GRANT SELECT ON ALL TABLES IN SCHEMA pg_catalog TO ".addslashes($dbuser).";");
                if (!$this->runQuery())
                {
                    $this->_disconnect();
                    $this->_ERROR = true;
                    $this->_ERROR_TEXT[] = 'Failed to grant permissions on tables in schema pg_catalog';
                    return false;
                }
                $this->setQuery("GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO ".addslashes($dbuser).";");
                if (!$this->runQuery())
                {
                    $this->_disconnect();
                    $this->_ERROR = true;
                    $this->_ERROR_TEXT[] = 'Failed to grant permissions on sequences';
                    return false;
                }
                $this->setQuery("GRANT ALL PRIVILEGES ON DATABASE ".addslashes($dbname)." TO ".addslashes($dbuser).";");
                if (!$result = $this->runQuery())
                {
                    $this->_disconnect();
                    $this->_ERROR = true;
                    $this->_ERROR_TEXT[] = 'Failed to grant permissions on database';
                    return false;
                }
                $this->_disconnect();
                return $result;
            }
            else
            {
                $this->_disconnect();
                $this->_ERROR = true;
                $this->_ERROR_TEXT[] = 'Failed to grant permissions';
                return false;
            }
        }
        $this->_ERROR = true;
        $this->_ERROR_TEXT[] = 'Connection error: Failed to create User DB';
        return false;
    }


    //
    // CREATE DATABASE
    // ========================================
    function createDatabase($dbname, $owner)
    {
        $conn = $this->_connect();
        if ($conn)
        {
            $this->setQuery("CREATE DATABASE ".addslashes($dbname)." OWNER ".addslashes($owner).";");
            if ($this->runQuery())
            {
                $this->setQuery("GRANT CONNECT,TEMPORARY ON DATABASE ".addslashes($dbname)." TO public;");
                $this->runQuery();
                $this->_disconnect();
                return true;
            }
            else
            {
                $this->_disconnect();
                $this->_ERROR = true;
                $this->_ERROR_TEXT[] = 'Query error: Failed to create DB';
                return false;
            }
        }
        $this->_ERROR = true;
        $this->_ERROR_TEXT[] = 'Connection error: Failed to create User DB';
        return false;
    }


    //
    // CREATE USER AND DB
    // ========================================
    function createUserDB($dbuser, $dbname, $dbpassword)
    {
        $this->setPersistent(true);
        $createdUser = $this->createUser($dbuser, $dbpassword);
        $createdDB = $this->createDatabase($dbname, $dbuser);
        $createdGrants = $this->createGrants($dbname, $dbuser);
        $this->_disconnect(true);
        return ($createdUser && $createdDB && $createdGrants) ? true : false;
    }


    //
    // CREATE USER AND DB
    // ========================================
    function grantUserOnDB($dbuser, $dbname)
    {
        $this->setPersistent(true);
        $createdGrants = $this->createGrants($dbname, $dbuser);
        $this->_disconnect(true);
        return ($createdGrants) ? true : false;
    }


    // 
    // CREATE AND NEW USER TO EXISTING DB
    // ========================================
    function createNewUser($dbuser, $dbname, $dbpassword)
    {
        $this->setPersistent(true);
        $createdUser = $this->createUser($dbuser, $dbpassword);
        $createdGrants = $this->grantUserOnDB($dbuser, $dbname);
        $this->_disconnect(true);
        return ($createdUser && $createdGrants) ? true : false;
    }


    function getLastError()
    {
        return $this->_PG_LAST_ERROR;
    }

    function getErrors()
    {
        return ['is_error' => $this->_ERROR, 'details' => $this->_ERROR_TEXT];
    }

    function getQueries()
    {
        return $this->_PG_QUERIES;
    }

    private function runQuery()
    {
        if ($query = $this->getQuery())
        {
            if ($result = pg_query($this->_PG_CONN, $query))
            {
                $this->setQuery(false);
                return $result;
            }
            else
            {
                $this->setQuery(false);
                $this->_PG_LAST_ERROR = pg_last_error($this->_PG_CONN);
                $this->_ERROR = true;
                $this->_ERROR_TEXT[] = 'Failed to run query: '. $query .', error: '.$this->_PG_LAST_ERROR;
                return false;
            }
        }
        else
        {
            $this->_ERROR = true;
            $this->_ERROR_TEXT[] = 'Can not run empty query';
            return false;
        }
    }

    private function setQuery($str)
    {
        if ($str) $this->_PG_QUERIES[] = sprintf("[%s][%s]: %s", pg_dbname($this->_PG_CONN), $this->_CONNECT_DB, $str);
        $this->query = $str;
    }

    private function setDBhost($str)
    {
        $this->_PG_HOST = $str;
    }

    private function setDBport($str)
    {
        $this->_PG_PORT = $str;
    }

    private function setDBname($str)
    {
        $this->_PG_DB = $str;
    }

    private function setDBuser($str)
    {
        $this->_PG_USER = $str;
    }

    private function setDBpassword($str)
    {
        $this->_PG_PASSWORD = $str;
    }

    private function setPersistent($bool)
    {
        $this->_PG_PERSISTENT = ($bool) ? true : false;
    }


    private function getQuery()
    {
        return $this->query;
    }

    private function getDBhost()
    {
        return $this->_PG_HOST;
    }

    private function getDBport()
    {
        return $this->_PG_PORT;
    }

    private function getDBname()
    {
        return $this->_PG_DB;
    }

    private function getDBuser()
    {
        return $this->_PG_USER;
    }

    private function getDBpassword()
    {
        return $this->_PG_PASSWORD;
    }

    private function _connect()
    {
        $conn = false;
        $user = $this->getDBuser();
        $password = $this->getDBpassword();
        $host = $this->getDBhost();
        $port = $this->getDBport();
        $dbname = $this->getDBname();

        if ($user && $password && $host)
        {
            $this->_CONNECT_DB = "user=".$user;
            if ($password) $this->_CONNECT_DB .= " password=". $password;
            if ($host) $this->_CONNECT_DB .= " host=". $host;
            if ($port) $this->_CONNECT_DB .= " port=". intval($port);
            if ($dbname && ($dbname !== '*')) $this->_CONNECT_DB .= " dbname=". $dbname;
            $conn = pg_connect($this->_CONNECT_DB);
        }
        $this->_PG_CONN=$conn;
        return $this->_PG_CONN;
    }

    private function _disconnect($force=false)
    {
        if ($force === true)
        {
            if ($this->_PG_CONN) pg_close($this->_PG_CONN);
        }
        else
        {
            if ($this->_PG_CONN && ($this->_PG_PERSISTENT == false)) pg_close($this->_PG_CONN);
        }
    }
}
// END
