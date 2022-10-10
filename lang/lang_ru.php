PLUGIN_HOME_TITLE="Управление PostgreSQL";
PLUGIN_HOME_DESCRIPTION="Управляйте базами PostgreSQL на своем аккаунте";
PLUGIN_FOOTER_TITLE="Плагин версии %s написан командой Poralix";

PLUGIN_ADMIN_PGSQL_ERROR="PHP расширение pgsql не доступно. Пересоберите PHP с поддержкой модуля pgsql.";
PLUGIN_ADMIN_PGSQL_OK="PHP расширение доступно - OK";
PLUGIN_ADMIN_PG_CONNECT_ERROR="PHP функция pg_connect() не доступна. Пересоберите PHP с поддержкой модуля pgsql.";
PLUGIN_ADMIN_PG_CONNECT_OK="PHP функция pg_connect() доступна - OK";
PLUGIN_ADMIN_PG_CONNECTED_ERROR="Попытка подключения к серверу PostgreSQL с паролем не удалась. Проверьте настройки сервера PostgreSQL и плагина.";
PLUGIN_ADMIN_PG_CONNECTED_OK="Подключение к серверу PostgreSQL прошло успешно - OK";

PLUGIN_DB_SIZE="Размер";
PLUGIN_DB_COUNT="Количество баз";
PLUGIN_DB_CREATE="Создать базу";
PLUGIN_DB_CREATE_NEW="Создать новую БД";
PLUGIN_DB_RESTORE="Восстановить БД";
PLUGIN_DB_DOWNLOAD="Скачать";
PLUGIN_DB_REPAIR="Починить";
PLUGIN_DB_CHECK="Проверить";
PLUGIN_DB_REINDEX="Перестроить индексы";
PLUGIN_DB_VACUUM="Перестроить";
PLUGIN_DB_OPTIMIZE="Оптимизировать";
PLUGIN_DB_DETAILS="Посмотреть БД";
PLUGIN_DB_DELETE="Удалить";
PLUGIN_DB_SELECTED="выбрано";
PLUGIN_DB_SELECT="Выбрать БД";
PLUGIN_DB_FILE="Выбрать файл для загрузки";
PLUGIN_DB_SAME_USERNAME="Совпадает с именем БД";

PLUGIN_TH_USERNAME="Пользователь";
PLUGIN_TH_CHANGE_PASSWORD="Пароль";
PLUGIN_TH_MODIFY_PRIVILEGES="Привилегии";
PLUGIN_CHANGE_PASSWORD="Изменить пароль";
PLUGIN_MODIFY_PRIVILEGES="Изменить привилегии";
PLUGIN_USER_DELETE="Удалить";
PLUGIN_USER_SELECTED="выбрано"
PLUGIN_USER_CREATE="Создать нового пользователя";
PLUGIN_USER_EXISTING_ADD="Добавить существующего пользователя";
PLUGIN_USERS_NOT_FOUND="Пользователи не найдены";

PLUGIN_GO_BACK="Вернуться";
PLUGIN_GO_BACK_LINK="<a href='/CMD_PLUGINS/postgresql/?'>Вернуться назад</a>";
PLUGIN_RELOAD="Обновить";
PLUGIN_LOADING_STATUS="Идет загрузка...";

PLUGIN_CONNECT_PHPPGADMIN="Подключиться к phpPgAdmin";

PLUGIN_TH_DATABASE="База данных";
PLUGIN_TH_DBOWNER="Владелец";
PLUGIN_TH_DBSIZE="Размер";

PLUGIN_DB_NAME="Имя базы данных";
PLUGIN_DB_USER="Пользователь БД";
PLUGIN_DB_PASSWORD="Пароль БД";
PLUGIN_USER_PASSWORD="Пароль пользователя";
PLUGIN_DATABASES_NOT_FOUND="Базы данных не обнаружены";

PLUGIN_USER_CREATE="Создать нового пользователя";
PLUGIN_USER_ADD="Добавить существующего пользователя";

PLUGIN_MODAL_CLOSE="Закрыть";
PLUGIN_SAVE_CHANGES="Сохранить";

TRY_AGAIN_LATER="<a href='/CMD_PLUGINS/postgresql/?'>Попробуйте еще раз позже...</a>";

