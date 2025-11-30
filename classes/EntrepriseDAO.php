<?php
/**
 * Classe EntrepriseDAO (Data Access Object)
 * Gère l'accès aux données des entreprises via PDO
 * 
 * @package IntranetStages
 * @author Projet BTS SIO
 */

require_once __DIR__ . '/Entreprise.php';

class EntrepriseDAO
{
    private PDO $pdo;

    /**
     * Constructeur - injection de la connexion PDO
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Récupère une entreprise par son ID
     */
    public function findById(int $id): ?Entreprise
    {
        $sql = "SELECT id, nom, siret, adresse, code_postal, ville, telephone, email, site_web, secteur_activite, date_creation 
                FROM entreprises 
                WHERE id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? Entreprise::fromArray($data) : null;
    }

    /**
     * Récupère une entreprise par son SIRET
     */
    public function findBySiret(string $siret): ?Entreprise
    {
        $sql = "SELECT id, nom, siret, adresse, code_postal, ville, telephone, email, site_web, secteur_activite, date_creation 
                FROM entreprises 
                WHERE siret = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$siret]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? Entreprise::fromArray($data) : null;
    }

    /**
     * Récupère toutes les entreprises
     */
    public function findAll(): array
    {
        $sql = "SELECT id, nom, siret, adresse, code_postal, ville, telephone, email, site_web, secteur_activite, date_creation 
                FROM entreprises 
                ORDER BY nom";
        
        $stmt = $this->pdo->query($sql);
        $entreprises = [];
        
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $entreprises[] = Entreprise::fromArray($data);
        }
        
        return $entreprises;
    }

    /**
     * Recherche des entreprises avec des critères
     */
    public function search(array $criteria = []): array
    {
        $sql = "SELECT id, nom, siret, adresse, code_postal, ville, telephone, email, site_web, secteur_activite, date_creation 
                FROM entreprises WHERE 1=1";
        $params = [];

        if (!empty($criteria['nom'])) {
            $sql .= " AND nom LIKE ?";
            $params[] = '%' . $criteria['nom'] . '%';
        }

        if (!empty($criteria['siret'])) {
            $sql .= " AND siret LIKE ?";
            $params[] = '%' . $criteria['siret'] . '%';
        }

        if (!empty($criteria['ville'])) {
            $sql .= " AND ville LIKE ?";
            $params[] = '%' . $criteria['ville'] . '%';
        }

        if (!empty($criteria['secteur'])) {
            $sql .= " AND secteur_activite LIKE ?";
            $params[] = '%' . $criteria['secteur'] . '%';
        }

        if (!empty($criteria['q'])) {
            $sql .= " AND (nom LIKE ? OR siret LIKE ? OR ville LIKE ? OR site_web LIKE ?)";
            $params[] = '%' . $criteria['q'] . '%';
            $params[] = '%' . $criteria['q'] . '%';
            $params[] = '%' . $criteria['q'] . '%';
            $params[] = '%' . $criteria['q'] . '%';
        }

        $sql .= " ORDER BY nom";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $entreprises = [];

        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $entreprises[] = Entreprise::fromArray($data);
        }

        return $entreprises;
    }

    /**
     * Insère une nouvelle entreprise
     */
    public function insert(Entreprise $entreprise): int
    {
        $sql = "INSERT INTO entreprises (nom, siret, adresse, code_postal, ville, telephone, email, site_web, secteur_activite, date_creation) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $entreprise->getNom(),
            $entreprise->getSiret(),
            $entreprise->getAdresse(),
            $entreprise->getCodePostal(),
            $entreprise->getVille(),
            $entreprise->getTelephone(),
            $entreprise->getEmail(),
            $entreprise->getSiteWeb(),
            $entreprise->getSecteurActivite()
        ]);
        
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Met à jour une entreprise
     */
    public function update(Entreprise $entreprise): bool
    {
        $sql = "UPDATE entreprises 
                SET nom = ?, siret = ?, adresse = ?, code_postal = ?, ville = ?, 
                    telephone = ?, email = ?, site_web = ?, secteur_activite = ? 
                WHERE id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $entreprise->getNom(),
            $entreprise->getSiret(),
            $entreprise->getAdresse(),
            $entreprise->getCodePostal(),
            $entreprise->getVille(),
            $entreprise->getTelephone(),
            $entreprise->getEmail(),
            $entreprise->getSiteWeb(),
            $entreprise->getSecteurActivite(),
            $entreprise->getId()
        ]);
    }

    /**
     * Supprime une entreprise
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM entreprises WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Compte le nombre total d'entreprises
     */
    public function count(): int
    {
        $sql = "SELECT COUNT(*) FROM entreprises";
        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    /**
     * Compte le nombre de stages par entreprise
     */
    public function countStagesById(int $id): int
    {
        $sql = "SELECT COUNT(*) FROM stages WHERE entreprise_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Récupère les entreprises avec le nombre de stages
     */
    public function findAllWithStagesCount(): array
    {
        $sql = "SELECT e.*, 
                       (SELECT COUNT(*) FROM stages s WHERE s.entreprise_id = e.id) AS nb_stages,
                       (SELECT COUNT(*) FROM offres_stage o WHERE o.entreprise_id = e.id) AS nb_offres
                FROM entreprises e 
                ORDER BY e.nom";
        
        $stmt = $this->pdo->query($sql);
        $result = [];
        
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[] = [
                'entreprise' => Entreprise::fromArray($data),
                'nb_stages' => (int) $data['nb_stages'],
                'nb_offres' => (int) $data['nb_offres']
            ];
        }
        
        return $result;
    }

    /**
     * Récupère les entreprises ayant des offres actives
     */
    public function findWithOffresActives(): array
    {
        $sql = "SELECT DISTINCT e.* 
                FROM entreprises e 
                INNER JOIN offres_stage o ON e.id = o.entreprise_id 
                WHERE o.statut = 'publiée'
                ORDER BY e.nom";
        
        $stmt = $this->pdo->query($sql);
        $entreprises = [];
        
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $entreprises[] = Entreprise::fromArray($data);
        }
        
        return $entreprises;
    }
}

