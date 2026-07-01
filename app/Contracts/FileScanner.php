<?php

namespace App\Contracts;

use Illuminate\Http\UploadedFile;

interface FileScanner
{
    /**
     * @throws \RuntimeException when the upload fails scanning
     */
    public function scan(UploadedFile $file): void;
}
