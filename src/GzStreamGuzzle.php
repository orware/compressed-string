<?php
namespace Orware\Compressed;

use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Psr7\StreamDecoratorTrait;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\StreamWrapper;

class GzStreamGuzzle implements StreamInterface
{
    use StreamDecoratorTrait;

    protected $mode;
    protected $level = 6;
    protected $headerLen = 0;
    protected $footerLen = 0;
    protected $hashCtx;
    protected $hashCtxBeforeFooter;
    protected $writeSize = 0;
    protected $filter;

    public function __construct(StreamInterface $stream, $readOnly = false, $level = 6)
    {
        $this->stream = $stream;
        $this->level = $level;

        if (!$stream->isWritable() || $readOnly) {
            $this->mode = 'r';
        } else {
            $this->mode = 'w';
        }

        if ($this->mode == 'r') {
            $this->offsetHeader();
            // inflate stream filter
            $resource = StreamWrapper::getResource($stream);
            $params = array('level' => $this->level);
            stream_filter_append($resource, 'zlib.inflate', STREAM_FILTER_READ, $params);
            $this->stream = new Stream($resource);
        }
    }

    public function readOnlyStream()
    {
        return new self($this->stream, true, $this->level);
    }

    public function read($length = 65536)
    {
        $ret = $this->stream->read($length);
        return $ret;
    }

    public function tell()
    {
        if ($this->mode == 'w') {
            return $this->stream->tell();
        }

        return $this->stream->tell() - $this->headerLen;
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        if ($whence !== SEEK_SET || $offset < 0) {
            throw new \RuntimeException(sprintf(
                'Cannot seek to offset % with whence %s',
                $offset,
                $whence
            ));
        }

        if ($this->mode == 'r') {
            $offset += $this->headerLen;
        } else {
            // if we are seeeking the write has ended, put the footer on
            $this->writeFooter();
        }

        $this->stream->seek($offset);
    }

    protected function offsetHeader()
    {
        $header = $this->stream->read(10);
        $this->headerLen += 10;
        $header = unpack("C10", $header);
        $flags  = $header[4];

        // FEXTRA
        if ($flags & 0x4) {
            $len = $this->stream->read(2);
            $len = unpack("S", $len);
            $this->stream->read($len[1]);
            $this->headerLen += 2+$len[1];
        }
        // FNAME
        if ($flags & 0x8) {
            $this->readToNull();
        }
        // FCOMMENT
        if ($flags & 0x10) {
            $this->readToNull();
        }
        // FHCRC
        if ($flags & 0x2) {
            $this->stream->read(2);
            $this->headerLen += 2;
        }
    }

    protected function readToNull()
    {
        while (($chr = $this->stream->read(1)) !== false) {
            $this->headerLen++;
            if ($chr == "\0") {
                return;
            }
        }
    }

    protected function writeHeader()
    {
        // no filename or mtime
        $header = "\x1F\x8B\x08\0".pack("V", 0)."\0\xFF";
        $this->stream->write($header);
        $this->headerLen = 10;
        $this->hashCtx = hash_init("crc32b");
    }

    public function write($string)
    {
        if ($this->footerLen > 0) {
            return false;
        }

        if (!$this->stream->isWritable()) {
            throw new \RuntimeException('Cannot write to a non-writable stream');
        }

        if ($this->headerLen == 0) {
            $this->writeHeader();
            $resource = StreamWrapper::getResource($this->stream);
            $params = array('level' => $this->level);
            $this->filter = stream_filter_append($resource, 'zlib.deflate', STREAM_FILTER_WRITE, $params);
            $this->stream = new Stream($resource);
        }

        hash_update($this->hashCtx, $string);
        $size = $this->stream->write($string);
        $this->writeSize += $size;
        return $size;
    }

    public function getSize()
    {
        $stat = fstat(StreamWrapper::getResource($this->stream));
        return $stat['size'];
    }

    public function getWriteSize()
    {
        return $this->writeSize;
    }

    public function close()
    {
        if ($this->mode == 'w' && $this->headerLen > 0) {
            // write the close hash and len
            $this->writeFooter();
        }

        $this->stream->close();
    }

    protected function writeFooter()
    {
        if ($this->footerLen > 0) {
            return;
        }

        $this->hashCtxBeforeFooter = hash_copy($this->hashCtx);
        $crc = hash_final($this->hashCtx, true);
        // remove filter
        if (is_resource($this->filter)) {
            stream_filter_remove($this->filter);
        }
        // need to reverse the hash_final string so it's little endian
        $this->stream->write($crc[3].$crc[2].$crc[1].$crc[0]);
        // write the original uncompressed file size
        $this->stream->write(pack("V", $this->writeSize));
        $this->footerLen = 8;
    }

    public function writeFooterEarly()
    {
        if ($this->footerLen > 0) {
            return;
        }

        $this->hashCtxBeforeFooter = hash_copy($this->hashCtx);
        $crc = hash_final($this->hashCtx, true);
        // remove filter
        if (is_resource($this->filter)) {
            stream_filter_remove($this->filter);
        }
        // need to reverse the hash_final string so it's little endian
        $this->stream->write($crc[3].$crc[2].$crc[1].$crc[0]);
        // write the original uncompressed file size
        $this->stream->write(pack("V", $this->writeSize));
        $this->footerLen = 8;
    }

    // Not working as I hoped:
    public function undoWriteFooter()
    {
        if ($this->footerLen > 0) {
        // Attempt to remove the footer content already written by moving to the start of the footer position:
            $this->seek(($this->headerLen + $this->writeSize) - 1);

            // Restore Hash Context:
            $this->hashCtx = $this->hashCtxBeforeFooter;

            // Add the filter back in:
            $resource = StreamWrapper::getResource($this->stream);
            $params = array('level' => $this->level);
            $this->filter = stream_filter_append($resource, 'zlib.deflate', STREAM_FILTER_WRITE, $params);
            $this->stream = new Stream($resource);

            // Reset the Footer Length:
            $this->footerLen = 0;
        }
    }
}
