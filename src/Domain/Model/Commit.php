<?php

namespace Anagit\Domain\Model;

class Commit
{
    /**
     * @var string
     */
    private $hash;

    /**
     * @var string
     */
    private $author;

    /**
     * @var string
     */
    private $date;

    private function __construct($hash, $author, $date)
    {
        $this->hash = $hash;
        $this->author = $author;
        $this->date = $date;
    }

    public static function createFromString($string)
    {
        $data = explode('|', $string);

        return new self($data[0], $data[1], $data[2]);
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @return string
     */
    public function getDate()
    {
        return $this->date;
    }

    public function __sleep()
    {
        return array('hash', 'author', 'date');
    }

}