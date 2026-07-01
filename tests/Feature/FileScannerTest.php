<?php

namespace Tests\Feature;

use App\Contracts\FileScanner;
use App\Services\FileScanners\NullFileScanner;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FileScannerTest extends TestCase
{
    public function test_null_scanner_allows_clean_uploads(): void
    {
        $scanner = new NullFileScanner;
        $scanner->scan(UploadedFile::fake()->create('document.pdf', 50));

        $this->assertTrue(true);
    }

    public function test_file_scanner_is_bound_in_container(): void
    {
        $this->assertInstanceOf(FileScanner::class, app(FileScanner::class));
    }
}
