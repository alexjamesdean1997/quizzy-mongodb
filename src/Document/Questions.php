<?php

// src/Document/Product.php
namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document
 */
class Questions
{
    /**
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @MongoDB\Field(type="integer")
     */
    protected $qid;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $language;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $category;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $theme;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $question;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $correct_answer;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $difficulty;

    /**
     * @MongoDB\Field(type="collection")
     */
    protected $choices = [];

    public function getId()
    {
        return $this->id;
    }

    public function setQid(int $qid): self
    {
        $this->qid = $qid;

        return $this;
    }

    public function getQid(): ?int
    {
        return $this->qid;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(string $language): self
    {
        $this->language = $language;

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

    public function getTheme(): ?string
    {
        return $this->theme;
    }

    public function setTheme(?string $theme): self
    {
        $this->theme = $theme;

        return $this;
    }

    public function getDifficulty(): ?string
    {
        return $this->difficulty;
    }

    public function setDifficulty(?string $difficulty): self
    {
        $this->difficulty = $difficulty;

        return $this;
    }

    public function getQuestion(): ?string
    {
        return $this->question;
    }

    public function setQuestion(string $question): self
    {
        $this->question = $question;

        return $this;
    }

    public function getCorrectAnswer(): ?string
    {
        return $this->correct_answer;
    }

    public function setCorrectAnswer(string $correct_answer): self
    {
        $this->correct_answer = $correct_answer;

        return $this;
    }

    public function getChoices(): ?array
    {
        return $this->choices;
    }

    public function setChoices(array $choices): self
    {
        $this->choices = $choices;

        return $this;
    }
}