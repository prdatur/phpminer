<?php

/**
 * @copyright Christian Ackermann (c) 2013 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 */

/**
 * Represents the config of php cgminer.
 */
class Config {

    /**
     * The singleton instance.
     * 
     * @var Config
     */
    private static $instance = null;

    /**
     * Singleton.
     * 
     * @return Config
     *   The instance object.
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Config();
        }

        return self::$instance;
    }

    public static $possible_configs = array(
        'api-allow' => array(
            'description' => "Allow API access only to the given list of [G:]IP[/Prefix] addresses[/subnets]",
            'values' => PDT_STRING,
            'multivalue' => true,
        ),
        'api-description' => array(
            'description' => "Description placed in the API status header, default: CGMiner/SGMiner version",
            'values' => PDT_STRING,
        ),
        'api-groups' => array(
            'description' => "API one letter groups G:cmd:cmd[,P:cmd:*...] defining the cmds a groups can use",
            'values' => PDT_STRING,
            'multivalue' => true,
        ),
        'api-listen' => array(
            'description' => "Enable API, default: disabled",
            'values' => PDT_BOOL,
        ),
        'api-mcast' => array(
            'description' => "Enable API Multicast listener, default: disabled",
            'values' => PDT_BOOL,
        ),
        'api-mcast-addr' => array(
            'description' => "API Multicast listen address",
            'values' => PDT_STRING,
        ),
        'api-mcast-code' => array(
            'description' => "Code expected in the API Multicast message, don't use '-'",
            'values' => PDT_STRING,
        ),
        'api-mcast-des' => array(
            'description' => "Description appended to the API Multicast reply, default: ''",
            'values' => PDT_STRING,
        ),
        'api-mcast-port' => array(
            'description' => "API Multicast listen port",
            'values' => PDT_INT,
            'range' => array(1, 65535),
        ),
        'api-network' => array(
            'description' => "Allow API (if enabled) to listen on/for any address, default: only 127.0.0.1",
            'values' => PDT_BOOL,
            'multivalue' => true,
        ),
        'api-port' => array(
            'description' => "Port number of miner API",
            'values' => PDT_INT,
            'range' => array(1, 65535),
        ),
        'auto-fan' => array(
            'description' => "Automatically adjust all GPU fan speeds to maintain a target temperature",
            'values' => PDT_BOOL,
        ),
        'auto-gpu' => array(
            'description' => "Automatically adjust all GPU engine clock speeds to maintain a target temperature",
            'values' => PDT_BOOL,
        ),
        'balance' => array(
            'description' => "Change multipool strategy from failover to even share balance",
            'values' => PDT_BOOL,
        ),
        'benchmark' => array(
            'description' => "Run CGMiner/SGMiner in benchmark mode - produces no shares",
            'values' => PDT_BOOL,
        ),
        'bfl-range' => array(
            'description' => "Use nonce range on bitforce devices if supported",
            'values' => PDT_BOOL,
        ),
        'bflsc-overheat' => array(
            'description' => "Set overheat temperature where BFLSC devices throttle, 0 to disable",
            'values' => PDT_INT,
            'range' => array(0, 200),
        ),
        'compact' => array(
            'description' => "Use compact display without per device statistics",
            'values' => PDT_BOOL,
        ),
        'debug' => array(
            'description' => "Enable debug output",
            'values' => PDT_BOOL,
        ),
        'device' => array(
            'description' => "Select device to use, one value, range and/or comma separated (e.g. 0-2,4) default: all",
            'values' => PDT_INT,
            'multivalue' => true,
        ),
        'disable-gpu' => array(
            'description' => "Disable GPU mining even if suitable devices exist",
            'values' => PDT_BOOL,
        ),
        'disable-rejecting' => array(
            'description' => "Automatically disable pools that continually reject shares",
            'values' => PDT_BOOL,
        ),
        'expiry' => array(
            'description' => "Upper bound on how many seconds after getting work we consider a share from it stale",
            'values' => PDT_INT,
            'range' => array(0, 9999),
        ),
        'failover-only' => array(
            'description' => "Don't leak work to backup pools when primary pool is lagging",
            'values' => PDT_BOOL,
        ),
        'fix-protocol' => array(
            'description' => "Do not redirect to a different getwork protocol (eg. stratum)",
            'values' => PDT_BOOL,
        ),
        'gpu-dyninterval' => array(
            'description' => "Set the refresh interval in ms for GPUs using dynamic intensity",
            'values' => PDT_INT,
            'range' => array(1, 65535),
        ),
        'gpu-platform' => array(
            'description' => "Select OpenCL platform ID to use for GPU mining",
            'values' => PDT_INT,
            'range' => array(1, 9999),
        ),
        'gpu-threads' => array(
            'description' => "Number of threads per GPU (1 - 10)",
            'values' => PDT_INT,
            'range' => array(1, 10),
            'multivalue' => true,
        ),
        'gpu-engine' => array(
            'description' => "GPU engine (over)clock range in Mhz - one value, range and/or comma separated list (e.g. 850-900,900,750-850)",
            'values' => PDT_INT,
            'multivalue' => true,
        ),
        'gpu-fan' => array(
            'description' => "GPU fan percentage range - one value, range and/or comma separated list (e.g. 0-85,85,65)",
            'values' => PDT_INT,
            'range' => array(0, 100),
            'multivalue' => true,
        ),
        'gpu-map' => array(
            'description' => "Map OpenCL to ADL device order manually, paired CSV (e.g. 1:0,2:1 maps OpenCL 1 to ADL 0, 2 to 1)",
            'values' => PDT_STRING,
            'multivalue' => true,
        ),
        'gpu-memclock' => array(
            'description' => "Set the GPU memory (over)clock in Mhz - one value for all or separate by commas for per card",
            'values' => PDT_INT,
            'multivalue' => true,
        ),
        'gpu-memdiff' => array(
            'description' => "Set a fixed difference in clock speed between the GPU and memory in auto-gpu mode",
            'values' => PDT_INT,
            'multivalue' => true,
        ),
        'gpu-powertune' => array(
            'description' => "Set the GPU powertune percentage - one value for all or separate by commas for per card",
            'values' => PDT_INT,
            'multivalue' => true,
        ),
        'gpu-reorder' => array(
            'description' => "Attempt to reorder GPU devices according to PCI Bus ID",
            'values' => PDT_BOOL,
        ),
        'gpu-vddc' => array(
            'description' => "Set the GPU voltage in Volts - one value for all or separate by commas for per card",
            'values' => PDT_FLOAT,
            'multivalue' => true,
        ),
        'lookup-gap' => array(
            'description' => "Set GPU lookup gap for scrypt mining, comma separated",
            'values' => PDT_INT,
            'multivalue' => true,
        ),
        'intensity' => array(
            'description' => "Intensity of GPU scanning: d or sha(-10 - 14), scrypt(8 - 20), default: d to maintain desktop interactivity)",
            'values' => PDT_INT,
            'multivalue' => true,
        ),
        'hotplug' => array(
            'description' => "Seconds between hotplug checks (0 means never check)",
            'values' => PDT_INT,
            'range' => array(0, 9999),
        ),
        'kernel-path' => array(
            'description' => "Specify a path to where bitstream and kernel files are",
            'values' => PDT_STRING,
        ),
        'kernel' => array(
            'description' => "Override sha256 kernel to use (diablo, poclbm, phatk, diakgcn or scrypt) - one value or comma separated",
            'values' => array(
                'poclbm',
                'phatk',
                'diakgcn',
                'diablo',
                'scrypt',
            ),
        ),
        'icarus-options' => array(
            'description' => "- no help available -",
            'values' => PDT_STRING,
        ),
        'icarus-timing' => array(
            'description' => "- no help available -",
            'values' => PDT_STRING,
        ),
        'avalon-auto' => array(
            'description' => "Adjust avalon overclock frequency dynamically for best hashrate",
            'values' => PDT_BOOL,
        ),
        'avalon-cutoff' => array(
            'description' => "Set avalon overheat cut off temperature",
            'values' => PDT_INT,
            'range' => array(0, 100),
        ),
        'avalon-fan' => array(
            'description' => "Set fanspeed percentage for avalon, single value or range (default: 20-100)",
            'values' => PDT_INT,
            'range' => array(0, 100),
        ),
        'avalon-freq' => array(
            'description' => "Set frequency range for avalon-auto, single value or range",
            'values' => PDT_INT,
        ),
        'avalon-options' => array(
            'description' => "Set avalon options baud:miners:asic:timeout:freq",
            'values' => PDT_STRING,
        ),
        'avalon-temp' => array(
            'description' => "Set avalon target temperature",
            'values' => PDT_INT,
            'range' => array(0, 100),
        ),
        'bitburner-voltage' => array(
            'description' => "Set BitBurner (Avalon) core voltage, in millivolts",
            'values' => PDT_FLOAT,
        ),
        'bitburner-fury-voltage' => array(
            'description' => "Set BitBurner Fury core voltage, in millivolts",
            'values' => PDT_FLOAT,
        ),
        'bitburner-fury-options' => array(
            'description' => "Override avalon-options for BitBurner Fury boards baud:miners:asic:timeout:freq",
            'values' => PDT_STRING,
        ),
        'klondike-options' => array(
            'description' => "Set klondike options clock:temptarget",
            'values' => PDT_STRING,
        ),
        'load-balance' => array(
            'description' => "Change multipool strategy from failover to quota based balance",
            'values' => PDT_BOOL
        ),
        'log' => array(
            'description' => "Interval in seconds between log output",
            'values' => PDT_INT,
            'range' => array(0, 9999),
        ),
        'lowmem' => array(
            'description' => "Minimise caching of shares for low memory applications",
            'values' => PDT_BOOL,
        ),
        'monitor' => array(
            'description' => "Use custom pipe cmd for output messages",
            'values' => PDT_STRING,
        ),
        'net-delay' => array(
            'description' => "Impose small delays in networking to not overload slow routers",
            'values' => PDT_BOOL,
        ),
        'no-adl' => array(
            'description' => "Disable the ATI display library used for monitoring and setting GPU parameters",
            'values' => PDT_BOOL,
        ),
        'no-pool-disable' => array(
            'description' => "- no help available -",
            'values' => PDT_BOOL,
        ),
        'no-restart' => array(
            'description' => "Do not attempt to restart GPUs that hang",
            'values' => PDT_BOOL,
        ),
        'no-submit-stale' => array(
            'description' => "Don't submit shares if they are detected as stale",
            'values' => PDT_BOOL,
        ),
        'pass' => array(
            'description' => "Password for bitcoin JSON-RPC server",
            'values' => PDT_STRING,
        ),
        'per-device-stats' => array(
            'description' => "Force verbose mode and output per-device statistics",
            'values' => PDT_BOOL,
        ),
        'protocol-dump' => array(
            'description' => "Verbose dump of protocol-level activities",
            'values' => PDT_BOOL,
        ),
        'queue' => array(
            'description' => "Minimum number of work items to have queued (0+)",
            'values' => PDT_INT,
            'range' => array(0, 9999),
        ),
        'quiet' => array(
            'description' => "Disable logging output, display status and errors",
            'values' => PDT_BOOL,
        ),
        'quota' => array(
            'description' => "quota;URL combination for server with load-balance strategy quotas",
            'values' => PDT_STRING,
        ),
        'real-quiet' => array(
            'description' => "Disable all output",
            'values' => PDT_BOOL,
        ),
        'remove-disabled' => array(
            'description' => "Remove disabled devices entirely, as if they didn't exist",
            'values' => PDT_BOOL,
        ),
        'retries' => array(
            'description' => "- no help available -",
            'values' => PDT_INT,
        ),
        'retry-pause' => array(
            'description' => "- no help available -",
            'values' => PDT_INT,
        ),
        'rotate' => array(
            'description' => "Change multipool strategy from failover to regularly rotate at N minutes",
            'values' => PDT_BOOL,
        ),
        'round-robin' => array(
            'description' => "Change multipool strategy from failover to round robin on failure",
            'values' => PDT_BOOL,
        ),
        'scan-serial' => array(
            'description' => "Serial port to probe for Serial FPGA Mining device",
            'values' => PDT_INT,
        ),
        'scan-time' => array(
            'description' => "Upper bound on time spent scanning current work, in seconds",
            'values' => PDT_INT,
            'range' => array(0, 9999),
        ),
        'sched-start' => array(
            'description' => "Set a time of day in HH:MM to start mining (a once off without a stop time)",
            'values' => PDT_STRING,
        ),
        'sched-stop' => array(
            'description' => "Set a time of day in HH:MM to stop mining (will quit without a start time)",
            'values' => PDT_STRING,
        ),
        'scrypt' => array(
            'description' => "Use the scrypt algorithm for mining (litecoin only)",
            'values' => PDT_BOOL,
        ),
        'shaders' => array(
            'description' => "GPU shaders per card for tuning scrypt, comma separated",
            'values' => PDT_INT,
            'multivalue' => true,
        ),
        'sharelog' => array(
            'description' => "Append share log to file",
            'values' => PDT_INT,
        ),
        'shares' => array(
            'description' => "Quit after mining N shares (default: unlimited)",
            'values' => PDT_INT,
        ),
        'socks-proxy' => array(
            'description' => "Set socks4 proxy (host:port)",
            'values' => PDT_STRING,
        ),
        'syslog' => array(
            'description' => "Use system log for output messages (default: standard error)",
            'values' => PDT_BOOL,
        ),
        'temp-cutoff' => array(
            'description' => "Temperature where a device will be automatically disabled, one value or comma separated list",
            'values' => PDT_INT,
            'multivalue' => true,
        ),
        'temp-hysteresis' => array(
            'description' => "Set how much the temperature can fluctuate outside limits when automanaging speeds",
            'values' => PDT_INT,
            'range' => array(1, 10),
            'multivalue' => true,
        ),
        'temp-overheat' => array(
            'description' => "Overheat temperature when automatically managing fan and GPU speeds, one value or comma separated list",
            'values' => PDT_INT,
            'multivalue' => true,
        ),
        'temp-target' => array(
            'description' => "Target temperature when automatically managing fan and GPU speeds, one value or comma separated list",
            'values' => PDT_INT,
            'multivalue' => true,
        ),
        'text-only' => array(
            'description' => "Disable ncurses formatted screen output",
            'values' => PDT_BOOL,
        ),
        'thread-concurrency' => array(
            'description' => "Set GPU thread concurrency for scrypt mining, comma separated",
            'values' => PDT_INT,
            'multivalue' => true,
        ),
        'url' => array(
            'description' => "URL for bitcoin JSON-RPC server",
            'values' => PDT_STRING,
        ),
        'user' => array(
            'description' => "Username for bitcoin JSON-RPC server",
            'values' => PDT_STRING,
        ),
        'usb' => array(
            'description' => "USB device selection",
            'values' => PDT_STRING,
        ),
        'usb-dump' => array(
            'description' => "- no help available -",
            'values' => PDT_INT,
            'range' => array(0, 10),
        ),
        'usb-list-all' => array(
            'description' => "- no help available -",
            'values' => PDT_BOOL,
        ),
        'vectors' => array(
            'description' => "Override detected optimal vector (1, 2 or 4) - one value or comma separated list",
            'values' => array(
                1,
                2,
                4
            ),
            'multivalue' => true,
        ),
        'verbose' => array(
            'description' => "Log verbose output to stderr as well as status output",
            'values' => PDT_BOOL,
        ),
        'worksize' => array(
            'description' => "Override detected optimal worksize - one value or comma separated list",
            'values' => PDT_INT,
            'multivalue' => true,
        ),
        'userpass' => array(
            'description' => "Username:Password pair for bitcoin JSON-RPC server",
            'values' => PDT_STRING,
        ),
        'worktime' => array(
            'description' => "Display extra work time debug information",
            'values' => PDT_BOOL,
        ),
    );
    protected $type = 'config';

    /**
     * Returns wether the config file is empty or not.
     * 
     * @return boolean
     *   True if config is empty, else false.
     */
    public function is_empty($type = null) {
        if ($type === null) {
            $type = $this->type;
        }
        $tmp = Db::getInstance()->querySingle('SELECT 1 FROM "config" WHERE "type" = :type LIMIT 1', false, array(
            ':type' => $type,
        ));
        return empty($tmp);
    }

