<?php
namespace Orware\Compressed;

use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\StreamDecoratorTrait;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\StreamWrapper;

class String
{
    protected $gzStream = null;
    protected $stream = null;
    protected $streamObject = null;
    protected $compressionLevel = 6;
    protected $isRealFile = false;

    public function __construct($readOnly = false, $compressionLevel = 6, $filepath = 'php://memory')
    {
        $this->replaceStream($readOnly, $compressionLevel, $filepath);
    }

    public function replaceStream($readOnly = false, $compressionLevel = 6, $filepath = 'php://memory')
    {
        if (substr($filepath, 0, 6) !== 'php://') {
            $this->isRealFile = true;
        }
        $this->compressionLevel = $compressionLevel;
        $this->stream = fopen($filepath, 'r+');
        $this->streamObject = Psr7\stream_for($this->stream);
        $this->gzStream = new GzStreamGuzzle($this->streamObject, $readOnly, $this->compressionLevel);
    }

    public function write($string, $options = 0, $depth = 512)
    {
        if (!is_string($string)) {
            $string = json_encode($string, $options, $depth);
        }

        return $this->getGzStream()->write($string);
    }

    public function read($length = 65536)
    {
        $ret = $this->getGzStream()->read($length);
        return $ret;
    }

    public function prepend($string, $compressionLevel = 6)
    {
        $this->prepareForRead();
        $gzStreamReadOnly = $this->getGzStream()->readOnlyStream();

        $this->replaceStream(false, $compressionLevel, 'php://memory');

        $this->getGzStream()->write($string);

        while ($buffer = $gzStreamReadOnly->read()) {
            $this->getGzStream()->write($buffer);
        }
    }

    public function getCompressedSize()
    {
        return strlen($this->getCompressedContents());
    }

    public function getDecompressedContents()
    {
        return gzdecode($this->getCompressedContents());
    }

    public function writeDecompressedContents($filename, $lock = false)
    {
        return file_put_contents($filename, $this->getDecompressedContents(), $lock ? LOCK_EX : 0);
    }

    public function getCompressedContents()
    {
        $this->prepareForRead();
        return stream_get_contents($this->getStream());
    }

    public function writeCompressedContents($filename, $lock = false)
    {
        return file_put_contents($filename, $this->getCompressedContents(), $lock ? LOCK_EX : 0);
    }

    public function getGzStream()
    {
        return $this->gzStream;
    }

    public function prepareForRead()
    {
        $this->getGzStream()->writeFooterEarly();
        $this->rewind();
    }

    public function rewind()
    {
        rewind($this->stream);
    }

    public function getStream()
    {
        return $this->stream;
    }

    public function getStreamObject()
    {
        return $this->streamObject;
    }

    public function getReadOnlyStream()
    {
        if ($this->isRealFile) {
            return $this->getGzStream();
        }

        // More specifically for the in-memory streams:
        $this->prepareForRead();
        $gzStreamReadOnly = $this->getGzStream()->readOnlyStream();

        return $gzStreamReadOnly;
    }
}
