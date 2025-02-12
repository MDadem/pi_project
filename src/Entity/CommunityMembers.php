<?php

namespace App\Entity;

use App\Repository\CommunityMembersRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommunityMembersRepository::class)]
class CommunityMembers
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'communityMembers')]
    private ?Community $community = null;

    #[ORM\ManyToOne(inversedBy: 'communityMembers')]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $joined_at = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCommunity(): ?Community
    {
        return $this->community;
    }

    public function setCommunity(?Community $community): static
    {
        $this->community = $community;

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

    public function getJoinedAt(): ?\DateTimeInterface
    {
        return $this->joined_at;
    }

    public function setJoinedAt(\DateTimeInterface $joined_at): static
    {
        $this->joined_at = $joined_at;

        return $this;
    }
}
