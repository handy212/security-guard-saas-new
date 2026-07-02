<?php

namespace App\Services\FileScanners;

use App\Contracts\FileScanner;
use Illuminate\Http\UploadedFile;

class NullFileScanner implements FileScanner
{
    public function scan(UploadedFile $file): void
    {
        //
    }
}
