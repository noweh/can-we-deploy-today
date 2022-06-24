<?php

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Noweh\TwitterApi\Client as TwitterClient;
use Noweh\CanWeDeployToday\DBAdapter;
use Noweh\CanWeDeployToday\SentenceService;

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
    $sentenceService = new SentenceService();

    // Find all mentions
    /** @var \stdClass $returnMentions */
    $returnMentions = $twitterClient->timeline()
        ->findRecentMentioningForUserId($twitterSettings['account_id'])
        ->performRequest()
    ;

    if (property_exists($returnMentions, 'errors')) {
        throw new \Exception(json_encode($returnMentions, JSON_THROW_ON_ERROR));
    }

    $mentionIdsToAnswer = [];
    foreach ($returnMentions->data as $mention) {
        $mentionIdsToAnswer[] = $mention->id;

        // Find history for mentions
        /** @var \stdClass $returnSearch */
        $returnSearch = $twitterClient->tweetSearch()->showUserDetails()->addFilterOnConversationId($mention->id)->performRequest();

        if (property_exists($returnSearch, 'errors')) {
            throw new \Exception(json_encode($returnSearch, JSON_THROW_ON_ERROR));
        }

        // Remove already answered in history
        if (property_exists($returnSearch, 'data')) {
            foreach ($returnSearch->data as $post) {
                if (($key = array_search($post->id, $mentionIdsToAnswer, true)) !== false) {
                    unset($mentionIdsToAnswer[$key]);
                }
            }
        }
    }

    $nbOfAnswers = 0;
    foreach ($mentionIdsToAnswer as $mentionIdToAnswer) {
        if (!$dbAdapter->searchAnswerId($mentionIdToAnswer)) {
            // Tweet an answer
            $return = (new TwitterClient($twitterSettings))->tweet()->performRequest('POST', [
                'text' => $sentenceService->generateSentence(),
                'reply' => ['in_reply_to_tweet_id' => $mentionIdToAnswer]
            ]);

            // Add the answer ID in DB
            $dbAdapter->addAnswerId($mentionIdToAnswer);
            ++$nbOfAnswers;
        }
    }

    echo "script completed without error\r\n$nbOfAnswers answers were given\r\n";
} catch (Exception | \GuzzleHttp\Exception\GuzzleException $e) {
    echo "error in script: " . $e->getMessage() . "\r\n";
}

echo 'execution time ' . round(microtime(true) - $start, 2) . ' seconds';
