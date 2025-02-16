<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $pwd = null;

    #[ORM\Column(length: 255)]
    private ?string $profileIMG = null;

    /**
     * @var Collection<int, CommunityMembers>
     */
    #[ORM\OneToMany(targetEntity: CommunityMembers::class, mappedBy: 'user')]
    private Collection $communityMembers;

    /**
     * @var Collection<int, JoinRequest>
     */
    #[ORM\OneToMany(targetEntity: JoinRequest::class, mappedBy: 'user')]
    private Collection $joinRequests;

    /**
     * @var Collection<int, Post>
     */
    #[ORM\OneToMany(targetEntity: Post::class, mappedBy: 'user')]
    private Collection $posts;

    public function __construct()
    {
        $this->communityMembers = new ArrayCollection();
        $this->joinRequests = new ArrayCollection();
        $this->posts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPwd(): ?string
    {
        return $this->pwd;
    }

    public function setPwd(string $pwd): static
    {
        $this->pwd = $pwd;

        return $this;
    }

    public function getProfileIMG(): ?string
    {
        return $this->profileIMG;
    }

    public function setProfileIMG(string $profileIMG): static
    {
        $this->profileIMG = $profileIMG;

        return $this;
    }

    /**
     * @return Collection<int, CommunityMembers>
     */
    public function getCommunityMembers(): Collection
    {
        return $this->communityMembers;
    }

    public function addCommunityMember(CommunityMembers $communityMember): static
    {
        if (!$this->communityMembers->contains($communityMember)) {
            $this->communityMembers->add($communityMember);
            $communityMember->setUser($this);
        }

        return $this;
    }

    public function removeCommunityMember(CommunityMembers $communityMember): static
    {
        if ($this->communityMembers->removeElement($communityMember)) {
            // set the owning side to null (unless already changed)
            if ($communityMember->getUser() === $this) {
                $communityMember->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, JoinRequest>
     */
    public function getJoinRequests(): Collection
    {
        return $this->joinRequests;
    }

    public function addJoinRequest(JoinRequest $joinRequest): static
    {
        if (!$this->joinRequests->contains($joinRequest)) {
            $this->joinRequests->add($joinRequest);
            $joinRequest->setUser($this);
        }

        return $this;
    }

    public function removeJoinRequest(JoinRequest $joinRequest): static
    {
        if ($this->joinRequests->removeElement($joinRequest)) {
            // set the owning side to null (unless already changed)
            if ($joinRequest->getUser() === $this) {
                $joinRequest->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Post>
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(Post $post): static
    {
        if (!$this->posts->contains($post)) {
            $this->posts->add($post);
            $post->setUser($this);
        }

        return $this;
    }

    public function removePost(Post $post): static
    {
        if ($this->posts->removeElement($post)) {
            // set the owning side to null (unless already changed)
            if ($post->getUser() === $this) {
                $post->setUser(null);
            }
        }

        return $this;
    }
}
