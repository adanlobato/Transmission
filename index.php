#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use adanlobato\Transmission\Transmission;
use Buzz\Browser;
use Buzz\Client\Curl;
use Buzz\Listener\BasicAuthListener;

$test_torrent = "http://www.slackware.com/torrents/slackware64-13.1-install-dvd.torrent";

$curl = new Curl();
$curl->setTimeout(30);
$buzz = new Browser($curl);
$buzz->addListener(new BasicAuthListener('test', 'testtest'));
$transmission = new Transmission($buzz);

try {
    $result = $transmission->session()->getStats();
    print "GET SESSION STATS... [{$result->result}]\n";

    sleep(2);

    $result = $transmission->torrent()->add($test_torrent, '/tmp');
    $id = $result->arguments->torrent_added->id;
    print "ADD TORRENT TEST... [{$result->result}] (id=$id)\n";

    sleep(2);

    $result = $transmission->torrent()->set($id, array('uploadLimit' => 10));
    print "SET TORRENT INFO TEST... [{$result->result}]\n";

    sleep(2);

    $transmission->setReturnAsArray(true);
    $result = $transmission->torrent()->get($id, array('uploadLimit'));
    print "GET TORRENT INFO AS ARRAY TEST... [{$result['result']}]\n";
    $transmission->setReturnAsArray(false);

    sleep(2);

    $result = $transmission->torrent()->get($id, array('uploadLimit'));
    print "GET TORRENT INFO AS OBJECT TEST... [{$result->result}]\n";

    sleep(2);

    $result2 = $result->arguments->torrents[0]->uploadLimit == 10 ? 'success' : 'failed';
    print "VERIFY TORRENT INFO SET/GET... [{$result2}] (" . $result->arguments->torrents[0]->uploadLimit . ")\n";

    $result = $transmission->torrent()->stop($id);
    print "STOP TORRENT TEST... [{$result->result}]\n";
    sleep(2);

    $result = $transmission->torrent()->verify($id);
    print "VERIFY TORRENT TEST... [{$result->result}]\n";

    sleep(10);

    $result = $transmission->torrent()->start($id);
    print "START TORRENT TEST... [{$result->result}]\n";

    sleep(2);

    $result = $transmission->torrent()->reannounce($id);
    print "REANNOUNCE TORRENT TEST... [{$result->result}]\n";

    sleep(2);

    $result = $transmission->torrent()->move($id, '/tmp/torrent-test', true);
    print "MOVE TORRENT TEST... [{$result->result}]\n";

    sleep(2);

    $result = $transmission->torrent()->remove($id, false);
    print "REMOVE TORRENT TEST... [{$result->result}]\n";

} catch (\Exception $e) {
    die('[ERROR] ' . $e->getMessage() . PHP_EOL);
}