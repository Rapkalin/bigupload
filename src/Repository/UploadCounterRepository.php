<?php

namespace App\Repository;

use Doctrine\DBAL\Connection;

/**
 * Total number of uploaded files, stored in the single row of the count_downloads table.
 */
final class UploadCounterRepository
{
    private const int COUNTER_ID = 1;

    public function __construct(private readonly Connection $connection)
    {
    }

    public function get(): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT uploaded_files FROM count_downloads WHERE id = ?',
            [self::COUNTER_ID]
        );
    }

    public function increment(): void
    {
        $this->connection->executeStatement(
            'UPDATE count_downloads SET uploaded_files = uploaded_files + 1 WHERE id = ?',
            [self::COUNTER_ID]
        );
    }
}
