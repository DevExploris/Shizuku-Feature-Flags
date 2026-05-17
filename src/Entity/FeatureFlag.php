<?php

namespace Devexploris\ShizukuFeatureFlags\Entity;

use Devexploris\ShizukuFeatureFlags\Repository\FeatureFlagRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FeatureFlagRepository::class)]
#[ORM\HasLifecycleCallbacks]
class FeatureFlag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $name;

    #[ORM\Column(length: 255)]
    private ?string $description;

    #[ORM\Column]
    private bool $isEnabled;

    #[ORM\Column]
    private bool $isLocked = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $enabledAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lockedAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $name, string $description, ?bool $isEnabled = false)
    {
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $name)) {
            throw new \InvalidArgumentException(
                sprintf('Flag name "%s" must be snake_case (lowercase letters, digits, underscores).', $name)
            );
        }
        $this->createdAt = new \DateTimeImmutable();
        $this->name = $name;
        $this->description = $description;
        $this->isEnabled = $isEnabled;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $name)) {
            throw new \InvalidArgumentException(
                sprintf('Flag name "%s" must be snake_case (lowercase letters, digits, underscores).', $name)
            );
        }

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

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function setIsEnabled(bool $isEnabled): static
    {
        $this->isEnabled = $isEnabled;

        return $this;
    }

    public function isLocked(): bool
    {
        return $this->isLocked;
    }

    public function setIsLocked(bool $isLocked): static
    {
        $this->isLocked = $isLocked;

        return $this;
    }

    public function getEnabledAt(): ?\DateTimeImmutable
    {
        return $this->enabledAt;
    }

    public function setEnabledAt(?\DateTimeImmutable $enabledAt): static
    {
        $this->enabledAt = $enabledAt;

        return $this;
    }

    public function getLockedAt(): ?\DateTimeImmutable
    {
        return $this->lockedAt;
    }

    public function setLockedAt(?\DateTimeImmutable $lockedAt): static
    {
        $this->lockedAt = $lockedAt;

        return $this;
    }

    #[ORM\PrePersist]
    public function PrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();

        if($this->isEnabled)
            $this->enabledAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function PreUpdate(): void
    {
        if($this->isEnabled && !$this->enabledAt)
            $this->enabledAt = new \DateTimeImmutable();

        if(!$this->isEnabled)
            $this->enabledAt = null;

        if($this->isLocked && !$this->lockedAt)
            $this->lockedAt = new \DateTimeImmutable();
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
