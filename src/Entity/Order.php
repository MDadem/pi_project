<?php
namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $total_price = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $creation_date = null;

    #[ORM\Column(length: 50)]
    private ?string $status = 'En cours';

    #[ORM\ManyToOne(inversedBy: 'orders')]
    private ?User $user = null;

    #[ORM\OneToMany(targetEntity: Cart::class, mappedBy: 'order', cascade: ['persist', 'remove'])]
    private Collection $cartItems;

    public function __construct()
    {
        $this->cartItems = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTotalPrice(): ?float
    {
        return $this->total_price;
    }

    public function setTotalPrice(float $total_price): self
    {
        $this->total_price = $total_price;
        return $this;
    }

    public function getCreationDate(): ?\DateTimeInterface
    {
        return $this->creation_date;
    }

    public function setCreationDate(\DateTimeInterface $creation_date): self
    {
        $this->creation_date = $creation_date;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return Collection<int, Cart>
     */
    public function getCartItems(): Collection
    {
        return $this->cartItems;
    }

    public function addCartItem(Cart $cartItem): self
    {
        if (!$this->cartItems->contains($cartItem)) {
            $this->cartItems->add($cartItem);
            $cartItem->setOrder($this);
        }
        return $this;
    }

    public function removeCartItem(Cart $cartItem): self
    {
        if ($this->cartItems->removeElement($cartItem)) {
            if ($cartItem->getOrder() === $this) {
                $cartItem->setOrder(null);
            }
        }
        return $this;
    }
}
