<?php
/**
 * Classe Tuteur
 * Représente un tuteur (enseignant ou entreprise) pour le suivi des stages
 * 
 * @package IntranetStages
 * @author Projet BTS SIO
 */
class Tuteur
{
    // Constantes pour les types de tuteur
    public const TYPE_ENSEIGNANT = 'enseignant';
    public const TYPE_ENTREPRISE = 'entreprise';

    private ?int $id;
    private string $nom;
    private string $prenom;
    private string $email;
    private ?string $telephone;
    private string $type; // 'enseignant' ou 'entreprise'
    private ?int $entrepriseId; // Seulement pour les tuteurs entreprise
    private ?string $fonction; // Poste/fonction dans l'entreprise
    private ?string $dateCreation;

    /**
     * Constructeur de la classe Tuteur
     */
    public function __construct(
        ?int $id = null,
        string $nom = '',
        string $prenom = '',
        string $email = '',
        ?string $telephone = null,
        string $type = self::TYPE_ENSEIGNANT,
        ?int $entrepriseId = null,
        ?string $fonction = null,
        ?string $dateCreation = null
    ) {
        $this->id = $id;
        $this->nom = $nom;
        $this->prenom = $prenom;
        $this->email = $email;
        $this->telephone = $telephone;
        $this->type = $type;
        $this->entrepriseId = $entrepriseId;
        $this->fonction = $fonction;
        $this->dateCreation = $dateCreation;
    }

    // ==================== GETTERS ====================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function getPrenom(): string
    {
        return $this->prenom;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getEntrepriseId(): ?int
    {
        return $this->entrepriseId;
    }

    public function getFonction(): ?string
    {
        return $this->fonction;
    }

    public function getDateCreation(): ?string
    {
        return $this->dateCreation;
    }

    /**
     * Retourne le nom complet (prénom + nom)
     */
    public function getNomComplet(): string
    {
        return trim($this->prenom . ' ' . $this->nom);
    }

    /**
     * Vérifie si c'est un tuteur enseignant
     */
    public function isEnseignant(): bool
    {
        return $this->type === self::TYPE_ENSEIGNANT;
    }

    /**
     * Vérifie si c'est un tuteur entreprise
     */
    public function isEntreprise(): bool
    {
        return $this->type === self::TYPE_ENTREPRISE;
    }

    /**
     * Retourne le libellé du type
     */
    public function getTypeLibelle(): string
    {
        return $this->type === self::TYPE_ENSEIGNANT ? 'Enseignant' : 'Entreprise';
    }

    // ==================== SETTERS ====================

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function setType(string $type): self
    {
        if (in_array($type, [self::TYPE_ENSEIGNANT, self::TYPE_ENTREPRISE])) {
            $this->type = $type;
        }
        return $this;
    }

    public function setEntrepriseId(?int $entrepriseId): self
    {
        $this->entrepriseId = $entrepriseId;
        return $this;
    }

    public function setFonction(?string $fonction): self
    {
        $this->fonction = $fonction;
        return $this;
    }

    public function setDateCreation(?string $dateCreation): self
    {
        $this->dateCreation = $dateCreation;
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
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'type' => $this->type,
            'entreprise_id' => $this->entrepriseId,
            'fonction' => $this->fonction,
            'date_creation' => $this->dateCreation
        ];
    }

    /**
     * Crée un objet Tuteur à partir d'un tableau (hydratation)
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? null,
            $data['nom'] ?? '',
            $data['prenom'] ?? '',
            $data['email'] ?? '',
            $data['telephone'] ?? null,
            $data['type'] ?? $data['role'] ?? self::TYPE_ENSEIGNANT,
            $data['entreprise_id'] ?? null,
            $data['fonction'] ?? null,
            $data['date_creation'] ?? null
        );
    }
}

