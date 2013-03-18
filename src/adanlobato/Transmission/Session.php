<?php

namespace adanlobato\Transmission;

use adanlobato\Transmission\Transmission;

class Session
{
    protected $transmission;

    /**
     * @param Transmission $transmission
     */
    public function __construct(Transmission $transmission)
    {
        $this->transmission = $transmission;
    }

    /**
     * Retrieve session statistics
     *
     * @returns array of statistics
     */
    public function getStats()
    {
        return $this->transmission->request("session-stats", array());
    }

    /**
     * Retrieve all session variables
     *
     * @returns array of session information
     */
    public function get()
    {
        return $this->transmission->request("session-get", array());
    }

    /**
     * Set session variable(s)
     *
     * @param array of session variables to set
     */
    public function set($arguments)
    {
        return $this->transmission->request("session-set", $arguments);
    }
}