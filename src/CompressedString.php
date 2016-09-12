<?php
namespace Orware\Compressed;

use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\StreamDecoratorTrait;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\StreamWrapper;

/**
* Compressed String Class
*
* Allows a user to directly generate a Gzip String in memory
* as its primary use case, though it also allows for you to
* create a stream for existing Gzip files on your computer.
*
*/
class CompressedString
{
    /**
    * Class constant
    *
    * Useful to pass into the CompressedStringList::merge() method
    * if you don't already have your own delimiter.
    *
    */
    const DEFAULT_DELIMITER = '#|_|#';

    /**
    * Holds the instance of GzStreamGuzzle
    *
    * @var GzStreamGuzzle
    */
    protected $gzStream = null;

    /**
    * Holds a copy of the raw stream resource
    *
    * @var resource
    */
    protected $stream = null;

    /**
    * Holds a copy of the GuzzleHttp\Psr7\Stream class.
    *
    * @var Stream
    */
    protected $streamObject = null;

    /**
    * Flag remembering whether the class
    * was instantiated as read-only or not.
    *
    * @var bool
    */
    protected $readOnly = false;

    /**
    * The Gzip Compression Level to use When Writing
    *
    * Valid values are 0-9
    * 0 = No Compression
    * 1 = Minimal Compression
    * 6 = Default Compression
    * 9 = Maximum Compression
    *
    * @var int
    */
    protected $compressionLevel = 6;

    /**
    * put your comment there...
    *
    * @var mixed
    */
    protected $isRealFile = false;

    /**
    * CompressedString constructor.
    *
    * @param bool $readOnly
    * @param int $compressionLevel
    * @param string $filepath
    */
    public function __construct($readOnly = false, $compressionLevel = 6, $filepath = 'php://memory')
    {
        $this->replaceStream($readOnly, $compressionLevel, $filepath);
    }

    /**
    * Creates the internal stream and GzStreamGuzzle instances.
    *
    * Used by other methods when needed.
    *
    * @param bool $readOnly
    * @param int $compressionLevel
    * @param string $filepath
    *
    * @return void
    */
    public function replaceStream($readOnly = false, $compressionLevel = 6, $filepath = 'php://memory')
    {
        if (substr($filepath, 0, 6) !== 'php://') {
            $this->isRealFile = true;
        }

        $this->filepath = $filepath;
        $this->compressionLevel = $compressionLevel;
        $this->stream = fopen($this->filepath, 'r+');
        $this->streamObject = Psr7\stream_for($this->stream);
        $this->readOnly = $readOnly;
        $this->gzStream = new GzStreamGuzzle($this->streamObject, $this->readOnly, $this->compressionLevel);
    }

    /**
    * Tells you whether or not the current CompressedString instance
    * was created for read-only, or whether it is writable.
    *
    * @return bool
    */
    public function isReadOnly()
    {
        return $this->readOnly;
    }

    /**
    * Tells you the filepath the current CompressedString instance
    * was created with.
    *
    * @return string
    */
    public function getPath()
    {
        return $this->filepath;
    }

    /**
    * Writes to the CompressedString instance.
    *
    * Takes a regular string and writes it to the
    * CompressedString.
    *
    * Additionally, you can also pass in an array or object
    * and it will json_encode it for you automatically.
    *
    * If you have any special options to pass into json_encode
    * in these situations you may use the optional $options
    * and $depth parameters.
    *
    * @param mixed $string
    * @param int $options
    * @param int $depth
    *
    * @return int
    */
    public function write($string, $options = 0, $depth = 512)
    {
        if (!is_string($string)) {
            $string = json_encode($string, $options, $depth);
        }

        return $this->getGzStream()->write($string);
    }

    /**
    * Reads from the stream and returns the read data
    * as a string.
    *
    * Depending on the state and type of instance you
    * have created, reading may result in uncompressed
    * or compressed output being returned.
    *
    * Therefore, depending on what you need, you may
    * want to use the getCompressedReadOnlyStream()
    * or getDecompressedReadOnlyStream() methods
    * instead.
    *
    * @param int $length
    *
    * @return string
    */
    public function read($length = 65536)
    {
        $ret = $this->getGzStream()->read($length);
        return $ret;
    }

