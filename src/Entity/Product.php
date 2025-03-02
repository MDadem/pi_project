<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\DBAL\Types\Types;
use App\Enum\Category;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    public function __construct()
    {
        $this->categories = []; // Initialize as an empty array
    }
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $productName = null;

    #[ORM\Column(length: 255)]
    private ?string $productDescription = null;

    #[ORM\Column]
    private ?float $productPrice = null;

    #[ORM\Column(length: 255)]
    private ?string $image_url = null;

    #[ORM\Column(type: 'boolean', nullable: true, options: ['default' => 1])]
    private ?bool $status = true;

    #[ORM\Column]
    private ?int $productStock = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: "json")]  // Store as JSON in the database
    private array $categories = [];


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

    public function setProductPrice(float $productPrice): static
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

    // public function getCategories(): array
    // {
    //     return  array_unique($this->categories);
    // }

    // public function setCategories(array $categories): self
    // {
    // $this->categories = $categories;
    // return $this;
    // }

    public function getCategories(): array
    {
        return $this->categories ?? [];
    }

    public function setCategories(array $categories): self
    {
        // Ensure all values are strings (Enum values)
        $this->categories = array_map(fn(Category $category) => $category->value, $categories);
        return $this;
    }
}
