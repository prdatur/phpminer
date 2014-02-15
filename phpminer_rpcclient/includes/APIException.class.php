<?php
/**
 * @copyright Christian Ackermann (c) 2013 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 */
class APIException extends Exception {
    const CODE_SOCKET_CREATE_ERROR = 501;
    const CODE_SOCKET_CONNECT_ERROR = 502;
    const CODE_SOCKET_NOT_CONNECTED = 503;
    const CODE_INVALID_PARAMETER = 504;
}