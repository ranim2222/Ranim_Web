<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\PenaliteRepository;

#[ORM\Entity(repositoryClass: PenaliteRepository::class)]
#[ORM\Table(name: 'penalite')]
class Penalite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $ID_pen = null;

    public function getID_pen(): ?int
    {
        return $this->ID_pen;
    }

    public function setID_pen(int $ID_pen): self
    {
        $this->ID_pen = $ID_pen;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: "Le type de pénalité est obligatoire.")]
    #[Assert\Choice(
        choices: ["Avertissement écrit", "Suspension temporaire", "Démotion"],
        message: "Veuillez choisir un type de pénalité valide."
    )]
    #[Assert\Length(
        min: 5,
        max: 50,
        minMessage: "Le type de pénalité doit comporter au moins {{ limit }} caractères.",
        maxMessage: "Le type de pénalité ne doit pas dépasser {{ limit }} caractères."
    )]
    private ?string $type = null;

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    #[Assert\NotBlank(message: "Le seuil d'absence est obligatoire.")]
    #[Assert\GreaterThanOrEqual(
        value: 0,
        message: "Le seuil d'absence doit être supérieur ou égal à zéro."
    )]
    #[Assert\LessThanOrEqual(
        value: 100,
        message: "Le seuil d'absence ne doit pas dépasser {{ limit }}."
    )]
    private ?int $seuil_abs = null;

    public function getSeuil_abs(): ?int
    {
        return $this->seuil_abs;
    }

    public function setSeuil_abs(int $seuil_abs): self
    {
        $this->seuil_abs = $seuil_abs;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    #[Assert\NotBlank(message: "Le CIN est obligatoire.")]
    #[Assert\Positive(message: "Le CIN doit être un nombre positif.")]
    private ?int $cin = null;

    public function getCin(): ?int
    {
        return $this->cin;
    }

    public function setCin(int $cin): self
    {
        $this->cin = $cin;
        return $this;
    }

    public function getIDPen(): ?int
    {
        return $this->ID_pen;
    }

    public function getSeuilAbs(): ?int
    {
        return $this->seuil_abs;
    }

    public function setSeuilAbs(int $seuil_abs): static
    {
        $this->seuil_abs = $seuil_abs;

        return $this;
    }}
