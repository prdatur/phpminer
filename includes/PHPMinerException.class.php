<?php
/**
 * @copyright Christian Ackermann (c) 2013 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 */
class PHPMinerException extends Exception {
    const CODE_CONFIG_NOT_READABLE = 502;    
    const CODE_CONFIG_NOT_WRITEABLE = 503;    
    const CODE_CONFIG_INVALID_JSON = 504;    
}