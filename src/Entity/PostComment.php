<?php

namespace App\Entity;

use App\Repository\PostCommentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PostCommentRepository::class)]
class PostComment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $pcommentContent = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $creationDate = null;

    #[ORM\ManyToOne(inversedBy: 'postComments')]
#[ORM\JoinColumn(nullable: false)]
private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'postComments')]
    private ?Post $post = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPcommentContent(): ?string
    {
        return $this->pcommentContent;
    }

    public function setPcommentContent(string $pcommentContent): static
    {
        $this->pcommentContent = $pcommentContent;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getPost(): ?Post
    {
        return $this->post;
    }

    public function setPost(?Post $post): static
    {
        $this->post = $post;

        return $this;
    }
}
