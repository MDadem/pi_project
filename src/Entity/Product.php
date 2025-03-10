<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;
use App\Entity\ProductCategory;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Product name cannot be blank.")]
    private ?string $productName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Product description cannot be blank.")]
    #[Assert\Length(min: 20, minMessage: "Product description must be at least 20 characters long.")]
    private ?string $productDescription = null;

    #[ORM\Column(nullable: true)]
    #[Assert\GreaterThan(0, message: "Product price must be positive.")]
    private ?float $productPrice = null;

    #[ORM\Column(length: 255)]
    private ?string $image_url = null;

    #[ORM\Column(type: 'boolean', nullable: true, options: ['default' => 1])]
    private ?bool $status = true;

    #[ORM\Column]
    #[Assert\NotBlank(message: "Product stock cannot be blank.")]
    #[Assert\GreaterThanOrEqual(0, message: "Product stock cannot be negative.")]
    #[Assert\LessThanOrEqual(50, message: "Product stock cannot exceed 50.")]
    private ?int $productStock = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $owner = null;

    #[ORM\ManyToOne(targetEntity: ProductCategory::class, inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ProductCategory $productCategory = null;

    #[Assert\LessThanOrEqual(100, message: "Product discount cannot exceed 100%.")]
    #[ORM\Column(type: "float", nullable: true)]
    private ?float $discount = null;

    #[ORM\Column(type: "boolean", options: ["default" => false])]
    private bool $useDynamicPricing = false;

    private ?float $dynamicPrice = null;

    #[ORM\Column(type: 'integer')]
    private int $voteScore = 0;

    // Getter and Setter methods...

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProductName(): ?string
    {
        return $this->productName;
    }

    public function setProductName(string $productName): static
    {
        $this->productName = $productName;
        return $this;
    }

    public function getProductDescription(): ?string
    {
        return $this->productDescription;
    }

    public function setProductDescription(string $productDescription): static
    {
        $this->productDescription = $productDescription;
        return $this;
    }

    public function getProductPrice(): ?float
    {
        return $this->productPrice;
    }

    public function setProductPrice(?float $productPrice): static
    {
        $this->productPrice = $productPrice;
        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->image_url;
    }

    public function setImageUrl(string $image_url): static
    {
        $this->image_url = $image_url;
        return $this;
    }

    public function isStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getProductStock(): ?int
    {
        return $this->productStock;
    }

    public function setProductStock(int $productStock): static
    {
        $this->productStock = $productStock;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;
        return $this;
    }

    public function getProductCategory(): ?ProductCategory
    {
        return $this->productCategory;
    }

    public function setProductCategory(?ProductCategory $productCategory): self
    {
        $this->productCategory = $productCategory;
        return $this;
    }

    public function getDiscount(): ?float
    {
        return $this->discount;
    }

    public function setDiscount(?float $discount): self
    {
        $this->discount = $discount;
        return $this;
    }

    public function getUseDynamicPricing(): bool
    {
        return $this->useDynamicPricing;
    }

    public function setUseDynamicPricing(bool $useDynamicPricing): self
    {
        $this->useDynamicPricing = $useDynamicPricing;
        return $this;
    }

    public function getDynamicPrice(): ?float
    {
        return $this->dynamicPrice ?? $this->productPrice ?? 0.0; // Default to 0.0 if both are null
    }

    public function setDynamicPrice(?float $dynamicPrice): self
    {
        $this->dynamicPrice = $dynamicPrice;
        return $this;
    }

    public function getVoteScore(): int
    {
        return $this->voteScore;
    }

    public function setVoteScore(int $voteScore): self
    {
        $this->voteScore = $voteScore;
        return $this;
    }
}