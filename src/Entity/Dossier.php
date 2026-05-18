<?php

/**
 * Entité Dossier
 * 
 * Représente un dossier de candidature BTS pour un utilisateur.
 * Stocke toutes les informations collectées à travers les 5 étapes d'inscription.
 * 
 * Structure de données:
 * - Métadonnées: id, user, statut, étapeActuelle, dates
 * - Étape 1: Informations personnelles (nom, prenom, adresse...)
 * - Étape 2: Parcours scolaire (diplôme, mention, année...)
 * - Étape 3: Choix du BTS et lettre de motivation
 * - Relation: PiecesJustificatives (fichiers uploadés)
 * 
 * @author Lycée Fulbert
 * @package App\Entity
 * 
 * Statuts possibles:
 * - brouillon: En cours de remplissage
 * - soumis: Dossier remis au lycée
 * - valide: Dossier accepté par le jury
 * - refuse: Dossier rejeté par le jury
 */

namespace App\Entity;

use App\Repository\DossierRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\PieceJustificative;

#[ORM\Entity(repositoryClass: DossierRepository::class)]
#[ORM\HasLifecycleCallbacks] // ✅ Active les callbacks du cycle de vie (PreUpdate, etc)
class Dossier
{
    // ═══════════════════════════════════════════════════════════
    // CONSTANTES - Statuts et listes
    // ═══════════════════════════════════════════════════════════

    /** @const STATUT_BROUILLON Dossier en cours de remplissage */
    const STATUT_BROUILLON = 'brouillon';
    
    /** @const STATUT_SOUMIS Dossier soumis au lycée (non modifiable) */
    const STATUT_SOUMIS    = 'soumis';
    
    /** @const STATUT_VALIDE Dossier validé par le jury */
    const STATUT_VALIDE    = 'valide';
    
    /** @const STATUT_REFUSE Dossier rejeté par le jury */
    const STATUT_REFUSE    = 'refuse';

    /** @const BTS_LIST Liste des BTS disponibles */
    const BTS_LIST = [
        'BTS SIO SLAM' => 'bts_sio_slam',
        'BTS SIO SISR' => 'bts_sio_sisr',
        'BTS GPME'     => 'bts_gpme',
        'BTS MCO'      => 'bts_mco',
        'BTS COMPTA'   => 'bts_compta',
        'BTS NDRC'      => 'bts_ndrc',
    ];

    // ═══════════════════════════════════════════════════════════
    // MÉTADONNÉES
    // ═══════════════════════════════════════════════════════════

    /** @var int|null Identifiant unique du dossier */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** @var User|null Utilisateur propriétaire du dossier */
    #[ORM\ManyToOne(inversedBy: 'dossiers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /** @var int Étape actuelle du processus (1-5) */
    #[ORM\Column(type: 'smallint')]
    private int $etapeActuelle = 1;

    /** @var string Statut du dossier (brouillon|soumis|valide|refuse) */
    #[ORM\Column(length: 20)]
    private string $statut = self::STATUT_BROUILLON;

    // ═══════════════════════════════════════════════════════════
    // ÉTAPE 1 - Informations personnelles
    // ═══════════════════════════════════════════════════════════

