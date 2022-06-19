<?php

namespace Noweh\CanWeDeployToday;

use SQLite3;
use Exception;
use JsonException;
use RuntimeException;

class DBAdapter extends SQLite3
{
    /**
     * Constructor
     * Create table if not exists
     * @param string $filename
     * @param int $flags
     * @param string $encryptionKey
     */
    public function __construct(
        string $filename,
        int $flags = SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE,
        string $encryptionKey = ''
    ) {
        parent::__construct($filename, $flags, $encryptionKey);

        // Create table if not exists
        $this->query(
            "CREATE TABLE IF NOT EXISTS answers (
                tweet_id VARCHAR(30) PRIMARY KEY,
                sql_time TIMESTAMP default CURRENT_TIMESTAMP not null
            );"
        );
    }

    /**
     * Insert the tweetId in database
     * @param string $tweetId
     * @return bool
     * @throws JsonException
     */
    public function addAnswerId(string $tweetId): bool
    {
        try {
            $stmt = $this->prepare("INSERT INTO answers (tweet_id) VALUES (:tweetId)");
            if ($stmt) {
                $stmt->bindValue(':tweetId', $tweetId, SQLITE3_TEXT);
                if ($stmt->execute() !== false) {
                    return true;
                }
            }
        } catch (Exception $e) {
            throw new RuntimeException(__METHOD__ . ': '. json_encode($e->getMessage(), JSON_THROW_ON_ERROR));
        }

        throw new RuntimeException(__METHOD__ . ': unable to execute statement');
    }

    /**
     * Search if the tweetId exists
     * @param string $tweetId
     * @return array<int, string>|false
     * @throws JsonException
     */
    public function searchAnswerId(string $tweetId): array|false
    {
        try {
            $stmt = $this->prepare("SELECT * FROM answers WHERE tweet_id=:tweetId");
            if ($stmt) {
                $stmt->bindValue(':tweetId', $tweetId, SQLITE3_TEXT);
                $result = $stmt->execute();
                if ($result) {
                    return $result->fetchArray();
                }
            }
        } catch (Exception $e) {
            throw new RuntimeException(__METHOD__ . ': '. json_encode($e->getMessage(), JSON_THROW_ON_ERROR));
        }

        throw new RuntimeException(__METHOD__ . ': unable to execute statement');
    }
}