    /**
    * Prepends an uncompressed string to the beginning
    * of the currently compressed string.
    *
    * This method is somewhat wasteful because it requires
    * creating a read-only copy of the current stream,
    * creating a new stream, writing the new prepended string,
    * and then decompress and write the old string onto the end
    * of the new string.
    *
    * It's here because it's necessary to do this sometimes, but
    * just keep in mind it may increase the execution time somewhat
    * for your script if used heavily.
    *
    * It optionally allows you to provide a new compression level to
    * use for the new compressed string.
    *
    * @todo Look into ways to improve this method in the future.
    *
    * @param mixed $string
    * @param int $compressionLevel
    *
    * @return void
    */
    public function prepend($string, $compressionLevel = 6)
    {
        $this->prepareForRead();
        $gzStreamReadOnly = $this->getGzStream()->getReadOnlyCopy();

        $this->replaceStream(false, $compressionLevel, 'php://memory');

        $this->getGzStream()->write($string);

        while ($buffer = $gzStreamReadOnly->read()) {
            $this->getGzStream()->write($buffer);
        }
    }

    /**
    * Returns the compressed size (in bytes)
    * of the current string.
    *
    * Since this method calls getCompressedContents()
    * you won't be able to write anymore to the string
    * if you call this method.
    *
    * @todo Look into ways to improve this method in the future.
    *
    * @return int
    */
    public function getCompressedSize()
    {
        return strlen($this->getCompressedContents());
    }

    /**
    * Returns the decompressed string
    *
    * Since this method calls getCompressedContents()
    * you won't be able to write anymore to the string
    * if you call this method.
    *
    * Also, since it calls gzdecode() it may also return
    * false if there are any issues with the compressed string.
    *
    * @todo Look into ways to improve this method in the future.
    *
    * @return string
    */
    public function getDecompressedContents()
    {
        return gzdecode($this->getCompressedContents());
    }

    /**
    * Writes the decompressed string to a file
    * with optional exclusive file locking.
    *
    * @param string $filename
    * @param bool $lock
    *
    * @return int
    */
    public function writeDecompressedContents($filename, $lock = false)
    {
        return file_put_contents($filename, $this->getDecompressedContents(), $lock ? LOCK_EX : 0);
    }

    /**
    * Returns the compressed contents as a string
    *
    * @return string
    */
    public function getCompressedContents()
    {
        $this->prepareForRead();
        return stream_get_contents($this->getStream());
    }

    /**
    * Writes the compressed string to a file
    * with optional exclusive file locking.
    *
    * @param string $filename
    * @param bool $lock
    *
    * @return int
    */
    public function writeCompressedContents($filename, $lock = false)
    {
        return file_put_contents($filename, $this->getCompressedContents(), $lock ? LOCK_EX : 0);
    }

    /**
    * Returns the internal GzStreamGuzzle instance.
    *
    * @return GzStreamGuzzle
    */
    public function getGzStream()
    {
        return $this->gzStream;
    }

    /**
    * Prepares the CompressedString stream for reading
    *
    * @return void
    */
    public function prepareForRead()
    {
        $this->getGzStream()->writeFooterEarly();
        $this->rewind();
    }

    /**
    * Rewinds the CompressedString stream
    *
    * @return void
    */
    public function rewind()
    {
        rewind($this->stream);
    }

    /**
    * Returns the internal stream resource
    *
    * @return resource
    */
    public function getStream()
    {
        return $this->stream;
    }

    /**
    * Returns the internal GuzzleHttp\Psr7\Stream Object.
    *
    * @return Stream
    */
    public function getStreamObject()
    {
        return $this->streamObject;
    }

    /**
    * Returns a read-only stream that can be used
    * for reading the compressed contents back out
    * as a stream.
    *
    * @return GzStreamGuzzle
    */
    public function getCompressedReadOnlyStream()
    {
        if ($this->isRealFile && $this->isReadOnly()) {
            $writable = new CompressedString(false, $this->compressionLevel, $this->filepath);
            return $writable->getGzStream();
        } elseif ($this->isRealFile) {
            return $this->getGzStream();
        }

        // More specifically for the in-memory streams:
        $this->prepareForRead();
        return $this->getGzStream();
    }

    /**
    * Returns a read-only stream that can be used
    * for reading the decompressed contents back out
    * as a stream.
    *
    * @return GzStreamGuzzle
    */
    public function getDecompressedReadOnlyStream()
    {
        if ($this->isRealFile && $this->isReadOnly()) {
            return $this->getGzStream();
        } elseif ($this->isRealFile) {
            return $this->getGzStream()->getReadOnlyCopy();
        }

        // More specifically for the in-memory streams:
        $this->prepareForRead();
        $gzStreamReadOnly = $this->getGzStream()->getReadOnlyCopy();

        return $gzStreamReadOnly;
    }
}
