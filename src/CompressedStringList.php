<?php
namespace Orware\Compressed;

class CompressedStringList
{
    protected $queue = null;

    public function __construct()
    {
        $this->queue = new \SplQueue();
        $this->queue->setIteratorMode(\SplDoublyLinkedList::IT_MODE_FIFO | \SplDoublyLinkedList::IT_MODE_DELETE);
    }

    public static function merge($subject, $delimiter, CompressedStringList $gzippedStrings, $compressionLevel = 6, $addQuotesToDelimiter = false)
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
            if (!$gzippedStrings->isEmpty()) {
                $string = $gzippedStrings->dequeue();

                $readStream = $string->getDecompressedReadOnlyStream();
                while ($buffer = $readStream->read()) {
                    $merged->write($buffer);
                }
            }
        }

        return $merged;
    }

    public function enqueue(CompressedString $string)
    {
        return $this->queue->enqueue($string);
    }

    public function dequeue()
    {
        return $this->queue->dequeue();
    }

    public function isEmpty()
    {
        return $this->queue->isEmpty();
    }

    public function count()
    {
        return $this->queue->count();
    }
}
