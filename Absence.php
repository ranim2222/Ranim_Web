<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\AbsenceRepository;

#[ORM\Entity(repositoryClass: AbsenceRepository::class)]
#[ORM\Table(name: 'absence')]
class Absence
{
    // La propriété Date est nullable
    #[ORM\Column(type: 'date', nullable: true)]  // La date peut être null
    #[Assert\NotBlank(message: 'La date est obligatoire.')]  // Assure que la date n'est pas vide si présente
    #[Assert\LessThan('today', message: 'La date doit être dans le passé.')]
    private ?\DateTimeInterface $Date = null;

    public function getDate(): ?\DateTimeInterface
    {
        return $this->Date;
    }

    // Méthode qui accepte une DateTimeInterface ou null
    public function setDate(?\DateTimeInterface $Date): self
    {
        $this->Date = $Date;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    #[Assert\NotBlank(message: 'Le nombre d\'absences est obligatoire.')]
    #[Assert\Range(
        min: 1, 
        max: 999, 
        notInRangeMessage: 'Le nombre d\'absences doit être entre {{ min }} et {{ max }}.'
    )]
    private ?int $nbr_abs = null;

    public function getNbr_abs(): ?int
    {
        return $this->nbr_abs;
    }

    public function setNbr_abs(int $nbr_abs): self
    {
        $this->nbr_abs = $nbr_abs;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'Le type d\'absence est obligatoire.')]
    #[Assert\Choice(
        choices: ['justifiee', 'non_justifiee'],
        message: 'Le type d\'absence doit être "justifiee" ou "non_justifiee".'
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

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $ID_abs = null;

    public function getID_abs(): ?int
    {
        return $this->ID_abs;
    }

    public function setID_abs(int $ID_abs): self
    {
        $this->ID_abs = $ID_abs;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    #[Assert\NotBlank(message: 'Le CIN est obligatoire.')]
    #[Assert\Regex(
        pattern: '/^\d+$/', 
        message: 'Le CIN doit être composé uniquement de chiffres.'
    )]
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

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\Image(
        maxSize: '2M',
        mimeTypes: ['image/jpeg', 'image/png', 'image/gif'],
        mimeTypesMessage: 'Seules les images JPEG, PNG et GIF sont acceptées.',
        maxSizeMessage: 'Le fichier image ne doit pas dépasser 2 Mo.',
        groups: ['image_required']  // Validation de l'image uniquement dans ce groupe
    )]
    private ?string $image_path = null;

    public function getImage_path(): ?string
    {
        return $this->image_path;
    }

    public function setImage_path(?string $image_path): self
    {
        $this->image_path = $image_path;
        return $this;
    }

    public function getNbrAbs(): ?int
    {
        return $this->nbr_abs;
    }

    public function setNbrAbs(int $nbr_abs): self
    {
    $this->nbr_abs = $nbr_abs;
        return $this;
    }

    public function getIDAbs(): ?int
    {
        return $this->ID_abs;
    }

    public function getImagePath(): ?string
    {
        return $this->image_path;
    }

    public function setImagePath(?string $image_path): self
    {
        $this->image_path = $image_path;
      return $this;
    }

    // Méthode pour vérifier si l'absence est justifiée
    public function isJustifiee(): bool
    {
        return $this->type === 'justifiee';
    }
}
