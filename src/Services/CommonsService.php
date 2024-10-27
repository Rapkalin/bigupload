<?php

namespace App\Services;

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
     *
     */
    public function formatMessage(
        string $color,
        string $message,
        bool $end = false
    ): string {
        $formattedMessage = $this->colors[$color] . $message . $this->colors['white'] . ( $end ? "" : PHP_EOL);
        if ($end) {
            $formattedMessage .= $this->addLine();
        }

        return $formattedMessage;
    }

    private function addLine(): string {
        return PHP_EOL . PHP_EOL . "----------------" . PHP_EOL . PHP_EOL;
    }

}