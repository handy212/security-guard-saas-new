<?php

namespace App\Services\FileScanners;

use App\Contracts\FileScanner;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Symfony\Component\Process\Process;

class ClamAvFileScanner implements FileScanner
{
    public function scan(UploadedFile $file): void
    {
        $binary = (string) config('file_scanner.clamav.binary', 'clamscan');

        $process = new Process([$binary, '--no-summary', $file->getRealPath()]);
        $process->run();

        if ($process->getExitCode() === 1) {
            throw new RuntimeException('Upload rejected: file failed virus scan.');
        }

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Upload rejected: virus scanner unavailable.');
        }
    }
}
