<?php
/**
 * Classe EleveDAO (Data Access Object)
 * Gère l'accès aux données des élèves via PDO
 * 
 * @package IntranetStages
 * @author Projet BTS SIO
 */

require_once __DIR__ . '/Eleve.php';

class EleveDAO
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
     * Récupère un élève par son ID
     */
    public function findById(int $id): ?Eleve
    {
        $sql = "SELECT id, nom, prenom, email, telephone, formation, date_creation 
                FROM users 
                WHERE id = ? AND role = 'etudiant'";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? Eleve::fromArray($data) : null;
    }

    /**
     * Récupère un élève par son email
     */
    public function findByEmail(string $email): ?Eleve
    {
        $sql = "SELECT id, nom, prenom, email, telephone, formation, date_creation 
                FROM users 
                WHERE email = ? AND role = 'etudiant'";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$email]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? Eleve::fromArray($data) : null;
    }

    /**
     * Récupère tous les élèves
     */
    public function findAll(): array
    {
        $sql = "SELECT id, nom, prenom, email, telephone, formation, date_creation 
                FROM users 
                WHERE role = 'etudiant' 
                ORDER BY nom, prenom";
        
        $stmt = $this->pdo->query($sql);
        $eleves = [];
        
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $eleves[] = Eleve::fromArray($data);
        }
        
        return $eleves;
    }

    /**
     * Recherche des élèves avec des critères
     */
    public function search(array $criteria = []): array
    {
        $sql = "SELECT id, nom, prenom, email, telephone, formation, date_creation 
                FROM users 
                WHERE role = 'etudiant'";
        $params = [];

        if (!empty($criteria['nom'])) {
            $sql .= " AND nom LIKE ?";
            $params[] = '%' . $criteria['nom'] . '%';
        }

        if (!empty($criteria['prenom'])) {
            $sql .= " AND prenom LIKE ?";
            $params[] = '%' . $criteria['prenom'] . '%';
        }

        if (!empty($criteria['email'])) {
            $sql .= " AND email LIKE ?";
            $params[] = '%' . $criteria['email'] . '%';
        }

        if (!empty($criteria['formation'])) {
            $sql .= " AND formation LIKE ?";
            $params[] = '%' . $criteria['formation'] . '%';
        }

        if (!empty($criteria['q'])) {
            $sql .= " AND (nom LIKE ? OR prenom LIKE ? OR email LIKE ?)";
            $params[] = '%' . $criteria['q'] . '%';
            $params[] = '%' . $criteria['q'] . '%';
            $params[] = '%' . $criteria['q'] . '%';
        }

        $sql .= " ORDER BY nom, prenom";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $eleves = [];

        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $eleves[] = Eleve::fromArray($data);
        }

        return $eleves;
    }

    /**
     * Insère un nouvel élève
     */
    public function insert(Eleve $eleve, string $motDePasse): int
    {
        $sql = "INSERT INTO users (role, nom, prenom, email, telephone, formation, mot_de_passe, date_creation) 
                VALUES ('etudiant', ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $eleve->getNom(),
            $eleve->getPrenom(),
            $eleve->getEmail(),
            $eleve->getTelephone(),
            $eleve->getFormation(),
            password_hash($motDePasse, PASSWORD_DEFAULT)
        ]);
        
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Met à jour un élève
     */
    public function update(Eleve $eleve): bool
    {
        $sql = "UPDATE users 
                SET nom = ?, prenom = ?, email = ?, telephone = ?, formation = ? 
                WHERE id = ? AND role = 'etudiant'";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $eleve->getNom(),
            $eleve->getPrenom(),
            $eleve->getEmail(),
            $eleve->getTelephone(),
            $eleve->getFormation(),
            $eleve->getId()
        ]);
    }

    /**
     * Supprime un élève
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM users WHERE id = ? AND role = 'etudiant'";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Compte le nombre total d'élèves
     */
    public function count(): int
    {
        $sql = "SELECT COUNT(*) FROM users WHERE role = 'etudiant'";
        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    /**
     * Récupère les élèves sans stage en cours
     */
    public function findSansStage(): array
    {
        $sql = "SELECT u.id, u.nom, u.prenom, u.email, u.telephone, u.formation, u.date_creation 
                FROM users u 
                WHERE u.role = 'etudiant' 
                AND u.id NOT IN (
                    SELECT s.etudiant_user_id FROM stages s WHERE s.statut IN ('préparation', 'en_cours')
                )
                ORDER BY u.nom, u.prenom";
        
        $stmt = $this->pdo->query($sql);
        $eleves = [];
        
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $eleves[] = Eleve::fromArray($data);
        }
        
        return $eleves;
    }

    /**
     * Récupère les élèves avec un stage en cours
     */
    public function findAvecStageEnCours(): array
    {
        $sql = "SELECT DISTINCT u.id, u.nom, u.prenom, u.email, u.telephone, u.formation, u.date_creation 
                FROM users u 
                INNER JOIN stages s ON u.id = s.etudiant_user_id
                WHERE u.role = 'etudiant' AND s.statut = 'en_cours'
                ORDER BY u.nom, u.prenom";
        
        $stmt = $this->pdo->query($sql);
        $eleves = [];
        
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $eleves[] = Eleve::fromArray($data);
        }
        
        return $eleves;
    }
}

