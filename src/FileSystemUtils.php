<?php

namespace TIPC;

class FileSystemUtils
{
    /**
     * Given a file name and a list of candidate directories,
     * find an existing, writable file.
     *
     * Return the path to the writable file,
     * or null if no file is found.
     *
     * @param string $fileName
     * @param array $candidateDirs
     * @return string|null
     */
    public static function findWritableFilePath(string $fileName, array $candidateDirs): ?string
    {
        foreach ($candidateDirs as $dir) {
            $candidate = $dir . '/' . $fileName;
            if (is_writable($candidate)) {
                return $candidate;
            }
        }
        fwrite(STDERR, "Could not find existing file: $fileName" . PHP_EOL);
        fwrite(
            STDERR,
            "Tried: " . implode(
                ', ',
                array_map(function ($dir) use ($fileName) {
                    return $dir . '/' . $fileName;
                }, $candidateDirs)
            ) . PHP_EOL
        );
        return null;
    }

    /**
     * Given a file name and a list of candidate directories, find a path
     * where a file can be created.
     *
     * If a directory from the candidates list does not exist, an attempt
     * will be made to create it.
     *
     * Returns the path to the file,
     * or null if a file can not be created.
     *
     * @param string $fileName
     * @param array $candida
     * @return string|null
     */
    public static function findCreatableFilePath(string $fileName, array $candidateDirs): ?string
    {
        if (is_null($socketDir = static::ensureWritableDir($candidateDirs))) {
            fwrite(STDERR, "Could not find a writable directory for: $fileName" . PHP_EOL);
            fwrite(STDERR, "Ensure one of these is writable: " . implode(', ', $candidateDirs) . PHP_EOL);
            return null;
        }
        return $socketDir . '/' . $fileName;
    }

    /**
     * Try to find a writable directory from a list of candidates,
     * possibly creating a new directory if possible.
     *
     * We are intentionally suppressing errors when attempting to create
     * directories, regardless of the reason (file exists,
     * insufficient permissions...), as this is not a critical failure.
     *
     * Returns path to a writeable directory, or false if a writeable
     * directory is not available.
     *
     * @param array $candidateDirs
     * @return ?string
     */
    private static function ensureWritableDir(array $candidateDirs): ?string
    {
        foreach ($candidateDirs as $candidateDir) {
            if (!file_exists($candidateDir)) {
                set_error_handler(static function (int $_errno, string $_errstr): bool {
                    return true;
                });
                @mkdir($candidateDir, 0700, true);
                restore_error_handler();
            }
            if (is_dir($candidateDir) && is_writable($candidateDir)) {
                return $candidateDir;
            }
        }
        return null;
    }
}
