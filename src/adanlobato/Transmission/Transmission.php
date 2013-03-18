<?php

namespace adanlobato\Transmission;

use adanlobato\Transmission\Exception\RuntimeException;

use Buzz\Browser as Buzz;

class Transmission
{

    /**
     * @var Buzz
     */
    protected $buzz;

    /**
     * The URL to the bittorent client you want to communicate with
     * the port (default: 9091) can be set in you Transmission preferences
     * @var string
     */
    protected $url = 'http://localhost:9091/transmission/rpc';

    /**
     * Return results as an array, or an object (default)
     * @var bool
     */
    protected $returnAsArray = false;

    /**
     * Transmission uses a session id to prevent CSRF attacks
     * @var string
     */
    protected $sessionId;

    protected $session;

    protected $torrent;

    /**
     * @param Buzz $buzz
     * @param string $url
     * @param boolean $returnAsArray
     */
    public function __construct(Buzz $buzz, $url = 'http://localhost:9091/transmission/rpc', $returnAsArray = false)
    {
        $this->buzz = $buzz;
        $this->url = $url;
        $this->returnAsArray = $returnAsArray;
    }

    /**
     * @param boolean $returnAsArray
     */
    public function setReturnAsArray($returnAsArray)
    {
        $this->returnAsArray = $returnAsArray;
    }

    /**
     * Returns the Session api to interact with Transmission session
     *
     * @return Session
     */
    public function session()
    {
        if (null === $this->session) {
            $this->session = new Session($this);
        }

        return $this->session;
    }

    /**
     * Returns the Torrent api to interact with torrents
     *
     * @return Torrent
     */
    public function torrent()
    {
        if (null === $this->torrent) {
            $this->torrent = new Torrent($this);
        }

        return $this->torrent;
    }

    /**
     * Clean up the request array. Removes any empty fields from the request
     *
     * @param array array The request associative array to clean
     * @returns array The cleaned array
     */
    protected function cleanRequestData($array)
    {
        if (!is_array($array) || count($array) == 0) return null; // Nothing to clean
        setlocale(LC_NUMERIC, 'en_US.utf8'); // Override the locale - if the system locale is wrong, then 12.34 will encode as 12,34 which is invalid JSON
        foreach ($array as $index => $value) {
            if (is_object($value)) $array[$index] = $value->toArray(); // Convert objects to arrays so they can be JSON encoded
            if (is_array($value)) $array[$index] = $this->cleanRequestData($value); // Recursion
            if (empty($value) && $value != 0) unset($array[$index]); // Remove empty members
            if (is_numeric($value)) $array[$index] = $value + 0; // Force type-casting for proper JSON encoding (+0 is a cheap way to maintain int/float/etc)
            if (is_bool($value)) $array[$index] = ($value ? 1 : 0); // Store boolean values as 0 or 1
            if (is_string($value)) $array[$index] = utf8_encode($value); // Make sure all data is UTF-8 encoded for Transmission
        }
        return $array;
    }

    /**
     * Clean up the result object. Replaces all minus(-) characters in the object properties with underscores
     * and converts any object with any all-digit property names to an array.
     *
     * @param object The request result to clean
     * @returns array The cleaned object
     */
    protected function cleanResultObject($object)
    {
        // Prepare and cast object to array
        $return_as_array = false;
        $array = $object;
        if (!is_array($array)) $array = (array)$array;
        foreach ($array as $index => $value) {
            if (is_array($array[$index]) || is_object($array[$index])) {
                $array[$index] = $this->cleanResultObject($array[$index]); // Recursion
            }
            if (strstr($index, '-')) {
                $valid_index = str_replace('-', '_', $index);
                $array[$valid_index] = $array[$index];
                unset($array[$index]);
                $index = $valid_index;
            }
            // Might be an array, check index for digits, if so, an array should be returned
            if (ctype_digit((string)$index)) {
                $return_as_array = true;
            }
            if (empty($value)) unset($array[$index]);
        }

        // Return array cast to object
        return $return_as_array ? $array : (object)$array;
    }

    /**
     * The request handler method handles all requests to the Transmission client
     *
     * @param string method The request method to use
     * @param array arguments The request arguments
     * @returns array The request result
     */
    public function request($method, $arguments)
    {
        // Check the parameters
        if (!is_scalar($method)) {
            throw new RuntimeException('Method name has no scalar value', RuntimeException::E_INVALIDARG);
        }

        if (!is_array($arguments)) {
            throw new RuntimeException('Arguments must be given as array', RuntimeException::E_INVALIDARG);
        }

        $arguments = $this->cleanRequestData($arguments); // Sanitize input

        // Grab the X-Transmission-Session-Id if we don't have it already
        if (!$this->sessionId) {
            if (!$this->getSessionId()) {
                throw new RuntimeException('Unable to acquire X-Transmission-Session-Id', RuntimeException::E_SESSIONID);
            }
        }

        // Build (and encode) request array
        $data = array(
            "method" => $method,
            "arguments" => $arguments
        );
        $data = json_encode($data);

        $response = $this->buzz->post(
            $this->url,
            array(
                'Content-type' => 'application/json',
                'X-Transmission-Session-Id' => $this->sessionId,
            ),
            $data
        );

        if (401 === $response->getStatusCode()) {
            throw new RuntimeException("Invalid username/password", RuntimeException::E_AUTHENTICATION);
        } elseif (409 === $response->getStatusCode()) {
            if (null === $response->getHeader('X-Transmission-Session-Id')) {
                throw new RuntimeException("Unable to retrieve X-Transmission-Session-Id", RuntimeException::E_SESSIONID);
            }

            $this->sessionId = $response->getHeader('X-Transmission-Session-Id');
        }

        return $this->returnAsArray ? json_decode($response->getContent(), true) : $this->cleanResultObject(json_decode($response->getContent())); // Return the sanitized result
    }

    /**
     * Performs an empty GET on the Transmission RPC to get the X-Transmission-Session-Id
     *
     * @return string
     * @throws RuntimeException
     */
    public function getSessionId()
    {
        $this->sessionId = null;

        /** @var $response \Buzz\Message\Response */
        $response = $this->buzz->get($this->url);

        if (401 === $response->getStatusCode()) {
            throw new RuntimeException("Invalid username/password", RuntimeException::E_AUTHENTICATION);
        } elseif (409 === $response->getStatusCode()) {
            if (null === $response->getHeader('X-Transmission-Session-Id')) {
                throw new RuntimeException("Unable to retrieve X-Transmission-Session-Id", RuntimeException::E_SESSIONID);
            }

            $this->sessionId = $response->getHeader('X-Transmission-Session-Id');
        } else {
            throw new RuntimeException("Unexpected response from Transmission RPC: " . $response->getContent());
        }

        return $this->sessionId;
    }
}