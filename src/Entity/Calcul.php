<?php

namespace App\Entity;

use App\Repository\CalculRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CalculRepository::class)]
class Calcul
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $typeEvenement = null;

    #[ORM\Column]
    private ?int $nbPersonnes = null;

    #[ORM\Column]
    private ?int $nbEnfants = 0;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $resultat = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dateEvenement = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateCalcul = null;

    #[ORM\Column(type: 'boolean' , options: ['default' => false])]
    private ?bool $sansAlcool = false;

    #[ORM\Column(type: Types::JSON, nullable: true)]
private ?array $viandesBarbecue = null;

public function getViandesBarbecue(): ?array
{
    return $this->viandesBarbecue;
}

public function setViandesBarbecue(?array $viandesBarbecue): static
{
    $this->viandesBarbecue = $viandesBarbecue;
    return $this;
}

    public function isSansAlcool(): ?bool
    {
        return $this->sansAlcool;
    }

    public function setSansAlcool(bool $sansAlcool): static
    {
        $this->sansAlcool = $sansAlcool;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTypeEvenement(): ?string
    {
        return $this->typeEvenement;
    }

    public function setTypeEvenement(string $typeEvenement): static
    {
        $this->typeEvenement = $typeEvenement;

        return $this;
    }

    public function getNbPersonnes(): ?int
    {
        return $this->nbPersonnes;
    }

    public function setNbPersonnes(int $nbPersonnes): static
    {
        $this->nbPersonnes = $nbPersonnes;

        return $this;
    }

    public function getNbEnfants(): ?int
    {
        return $this->nbEnfants;
    }

    public function setNbEnfants(int $nbEnfants): static
    {
        $this->nbEnfants = $nbEnfants;
        return $this;
    }

    // ✅ MÉTHODE UTILE : Calculer le total avec pondération enfants
    public function getTotalInvites(): float
    {
        // Les enfants comptent pour 0.5 dans les calculs de quantités
        return $this->nbPersonnes + ($this->nbEnfants * 0.5);
    }

    // ✅ MÉTHODE UTILE : Nombre total de personnes (adultes + enfants)
    public function getTotalPersonnes(): int
    {
        return $this->nbPersonnes + $this->nbEnfants;
    }

    public function getResultat(): ?string
    {
        return $this->resultat;
    }

    public function setResultat(?string $resultat): static
    {
        $this->resultat = $resultat;

        return $this;
    }

    public function getDateEvenement(): ?\DateTimeImmutable
    {
        return $this->dateEvenement;
    }

    public function setDateEvenement(?\DateTimeImmutable $dateEvenement): static
    {
        $this->dateEvenement = $dateEvenement;

        return $this;
    }

    public function getDateCalcul(): ?\DateTimeImmutable
    {
        return $this->dateCalcul;
    }

    public function setDateCalcul(\DateTimeImmutable $dateCalcul): static
    {
        $this->dateCalcul = $dateCalcul;

        return $this;
    }
}
