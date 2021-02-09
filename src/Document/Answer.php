<?php

namespace App\Document;


use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\ODM\MongoDB\Mapping\Annotations\EmbeddedDocument;

/** @EmbeddedDocument */
class Answer
{
    /**
     * @var string
     * @MongoDB\Id
     */
    private $id;

    /**
     * @MongoDB\Field(type="boolean")
     */
    private $success;

    /**
     * @MongoDB\Field(type="string")
     */
    private $category;

    /**
     * @MongoDB\Field(type="integer")
     */
    private $questionId;

    /**
     * @MongoDB\Field(type="integer")
     */
    private $score;

    /**
     * @MongoDB\Field(type="date")
     */
    private $date;

    public function getId()
    {
        return $this->id;
    }

    public function getSuccess(): ?bool
    {
        return $this->success;
    }

    public function setSuccess(bool $success): self
    {
        $this->success = $success;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getQuestionId(): ?int
    {
        return $this->questionId;
    }

    public function setQuestionId(?int $questionId): self
    {
        $this->questionId = $questionId;

        return $this;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(?int $score): self
    {
        $this->score = $score;

        return $this;
    }

    public function getDate(): ?DateTime {
        return $this->date;
    }

    public function setDate(DateTime $date) {
        $this->date = $date;
    }
}