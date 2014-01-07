Soopfw.behaviors.system_setup = function() {

    var dialog = "";

    dialog += '    Could not connect to cgminer with settings: <b>127.0.0.1:' + phpminer.settings.cgminer.port + '</b><br /><br />';
    dialog += '    Please provide the connection settings to your cgminer. <br />';
    dialog += '    You can find it within cgminer.conf <br />';
    dialog += '    Please make sure to enable api-listen and set "api-allow": "W:127.0.0.1"<br />';
    dialog += '    The port is written at <b>api-port</b> (default: 4028)<br />';
    dialog += '    <div class="simpleform">';
    dialog += '        <div class="form-element">';
    dialog += '            <label for="port">Port:</label>';
    dialog += '            <input type="text" name="port" id="port" value="' + phpminer.settings.cgminer.port + '"/> ';
    dialog += '        </div>';
    dialog += '    </div>';


    make_modal_dialog('Setup', dialog,
        [
            {
                title: 'Check connect',
                type: 'primary',
                id: 'setup_check_connection',
                data: {
                    "loading-text": 'Checking connection...'
                },
                click: function() {
                    var that = this;
                    ajax_request(murl('main', 'check_connection'), {
                        'port': $('#port').val()
                    }, function() {
                        Soopfw.reload();
                    }, function() {
                        $('#setup_check_connection').button('reset');
                    });
                }
            }
        ], {
        width: 660,
        keyboard: false,
        cancelable: false
    });
};

