PLUGIN_HOME_TITLE="PostgreSQL Management";
PLUGIN_HOME_DESCRIPTION="Here you can manage PostgreSQL databases available for your account";
PLUGIN_FOOTER_TITLE="Plugin version %s written by Poralix";

PLUGIN_ADMIN_PGSQL_ERROR="PHP extension pgsql is not loaded. You need to build PHP with pgsql module.";
PLUGIN_ADMIN_PGSQL_OK="The pgsql module is loaded in PHP - OK";
PLUGIN_ADMIN_PG_CONNECT_ERROR="PHP function pg_connect() is not available. You need to build PHP with pgsql module.";
PLUGIN_ADMIN_PG_CONNECT_OK="PHP function pg_connect() is available - OK";
PLUGIN_ADMIN_PG_CONNECTED_ERROR="Failed to connect to PostgreSQL server with a password authentication. You might need to check its settings.";
PLUGIN_ADMIN_PG_CONNECTED_OK="Connected to PostgreSQL server with a password authentication - OK";

PLUGIN_DB_SIZE="Usage";
PLUGIN_DB_COUNT="Database Count";
PLUGIN_DB_CREATE="Create database";
PLUGIN_DB_CREATE_NEW="Create new database";
PLUGIN_DB_RESTORE="Upload backup";
PLUGIN_DB_DOWNLOAD="Download";
PLUGIN_DB_REPAIR="Repair";
PLUGIN_DB_CHECK="Check";
PLUGIN_DB_REINDEX="Reindex";
PLUGIN_DB_VACUUM="Vacuum";
PLUGIN_DB_OPTIMIZE="Optimize";
PLUGIN_DB_DETAILS="View Database";
PLUGIN_DB_DELETE="Delete";
PLUGIN_DB_SELECTED="selected";
PLUGIN_DB_SELECT="Select database";
PLUGIN_DB_FILE="Select file to upload";
PLUGIN_DB_SAME_USERNAME="Same as database name";

PLUGIN_TH_USERNAME="User";
PLUGIN_TH_CHANGE_PASSWORD="Password";
PLUGIN_TH_MODIFY_PRIVILEGES="Privileges";
PLUGIN_CHANGE_PASSWORD="Change Password";
PLUGIN_MODIFY_PRIVILEGES="Change Privileges";
PLUGIN_USER_DELETE="Delete";
PLUGIN_USER_SELECTED="selected"
PLUGIN_USER_CREATE="Create New User";
PLUGIN_USER_EXISTING_ADD="Add Existing User";
PLUGIN_USERS_NOT_FOUND="Users not found";

PLUGIN_GO_BACK="Back";
PLUGIN_GO_BACK_LINK="<a href='/CMD_PLUGINS/postgresql/?'>Go back</a>";
PLUGIN_RELOAD="Reload";
PLUGIN_LOADING_STATUS="Loading...";

PLUGIN_CONNECT_PHPPGADMIN="Connect to phpPgAdmin";

PLUGIN_TH_DATABASE="Database";
PLUGIN_TH_DBOWNER="Owner";
PLUGIN_TH_DBSIZE="Size";

PLUGIN_DB_NAME="Database Name";
PLUGIN_DB_USER="Database User";
PLUGIN_DB_PASSWORD="Database Password";
PLUGIN_USER_PASSWORD="Username Password";
PLUGIN_DATABASES_NOT_FOUND="Databases not found";

PLUGIN_USER_CREATE="Create New User";
PLUGIN_USER_ADD="Add Existing User";

PLUGIN_MODAL_CLOSE="Close";
PLUGIN_SAVE_CHANGES="Save";

TRY_AGAIN_LATER="<a href='/CMD_PLUGINS/postgresql/?'>Try again later...</a>";

