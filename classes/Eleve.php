<?php
/**
 * Classe Eleve (Étudiant)
 * Représente un étudiant dans le système de gestion des stages
 * 
 * @package IntranetStages
 * @author Projet BTS SIO
 */
class Eleve
{
    private ?int $id;
    private string $nom;
    private string $prenom;
    private string $email;
    private ?string $telephone;
    private ?string $formation;
    private ?string $dateCreation;

    /**
     * Constructeur de la classe Eleve
     */
    public function __construct(
        ?int $id = null,
        string $nom = '',
        string $prenom = '',
        string $email = '',
        ?string $telephone = null,
        ?string $formation = null,
        ?string $dateCreation = null
    ) {
        $this->id = $id;
        $this->nom = $nom;
        $this->prenom = $prenom;
        $this->email = $email;
        $this->telephone = $telephone;
        $this->formation = $formation;
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

    public function getFormation(): ?string
    {
        return $this->formation;
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

    public function setFormation(?string $formation): self
    {
        $this->formation = $formation;
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
            'formation' => $this->formation,
            'date_creation' => $this->dateCreation
        ];
    }

    /**
     * Crée un objet Eleve à partir d'un tableau (hydratation)
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? null,
            $data['nom'] ?? '',
            $data['prenom'] ?? '',
            $data['email'] ?? '',
            $data['telephone'] ?? null,
            $data['formation'] ?? null,
            $data['date_creation'] ?? null
        );
    }
}

