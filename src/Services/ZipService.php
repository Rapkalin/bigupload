<?php

namespace App\Services;

use ZipArchive;

class ZipService
{
    /**
     * Zip un dossier et sauvegarde l'archive dans un fichier.
     *
     * @param string $source      Chemin absolu du dossier à zipper
     * @param string $destination Chemin absolu du fichier zip à créer
     *
     * @return bool True si le zip est créé avec succès, sinon False
     */
    public function zipDirectory(string $source, string $destination): bool
    {
        if (!is_dir($source)) {
            throw new \InvalidArgumentException("Le chemin source '$source' n'est pas un dossier valide.");
        }

        $zip = new ZipArchive();

        if ($zip->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        $source = realpath($source);

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($source) + 1);

                $zip->addFile($filePath, $relativePath);
            }
        }

        return $zip->close();
    }
}