    /** @var string|null Nom de famille du candidat */
    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\NotBlank(message: 'Nom obligatoire', groups: ['step1'])]
    private ?string $nom = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\NotBlank(message: 'Prénom obligatoire', groups: ['step1'])]
    private ?string $prenom = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\NotBlank(message: 'Date obligatoire', groups: ['step1'])]
    #[Assert\LessThan(value: 'today', message: 'Date invalide', groups: ['step1'])]
    private ?\DateTimeInterface $dateNaissance = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\NotBlank(groups: ['step1'])]
    private ?string $genre = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\NotBlank(groups: ['step1'])]
    private ?string $nationalite = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\NotBlank(groups: ['step1'])]
    #[Assert\Regex(
        pattern: '/^[0-9]{2}\s[0-9]{2}\s[0-9]{2}\s[0-9]{2}\s[0-9]{2}$/',
        message: 'Téléphone invalide (format: XX XX XX XX XX)',
        groups: ['step1']
    )]
    private ?string $telephone = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(groups: ['step1'])]
    private ?string $adresse = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Assert\NotBlank(groups: ['step1'])]
    #[Assert\Regex(
        pattern: '/^[0-9]{5}$/',
        message: 'Code postal invalide',
        groups: ['step1']
    )]
    private ?string $codePostal = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\NotBlank(groups: ['step1'])]
    private ?string $ville = null;

    // ───────────── ÉTAPE 2 ─────────────

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\NotBlank(groups: ['step2'])]
    private ?string $dernierDiplome = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\NotBlank(groups: ['step2'])]
    private ?string $mentionBac = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    #[Assert\NotBlank(groups: ['step2'])]
    #[Assert\Range(min: 1950, max: 2030, groups: ['step2'])]
    private ?int $anneeObtention = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(groups: ['step2'])]
    private ?string $etablissementOrigine = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $experiencePro = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\NotBlank(groups: ['step2'])]
    private ?string $specialiteBac = null;

    // ───────────── ÉTAPE 3 ─────────────

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\NotBlank(groups: ['step3'])]
    #[Assert\Choice(
        choices: ['bts_sio_slam','bts_sio_sisr','bts_gpme','bts_mco','bts_compta', 'bts_ndrc'],
        groups: ['step3']
    )]
    private ?string $btsChoisi = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $btsChoisi2 = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank(groups: ['step3'])]
    #[Assert\Length(min: 200, groups: ['step3'])]
    private ?string $lettreMotivation = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $regimeAppr = null;

    // ───────────── RELATIONS ─────────────

    #[ORM\OneToMany(mappedBy: 'dossier', targetEntity: PieceJustificative::class, cascade: ['persist', 'remove'])]
    private Collection $piecesJustificatives;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $soumisAt = null;

    public function __construct()
    {
        $this->piecesJustificatives = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function updateDate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // ───────────── LOGIQUE ─────────────

    public function peutEtreModifie(): bool
    {
        return $this->statut === self::STATUT_BROUILLON;
    }

    public function peutEtreSoumis(): bool
    {
        return $this->etapeActuelle >= 5 && $this->statut === self::STATUT_BROUILLON;
    }

    // ───────────── GETTERS / SETTERS ─────────────

    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getEtapeActuelle(): int { return $this->etapeActuelle; }
    public function setEtapeActuelle(int $v): static { $this->etapeActuelle = $v; return $this; }

    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $v): static { $this->statut = $v; return $this; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(?string $v): static { $this->nom = $v; return $this; }

    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(?string $v): static { $this->prenom = $v; return $this; }

    public function getDateNaissance(): ?\DateTimeInterface { return $this->dateNaissance; }
    public function setDateNaissance(?\DateTimeInterface $v): static { $this->dateNaissance = $v; return $this; }

    public function getGenre(): ?string { return $this->genre; }
    public function setGenre(?string $v): static { $this->genre = $v; return $this; }

    public function getNationalite(): ?string { return $this->nationalite; }
    public function setNationalite(?string $v): static { $this->nationalite = $v; return $this; }

    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $v): static { $this->telephone = $v; return $this; }

    public function getAdresse(): ?string { return $this->adresse; }
    public function setAdresse(?string $v): static { $this->adresse = $v; return $this; }

    public function getCodePostal(): ?string { return $this->codePostal; }
    public function setCodePostal(?string $v): static { $this->codePostal = $v; return $this; }

    public function getVille(): ?string { return $this->ville; }
    public function setVille(?string $v): static { $this->ville = $v; return $this; }

    // STEP 2

    public function getSpecialiteBac(): ?string
    {
        return $this->specialiteBac;
    }

    public function setSpecialiteBac(?string $value): static
    {
        $this->specialiteBac = $value;
        return $this;
    }

    public function getDernierDiplome(): ?string
    {
        return $this->dernierDiplome;
    }

    public function setDernierDiplome(?string $value): static
    {
        $this->dernierDiplome = $value;
        return $this;
    }

    public function getMentionBac(): ?string
    {
        return $this->mentionBac;
    }

    public function setMentionBac(?string $value): static
    {
        $this->mentionBac = $value;
        return $this;
    }

    public function getAnneeObtention(): ?int
    {
        return $this->anneeObtention;
    }

    public function setAnneeObtention(?int $value): static
    {
        $this->anneeObtention = $value;
        return $this;
    }

    public function getEtablissementOrigine(): ?string
    {
        return $this->etablissementOrigine;
    }

    public function setEtablissementOrigine(?string $value): static
    {
        $this->etablissementOrigine = $value;
        return $this;
    }

    public function getExperiencePro(): ?string
    {
        return $this->experiencePro;
    }

    public function setExperiencePro(?string $value): static
    {
        $this->experiencePro = $value;
        return $this;
    }

    // ───────── STEP 3 ─────────

    public function getBtsChoisi(): ?string
    {
        return $this->btsChoisi;
    }

    public function setBtsChoisi(?string $value): static
    {
        $this->btsChoisi = $value;
        return $this;
    }

    public function getBtsChoisi2(): ?string
    {
        return $this->btsChoisi2;
    }

    public function setBtsChoisi2(?string $value): static
    {
        $this->btsChoisi2 = $value;
        return $this;
    }

    public function getLettreMotivation(): ?string
    {
        return $this->lettreMotivation;
    }

    public function setLettreMotivation(?string $value): static
    {
        $this->lettreMotivation = $value;
        return $this;
    }
    
    public function isRegimeAppr(): ?bool
    {
        return $this->regimeAppr;
    }

    public function setRegimeAppr(?bool $regimeAppr): self
    {
        $this->regimeAppr = $regimeAppr;
        return $this;
    }

    //Step4
    public function getPiecesJustificatives(): Collection
    {
        return $this->piecesJustificatives;
    }

    // step5
    public function getStatutLabel(): string
{
        return match ($this->statut) {
            self::STATUT_BROUILLON => 'Brouillon',
            self::STATUT_SOUMIS => 'Soumis',
            self::STATUT_VALIDE => 'Validé',
            self::STATUT_REFUSE => 'Refusé',
            default => 'Inconnu',
        };
    }

    public function getProgression(): int
    {
        return match ($this->etapeActuelle) {
            1 => 20,
            2 => 40,
            3 => 60,
            4 => 80,
            5 => 100,
            default => 0,
        };
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getSoumisAt(): ?\DateTimeImmutable
    {
        return $this->soumisAt;
    }

    public function setSoumisAt(?\DateTimeImmutable $soumisAt): static
    {
        $this->soumisAt = $soumisAt;
        return $this;
    }
}