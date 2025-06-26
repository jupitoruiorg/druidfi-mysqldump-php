<?php

namespace Druidfi\Mysqldump\Compress;

use Exception;

class CompressLz4 implements CompressInterface
{
    private $fileHandler;
    private string $buffer = '';
    private int $compressionLevel;

    /**
     * @throws Exception
     */
    public function __construct(int $compressionLevel = 1)
    {
        if (!extension_loaded('lz4')) {
            throw new Exception('Compression is enabled, but lz4 extension is not installed or configured properly');
        }

        // Ensure compression level is within valid range (1-12 for LZ4)
        $this->compressionLevel = max(1, min(12, $compressionLevel));
    }

    /**
     * @throws Exception
     */
    public function open(string $filename): bool
    {
        $this->fileHandler = fopen($filename, 'wb');

        if (false === $this->fileHandler) {
            throw new Exception('Output file is not writable');
        }

        return true;
    }

    /**
     * @throws Exception
     */
    public function write(string $str): int
    {
        $this->buffer .= $str;
        return strlen($str);
    }

    public function close(): bool
    {
        if (empty($this->buffer)) {
            return true;
        }

        $compressed = lz4_compress($this->buffer, $this->compressionLevel);

        if ($compressed === false) {
            return false;
        }

        $bytesWritten = fwrite($this->fileHandler, $compressed);
        $result = fclose($this->fileHandler);

        return ($bytesWritten !== false) && $result;
    }
}
