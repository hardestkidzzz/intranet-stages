<?php
/**
 * Classe Entreprise
 * Représente une entreprise partenaire pour les stages
 * 
 * @package IntranetStages
 * @author Projet BTS SIO
 */
class Entreprise
{
    private ?int $id;
    private string $nom;
    private ?string $siret;
    private ?string $adresse;
    private ?string $codePostal;
    private ?string $ville;
    private ?string $telephone;
    private ?string $email;
    private ?string $siteWeb;
    private ?string $secteurActivite;
    private ?string $dateCreation;

    /**
     * Constructeur de la classe Entreprise
     */
    public function __construct(
        ?int $id = null,
        string $nom = '',
        ?string $siret = null,
        ?string $adresse = null,
        ?string $codePostal = null,
        ?string $ville = null,
        ?string $telephone = null,
        ?string $email = null,
        ?string $siteWeb = null,
        ?string $secteurActivite = null,
        ?string $dateCreation = null
    ) {
        $this->id = $id;
        $this->nom = $nom;
        $this->siret = $siret;
        $this->adresse = $adresse;
        $this->codePostal = $codePostal;
        $this->ville = $ville;
        $this->telephone = $telephone;
        $this->email = $email;
        $this->siteWeb = $siteWeb;
        $this->secteurActivite = $secteurActivite;
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

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function getCodePostal(): ?string
    {
        return $this->codePostal;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getSiteWeb(): ?string
    {
        return $this->siteWeb;
    }

    public function getSecteurActivite(): ?string
    {
        return $this->secteurActivite;
    }

    public function getDateCreation(): ?string
    {
        return $this->dateCreation;
    }

    /**
     * Retourne l'adresse complète formatée
     */
    public function getAdresseComplete(): string
    {
        $parts = array_filter([
            $this->adresse,
            trim(($this->codePostal ?? '') . ' ' . ($this->ville ?? ''))
        ]);
        return implode(', ', $parts);
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

    public function setSiret(?string $siret): self
    {
        $this->siret = $siret;
        return $this;
    }

    public function setAdresse(?string $adresse): self
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function setCodePostal(?string $codePostal): self
    {
        $this->codePostal = $codePostal;
        return $this;
    }

    public function setVille(?string $ville): self
    {
        $this->ville = $ville;
        return $this;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function setSiteWeb(?string $siteWeb): self
    {
        $this->siteWeb = $siteWeb;
        return $this;
    }

    public function setSecteurActivite(?string $secteurActivite): self
    {
        $this->secteurActivite = $secteurActivite;
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
            'siret' => $this->siret,
            'adresse' => $this->adresse,
            'code_postal' => $this->codePostal,
            'ville' => $this->ville,
            'telephone' => $this->telephone,
            'email' => $this->email,
            'site_web' => $this->siteWeb,
            'secteur_activite' => $this->secteurActivite,
            'date_creation' => $this->dateCreation
        ];
    }

    /**
     * Crée un objet Entreprise à partir d'un tableau (hydratation)
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? null,
            $data['nom'] ?? '',
            $data['siret'] ?? null,
            $data['adresse'] ?? null,
            $data['code_postal'] ?? null,
            $data['ville'] ?? null,
            $data['telephone'] ?? null,
            $data['email'] ?? null,
            $data['site_web'] ?? null,
            $data['secteur_activite'] ?? null,
            $data['date_creation'] ?? null
        );
    }

    /**
     * Valide le format du SIRET (14 chiffres)
     */
    public function isValidSiret(): bool
    {
        if (empty($this->siret)) {
            return true; // SIRET optionnel
        }
        return preg_match('/^\d{14}$/', preg_replace('/\s/', '', $this->siret)) === 1;
    }
}

