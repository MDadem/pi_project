<?php

namespace App\Entity;

use App\Repository\BlogCommentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BlogCommentRepository::class)]
class BlogComment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $cbContent = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $creationDate = null;


    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $modficationDate = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCbContent(): ?string
    {
        return $this->cbContent;
    }

    public function setCbContent(string $cbContent): static
    {
        $this->cbContent = $cbContent;

        return $this;
    }

    public function getCreationDate(): ?\DateTimeInterface
    {
        return $this->creationDate;
    }

    public function setCreationDate(\DateTimeInterface $creationDate): static
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getModificationDate(): ?string
    {
        return $this->modificationDate;
    }

    public function setModificationDate(string $modificationDate): static
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    public function getModficationDate(): ?\DateTimeInterface
    {
        return $this->modficationDate;
    }

    public function setModficationDate(\DateTimeInterface $modficationDate): static
    {
        $this->modficationDate = $modficationDate;

        return $this;
    }
}
