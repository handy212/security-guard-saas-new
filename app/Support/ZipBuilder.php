<?php

namespace App\Support;

/**
 * Minimal ZIP writer (stored + deflated) without the PHP zip extension.
 */
class ZipBuilder
{
    /** @var list<array{name: string, data: string, compressed: string, method: int, crc: int}> */
    private array $entries = [];

    public function add(string $name, string $data): self
    {
        $name = ltrim(str_replace('\\', '/', $name), '/');
        $crc = crc32($data) & 0xFFFFFFFF;
        $deflated = gzdeflate($data, 9);
        $useDeflate = is_string($deflated) && strlen($deflated) < strlen($data);

        $this->entries[] = [
            'name' => $name,
            'data' => $data,
            'compressed' => $useDeflate ? $deflated : $data,
            'method' => $useDeflate ? 8 : 0,
            'crc' => $crc,
        ];

        return $this;
    }

    public function isEmpty(): bool
    {
        return $this->entries === [];
    }

    public function count(): int
    {
        return count($this->entries);
    }

    public function build(): string
    {
        $local = '';
        $central = '';
        $offset = 0;

        foreach ($this->entries as $entry) {
            $name = $entry['name'];
            $nameLen = strlen($name);
            $compSize = strlen($entry['compressed']);
            $uncompSize = strlen($entry['data']);
            $method = $entry['method'];
            $crc = $entry['crc'];

            $localHeader = pack('VvvvvvVVVvv',
                0x04034B50, // local file header signature
                20,         // version needed
                0,          // flags
                $method,
                0,          // mod time
                0,          // mod date
                $crc,
                $compSize,
                $uncompSize,
                $nameLen,
                0           // extra length
            ).$name.$entry['compressed'];

            $central .= pack('VvvvvvvVVVvvvvvVV',
                0x02014B50, // central directory header
                20,         // version made by
                20,         // version needed
                0,          // flags
                $method,
                0,          // mod time
                0,          // mod date
                $crc,
                $compSize,
                $uncompSize,
                $nameLen,
                0,          // extra length
                0,          // comment length
                0,          // disk number start
                0,          // internal attrs
                0,          // external attrs
                $offset
            ).$name;

            $offset += strlen($localHeader);
            $local .= $localHeader;
        }

        $centralSize = strlen($central);
        $end = pack('VvvvvVVv',
            0x06054B50,           // end of central directory
            0,                    // disk number
            0,                    // disk with central dir
            count($this->entries),
            count($this->entries),
            $centralSize,
            $offset,
            0                     // comment length
        );

        return $local.$central.$end;
    }
}
