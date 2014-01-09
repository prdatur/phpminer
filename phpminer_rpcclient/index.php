<?php

declare(ticks = 1);
/* * ********* CONFIG ***************** */

// Service IP.
// This address is used to bind the service.
// If you provide 0.0.0.0 all interface are bound, this means that the api is connectable at any ip-address on this machine.
// Provide 127.0.0.1 to only allow localhost.
// If your rig is within your local network, provide the ip address which you eather configurated by your self or got from your router per DHCP.
$config['ip'] = '127.0.0.1';

// Service port, change it to your needs, please keep in mind, in Linux ports lower 1000 can only be created by user root.
$config['port'] = 11111;

// RPC Secret key.
$config['rpc_key'] = '3_Kebju-55Xn-EigZb';

// The path + file where the cgminer.conf is.
// Please make sure that the user which run's this script has the permission to edit this file.
$config['cgminer_config_path'] = '/opt/cgminer/cgminer.conf';

// The path where the cgminer executable is.
// Please make sure that the user which run's this script has the permission to start cgminer.
$config['cgminer_path'] = '/opt/cgminer';

// Path to AMD SDK if available (Normally this is only needed within Linux)
$config['amd_sdk'] = '';

/* * ********* CONFIG END ************* */

$check_file = '/tmp/phpminer_rpcclient.pid';

function sig_handler($signo) {
    global $check_file;
    switch ($signo) {
        case SIGTERM:
        case SIGINT:
            unlink($check_file);
            exit;
    }
}

pcntl_signal(SIGTERM, "sig_handler");
pcntl_signal(SIGINT, "sig_handler");

if (file_exists($check_file)) {
    exit;
}

file_put_contents($check_file, getmypid());

