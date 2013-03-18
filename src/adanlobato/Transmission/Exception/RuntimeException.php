<?php

namespace adanlobato\Transmission\Exception;

class RuntimeException extends \Exception
{
    /**
     * Exception: Invalid arguments
     */
    const E_INVALIDARG = -1;

    /**
     * Exception: Invalid Session-Id
     */
    const E_SESSIONID = -2;

    /**
     * Exception: Error while connecting
     */
    const E_CONNECTION = -3;

    /**
     * Exception: Error 401 returned, unauthorized
     */
    const E_AUTHENTICATION = -4;
}