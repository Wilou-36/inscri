<?php
// src/Entity/PieceJustificative.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class PieceJustificative
{
    const TYPES = [
        'cni'          => 'Pièce d\'identité (recto/verso)',
        'photo'        => 'Photo d\'identité',
        'releve_notes' => 'Relevé de notes (terminale)',
        'diplome_bac'  => 'Diplôme du Bac ou équivalent',
        'cv'           => 'Curriculum Vitae',
        'certificat'   => 'Certificat de scolarité / attestation employeur',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Dossier::class, inversedBy: 'piecesJustificatives')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Dossier $dossier = null;

    #[ORM\Column(length: 50)]
    private string $type;

    #[ORM\Column(length: 255)]
    private string $nomFichier;

    #[ORM\Column(length: 255)]
    private string $cheminFichier;

    #[ORM\Column(length: 50)]
    private string $mimeType;

    #[ORM\Column(type: 'integer')]
    private int $taille; // en octets

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $uploadedAt;

    public function __construct()
    {
        $this->uploadedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getDossier(): ?Dossier { return $this->dossier; }
    public function setDossier(?Dossier $d): static { $this->dossier = $d; return $this; }
    public function getType(): string { return $this->type; }
    public function setType(string $v): static { $this->type = $v; return $this; }
    public function getNomFichier(): string { return $this->nomFichier; }
    public function setNomFichier(string $v): static { $this->nomFichier = $v; return $this; }
    public function getCheminFichier(): string { return $this->cheminFichier; }
    public function setCheminFichier(string $v): static { $this->cheminFichier = $v; return $this; }
    public function getMimeType(): string { return $this->mimeType; }
    public function setMimeType(string $v): static { $this->mimeType = $v; return $this; }
    public function getTaille(): int { return $this->taille; }
    public function setTaille(int $v): static { $this->taille = $v; return $this; }
    public function getUploadedAt(): \DateTimeImmutable { return $this->uploadedAt; }
    public function getTailleFormatee(): string
    {
        if ($this->taille < 1024) return $this->taille . ' o';
        if ($this->taille < 1048576) return round($this->taille / 1024, 1) . ' Ko';
        return round($this->taille / 1048576, 1) . ' Mo';
    }
    public function getTypeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
    public const REQUIRED_TYPES = ['cni', 'photo', 'releve_notes'];
}
