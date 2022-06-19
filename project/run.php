<?php

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Noweh\TwitterApi\Client as TwitterClient;
use Noweh\CanWeDeployToday\DBAdapter;

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

    $twitterClient = new TwitterClient($twitterSettings);
    $dbAdapter = new DBAdapter(__DIR__ . '//database//db.sqlite');

    // Find all mentions
    /** @var \stdClass $returnMentions */
    $returnMentions = $twitterClient->timeline()
        ->findRecentMentioningForUserId($twitterSettings['account_id'])
        ->performRequest()
    ;

    if (property_exists($returnMentions, 'errors')) {
        throw new \Exception(json_encode($returnMentions, JSON_THROW_ON_ERROR));
    }

    $nbOfAnswers = 0;
    foreach ($returnMentions->data as $mention) {
        if (!$dbAdapter->searchAnswerId($mention->id)) {
            // Tweet an answer
            $return = (new TwitterClient($twitterSettings))->tweet()->performRequest('POST', [
                'text' => 'this is a test',
                'reply' => ['in_reply_to_tweet_id' => $mention->id]
            ]);

            // Add the answer ID in DB
            $dbAdapter->addAnswerId($mention->id);
            ++$nbOfAnswers;
        }
    }

    echo "script completed without error\r\n$nbOfAnswers answers were given\r\n";
} catch (Exception | \GuzzleHttp\Exception\GuzzleException $e) {
    echo "error in script: " . $e->getMessage() . "\r\n";
}

echo 'execution time ' . round(microtime(true) - $start, 2) . ' seconds';
