<?php

namespace App\Entity;

use App\Repository\UserRepository;



use Doctrine\ORM\Mapping as ORM;
use App\Entity\Cart;
use App\Enum\Role;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;


#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface

{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'First name cannot be blank')]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Last name cannot be blank')]
    private ?string $lastName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Email cannot be blank')]
    #[Assert\Email(message: 'Invalid email format')]
    private ?string $email = null;


//    #[ORM\Column(type: 'boolean')]
//    private bool $isVerified = false;

    #[ORM\Column(type: 'boolean')]
    private bool $isBlocked = false;
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Password cannot be blank')]
    #[Assert\Length(min: 8, minMessage: 'Password must be at least 8 characters long')]
    #[Assert\Regex(
        pattern: '/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&]).{8,}$/',
        message: 'Password must contain at least one uppercase letter, one digit, and one special character'
    )]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
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


    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
private ?Cart $cart = null;




    public function __construct()
    {
        $this->roles = [];
        $this->communityMembers = new ArrayCollection();
        $this->joinRequests = new ArrayCollection();
        $this->posts = new ArrayCollection();

        $this->postComments = new ArrayCollection();

        $this->eventRegistrations = new ArrayCollection();

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


    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;


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

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    /**
     * @var Collection<int, PostComment>
     */
    #[ORM\OneToMany(targetEntity: PostComment::class, mappedBy: 'user')]
    private Collection $postComments;


    public function getRoles(): array
    {
        // Ensure roles are correctly retrieved
        return array_unique($this->roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function eraseCredentials()
    {
        // TODO: Implement eraseCredentials() method.
    }

    public function getUserIdentifier(): string
    {
        return $this->email;    }


//    public function isVerified(): bool
//    {
//        return $this->isVerified;
//    }
//
//    public function setIsVerified(bool $isVerified): static
//    {
//        $this->isVerified = $isVerified;
//
//        return $this;
//    }
    public function isBlocked(): bool
    {
        return $this->isBlocked;
    }

    public function setIsBlocked(bool $isBlocked): static
    {
        $this->isBlocked = $isBlocked;

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


    /**
     * @return Collection<int, PostComment>
     */
    public function getPostComments(): Collection
    {
        return $this->postComments;
    }

    public function addPostComment(PostComment $postComment): static
    {
        if (!$this->postComments->contains($postComment)) {
            $this->postComments->add($postComment);
            $postComment->setUser($this);
        }

        return $this;
    }

    public function removePostComment(PostComment $postComment): static
    {
        if ($this->postComments->removeElement($postComment)) {
            // set the owning side to null (unless already changed)
            if ($postComment->getUser() === $this) {
                $postComment->setUser(null);
            }
        }

        return $this;
    }
    

    /**
     * @return Collection<int, EventRegistration>
     */
    public function getEventRegistrations(): Collection
    {
        return $this->eventRegistrations;
    }

    public function addEventRegistration(EventRegistration $eventRegistration): static
    {
        if (!$this->eventRegistrations->contains($eventRegistration)) {
            $this->eventRegistrations->add($eventRegistration);
            $eventRegistration->setUser($this);
        }

        return $this;
    }

    public function removeEventRegistration(EventRegistration $eventRegistration): static
    {
        if ($this->eventRegistrations->removeElement($eventRegistration)) {
            // set the owning side to null (unless already changed)
            if ($eventRegistration->getUser() === $this) {
                $eventRegistration->setUser(null);
            }
        }
        return $this;
    }



    public function getCart(): ?Cart
{
    return $this->cart;
}

public function setCart(?Cart $cart): static
{
    if ($cart === null && $this->cart !== null) {
        $this->cart->setUser(null);
    }

    if ($cart !== null && $cart->getUser() !== $this) {
        $cart->setUser($this);
    }

    $this->cart = $cart;

    return $this; 
}

}


