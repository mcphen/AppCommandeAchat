<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DatabaseBackupDownloadController extends Controller
{
    public function __invoke(string $filename): BinaryFileResponse
    {
        abort_unless(basename($filename) === $filename && str_ends_with($filename, '.zip'), 404);

        $path = rtrim((string) config('backup.directory'), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$filename;
        abort_unless(is_file($path), 404);

        return response()->download($path, $filename, ['Content-Type' => 'application/zip']);
    }
}
