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
    $result = $transmission->sstats();
    print "GET SESSION STATS... [{$result->result}]\n";

    sleep(2);

    $result = $transmission->add($test_torrent, '/tmp');
    $id = $result->arguments->torrent_added->id;
    print "ADD TORRENT TEST... [{$result->result}] (id=$id)\n";

    sleep(2);

    $result = $transmission->set($id, array('uploadLimit' => 10));
    print "SET TORRENT INFO TEST... [{$result->result}]\n";

    sleep(2);

    $transmission->setReturnAsArray(true);
    $result = $transmission->get($id, array('uploadLimit'));
    print "GET TORRENT INFO AS ARRAY TEST... [{$result['result']}]\n";
    $transmission->setReturnAsArray(false);

    sleep(2);

    $result = $transmission->get($id, array('uploadLimit'));
    print "GET TORRENT INFO AS OBJECT TEST... [{$result->result}]\n";

    sleep(2);

    $result2 = $result->arguments->torrents[0]->uploadLimit == 10 ? 'success' : 'failed';
    print "VERIFY TORRENT INFO SET/GET... [{$result2}] (" . $result->arguments->torrents[0]->uploadLimit . ")\n";

    $result = $transmission->stop($id);
    print "STOP TORRENT TEST... [{$result->result}]\n";
    sleep(2);

    $result = $transmission->verify($id);
    print "VERIFY TORRENT TEST... [{$result->result}]\n";

    sleep(10);

    $result = $transmission->start($id);
    print "START TORRENT TEST... [{$result->result}]\n";

    sleep(2);

    $result = $transmission->reannounce($id);
    print "REANNOUNCE TORRENT TEST... [{$result->result}]\n";

    sleep(2);

    $result = $transmission->move($id, '/tmp/torrent-test', true);
    print "MOVE TORRENT TEST... [{$result->result}]\n";

    sleep(2);

    $result = $transmission->remove($id, false);
    print "REMOVE TORRENT TEST... [{$result->result}]\n";

} catch (Exception $e) {
    die('[ERROR] ' . $e->getMessage() . PHP_EOL);
}