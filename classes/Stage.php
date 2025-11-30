<?php
/**
 * Classe Stage
 * Représente un stage avec toutes ses informations
 * 
 * @package IntranetStages
 * @author Projet BTS SIO
 */
class Stage
{
    // Constantes pour les statuts
    public const STATUT_PREPARATION = 'préparation';
    public const STATUT_EN_COURS = 'en_cours';
    public const STATUT_TERMINE = 'terminé';
    public const STATUT_RUPTURE = 'rupture';

    public const STATUTS_VALIDES = [
        self::STATUT_PREPARATION,
        self::STATUT_EN_COURS,
        self::STATUT_TERMINE,
        self::STATUT_RUPTURE
    ];

    private ?int $id;
    private int $etudiantUserId;
    private int $entrepriseId;
    private ?int $tuteurEnseignantUserId;
    private ?int $tuteurEntrepriseUserId;
    private string $dateDebut;
    private string $dateFin;
    private ?string $sujet;
    private ?string $description;
    private string $statut;
    private ?string $dateCreation;

    // Objets liés (pour les jointures)
    private ?Eleve $etudiant = null;
    private ?Entreprise $entreprise = null;
    private ?Tuteur $tuteurEnseignant = null;
    private ?Tuteur $tuteurEntreprise = null;

    /**
     * Constructeur de la classe Stage
     */
    public function __construct(
        ?int $id = null,
        int $etudiantUserId = 0,
        int $entrepriseId = 0,
        ?int $tuteurEnseignantUserId = null,
        ?int $tuteurEntrepriseUserId = null,
        string $dateDebut = '',
        string $dateFin = '',
        ?string $sujet = null,
        ?string $description = null,
        string $statut = self::STATUT_PREPARATION,
        ?string $dateCreation = null
    ) {
        $this->id = $id;
        $this->etudiantUserId = $etudiantUserId;
        $this->entrepriseId = $entrepriseId;
        $this->tuteurEnseignantUserId = $tuteurEnseignantUserId;
        $this->tuteurEntrepriseUserId = $tuteurEntrepriseUserId;
        $this->dateDebut = $dateDebut;
        $this->dateFin = $dateFin;
        $this->sujet = $sujet;
        $this->description = $description;
        $this->statut = $statut;
        $this->dateCreation = $dateCreation;
    }

    // ==================== GETTERS ====================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEtudiantUserId(): int
    {
        return $this->etudiantUserId;
    }

    public function getEntrepriseId(): int
    {
        return $this->entrepriseId;
    }

    public function getTuteurEnseignantUserId(): ?int
    {
        return $this->tuteurEnseignantUserId;
    }

    public function getTuteurEntrepriseUserId(): ?int
    {
        return $this->tuteurEntrepriseUserId;
    }

    public function getDateDebut(): string
    {
        return $this->dateDebut;
    }

    public function getDateFin(): string
    {
        return $this->dateFin;
    }