    /**
     * Retrieves config the value.
     * 
     * @param string $name
     *   The config key.
     * 
     * @return null|mixed
     *   Null if config key does not exist, else the value.
     */
    public function __get($name) {
        return $this->get_value($name);
    }

    /**
     * Retrieves config the value.
     * 
     * @param string $name
     *   The config key.
     * @param string $type
     *   The type, if not provided own type will be used (Optional, default = null)
     * 
     * @return null|mixed
     *   Null if config key does not exist, else the value.
     */
    public function get_value($name, $type = null) {

        if ($type === null) {
            $type = $this->type;
        }

        if ($name === 'rigs' && $type === 'config') {
            $result = array();
            $sql = Db::getInstance()->query('SELECT * FROM "config" WHERE "type" = :type', array(
                ':type' => 'rigs',
            ));
            if ($sql instanceof PDOStatement) {
                while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
                    $result[$row['key']] = json_decode($row['value'], true);
                }
            }
            return $result;
        }


        $tmp = Db::getInstance()->querySingle('SELECT "value" FROM "config" WHERE "key" = :name AND "type" = :type', true, array(
            ':name' => $name,
            ':type' => $type,
        ));
        if (!isset($tmp['value'])) {
            return null;
        }
        return json_decode($tmp['value'], true);
    }

    /**
     * Retrieves the rig config.
     * 
     * @param string $rig
     *   The rig name.
     * 
     * @return array|null
     *   The rig config array or null on error.
     */
    public function get_rig($rig) {
        $rig = $this->get_value($rig, 'rigs');
        if (empty($rig)) {
            return null;
        }
        return $rig;
    }

    /**
     * Set the rig config.
     * 
     * @param string $rig
     *   The rig name.
     * @param array $config
     *   The config.
     */
    public function set_rig($rig, $config) {
        $this->set_value($rig, $config, 'rigs');
    }

    /**
     * Returns the hole config.
     * 
     * @param string $type
     *   The type, if not provided own type will be used (Optional, default = null)
     * 
     * @return array
     *   The config array.
     */
    public function get_config($type = null) {
        if ($type === null) {
            $type = $this->type;
        }
        $config = array();
        $sql = Db::getInstance()->query('SELECT * FROM "config" WHERE "type" = :type', array(
            ':type' => $type,
        ));
        if ($sql instanceof PDOStatement) {
            while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
                $config[$row['key']] = json_decode($row['value'], true);
            }
        }
        if ($type === 'config') {
            $config['rigs'] = $this->rigs;
        }
        return $config;
    }

    /**
     * Set the config key and write the new config file directly.
     * 
     * @param string $name
     *   The config key to set.
     * @param mixed $value
     *   The value.
     */
    public function __set($name, $value) {
        $this->set_value($name, $value);
    }

    /**
     * Set the config key and write the new config file directly.
     * 
     * @param string $name
     *   The config key to set.
     * @param mixed $value
     *   The value.
     * @param string $type
     *   The type, if not provided own type will be used (Optional, default = null)
     */
    public function set_value($name, $value, $type = null) {
        if ($type === null) {
            $type = $this->type;
        }

        if ($name === 'rigs' && $type === 'config') {
            if (is_array($value)) {
                Db::getInstance()->exec('DELETE FROM "config" WHERE "type" = :type', array(
                    ':type' => 'rigs',
                ));
                foreach ($value AS $rig => $rig_data) {
                    if (empty($rig) || empty($rig_data)) {
                        continue;
                    }
                    $this->set_value($rig, $rig_data, 'rigs');
                }
            }

            return;
        }

        if (is_array($value) && isset($value['rigs']) && $type === 'config') {
            $this->rigs = $value['rigs'];
            unset($value['rigs']);
        }
        
        $exists = Db::getInstance()->querySingle('SELECT 1 FROM "config" WHERE "key" = :key AND "type" = :type' , false, array(
            ':key' => $name,
            ':type' => $type,            
        ));
        if (empty($exists)) {
            Db::getInstance()->exec('INSERT INTO "config" ("value", "key", "type") VALUES (:value, :key, :type)', array(
                ':value' => json_encode($value),
                ':key' => $name,
                ':type' => $type,
            ));
        }
        else {
            Db::getInstance()->exec('UPDATE "config" SET "value" = :value WHERE "key" = :key AND "type" = :type', array(
                ':value' => json_encode($value),
                ':key' => $name,
                ':type' => $type,
            ));
        }
    }

    /**
     * Returns wether the config key exists or not.
     * 
     * @param string $name
     *   The config key.
     * 
     * @return boolean
     *   True if config key is set, else false.
     */
    public function __isset($name) {
        if ($name === 'rigs') {
            $tmp = Db::getInstance()->querySingle('SELECT 1 FROM "config" WHERE "type" = :type', false, array(
                ':type' => 'rigs',
            ));
        } else {
            $tmp = Db::getInstance()->querySingle('SELECT 1 FROM "config" WHERE "key" = :name AND "type" = :type', false, array(
                ':name' => $name,
                ':type' => $this->type,
            ));
        }
        return !empty($tmp);
    }

}
