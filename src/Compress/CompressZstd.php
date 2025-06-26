<?php

namespace Druidfi\Mysqldump\Compress;

use Exception;

class CompressZstd implements CompressInterface
{
    private $fileHandler;
    private string $buffer = '';
    private int $compressionLevel;

    /**
     * @throws Exception
     */
    public function __construct(int $compressionLevel = 3)
    {
        if (!extension_loaded('zstd')) {
            throw new Exception('Compression is enabled, but zstd extension is not installed or configured properly');
        }

        // Ensure compression level is within valid range (1-22 for zstd)
        $this->compressionLevel = max(1, min(22, $compressionLevel));
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

        $compressed = zstd_compress($this->buffer, $this->compressionLevel);

        if ($compressed === false) {
            return false;
        }

        $bytesWritten = fwrite($this->fileHandler, $compressed);
        $result = fclose($this->fileHandler);

        return ($bytesWritten !== false) && $result;
    }
}