    public function getSujet(): ?string
    {
        return $this->sujet;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function getDateCreation(): ?string
    {
        return $this->dateCreation;
    }

    public function getEtudiant(): ?Eleve
    {
        return $this->etudiant;
    }

    public function getEntreprise(): ?Entreprise
    {
        return $this->entreprise;
    }

    public function getTuteurEnseignant(): ?Tuteur
    {
        return $this->tuteurEnseignant;
    }

    public function getTuteurEntreprise(): ?Tuteur
    {
        return $this->tuteurEntreprise;
    }

    /**
     * Calcule la durée du stage en semaines
     */
    public function getDureeSemaines(): int
    {
        if (empty($this->dateDebut) || empty($this->dateFin)) {
            return 0;
        }
        $debut = new DateTime($this->dateDebut);
        $fin = new DateTime($this->dateFin);
        $diff = $debut->diff($fin);
        return (int) ceil($diff->days / 7);
    }

    /**
     * Calcule la durée du stage en jours
     */
    public function getDureeJours(): int
    {
        if (empty($this->dateDebut) || empty($this->dateFin)) {
            return 0;
        }
        $debut = new DateTime($this->dateDebut);
        $fin = new DateTime($this->dateFin);
        return $debut->diff($fin)->days;
    }

    /**
     * Retourne le libellé du statut
     */
    public function getStatutLibelle(): string
    {
        return ucfirst(str_replace('_', ' ', $this->statut));
    }

    /**
     * Vérifie si le stage est en cours
     */
    public function isEnCours(): bool
    {
        return $this->statut === self::STATUT_EN_COURS;
    }

    /**
     * Vérifie si le stage est terminé
     */
    public function isTermine(): bool
    {
        return $this->statut === self::STATUT_TERMINE;
    }

    // ==================== SETTERS ====================

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function setEtudiantUserId(int $etudiantUserId): self
    {
        $this->etudiantUserId = $etudiantUserId;
        return $this;
    }

    public function setEntrepriseId(int $entrepriseId): self
    {
        $this->entrepriseId = $entrepriseId;
        return $this;
    }

    public function setTuteurEnseignantUserId(?int $tuteurEnseignantUserId): self
    {
        $this->tuteurEnseignantUserId = $tuteurEnseignantUserId;
        return $this;
    }

    public function setTuteurEntrepriseUserId(?int $tuteurEntrepriseUserId): self
    {
        $this->tuteurEntrepriseUserId = $tuteurEntrepriseUserId;
        return $this;
    }

    public function setDateDebut(string $dateDebut): self
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function setDateFin(string $dateFin): self
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function setSujet(?string $sujet): self
    {
        $this->sujet = $sujet;
        return $this;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function setStatut(string $statut): self
    {
        if (in_array($statut, self::STATUTS_VALIDES)) {
            $this->statut = $statut;
        }
        return $this;
    }

    public function setDateCreation(?string $dateCreation): self
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    public function setEtudiant(?Eleve $etudiant): self
    {
        $this->etudiant = $etudiant;
        return $this;
    }

    public function setEntreprise(?Entreprise $entreprise): self
    {
        $this->entreprise = $entreprise;
        return $this;
    }

    public function setTuteurEnseignant(?Tuteur $tuteur): self
    {
        $this->tuteurEnseignant = $tuteur;
        return $this;
    }

    public function setTuteurEntreprise(?Tuteur $tuteur): self
    {
        $this->tuteurEntreprise = $tuteur;
        return $this;
    }

    // ==================== MÉTHODES UTILITAIRES ====================

    /**
     * Convertit l'objet en tableau associatif
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'etudiant_user_id' => $this->etudiantUserId,
            'entreprise_id' => $this->entrepriseId,
            'tuteur_enseignant_user_id' => $this->tuteurEnseignantUserId,
            'tuteur_entreprise_user_id' => $this->tuteurEntrepriseUserId,
            'date_debut' => $this->dateDebut,
            'date_fin' => $this->dateFin,
            'sujet' => $this->sujet,
            'description' => $this->description,
            'statut' => $this->statut,
            'date_creation' => $this->dateCreation
        ];
    }

    /**
     * Crée un objet Stage à partir d'un tableau (hydratation)
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? null,
            (int)($data['etudiant_user_id'] ?? 0),
            (int)($data['entreprise_id'] ?? 0),
            $data['tuteur_enseignant_user_id'] ?? null,
            $data['tuteur_entreprise_user_id'] ?? null,
            $data['date_debut'] ?? '',
            $data['date_fin'] ?? '',
            $data['sujet'] ?? null,
            $data['description'] ?? null,
            $data['statut'] ?? self::STATUT_PREPARATION,
            $data['date_creation'] ?? null
        );
    }

    /**
     * Valide les dates du stage
     */
    public function isValidDates(): bool
    {
        if (empty($this->dateDebut) || empty($this->dateFin)) {
            return false;
        }
        return $this->dateDebut <= $this->dateFin;
    }
}

