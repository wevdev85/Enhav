<?php
namespace esperanto\ContentBundle\Item\Type;

use Doctrine\Common\Collections\ArrayCollection;

class CiteText
{
    /**
     * @var string
     */
    private $text;

    private $title;

    private $cite;

    private $type;

    private $textLeft;

    public function __construct()
    {
        $this->files = new ArrayCollection();
    }

    public function setTextLeft($textLeft)
    {
        $this->textLeft = $textLeft;

        return $this;
    }

    public function getTextLeft()
    {
        if($this->textLeft === null) {
            return false;
        }

        return $this->textLeft;
    }


    /**
     * @param string $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return mixed
     */
    public function getCite()
    {
        return $this->cite;
    }

    /**
     * @param mixed $cite
     */
    public function setCite($cite)
    {
        $this->cite = $cite;
    }

}