OK_MESSAGE_USER_GRANTED="Access to the selected databases has been granted to the user per your request. Use the following details to connect to it: <div class='text-left' style='padding-top:15px;'><ul><li>Username: %s<li>Password: %s<li>Database: %s<li>Hostname: %s<li>Port: %d</ul></div><a href='?database=%s'>Go back</a>";
OK_MESSAGE_USER_CREATED="A new user has been created per your request. Use the following details to connect to it: <div class='text-left' style='padding-top:15px;'><ul><li>Username: %s<li>Password: %s<li>Database: %s<li>Hostname: %s<li>Port: %d</ul></div><a href='?database=%s'>Go back</a>";
OK_MESSAGE_PASSWORD_CHANGED="User's password has been changed per your request. Use the following details to connect to it: <div class='text-left' style='padding-top:15px;'><ul><li>Username: %s<li>Password: %s<li>Database: %s<li>Hostname: %s<li>Port: %d</ul></div><a href='?database=%s'>Go back</a>";
OK_MESSAGE_CREATED_DB="A new DB has been created per your request. Use the following details to connect to it: <div class='text-left' style='padding-top:15px;'><ul><li>Username: %s<li>Password: %s<li>Database: %s<li>Hostname: %s<li>Port: %d</ul></div><a href='?'>Go back</a>";
OK_MESSAGE_COMPLETED_ACTION_ON_DB="<div style='padding-bottom: 10px;'>A requested action: '%s' has been successfully completed on the selected database(s): %s.</div><a href='?'>Go back</a>";
OK_MESSAGE_COMPLETED_ACTION_ON_USERS="<div style='padding-bottom: 10px;'>A requested action: '%s' has been successfully completed on the selected users: %s.</div><a href='?database=%s'>Go back</a>";

ERROR_MESSAGE_UNKNOWN_ACTION="Don't known what to do...";
ERROR_DETAILS_UKNOWN_DETAILS="Your request can not be processed....";
ERROR_MESSAGE_PGSQL_NOT_ALLOWED="You are not allowed to create PostgreSQL databases";
ERROR_DETAILS_PGSQL_NOT_ALLOWED="Your hosting package does not allow to use PostgreSQL server. Contact your hosting company for more details";
ERROR_MESSAGE_PGSQL_LIMIT_HIT="A limit on number of databases hit";
ERROR_DETAILS_PGSQL_LIMIT_HIT="You are not allowed to create more databases. Contact your hosting company for more details";
ERROR_MESSAGE_FAILED_CREATE_DB="Failed to create new DB";
ERROR_DETAILS_FAILED_CREATE_DB="An error occurred while trying to create a new DB for your account. Contact your hosting company for more details";
ERROR_MESSAGE_FAILED_DOWNLOAD="Download failed";
ERROR_DETAILS_FAILED_DOWNLOAD="An error occurred while creating dump of the requested database. Contact your hosting company for more details";
ERROR_DETAILS_FAILED_LIST_DATABASES="An error occurred while getting a list of databases. Contact your hosting company for more details";
ERROR_MESSAGE_FAILED_DETAILS="Failed to get details for the database";
ERROR_DETAILS_FAILED_OWNER="The requested database either does not exist on the server or is not owned by you. Contact your hosting company for more details";
ERROR_DETAILS_FAILED_DBUSER="The requested username either does not exist on the server or is not owned by you. Contact your hosting company for more details";
ERROR_MESSAGE_FAILED_ACTION_ON_DB="Requested action failed";
ERROR_DETAILS_FAILED_ACTION_ON_DB="An error occurred while performing the selected action on database(s). Contact your hosting company for more details";
ERROR_MESSAGE_FAILED_RESTORE_DB="Failed to restore Database";
ERROR_DETAILS_FAILED_RESTORE_DB="An error occurred while restoring the selected database from a dump. Contact your hosting company for more details";
ERROR_DETAILS_FAILED_CONNECT_DB="An error occurred while connecting to the database with the specified credentials. Please make sure to use a correct username/password";
ERROR_DETAILS_ERROR_CODE="An error occurred while performing a '%s' action. Contact your hosting company for more details. ERROR CODE: %s-%d";
ERROR_DETAILS_FAILED_GRANT="An error occurred while adding an existing user to the selected database. Contact your hosting company for more details";
ERROR_DETAILS_FAILED_DBUSER_EXISTS="The user already exists. Try and create an user with another name.";
ERROR_DETAILS_FAILED_ALREADY_GRANTED="The user already is allowed to access the selected Database.";
ERROR_MESSAGE_FAILED_PHPPGADMIN="Failed to open phpPgAdmin";
ERROR_DETAILS_FAILED_PHPPGADMIN="An error occurred while redirecting to phpPgAdmin. Contact your hosting company for more details";