OK_MESSAGE_USER_GRANTED="В соответствии с вашим запросом доступ к выбранным БД был предоставлен пользователю со следующими учетными данными: <div class='text-left' style='padding-top:15px;'><ul><li>Имя пользователя: %s<li>Пароль: %s<li>База данных: %s<li>Имя хоста: %s<li>Порт: %d</ul></div><a href='?database=%s'>Вернуться назад</a>";
OK_MESSAGE_USER_CREATED="В соответствии с вашим запросом был создан новый пользователь со следующими учетными данными: <div class='text-left' style='padding-top:15px;'><ul><li>Имя пользователя: %s<li>Пароль: %s<li>База данных: %s<li>Имя хоста: %s<li>Порт: %d</ul></div><a href='?database=%s'>Вернуться назад</a>";
OK_MESSAGE_PASSWORD_CHANGED="В соответствии с вашим запросом пароль пользователя был изменен. Используйте для подключения следующие данные: <div class='text-left' style='padding-top:15px;'><ul><li>Имя пользователя: %s<li>Пароль: %s<li>База данных: %s<li>Имя хоста: %s<li>Порт: %d</ul></div><a href='?database=%s'>Вернуться назад</a>";
OK_MESSAGE_CREATED_DB="В соответствии с вашим запросом была создана новая База Данных. Используйте для подключения следующие данные: <div class='text-left' style='padding-top:15px;'><ul><li>Имя пользователя: %s<li>Пароль: %s<li>База данных: %s<li>Имя хоста: %s<li>Порт: %d</ul></div><a href='?database=%s'>Вернуться назад</a>";
OK_MESSAGE_COMPLETED_ACTION_ON_DB="<div style='padding-bottom: 10px;'>Выполнены следующие действия: '%s' в отношении следующих Баз Данных: %s.</div><a href='?'>Вернуться назад</a>";
OK_MESSAGE_COMPLETED_ACTION_ON_USERS="<div style='padding-bottom: 10px;'>Выполнены следующие действия: '%s' в отношении следующих пользователей: %s.</div><a href='?database=%s'>Вернуться назад</a>";

ERROR_MESSAGE_UNKNOWN_ACTION="Запрошено неивестное действие...";
ERROR_DETAILS_UKNOWN_DETAILS="Ваш запрос не может быть выполнен....";
ERROR_MESSAGE_PGSQL_NOT_ALLOWED="Вам не разрешено создавать Базы Данных на сервере PostgreSQL";
ERROR_DETAILS_PGSQL_NOT_ALLOWED="Ваш тарифный план не поддерживает создание Баз Данных на сервере PostgreSQL. Обратитесь в вашу хостинг компанию за дополнительной информацией";
ERROR_MESSAGE_PGSQL_LIMIT_HIT="Достигнут лимит на количество Баз Данных";
ERROR_DETAILS_PGSQL_LIMIT_HIT="Вам не разрешается создавать новые базы данных на сервере PostgreSQL. Обратитесь в вашу хостинг компанию за дополнительной информацией";
ERROR_MESSAGE_FAILED_CREATE_DB="База Данных не создана";
ERROR_DETAILS_FAILED_CREATE_DB="Во время создания новой базы данных на сервере PostgreSQL произошла ошибка. Обратитесь в вашу хостинг компанию за дополнительной информацией";
ERROR_MESSAGE_FAILED_DOWNLOAD="Загрузка провалилась";
ERROR_DETAILS_FAILED_DOWNLOAD="Во время создания резервной копии базы данных на сервере PostgreSQL произошла ошибка. Обратитесь в вашу хостинг компанию за дополнительной информацией";
ERROR_DETAILS_FAILED_LIST_DATABASES="Во время получения списка баз данных на сервере PostgreSQL произошла ошибка. Обратитесь в вашу хостинг компанию за дополнительной информацией";
ERROR_MESSAGE_FAILED_DETAILS="Детали БД недоступны";
ERROR_DETAILS_FAILED_OWNER="Запрошенная БД на сервере остутствует либо принадлежит другому пользователю. Обратитесь в вашу хостинг компанию за дополнительной информацией";
ERROR_DETAILS_FAILED_DBUSER="Запрошенный пользователь БД на сервере остутствует либо принадлежит другому аккаунту. Обратитесь в вашу хостинг компанию за дополнительной информацией";
ERROR_MESSAGE_FAILED_ACTION_ON_DB="Не могу завершить операцию";
ERROR_DETAILS_FAILED_ACTION_ON_DB="Во время выполнения запрошенной операции на сервере PostgreSQL произошла ошибка. Обратитесь в вашу хостинг компанию за дополнительной информацией";
ERROR_MESSAGE_FAILED_RESTORE_DB="Не могу восстановить базу данных";
ERROR_DETAILS_FAILED_RESTORE_DB="Во время восстановления базы данных из резервной копии на сервере PostgreSQL произошла ошибка. Обратитесь в вашу хостинг компанию за дополнительной информацией";
ERROR_DETAILS_FAILED_CONNECT_DB="Во время подключения к серверу PostgreSQL произошла ошибка. Неверное сочетанию имени пользователя и пароля.";
ERROR_DETAILS_ERROR_CODE="Во время выполенния операции '%s' произошла ошибка. Обратитесь в вашу хостинг компанию за дополнительной информацией. ERROR CODE: %s-%d";
ERROR_DETAILS_FAILED_GRANT="Во время добавления пользователя в список доступа для выбранной БД произошла ошибка. Обратитесь в вашу хостинг компанию за дополнительной информацией";
ERROR_DETAILS_FAILED_DBUSER_EXISTS="Пользователь с таким именем уже существует. Используйте доугое имя.";
ERROR_DETAILS_FAILED_ALREADY_GRANTED="Этот пользователь уже имеет доступ к выбранной БД";
ERROR_MESSAGE_FAILED_PHPPGADMIN="Не могу открыть phpPgAdmin";
ERROR_DETAILS_FAILED_PHPPGADMIN="Во время подключения к phpPgAdmin произошла ошибка. Обратитесь в вашу хостинг компанию за дополнительной информацией";
