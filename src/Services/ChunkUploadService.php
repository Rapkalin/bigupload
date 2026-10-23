<?php

namespace App\Services;

use App\Constant\UploadConfig;
use App\Exception\UploadException;
use App\Model\ReceivedChunk;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rebuilds a file sent by PLupload in chunks.
 *
 * Each upload is written in "<uploadId>.part", where uploadId is a random token generated on the first chunk
 * and returned to the client: two uploads with the same file name never share a temp file, and nobody can
 * append data to an upload without knowing its token.
 * Each chunk is written at its own offset, so a chunk retried by PLupload never duplicates data.
 */
final class ChunkUploadService
{
    private const string UPLOAD_ID_PATTERN = '/^[a-f0-9]{32}$/';

    /**
     * $chunkSize and $maxFileSize are UploadConfig::CHUNK_SIZE and UploadConfig::MAX_FILE_SIZE (smaller in tests)
     */
    public function __construct(
        private readonly string $uploadDir,
        private readonly int $chunkSize,
        private readonly int $maxFileSize,
        private readonly Filesystem $filesystem,
    ) {
    }

    /**
     * @param resource $input
     */
    public function receive(string $fileName, int $chunk, int $chunks, ?string $uploadId, $input): ReceivedChunk
    {
        $chunks = max($chunks, 1);

        if (!UploadConfig::isAllowedExtension($fileName)) {
            throw new UploadException("$fileName has a file type that is not allowed.", Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
        }

        if ($chunks > intdiv($this->maxFileSize + $this->chunkSize - 1, $this->chunkSize)) {
            throw new UploadException("$fileName exceeds the maximum file size.", Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
        }

        if ($chunk < 0 || $chunk >= $chunks) {
            throw new UploadException('Invalid chunk number.', Response::HTTP_BAD_REQUEST);
        }

        if (0 === $chunk) {
            $uploadId = bin2hex(random_bytes(16));
            $this->filesystem->mkdir($this->uploadDir);
        } elseif (null === $uploadId || !preg_match(self::UPLOAD_ID_PATTERN, $uploadId) || !is_file($this->getPartPath($uploadId))) {
            throw new UploadException("$fileName is not allowed to proceed.", Response::HTTP_FORBIDDEN);
        }

        $partPath = $this->getPartPath($uploadId);
        $this->writeChunk($partPath, $chunk * $this->chunkSize, $input);

        return new ReceivedChunk($uploadId, $partPath, $chunk === $chunks - 1);
    }

    private function getPartPath(string $uploadId): string
    {
        return $this->uploadDir . DIRECTORY_SEPARATOR . $uploadId . '.' . UploadConfig::PART_EXTENSION;
    }

    /**
     * @param resource $input
     */
    private function writeChunk(string $partPath, int $offset, $input): void
    {
        clearstatcache(true, $partPath);
        if ((is_file($partPath) ? filesize($partPath) : 0) < $offset) {
            throw new UploadException('A previous chunk is missing.', Response::HTTP_CONFLICT);
        }

        $output = fopen($partPath, 'c+b');
        if (false === $output) {
            throw new \RuntimeException("Failed to open $partPath.");
        }

        try {
            fseek($output, $offset);
            // Read one byte more than allowed to detect oversized chunks
            $written = stream_copy_to_stream($input, $output, $this->chunkSize + 1);
            if (false === $written) {
                throw new \RuntimeException('Failed to read the uploaded chunk.');
            }
            // Drops any data left by a previous attempt of this chunk
            ftruncate($output, $offset + $written);
        } finally {
            fclose($output);
        }

        if ($written > $this->chunkSize || $offset + $written > $this->maxFileSize) {
            $this->filesystem->remove($partPath);
            throw new UploadException('The file exceeds the maximum file size.', Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
        }
    }
}
