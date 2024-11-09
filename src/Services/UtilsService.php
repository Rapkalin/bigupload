<?php

use Symfony\Component\HttpFoundation\JsonResponse;

if (!function_exists('formatJsonResponseData')) {
    function formatJsonResponseData(
        string $status,
        string $context,
        string $message,
        int $httpStatusCode,
        string $errorMessage = '',
        array $extraData = []
    ): JsonResponse
    {
        $data = [
            'status' => $status,
            'details' => [
                'message' => $message
            ]
        ];

        if ($_ENV['APP_ENV'] !== 'prod') {
            $data['details']['context'] = $context;

            if ($errorMessage) {
                $data['details']['error'] = $errorMessage;
            }
        }

        if ($extraData) {
            $data = array_merge($data['details'], $extraData);
        }

        return new JsonResponse($data , $httpStatusCode);
    }

    /**
     * @param string $color Available colors are:
     * ['black','red','green','yellow','blue','cyan','white']
     *
     * @param string $message
     * @param bool $end
     * @return string
     *
     */
     function formatMessage(
        string $color,
        string $message,
        bool $end = false
    ): string
    {
        $colors = [
            'black' => "\033[30m",
            'red' => "\033[31m",
            'green' => "\033[32m",
            'yellow' => "\033[33m",
            'blue' => "\033[34m",
            'cyan' => "\033[36m",
            'white' => "\033[39m",
        ];

        $formattedMessage = $colors[$color] . $message . $colors['white'] . ( $end ? "" : PHP_EOL);
        if ($end) {
            $formattedMessage .= addLine();
        }

        return $formattedMessage;
    }

    function addLine(): string
    {
        return PHP_EOL . PHP_EOL . "----------------" . PHP_EOL . PHP_EOL;
    }

}