$is_windows = (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN');
$sockets = $clients = array();

function response(&$socket, $msg = 'ok', $error = false) {
    $buffer = json_encode(array(
        'msg' => $msg,
        'error' => $error,
    ));
    write_buffer($socket, $buffer);
    disconnect($socket);
}

function is_cgminer_running() {
    global $is_windows;
    if ($is_windows) {
        exec("tasklist 2>NUL", $task_list);
        foreach ($task_list AS $task) {
            if (preg_match("/cgminer\.exe/", $task)) {
                return true;
            }
        }
        return false;
    } else {
        $res = trim(shell_exec("ps a | grep \"cgminer -c\" | grep -v grep | grep -v SCREEN | grep -v \"php -f \" | awk '{print $1}'"));
        return !empty($res);
    }
}

function is_cgminer_defunc() {
    global $is_windows;
    if ($is_windows) {
        return false;
    } else {
        $res = trim(shell_exec("ps a | grep cgminer | grep defunc | grep -v grep | grep -v SCREEN | grep -v \"php -f \" | awk '{print $1}'"));
        return !empty($res);
    }
}

function restart_cgminer() {
    global $is_windows, $config;
    if (is_cgminer_running()) {
        return;
    }
    if (!$is_windows) {
        $cmd = "#!/bin/bash\n"
                . "export GPU_MAX_ALLOC_PERCENT=100;\n"
                . "export GPU_USE_SYNC_OBJECTS=1;\n"
                . "export DISPLAY=:0;\n";
        if (!empty($config['amd_sdk'])) {
            $cmd .= "export LD_LIBRARY_PATH=" . escapeshellarg($config['amd_sdk']) . ":;\n";
        }
        $cmd .= "cd " . escapeshellarg($config['cgminer_path']) . ";\n";
        $cmd .= "screen -d -m -S cgminer ./cgminer -c " . escapeshellarg($config['cgminer_config_path']) . "\n";

        file_put_contents('/tmp/startcg', $cmd);
        @chmod('/tmp/startcg', 0777);
        shell_exec('/tmp/startcg');
        unlink('/tmp/startcg');
    } else {
        $cmd = ""
                . "setx GPU_MAX_ALLOC_PERCENT 100\n"
                . "setx GPU_USE_SYNC_OBJECTS 1\n";
        $cmd .= "cd " . escapeshellarg($config['cgminer_path']) . "\n";
        $cmd .= "cgminer.exe -c " . escapeshellarg($config['cgminer_config_path']) . "\n";
        $temp = sys_get_temp_dir();
        if (!preg_match("/(\/|\\)$/", $temp)) {
            $temp .= "\\";
        }
        file_put_contents($temp . '\startcg.bat', $cmd);
        pclose(popen('start ' . $temp . '\startcg.bat', 'r'));
        sleep(2);
        unlink($temp . '\startcg.bat');
    }
}

function reboot() {
    global $is_windows;
    if ($is_windows) {
        exec('shutdown -r NOW');
    } else {
        $user = trim(shell_exec("ps uh " . getmypid() . " | awk '{print $1'}"));

        // Any time just try to call "reboot" maybe the user can call it.
        exec('shutdown -r NOW');

        // If the user of the cron.php is root, we can call reboot, so don't try sudo fallback.
        if ($user !== 'root') {

            // Call sudo fallback.
            exec('sudo shutdown -r NOW');
        }
    }
}

function log_console($msg) {
    echo date('d.m.Y H:i:s') . ': ' . $msg . "\n";
}

function read_buffer($resource) {
    $buffer = '';
    $buffsize = 8192;
    $metadata['unread_bytes'] = 0;
    do {
        if (feof($resource)) {
            return false;
        }
        $result = fread($resource, $buffsize);
        if ($result === false) {
            return false;
        }
        $buffer .= $result;
        $metadata = stream_get_meta_data($resource);
        $buffsize = ($metadata['unread_bytes'] > $buffsize) ? $buffsize : $metadata['unread_bytes'];
    } while ($metadata['unread_bytes'] > 0);
    return $buffer;
}

function write_buffer($resource, $string) {
    $string_length = strlen($string);
    for ($written = 0; $written < $string_length; $written += $fwrite) {
        $fwrite = @fwrite($resource, substr($string, $written));
        if ($fwrite === false) {
            return false;
        } elseif ($fwrite === 0) {
            return false;
        }
    }
    return $written;
}

function disconnect($socket) {
    global $clients, $sockets;
    if (isset($clients[(int) $socket])) {
        /* @var $client WebSocketUser */
        $client = $clients[(int) $socket];
        stream_socket_shutdown($socket, STREAM_SHUT_RDWR);
        unset($sockets[$client['id']]);
        unset($clients[(int) $socket]);
    }
}

/**
 * Format a json string into pretty looking.
 * 
 * @param string $json
 *  The json which needs to be formated.
 * 
 * @return string
 *   The pretty printed json.
 */
function prettyPrint($json) {
    $result = '';
    $level = 0;
    $prev_char = '';
    $in_quotes = false;
    $ends_line_level = NULL;
    $json_length = strlen($json);

    for ($i = 0; $i < $json_length; $i++) {
        $char = $json[$i];
        $new_line_level = NULL;
        $post = "";
        if ($ends_line_level !== NULL) {
            $new_line_level = $ends_line_level;
            $ends_line_level = NULL;
        }
        if ($char === '"' && $prev_char != '\\') {
            $in_quotes = !$in_quotes;
        } else if (!$in_quotes) {
            switch ($char) {
                case '}': case ']':
                    $level--;
                    $ends_line_level = NULL;
                    $new_line_level = $level;
                    break;

                case '{': case '[':
                    $level++;
                case ',':
                    $ends_line_level = $level;
                    break;

                case ':':
                    $post = " ";
                    break;

                case " ": case "\t": case "\n": case "\r":
                    $char = "";
                    $ends_line_level = $new_line_level;
                    $new_line_level = NULL;
                    break;
            }
        }
        if ($new_line_level !== NULL) {
            $result .= "\n" . str_repeat("\t", $new_line_level);
        }
        $result .= $char . $post;
        $prev_char = $char;
    }

    return $result;
}

log_console('Starting RPC Server at ' . $config['ip'] . ' on port ' . $config['port']);
$master = stream_socket_server('tcp://' . $config['ip'] . ':' . $config['port'], $errno, $err, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, stream_context_create()) or die("socket_create() failed");
$sockets[] = $master;

function &get_user_by_socket($socket) {
    global $clients;
    $found = null;
    foreach ($clients as &$user) {
        if ($user['socket'] == $socket) {
            $found = $user;
            break;
        }
    }
    return $found;
}

while (true) {
    $changed_sockets = $sockets;
    @stream_select($changed_sockets, $write = null, $except = null, 0, 5000);
    foreach ($changed_sockets as $socket) {
        if ($socket == $master) {
            if (($client_socket = stream_socket_accept($master)) === false) {
                log_console('Socket error: ' . socket_strerror(socket_last_error($ressource)));
                continue;
            }
            $client = array(
                'id' => uniqid(),
                'socket' => &$client_socket
            );

            $clients[(int) $client_socket] = $client;
            $sockets[$client['id']] = &$client_socket;
        } else {

            $data = read_buffer($socket);

            $bytes = strlen($data);
            if ($bytes === 0 || $data === false) {
                disconnect($socket);
                exit;
            }

            $user = get_user_by_socket($socket);

            $rpc_data = json_decode($data, true);

            if (empty($rpc_data)) {
                log_console('Incoming data request');
                response($socket, 'No data', true);
            }

            if (!isset($rpc_data) || !isset($rpc_data['rpc_key']) || $rpc_data['rpc_key'] !== $config['rpc_key']) {
                log_console('Incoming data request');
                response($socket, 'RPC Key not found or invalid.', true);
            }

            if (!isset($rpc_data['command'])) {
                log_console('Incoming data request');
                response($socket, 'No command specified', true);
            }

            if ($rpc_data['command'] !== 'ping') {
                log_console('Incoming data request: ' . $rpc_data['command']);
            }

            switch ($rpc_data['command']) {
                case 'ping':
                    response($socket, 'pong');
                    continue;
                case 'is_cgminer_defunc':
                    response($socket, is_cgminer_defunc());
                    continue;
                case 'is_cgminer_running':
                    response($socket, is_cgminer_running());
                    continue;
                case 'restart_cgminer':
                    response($socket, restart_cgminer(), true);
                    continue;
                case 'reboot':
                    response($socket, reboot());
                    continue;
                case 'set_config':

                    $conf = json_decode(file_get_contents($config['cgminer_config_path']), true);

                    if (empty($rpc_data['data']['value'])) {
                        if (isset($conf[$rpc_data['data']['key']])) {
                            unset($conf[$rpc_data['data']['key']]);
                        }
                    } else {
                        if (isset($rpc_data['data']['gpu'])) {

                            if (!isset($conf[$rpc_data['data']['key']])) {
                                $conf[$rpc_data['data']['key']] = '';
                            }
                            $config_values = explode(",", $conf[$rpc_data['data']['key']]);
                            if (empty($config_values)) {
                                $config_values = array();
                            }

                            $device_values = array();
                            if (isset($rpc_data['data']['current_values']) && !empty($rpc_data['data']['current_values'])) {
                                $device_values = explode(",", $rpc_data['data']['current_values']);
                                if (empty($device_values)) {
                                    $device_values = array();
                                }
                            }
                            $device_count = $rpc_data['data']['devices'];
                            for ($i = 0; $i < $device_count; $i++) {
                                if (!isset($config_values[$i])) {
                                    $config_values[$i] = (!isset($device_values[$i])) ? 0 : $device_values[$i];
                                }
                            }
                            $config_values[$rpc_data['data']['gpu']] = $rpc_data['data']['value'];
                            $conf[$rpc_data['data']['key']] = implode(",", $config_values);
                        } else {
                            if ($rpc_data['data']['value'] === 'true') {
                                $rpc_data['data']['value'] = true;
                            }
                            if ($rpc_data['data']['value'] === 'false') {
                                $rpc_data['data']['value'] = false;
                            }
                            $conf[$rpc_data['data']['key']] = $rpc_data['data']['value'];
                        }
                    }

                    if (file_put_contents($config['cgminer_config_path'], str_replace('\\/', '/', prettyPrint(json_encode($conf)))) === false) {
                        response($socket, 'Could not write config file', true);
                    }
                    response($socket);
                    continue;
                case 'get_config':
                    response($socket, file_get_contents($config['cgminer_config_path']));
                    continue;
                case 'check_cgminer_config_path':
                    response($socket, '', !is_writable($config['cgminer_config_path']));
                    continue;
            }
        }
    }
}

