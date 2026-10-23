<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class ZipService
{
    /**
     * Zips $workingDir/$dirName into $workingDir/$zipName.
     * Files are stored without compression (-0): the allowed files (videos, images, pdf, zip) are already
     * compressed, so deflating them costs a lot of time for no size gain.
     *
     * @throws \Symfony\Component\Process\Exception\ProcessFailedException
     */
    public function zipDirectory(string $workingDir, string $zipName, string $dirName): void
    {
        (new Process(['zip', '-0', '-q', '-r', $zipName, $dirName], $workingDir, timeout: null))->mustRun();
    }
}
