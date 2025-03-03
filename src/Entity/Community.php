<?php

namespace App\Entity;

use App\Enums\CategoryGrp;
use App\Repository\CommunityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommunityRepository::class)]
class Community
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $banner = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $creationDate = null;

    /**
     * @var Collection<int, CommunityMembers>
     */
    #[ORM\OneToMany(targetEntity: CommunityMembers::class, mappedBy: 'community')]
    private Collection $communityMembers;

    #[ORM\Column(enumType: CategoryGrp::class)]
    private ?CategoryGrp $category = null;

    /**
     * @var Collection<int, JoinRequest>
     */
    #[ORM\OneToMany(targetEntity: JoinRequest::class, mappedBy: 'community')]
    private Collection $joinRequests;

    /**
     * @var Collection<int, Post>
     */
    #[ORM\OneToMany(targetEntity: Post::class, mappedBy: 'community')]
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getBanner(): ?string
    {
        return $this->banner;
    }

    public function setBanner(string $banner): static
    {
        $this->banner = $banner;

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
            $communityMember->setCommunity($this);
        }

        return $this;
    }
    public function addMember(User $user): static
    {
        foreach ($this->communityMembers as $member) {
            if ($member->getUser() === $user) {
                return $this; // L'utilisateur est déjà membre
            }
        }
    
        $communityMember = new CommunityMembers();
        $communityMember->setUser($user);
        $communityMember->setCommunity($this);
        
        $this->communityMembers->add($communityMember);
    
        return $this;
    }
    
    public function removeCommunityMember(CommunityMembers $communityMember): static
    {
        if ($this->communityMembers->removeElement($communityMember)) {
            // set the owning side to null (unless already changed)
            if ($communityMember->getCommunity() === $this) {
                $communityMember->setCommunity(null);
            }
        }

        return $this;
    }

    public function getCategory(): ?CategoryGrp
    {
        return $this->category;
    }

    public function setCategory(CategoryGrp $category): static
    {
        $this->category = $category;

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
            $joinRequest->setCommunity($this);
        }

        return $this;
    }

    public function removeJoinRequest(JoinRequest $joinRequest): static
    {
        if ($this->joinRequests->removeElement($joinRequest)) {
            // set the owning side to null (unless already changed)
            if ($joinRequest->getCommunity() === $this) {
                $joinRequest->setCommunity(null);
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
            $post->setCommunity($this);
        }

        return $this;
    }

    public function removePost(Post $post): static
    {
        if ($this->posts->removeElement($post)) {
            // set the owning side to null (unless already changed)
            if ($post->getCommunity() === $this) {
                $post->setCommunity(null);
            }
        }

        return $this;
    }
}
