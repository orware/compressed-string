<?php
namespace Orware\Compressed;

/**
* Compressed String List Class
*
* Allows you to queue up multiple instances of CompressedString
* so that you can use later or with the merge method, which allows
* a new compressed string to be created with the combined output.
*
* e.g. Merging one or more compressed JSON strings of database results
* into a JSON object containing metadata.
*
*/
class CompressedStringList
{
    /**
    * The internal SplQueue instance
    *
    * @var \SplQueue
    */
    protected $queue = null;

    /**
    * Creates the CompressedStringList
    *
    * By default it will not keep copies of the
    * queued objects during iteration in order to
    * conserve memory usage.
    *
    */
    public function __construct($keep = false)
    {
        $this->queue = new \SplQueue();
        $mode = \SplDoublyLinkedList::IT_MODE_DELETE;
        if ($keep) {
            $mode = \SplDoublyLinkedList::IT_MODE_KEEP;
        }
        $this->queue->setIteratorMode(\SplDoublyLinkedList::IT_MODE_FIFO | $mode);
    }

    /**
    * Merges multiple CompressedStrings contained
    * in a CompressedStringList into the $subject
    * string based on the locations of the $delimiter.
    *
    * The subject will normally be a string, but you may also
    * provide an array or object and it will be JSON encoded and then
    * split on the provided delimiter.
    *
    * You may optionally provide a new compression level
    * and make use of the option to automatically add quotes
    * to the delimiter (useful for when the delimiter has been added
    * to text that was then JSON encoded, otherwise your inserted text might
    * included additional quotes around it that you won't want).
    *
    * @param mixed $subject
    * @param string $delimiter
    * @param CompressedStringList $compressedStringList
    * @param int $compressionLevel
    * @param bool $addQuotesToDelimiter
    *
    * @return CompressedString
    */
    public static function merge($subject, $delimiter, CompressedStringList $compressedStringList, $compressionLevel = 6, $addQuotesToDelimiter = false)
    {
        if (!is_string($subject)) {
            $subject = json_encode($subject);
        }

        if ($addQuotesToDelimiter) {
            $delimiter = '"'.$delimiter.'"';
        }

        $subjectParts = explode($delimiter, $subject);

        $merged = new CompressedString(false, $compressionLevel);

        foreach ($subjectParts as $part) {
            $merged->write($part);
            if (!$compressedStringList->isEmpty()) {
                $string = $compressedStringList->dequeue();

                $readStream = $string->getDecompressedReadOnlyStream();
                while ($buffer = $readStream->read()) {
                    $merged->write($buffer);
                }
            }
        }

        return $merged;
    }

    /**
    * Add a CompressedString to the Queue.
    *
    * @param CompressedString $string
    *
    * @return void
    */
    public function enqueue(CompressedString $string)
    {
        return $this->queue->enqueue($string);
    }

    /**
    * Returns a CompressedString from the Queue
    *
    * @return CompressedString
    */
    public function dequeue()
    {
        return $this->queue->dequeue();
    }

    /**
    * Checks whether the list is empty
    *
    * @return bool
    */
    public function isEmpty()
    {
        return $this->queue->isEmpty();
    }

    /**
    * Returns the number of entries in the Queue
    *
    * @return int
    */
    public function count()
    {
        return $this->queue->count();
    }
}
