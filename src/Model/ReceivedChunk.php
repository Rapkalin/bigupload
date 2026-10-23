<?php

namespace App\Model;

final readonly class ReceivedChunk
{
    public function __construct(
        public string $uploadId,
        public string $partPath,
        public bool $isComplete,
    ) {
    }
}
