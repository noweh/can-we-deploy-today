<?php

namespace Noweh\CanWeDeployToday;

class SentenceService
{
    /**
     * @return string
     * @throws \Exception
     */
    public function generateSentence(): string
    {
        $jsonData = file_get_contents(filename: __DIR__ . '/config/sentences.json');

        if ($jsonData) {
            try {
                /** @var array<string, array<string, string>> $sentences */
                $sentences = json_decode(
                    json: $jsonData,
                    associative: true,
                    depth: 512,
                    flags: JSON_THROW_ON_ERROR
                );

                $key = match (date('N')) {
                    '1', '2', '3' => 'green',
                    '4' => 'orange',
                    default => 'red',
                };

                if (!empty($sentences[$key][array_rand($sentences[$key])])) {
                    return $sentences[$key][array_rand($sentences[$key])];
                }
            } catch (\JsonException $e) {
                throw new \Exception($e->getMessage());
            }
        }

        throw new \Exception('Error while reading file: ' . __DIR__ . '/config/sentences.json');
    }
}
