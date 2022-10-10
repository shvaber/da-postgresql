    // plugins.js
    jQuery.noConflict();
    (function( $ ) {
        $(function()
        {
            var px_startPreload = function()
            {
                console.log('[OK] start preloading...');
                if ($("#px_plugin-submit-active").length)  $("#px_plugin-submit-active").addClass("sr-only");
                if ($("#px_plugin-submit-spinner").length) $("#px_plugin-submit-spinner").removeClass("sr-only");
                if ($("#px_plugin-submit-loading").length) $("#px_plugin-submit-loading").removeClass("sr-only");
                $("#px_plugin-container").find('input, textarea, button, select').attr('disabled','disabled');
            }
            var px_stopPreload = function()
            {
                console.log('[OK] stop preloading...');
                if ($("#px_plugin-submit-active").length)  $("#px_plugin-submit-active").removeClass("sr-only");
                if ($("#px_plugin-submit-spinner").length) $("#px_plugin-submit-spinner").addClass("sr-only");
                if ($("#px_plugin-submit-loading").length) $("#px_plugin-submit-loading").addClass("sr-only");
                $("#px_plugin-container").find('input, textarea, button, select').attr('disabled',false);
                if ($("#px_plugin-confirmation").length) $("#px_plugin-confirmation").prop("checked", false);
                //px_checkRequired();
            }
            var px_hideMessages = function()
            {
                if ($("#px-message-container").length) $("#px-message-container").hide();
                if ($("#px-error-container").length) $("#px-error-container").hide();
            }
            var px_dropError = function(message)
            {
                if ($("#px-error-container").length) {
                    $("#px-error-container div").html(message)
                    $("#px-error-container").show();
                }
                if ($("#px-message-container").length) $("#px-message-container").hide();
                $("#px-phppgadmin-container").addClass("sr-only");
            }
            var px_dropMessage = function(message)
            {
                if ($("#px-message-container").length) {
                    $("#px-message-container div").html(message)
                    $("#px-message-container").show();
                }
                if ($("#px-error-container").length) $("#px-error-container").hide();
                $("#px-phppgadmin-container").addClass("sr-only");
            }
            var px_selectDbShowActionButtons = function(checkAll)
            {
                //alert("Checkbox is checked." );
                if (checkAll == true) $("table#px_plugin_db_list_user .px_plugin_select_db").prop("checked",true);
                $("#px_plugin_db_list_selected").removeClass("sr-only");
                $("#px_plugin_db_list_regular").addClass("sr-only");
                $("#px_plugin_db_select_all").prop("checked",true);
                var checked = $("table#px_plugin_db_list_user .px_plugin_select_db:checked").length;
                var total = $("table#px_plugin_db_list_user .px_plugin_select_db").length;
                $("#px_plugin_db_select_all_label").html(checked +"/"+total+" "+selected_el);
            }
            var px_selectDbHideActionButtons = function(checkAll)
            {
                //alert("Checkbox is unchecked." );
                if (checkAll == true) $("table#px_plugin_db_list_user .px_plugin_select_db").prop("checked",false);
                var checked = $("table#px_plugin_db_list_user .px_plugin_select_db:checked").length;
                var total = $("table#px_plugin_db_list_user .px_plugin_select_db").length;
                if (checked == 0) {
                    $("#px_plugin_db_list_selected").addClass("sr-only");
                    $("#px_plugin_db_list_regular").removeClass("sr-only");
                    $("#px_plugin_db_select input").prop("checked",false);
                }
                $("#px_plugin_db_select_all_label").html(checked +"/"+total+" "+selected_el);
            }
            var px_selectUserShowActionButtons = function(checkAll)
            {
                //alert("Checkbox is checked." );
                if (checkAll == true) $("table#px_plugin_user_list_user .px_plugin_select_user").prop("checked",true);
                $("#px_plugin_user_list_selected").removeClass("sr-only");
                $("#px_plugin_user_list_regular").addClass("sr-only");
                $("#px_plugin_user_select_all").prop("checked",true);
                var checked = $("table#px_plugin_user_list_user .px_plugin_select_user:checked").length;
                var total = $("table#px_plugin_user_list_user .px_plugin_select_user").length;
                $("#px_plugin_user_select_all_label").html(checked +"/"+total+" "+selected_el);
            }
            var px_selectUserHideActionButtons = function(checkAll)
            {
                //alert("Checkbox is unchecked." );
                if (checkAll == true) $("table#px_plugin_user_list_user .px_plugin_select_user").prop("checked",false);
                var checked = $("table#px_plugin_user_list_user .px_plugin_select_user:checked").length;
                var total = $("table#px_plugin_user_list_user .px_plugin_select_user").length;
                if (checked == 0) {
                    $("#px_plugin_user_list_selected").addClass("sr-only");
                    $("#px_plugin_user_list_regular").removeClass("sr-only");
                    $("#px_plugin_users_select input").prop("checked",false);
                }
                $("#px_plugin_user_select_all_label").html(checked +"/"+total+" "+selected_el);
            }
            var px_randomPasswordGenerator = function(length)
            {
                var chars1 = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
                var chars2 = "";
                for (i=33; i<=126; i++)
                {
                    if (i == 47) continue;
                    if (i == 92) continue;
                    chars2 = chars2 + String.fromCharCode(i);
                }
                var pass = "";
                var spec_chars_num = Math.floor(0); //(length/3);
                for (var x = 0; x < (length-spec_chars_num); x++) {
                    var i = Math.floor(Math.random() * chars1.length);
                    pass += chars1.charAt(i);
                }
                for (var x = 0; x < spec_chars_num; x++) {
                    var i = Math.floor(Math.random() * chars2.length);
                    pass += chars2.charAt(i);
                }
                return pass;
            }
            var px_validateDBusername = function()
            {
                var method = "GET";
                var url = '/CMD_PLUGINS/postgresql/validate.raw?type=username&value='+$("#px_input_db_user").val();
                $.ajax({
                    url: url,
                    type: method,
                    dataType: 'json',
                    success: function(response) {
                        var items = new Array();
                        var is_success = false;
                        $.each(response, function(key, val){
                            items[key] = val;
                            console.log("[OK] found "+key+" set to "+val);
                        });
                        if ((typeof items['error'] !== 'undefined') && (typeof items['message'] !== 'undefined')) {
                            if (items['error'] == true) {
                                $("#px_input_db_password").prop("required",false);
                                $("#px_input_password_container").addClass("sr-only");
                            } else {
                                $("#px_input_db_password").prop("required",true);
                                $("#px_input_password_container").removeClass("sr-only");
                            }
                        }
                    }
                });
            }
            var px_processRequest = function(url, method, data)
            {
                $.ajax({
                    url: url,
                    type: method,
                    data: data,
                    enctype: 'multipart/form-data',
                    processData: false,
                    contentType: false,
                    cache : false,
                    //dataType: 'json',
                    success: function(response) {
                        var items = new Array();
                        var is_success = false;
                        $.each(response, function(key, val){
                            items[key] = val;
                            console.log("[OK] found "+key+" set to "+val);
                        });
                        if ((typeof items['error'] !== 'undefined') && (typeof items['message'] !== 'undefined')) {
                            if (items['error'] == true) {
                                px_stopPreload(); px_dropError('<h4>Error</h4>'+items['message']+'<br><br>');
                                return false;
                            } else {
                                // Success
                                px_stopPreload(); px_dropMessage('<h4>Well done!</h4>'+items['message']);
                                $("#px_plugin-container").find('input, textarea, button, select').attr('disabled','disabled'); $("#px_plugin-main-content").hide('slow');
                                return true;
                            }
                        } else {
                            px_stopPreload(); px_dropError('Uknown error.... try again later...');
                            return false;
                        }
                        return true;
                    }
                });
            }

            $(document).ready(function()
            {
                if ($("div#px-error-container div").html().length > 1) {
                    $("div#px-error-container").show();
                }
                $("button#px_plugin_db_create_link").click(function(){
                    location.href="/CMD_PLUGINS/postgresql/create.html";
                });
                $("button#px_plugin_db_restore_link").click(function(){
                    location.href="/CMD_PLUGINS/postgresql/restore.html";
                });
                $("button#px_plugin_back_link").click(function(){
                    location.href="/CMD_PLUGINS/postgresql/";
                });
                // LIST DATABASES ACTIONS
                if ($("table#px_plugin_db_list_user").length) {
                    selected_el = $("#px_plugin_db_select_all_label").html();
                    $("#px_plugin_db_select").html("<input type='checkbox' class='' />");
                    $("table#px_plugin_db_list_user .px_plugin_select_db").click(function(){
                        if($(this).prop("checked") == true) {
                            px_selectDbShowActionButtons(false);
                        } else {
                            px_selectDbHideActionButtons(false);
                        }
                    });
                    $("table#px_plugin_db_list_user #px_plugin_db_select_all").click(function(){
                        if($(this).prop("checked") == true) {
                            px_selectDbShowActionButtons(true);
                        } else {
                            px_selectDbHideActionButtons(true);
                        }
                    });
                    $("#px_plugin_db_select input").click(function(){
                        if($(this).prop("checked") == true) {
                            $("table#px_plugin_db_list_user .px_plugin_select_db").prop('checked', true);
                            px_selectDbShowActionButtons(false);
                        } else {
                            $("table#px_plugin_db_list_user .px_plugin_select_db").prop('checked', false);
                            px_selectDbHideActionButtons(false);
                        }
                    });
                }
                // LIST USERS ACTIONS
                if ($("table#px_plugin_user_list_user").length) {
                    selected_el = $("#px_plugin_user_select_all_label").html();
                    $("#px_plugin_users_select").html("<input type='checkbox' class='' />");
                    $("table#px_plugin_user_list_user .px_plugin_select_user").click(function(){
                        // select/deselect user
                        if($(this).prop("checked") == true) {
                            px_selectUserShowActionButtons(false);
                        } else {
                            px_selectUserHideActionButtons(false);
                        }
                    });
                    $("table#px_plugin_user_list_user #px_plugin_user_select_all").click(function(){
                        // deselect all users
                        if($(this).prop("checked") == true) {
                            px_selectUserShowActionButtons(true);
                        } else {
                            px_selectUserHideActionButtons(true);
                        }
                    });
                    $("#px_plugin_users_select input").click(function(){
                        // select all users
                        if($(this).prop("checked") == true) {
                            $("table#px_plugin_user_list_user .px_plugin_select_user").prop('checked', true);
                            px_selectUserShowActionButtons(false);
                        } else {
                            $("table#px_plugin_user_list_user .px_plugin_select_user").prop('checked', false);
                            px_selectUserHideActionButtons(false);
                        }
                    });
                }
                // DATABASE GENERATE PAGE
                if ($("#px_input_db_password_generate_link").length) {
                    var content = $("#px_input_db_password_generate_link_container").html();
                    $("#px_input_db_password").before(content);
                    $("#px_input_db_password_generate_link button").click(function(event){
                        event.preventDefault();
                        var password = px_randomPasswordGenerator(16);
                        $("#px_db_password input").val(password);
                    });
                }
                // VACUUM DB
                $("#px_plugin_db_vacuum").click(function(event){
                    event.preventDefault();
                    var data = $("#px_plugin_db_actions_form").serialize();
                    var method = $("#px_plugin_db_actions_form").attr('method');
                    var url = '/CMD_PLUGINS/postgresql/vacuum.raw?r='+Math.random();
                    px_hideMessages();
                    px_startPreload();
                    px_processRequest(url, method, data);
                });
                // REINDEX DB
                $("#px_plugin_db_reindex").click(function(event){
                    event.preventDefault();
                    var data = $("#px_plugin_db_actions_form").serialize();
                    var method = $("#px_plugin_db_actions_form").attr('method');
                    var url = '/CMD_PLUGINS/postgresql/reindex.raw?r='+Math.random();
                    px_hideMessages();
                    px_startPreload();
                    px_processRequest(url, method, data);
                });
                // DELETE DB
                $("#px_plugin_db_delete").click(function(event){
                    event.preventDefault();
                    var data = $("#px_plugin_db_actions_form").serialize();
                    var method = $("#px_plugin_db_actions_form").attr('method');
                    var url = '/CMD_PLUGINS/postgresql/delete.raw?r='+Math.random();
                    px_hideMessages();
                    px_startPreload();
                    px_processRequest(url, method, data);
                });
                // DELET USER
                $("#px_plugin_user_delete").click(function(event){
                    event.preventDefault();
                    var data = $("#px_plugin_user_actions_form").serialize();
                    var method = $("#px_plugin_user_actions_form").attr('method');
                    var url = '/CMD_PLUGINS/postgresql/deluser.raw?r='+Math.random();
                    px_hideMessages();
                    px_startPreload();
                    px_processRequest(url, method, data);
                });
                // CREATE DATABASE AND USER
                $("#px_plugin_create_db_form").submit(function(event){
                    event.preventDefault();
                    var data = $(this).serialize();
                    var method = $(this).attr('method');
                    var url = '/CMD_PLUGINS/postgresql/create.raw?r='+Math.random();
                    px_hideMessages();
                    px_startPreload();
                    px_processRequest(url, method, data);

                });
                // CREATE DATABASE AND USER
                $("#px_input_db_same_username").click(function(event){
                    if($(this).prop("checked") == true) {
                        $("#px_input_db_user").prop("readonly",true);
                        $("#px_input_db_user").prop("required",false);
                    } else if($(this).prop("checked") == false) {
                        $("#px_input_db_user").prop("readonly",false);
                        $("#px_input_db_user").prop("required",true);
                    }
                });
                $("#px_input_db_name").keyup(function(event){
                    if ($("#px_input_db_same_username").prop("checked") == true) {
                        var data = $(this).val();
                        $("#px_input_db_user").val(data);
                        px_validateDBusername();
                    }
                });
                $("#px_input_db_user").keyup(function(event){
                    px_validateDBusername();
                });
                // RESTORE DATABASE
                $("#px_input_db_file").change(function(event){
                    var data = $(this).val();
                    $("#px_input_fname").val(data);
                });
                // RESTORE DATABASE
                $("#px_plugin_restore_db_form").submit(function(event){
                    event.preventDefault();
                    var form = $(this);
                    var formdata = false;
                    if (window.FormData) {formdata = new FormData(form[0]);}
                    var data = formdata ? formdata : form.serialize();
                    var method = $(this).attr('method');
                    var url = '/CMD_PLUGINS/postgresql/restore.raw?r='+Math.random();
                    px_hideMessages();
                    px_startPreload();
                    px_processRequest(url, method, data);

                });
                // CHANGE PASSWORD
                $(".px_plugin_change_password").click(function(event){
                    event.preventDefault();
                    $("#px_ModalLongTitle").html($(this).html());
                    $("#px_select_username_container").addClass("sr-only");
                    $("#px_input_username_container").addClass("sr-only");
                    $("#px_input_password_container").removeClass("sr-only");
                    $("#px_input_db_user").attr("required", false);
                    $("#px_select_db_user").attr("required", false);
                    $("#px_input_db_password").attr("required", true);
                    var url = $(this).prop('href');
                    var tmp = url.split('?');
                    var urlAux;
                    let items = new Array();
                    px_hideMessages();
                    if (typeof tmp[1] != "undefined") {
                        urlAux = tmp[1].split('&');
                        urlAux.forEach(function(item){
                            var tmp = item.split('=');
                            items[tmp[0]] = tmp[1];
                        });
                        $("#px_plugin_database_form input[name='dbpass']").val('');
                        $("#px_plugin_database_form input[name='dbname']").val(items['dbname']);
                        $("#px_plugin_database_form input[name='dbuser']").val(items['dbuser']);
                        $("#px_plugin_database_form input[name='mode']").val(items['mode']);
                    }
                });
                // CREATE NEW USER
                $("#px_plugin_user_create_link").click(function(event){
                    event.preventDefault();
                    $("#px_ModalLongTitle").html($(this).html());
                    $("#px_select_username_container").addClass("sr-only");
                    $("#px_input_username_container").removeClass("sr-only");
                    $("#px_input_password_container").removeClass("sr-only");
                    $("#px_input_db_user").attr("required", true);
                    $("#px_select_db_user").attr("required", false);
                    $("#px_input_db_password").attr("required", true);
                    $("#px_plugin_database_form input[name='dbpass']").val('');
                    $("#px_plugin_database_form input[name='dbuser']").val('');
                    $("#px_plugin_database_form input[name='mode']").val('create');
                    $('#px_ModalCenter').modal('show');
                });
                // ADD EXISTING USER
                $("#px_plugin_user_add_link").click(function(event){
                    event.preventDefault();
                    $("#px_ModalLongTitle").html($(this).html());
                    $("#px_select_username_container").removeClass("sr-only");
                    $("#px_input_username_container").addClass("sr-only");
                    $("#px_input_password_container").addClass("sr-only");
                    $("#px_input_db_user").attr("required", false);
                    $("#px_select_db_user").attr("required", true);
                    $("#px_input_db_password").attr("required", false);
                    $("#px_plugin_database_form input[name='dbpass']").val('');
                    $("#px_plugin_database_form input[name='dbuser']").val('');
                    $("#px_plugin_database_form input[name='mode']").val('existing');
                    $('#px_ModalCenter').modal('show');
                });
                // CHANGE PASSWORD
                $("#px_plugin_database_form").submit(function(event){
                    event.preventDefault();
                    var data = $(this).serialize();
                    var method = $(this).attr('method');
                    var url = '/CMD_PLUGINS/postgresql/database.raw?r='+Math.random();
                    $('#px_ModalCenter').modal('hide');
                    px_hideMessages();
                    px_startPreload();
                    px_processRequest(url, method, data);

                });
                px_stopPreload();
            });

        });
    })(jQuery);

