<?php

namespace App\Exception;

/**
 * Upload refused because of the client request: the exception code is the HTTP status returned.
 */
final class UploadException extends \RuntimeException
{
    public function __construct(string $message, int $httpStatusCode)
    {
        parent::__construct($message, $httpStatusCode);
    }
}
