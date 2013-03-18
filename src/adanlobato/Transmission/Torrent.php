<?php

namespace adanlobato\Transmission;

use adanlobato\Transmission\Transmission;

class Torrent
{
    const STATUS_STOPPED = 0;
    const STATUS_CHECK_WAIT = 1;
    const STATUS_CHECK = 2;
    const STATUS_DOWNLOAD_WAIT = 3;
    const STATUS_DOWNLOAD = 4;
    const STATUS_SEED_WAIT = 5;
    const STATUS_SEED = 6;

    const LEGACY_STATUS_CHECK_WAIT = 1;
    const LEGACY_STATUS_CHECK = 2;
    const LEGACY_STATUS_DOWNLOAD = 4;
    const LEGACY_STATUS_SEED = 8;
    const LEGACY_STATUS_STOPPED = 16;

    protected $transmission;

    protected $rpcVersion;

    /**
     * @param Transmission $transmission
     */
    public function __construct(Transmission $transmission)
    {
        $this->transmission = $transmission;

        // Get the Transmission RPC_version
        $this->rpcVersion = $this->transmission->session()->get()->arguments->rpc_version;
    }

    /**
     * Start one or more torrents
     *
     * @param int|array ids A list of transmission torrent ids
     */
    public function start($ids)
    {
        if (!is_array($ids)) $ids = array($ids);
        $request = array("ids" => $ids);

        return $this->transmission->request("torrent-start", $request);
    }

    /**
     * Stop one or more torrents
     *
     * @param int|array ids A list of transmission torrent ids
     */
    public function stop($ids)
    {
        if (!is_array($ids)) $ids = array($ids);
        $request = array("ids" => $ids);

        return $this->transmission->request("torrent-stop", $request);
    }

    /**
     * Reannounce one or more torrents
     *
     * @param int|array ids A list of transmission torrent ids
     */
    public function reannounce($ids)
    {
        if (!is_array($ids)) $ids = array($ids);
        $request = array("ids" => $ids);

        return $this->transmission->request("torrent-reannounce", $request);
    }

    /**
     * Verify one or more torrents
     *
     * @param int|array ids A list of transmission torrent ids
     */
    public function verify($ids)
    {
        if (!is_array($ids)) $ids = array($ids);
        $request = array("ids" => $ids);

        return $this->transmission->request("torrent-verify", $request);
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
        if (!is_array($ids)) $ids = array($ids);
        if (count($fields) == 0) $fields = array("id", "name", "status", "doneDate", "haveValid", "totalSize"); // Defaults
        $request = array(
            "fields" => $fields,
            "ids" => $ids
        );

        return $this->transmission->request("torrent-get", $request);
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
        if (!is_array($ids)) $ids = array($ids);
        if (!isset($arguments['ids'])) $arguments['ids'] = $ids; // Any $ids given in $arguments overrides the method parameter

        return $this->transmission->request("torrent-set", $arguments);
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

        return $this->transmission->request("torrent-add", $extra_options);
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

        return $this->transmission->request("torrent-add", $extra_options);
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
        if (!is_array($ids)) $ids = array($ids);
        $request = array(
            "ids" => $ids,
            "delete-local-data" => $delete_local_data
        );

        return $this->transmission->request("torrent-remove", $request);
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
        if (!is_array($ids)) $ids = array($ids);
        $request = array(
            "ids" => $ids,
            "location" => $target_location,
            "move" => $move_existing_data
        );

        return $this->transmission->request("torrent-set-location", $request);
    }

    /**
     * Return the interpretation of the torrent status
     *
     * @param int The integer "torrent status"
     * @returns string The translated meaning
     */
    public function getStatusMessage($statusCode)
    {
        if ($this->rpcVersion < 14) {
            if ($statusCode === static::LEGACY_STATUS_CHECK_WAIT) {
                return "Waiting to verify local files";
            }

            if ($statusCode === static::LEGACY_STATUS_CHECK) {
                return "Verifying local files";
            }

            if ($statusCode === static::LEGACY_STATUS_DOWNLOAD) {
                return "Downloading";
            }

            if ($statusCode === static::LEGACY_STATUS_SEED) {
                return "Seeding";
            }

            if ($statusCode === static::LEGACY_STATUS_STOPPED) {
                return "Stopped";
            }
        } else {
            if ($statusCode === static::STATUS_CHECK_WAIT) {
                return "Waiting to verify local files";
            }

            if ($statusCode === static::STATUS_CHECK) {
                return "Verifying local files";
            }

            if ($statusCode === static::STATUS_DOWNLOAD) {
                return "Downloading";
            }

            if ($statusCode === static::STATUS_SEED) {
                return "Seeding";
            }

            if ($statusCode === static::STATUS_STOPPED) {
                return "Stopped";
            }

            if ($statusCode === static::STATUS_SEED_WAIT) {
                return "Queued for seeding";
            }

            if ($statusCode === static::STATUS_DOWNLOAD_WAIT) {
                return "Queued for download";
            }
        }

        return "Unknown";
    }
}