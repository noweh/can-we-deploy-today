<?php

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Noweh\TwitterApi\Client as TwitterClient;

// Only allowed for cli
if (PHP_SAPI !== 'cli') {
    die('Not allowed');
}

$start = microtime(true);

// Load .env data
$dotenv = Dotenv::createUnsafeImmutable(__DIR__.'/config', '.env');
$dotenv->safeLoad();

try {
    // Retrieve Twitter env data and load Clients
    $twitterSettings = [];
    foreach (getenv() as $settingKey => $settingValue) {
        if (str_starts_with($settingKey, 'TWITTER_')) {
            $twitterSettings[str_replace('twitter_', '', mb_strtolower($settingKey))] = $settingValue;
        }
    }

    // Tweet a text
    $return = (new TwitterClient($twitterSettings))->tweet()->performRequest('POST', [
        'text' => 'this is a test'
    ]);

    echo "script completed without error\r\n";
} catch (Exception | \GuzzleHttp\Exception\GuzzleException $e) {
    echo "error in script: " . $e->getMessage() . "\r\n";
}

echo 'execution time ' . round(microtime(true) - $start, 2) . ' seconds';