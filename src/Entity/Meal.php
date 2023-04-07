<?php

namespace App\Entity;

use App\Repository\MealRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: MealRepository::class)]
class Meal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Gedmo\Translatable]
    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Gedmo\Translatable]
    private ?string $description = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[Gedmo\Timestampable]
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    #[ORM\ManyToMany(targetEntity: Ingredient::class)]
    private Collection $ingredients;

    #[ORM\ManyToMany(targetEntity: Tag::class)]
    private Collection $tags;

    #[ORM\ManyToOne(inversedBy: 'meals')]
    private ?Category $category = null;

    #[Gedmo\Locale]
    private $locale;

    public function __construct()
    {
        $this->ingredients = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

    public function toArray($dateTime, $with): array
    {
        $data = [
            'id' => $this->getId(),
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
        ];
        $status = 'CREATED';
        if ($dateTime !== null) {
            if ($this->getCreatedAt()->format('Y-m-d H:i:s') <= $dateTime) {
                if ($this->getDeletedAt() && $this->getDeletedAt()->format('Y-m-d H:i:s') <= $dateTime) {
                    $status = 'DELETED';
                } else if ($this->getUpdatedAt() && $this->getUpdatedAt()->format('Y-m-d H:i:s') <= $dateTime) {
                    $status = 'UPDATED';
                } else {
                    $status = 'CREATED';
                }
            }
        }
        $data['status'] = $status;
        if (strpos($with, "ingredients") !== false) {
            $ingredients = [];
            foreach ($this->getIngredients() as $ingredient) {
                $ingredients[] = [
                    'id' => $ingredient->getId(),
                    'title' => $ingredient->getTitle(),
                    'slug' => $ingredient->getSlug(),
                ];
            }
            $data['ingredients'] = $ingredients;
        }
    
        if (strpos($with, "tags") !== false) {
            $tags = [];
            foreach ($this->getTags() as $tag) {
                $tags[] = [
                    'id' => $tag->getId(),
                    'title' => $tag->getTitle(),
                    'slug' => $tag->getSlug(),
                ];
            }
            $data['tags'] = $tags;
        }
    
        if (strpos($with, "category") !== false) {
            $category = $this->getCategory() ? [
                'id' => $this->getCategory()->getId(),
                'title' => $this->getCategory()->getTitle(),
                'slug' => $this->getCategory()->getSlug(),
            ] : null;
            $data['category'] = $category;
        }
    
        return $data;
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * @return Collection<int, Ingredient>
     */
    public function getIngredients(): Collection
    {
        return $this->ingredients;
    }

    public function addIngredient(Ingredient $ingredient): self
    {
        if (!$this->ingredients->contains($ingredient)) {
            $this->ingredients->add($ingredient);
        }

        return $this;
    }

    public function removeIngredient(Ingredient $ingredient): self
    {
        $this->ingredients->removeElement($ingredient);

        return $this;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }

        return $this;
    }

    public function removeTag(Tag $tag): self
    {
        $this->tags->removeElement($tag);

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function setTranslatableLocale($locale)
    {
        $this->locale = $locale;
    }
}
