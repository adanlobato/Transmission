<?php

namespace adanlobato\Transmission;

use adanlobato\Transmission\Exception\TransmissionRPCException;

use Buzz\Browser as Buzz;

class Transmission
{
    /**
     * Constants for torrent status
     */
    const TR_STATUS_STOPPED = 0;
    const TR_STATUS_CHECK_WAIT = 1;
    const TR_STATUS_CHECK = 2;
    const TR_STATUS_DOWNLOAD_WAIT = 3;
    const TR_STATUS_DOWNLOAD = 4;
    const TR_STATUS_SEED_WAIT = 5;
    const TR_STATUS_SEED = 6;

    const RPC_LT_14_TR_STATUS_CHECK_WAIT = 1;
    const RPC_LT_14_TR_STATUS_CHECK = 2;
    const RPC_LT_14_TR_STATUS_DOWNLOAD = 4;
    const RPC_LT_14_TR_STATUS_SEED = 8;
    const RPC_LT_14_TR_STATUS_STOPPED = 16;

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
     * Transmission RPC version
     * @var int
     */
    protected $rpcVersion;

    /**
     * Transmission uses a session id to prevent CSRF attacks
     * @var string
     */
    protected $sessionId;

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

        // Get the Transmission RPC_version
        $this->rpcVersion = self::sget()->arguments->rpc_version;
    }

    /**
     * @param boolean $returnAsArray
     */
    public function setReturnAsArray($returnAsArray)
    {
        $this->returnAsArray = $returnAsArray;
    }

    /**
     * Start one or more torrents
     *
     * @param int|array ids A list of transmission torrent ids
     */
    public function start($ids)
    {
        if (!is_array($ids)) $ids = array($ids); // Convert $ids to an array if only a single id was passed
        $request = array("ids" => $ids);
        return $this->request("torrent-start", $request);
    }

    /**
     * Stop one or more torrents
     *
     * @param int|array ids A list of transmission torrent ids
     */
    public function stop($ids)
    {
        if (!is_array($ids)) $ids = array($ids); // Convert $ids to an array if only a single id was passed
        $request = array("ids" => $ids);
        return $this->request("torrent-stop", $request);
    }

    /**
     * Reannounce one or more torrents
     *
     * @param int|array ids A list of transmission torrent ids
     */
    public function reannounce($ids)
    {
        if (!is_array($ids)) $ids = array($ids); // Convert $ids to an array if only a single id was passed
        $request = array("ids" => $ids);
        return $this->request("torrent-reannounce", $request);
    }

    /**
     * Verify one or more torrents
     *
     * @param int|array ids A list of transmission torrent ids
     */
    public function verify($ids)
    {
        if (!is_array($ids)) $ids = array($ids); // Convert $ids to an array if only a single id was passed
        $request = array("ids" => $ids);
        return $this->request("torrent-verify", $request);
    }

    /**
     * Get information on torrents in transmission, if the ids parameter is
     * empty all torrents will be returned. The fields array can be used to return certain
     * fields. Default fields are: "id", "name", "status", "doneDate", "haveValid", "totalSize".
     * See https://trac.transmissionbt.com/browser/trunk/doc/rpc-spec.txt for available fields
     *
     * @param array fields An array of return fields
     * @param int|array ids A list of transmission torrent ids
     */
    public function get($ids = array(), $fields = array())
    {
        if (!is_array($ids)) $ids = array($ids); // Convert $ids to an array if only a single id was passed
        if (count($fields) == 0) $fields = array("id", "name", "status", "doneDate", "haveValid", "totalSize"); // Defaults
        $request = array(
            "fields" => $fields,
            "ids" => $ids
        );
        return $this->request("torrent-get", $request);
    }

    /**
     * Set properties on one or more torrents, available fields are:
     *   "bandwidthPriority"   | number     this torrent's bandwidth tr_priority_t
     *   "downloadLimit"       | number     maximum download speed (in K/s)
     *   "downloadLimited"     | boolean    true if "downloadLimit" is honored
     *   "files-wanted"        | array      indices of file(s) to download
     *   "files-unwanted"      | array      indices of file(s) to not download
     *   "honorsSessionLimits" | boolean    true if session upload limits are honored
     *   "ids"                 | array      torrent list, as described in 3.1
     *   "location"            | string     new location of the torrent's content
     *   "peer-limit"          | number     maximum number of peers
     *   "priority-high"       | array      indices of high-priority file(s)
     *   "priority-low"        | array      indices of low-priority file(s)
     *   "priority-normal"     | array      indices of normal-priority file(s)
     *   "seedRatioLimit"      | double     session seeding ratio
     *   "seedRatioMode"       | number     which ratio to use.  See tr_ratiolimit
     *   "uploadLimit"         | number     maximum upload speed (in K/s)
     *   "uploadLimited"       | boolean    true if "uploadLimit" is honored
     * See https://trac.transmissionbt.com/browser/trunk/doc/rpc-spec.txt for more information
     *
     * @param array arguments An associative array of arguments to set
     * @param int|array ids A list of transmission torrent ids
     */
    public function set($ids = array(), $arguments = array())
    {
        // See https://trac.transmissionbt.com/browser/trunk/doc/rpc-spec.txt for available fields
        if (!is_array($ids)) $ids = array($ids); // Convert $ids to an array if only a single id was passed
        if (!isset($arguments['ids'])) $arguments['ids'] = $ids; // Any $ids given in $arguments overrides the method parameter
        return $this->request("torrent-set", $arguments);
    }

    /**
     * Add a new torrent
     *
     * Available extra options:
     *  key                  | value type & description
     *  ---------------------+-------------------------------------------------
     *  "download-dir"       | string      path to download the torrent to
     *  "filename"           | string      filename or URL of the .torrent file
     *  "metainfo"           | string      base64-encoded .torrent content
     *  "paused"             | boolean     if true, don't start the torrent
     *  "peer-limit"         | number      maximum number of peers
     *  "bandwidthPriority"  | number      torrent's bandwidth tr_priority_t
     *  "files-wanted"       | array       indices of file(s) to download
     *  "files-unwanted"     | array       indices of file(s) to not download
     *  "priority-high"      | array       indices of high-priority file(s)
     *  "priority-low"       | array       indices of low-priority file(s)
     *  "priority-normal"    | array       indices of normal-priority file(s)
     *
     *   Either "filename" OR "metainfo" MUST be included.
     *     All other arguments are optional.
     *
     * @param torrent_location The URL or path to the torrent file
     * @param save_path Folder to save torrent in
     * @param extra options Optional extra torrent options
     */
    public function addFile($torrent_location, $save_path = '', $extra_options = array())
    {
        $extra_options['download-dir'] = $save_path;
        $extra_options['filename'] = $torrent_location;

        return $this->request("torrent-add", $extra_options);
    }

    /**
     * Add a torrent using the raw torrent data
     *
     * @param torrent_metainfo The raw, unencoded contents (metainfo) of a torrent
     * @param save_path Folder to save torrent in
     * @param extra options Optional extra torrent options
     */
    public function addMetainfo($torrent_metainfo, $save_path = '', $extra_options = array())
    {
        $extra_options['download-dir'] = $save_path;
        $extra_options['metainfo'] = base64_encode($torrent_metainfo);

        return $this->request("torrent-add", $extra_options);
    }

    /* Add a new torrent using a file path or a URL (For backwards compatibility)
     * @param torrent_location The URL or path to the torrent file
     * @param save_path Folder to save torrent in
     * @param extra options Optional extra torrent options
     */
    public function add($torrent_location, $save_path = '', $extra_options = array())
    {
        return $this->addFile($torrent_location, $save_path, $extra_options);
    }

    /**
     * Remove torrent from transmission
     *
     * @param bool delete_local_data Also remove local data?
     * @param int|array ids A list of transmission torrent ids
     */
    public function remove($ids, $delete_local_data = false)
    {
        if (!is_array($ids)) $ids = array($ids); // Convert $ids to an array if only a single id was passed
        $request = array(
            "ids" => $ids,
            "delete-local-data" => $delete_local_data
        );
        return $this->request("torrent-remove", $request);
    }

    /**
     * Move local storage location
     *
     * @param int|array ids A list of transmission torrent ids
     * @param string target_location The new storage location
     * @param string move_existing_data Move existing data or scan new location for available data
     */
    public function move($ids, $target_location, $move_existing_data = true)
    {
        if (!is_array($ids)) $ids = array($ids); // Convert $ids to an array if only a single id was passed
        $request = array(
            "ids" => $ids,
            "location" => $target_location,
            "move" => $move_existing_data
        );
        return $this->request("torrent-set-location", $request);
    }

    /**
     * Retrieve session statistics
     *
     * @returns array of statistics
     */
    public function sstats()
    {
        return $this->request("session-stats", array());
    }

    /**
     * Retrieve all session variables
     *
     * @returns array of session information
     */
    public function sget()
    {
        return $this->request("session-get", array());
    }

    /**
     * Set session variable(s)
     *
     * @param array of session variables to set
     */
    public function sset($arguments)
    {
        return $this->request("session-set", $arguments);
    }

    /**
     * Return the interpretation of the torrent status
     *
     * @param int The integer "torrent status"
     * @returns string The translated meaning
     */
    public function getStatusString($intstatus)
    {
        if ($this->rpcVersion < 14) {
            if ($intstatus == self::RPC_LT_14_TR_STATUS_CHECK_WAIT)
                return "Waiting to verify local files";
            if ($intstatus == self::RPC_LT_14_TR_STATUS_CHECK)
                return "Verifying local files";
            if ($intstatus == self::RPC_LT_14_TR_STATUS_DOWNLOAD)
                return "Downloading";
            if ($intstatus == self::RPC_LT_14_TR_STATUS_SEED)
                return "Seeding";
            if ($intstatus == self::RPC_LT_14_TR_STATUS_STOPPED)
                return "Stopped";
        } else {
            if ($intstatus == self::TR_STATUS_CHECK_WAIT)
                return "Waiting to verify local files";
            if ($intstatus == self::TR_STATUS_CHECK)
                return "Verifying local files";
            if ($intstatus == self::TR_STATUS_DOWNLOAD)
                return "Downloading";
            if ($intstatus == self::TR_STATUS_SEED)
                return "Seeding";
            if ($intstatus == self::TR_STATUS_STOPPED)
                return "Stopped";
            if ($intstatus == self::TR_STATUS_SEED_WAIT)
                return "Queued for seeding";
            if ($intstatus == self::TR_STATUS_DOWNLOAD_WAIT)
                return "Queued for download";
        }
        return "Unknown";
    }


    /**
     * Here be dragons (Internal methods)
     */


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
    protected function request($method, $arguments)
    {
        // Check the parameters
        if (!is_scalar($method)) {
            throw new TransmissionRPCException('Method name has no scalar value', TransmissionRPCException::E_INVALIDARG);
        }

        if (!is_array($arguments)) {
            throw new TransmissionRPCException('Arguments must be given as array', TransmissionRPCException::E_INVALIDARG);
        }

        $arguments = $this->cleanRequestData($arguments); // Sanitize input

        // Grab the X-Transmission-Session-Id if we don't have it already
        if (!$this->sessionId) {
            if (!$this->getSessionId()) {
                throw new TransmissionRPCException('Unable to acquire X-Transmission-Session-Id', TransmissionRPCException::E_SESSIONID);
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
            throw new TransmissionRPCException("Invalid username/password", TransmissionRPCException::E_AUTHENTICATION);
        } elseif (409 === $response->getStatusCode()) {
            if (null === $response->getHeader('X-Transmission-Session-Id')) {
                throw new TransmissionRPCException("Unable to retrieve X-Transmission-Session-Id", TransmissionRPCException::E_SESSIONID);
            }

            $this->sessionId = $response->getHeader('X-Transmission-Session-Id');
        }

        return $this->returnAsArray ? json_decode($response->getContent(), true) : $this->cleanResultObject(json_decode($response->getContent())); // Return the sanitized result
    }

    /**
     * Performs an empty GET on the Transmission RPC to get the X-Transmission-Session-Id
     *
     * @return string
     * @throws TransmissionRPCException
     */
    public function getSessionId()
    {
        $this->sessionId = null;

        /** @var $response \Buzz\Message\Response */
        $response = $this->buzz->get($this->url);

        if (401 === $response->getStatusCode()) {
            throw new TransmissionRPCException("Invalid username/password", TransmissionRPCException::E_AUTHENTICATION);
        } elseif (409 === $response->getStatusCode()) {
            if (null === $response->getHeader('X-Transmission-Session-Id')) {
                throw new TransmissionRPCException("Unable to retrieve X-Transmission-Session-Id", TransmissionRPCException::E_SESSIONID);
            }

            $this->sessionId = $response->getHeader('X-Transmission-Session-Id');
        } else {
            throw new TransmissionRPCException("Unexpected response from Transmission RPC: " . $response->getContent());
        }

        return $this->sessionId;
    }
}