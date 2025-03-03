<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Category;
use App\Entity\EventRegistration;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private ?string $eventDescription = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull]
    #[Assert\GreaterThan('today')]
    private ?\DateTimeInterface $eventDate = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $eventLocation = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'active';

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Category $category = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotNull]
    #[Assert\Positive]
    private ?int $numberOfPlaces = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageFilename = null;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: EventRegistration::class)]
    private Collection $eventRegistrations;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';

    public function __construct()
    {
        $this->eventRegistrations = new ArrayCollection();
    }

    public static function getStatusChoices(): array
    {
        return [
            'Active' => self::STATUS_ACTIVE,
            'Cancelled' => self::STATUS_CANCELLED,
            'Completed' => self::STATUS_COMPLETED,
        ];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getEventDescription(): ?string
    {
        return $this->eventDescription;
    }

    public function setEventDescription(string $eventDescription): static
    {
        $this->eventDescription = $eventDescription;
        return $this;
    }

    public function getEventDate(): ?\DateTimeInterface
    {
        return $this->eventDate;
    }

    public function setEventDate(\DateTimeInterface $eventDate): static
    {
        $this->eventDate = $eventDate;
        return $this;
    }

    public function getEventLocation(): ?string
    {
        return $this->eventLocation;
    }

    public function setEventLocation(string $eventLocation): static
    {
        $this->eventLocation = $eventLocation;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        if (!in_array($status, [self::STATUS_ACTIVE, self::STATUS_CANCELLED, self::STATUS_COMPLETED])) {
            throw new \InvalidArgumentException('Invalid status');
        }
        $this->status = $status;
        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getNumberOfPlaces(): ?int
    {
        return $this->numberOfPlaces;
    }

    public function setNumberOfPlaces(int $numberOfPlaces): static
    {
        $this->numberOfPlaces = $numberOfPlaces;
        return $this;
    }

    public function getImageFilename(): ?string
    {
        return $this->imageFilename;
    }

    public function setImageFilename(?string $imageFilename): static
    {
        $this->imageFilename = $imageFilename;
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
            $eventRegistration->setEvent($this);
            $this->decrementPlaces(); // Decrease available places
        }

        return $this;
    }

    public function removeEventRegistration(EventRegistration $eventRegistration): static
    {
        if ($this->eventRegistrations->removeElement($eventRegistration)) {
            if ($eventRegistration->getEvent() === $this) {
                $eventRegistration->setEvent(null);
            }
            $this->numberOfPlaces++; // Increase available places
        }

        return $this;
    }

    public function hasAvailablePlaces(): bool
    {
        return $this->numberOfPlaces > 0;
    }

    public function decrementPlaces(): void
    {
        if ($this->hasAvailablePlaces()) {
            $this->numberOfPlaces--;
        }
    }
}
