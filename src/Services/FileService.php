<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\JsonResponse;

final class CommonsService
{
    protected array $colors = [
        'black' => "\033[30m",
        'red' => "\033[31m",
        'green' => "\033[32m",
        'yellow' => "\033[33m",
        'blue' => "\033[34m",
        'cyan' => "\033[36m",
        'white' => "\033[39m",
    ];

    /**
     * @param string $color Available colors are:
     * ['black','red','green','yellow','blue','cyan','white']
     *
     * @param string $message
     * @param bool $end
     * @return string
     *
     */
    public function formatMessage(
        string $color,
        string $message,
        bool $end = false
    ): string
    {
        $formattedMessage = $this->colors[$color] . $message . $this->colors['white'] . ( $end ? "" : PHP_EOL);
        if ($end) {
            $formattedMessage .= $this->addLine();
        }

        return $formattedMessage;
    }

    private function addLine(): string
    {
        return PHP_EOL . PHP_EOL . "----------------" . PHP_EOL . PHP_EOL;
    }

    /**
     * @param $filename
     * @return string|null Retrieve the captured extension or null if no extension has been found
     */
    public function getFileExtension($filename): ?string
    {
        if (preg_match('/\.([a-zA-Z0-9]+)$/', $filename, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * @param string $filepath
     * @return string|null Retrieve the captured extension or null if no extension has been found
     */
    public function getFileSize(string $filepath): ?string
    {
        return filesize($filepath);
    }

    /**
     * @param string $filepath
     * @return string|null Retrieve the captured extension or null if no extension has been found
     * @throws \DateMalformedStringException
     */
    public function getFileCreatedAt(string $filepath): ?string
    {
        $created_at = filectime($filepath);
        return (new \DateTimeImmutable($created_at))->format('Y-m-d H:i:s');
    }

    /**
     * @param string $filepath
     * @param int $created_at
     * @return string|null Retrieve the captured extension or null if no extension has been found
     */
    public function getFileSizeExpirationDate(string $filepath, int $created_at): ?string
    {
        // Todo: calcul expiration date
        return filectime($filepath);
    }

    public function formatJsonResponseData(
        string $status,
        string $context,
        string $message,
        int $httpStatusCode,
        array $extraData = []
    ): JsonResponse
    {
        $data = [
            'status' => $status,
            'details' => [
                'context' => $context,
                'message' => $message
            ]
        ];

        if ($extraData) {
            $data = array_merge($data['details'], $extraData);
        }

        return new JsonResponse($data , $httpStatusCode);
    }
}