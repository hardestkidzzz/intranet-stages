<?php
/**
 * Classe TuteurDAO (Data Access Object)
 * Gère l'accès aux données des tuteurs via PDO
 * 
 * @package IntranetStages
 * @author Projet BTS SIO
 */

require_once __DIR__ . '/Tuteur.php';

class TuteurDAO
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
     * Récupère un tuteur par son ID
     */
    public function findById(int $id): ?Tuteur
    {
        $sql = "SELECT id, nom, prenom, email, telephone, role AS type, date_creation 
                FROM users 
                WHERE id = ? AND role IN ('enseignant', 'entreprise')";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? Tuteur::fromArray($data) : null;
    }

    /**
     * Récupère tous les tuteurs enseignants
     */
    public function findAllEnseignants(): array
    {
        $sql = "SELECT id, nom, prenom, email, telephone, role AS type, date_creation 
                FROM users 
                WHERE role = 'enseignant' 
                ORDER BY nom, prenom";
        
        $stmt = $this->pdo->query($sql);
        $tuteurs = [];
        
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tuteurs[] = Tuteur::fromArray($data);
        }
        
        return $tuteurs;
    }

    /**
     * Récupère tous les tuteurs entreprise
     */
    public function findAllEntreprises(): array
    {
        $sql = "SELECT u.id, u.nom, u.prenom, u.email, u.telephone, u.role AS type, 
                       te.entreprise_id, te.fonction, u.date_creation 
                FROM users u
                LEFT JOIN tuteurs_entreprise te ON te.user_id = u.id
                WHERE u.role = 'entreprise' 
                ORDER BY u.nom, u.prenom";
        
        $stmt = $this->pdo->query($sql);
        $tuteurs = [];
        
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tuteurs[] = Tuteur::fromArray($data);
        }
        
        return $tuteurs;
    }

    /**
     * Récupère tous les tuteurs (enseignants et entreprise)
     */
    public function findAll(): array
    {
        return array_merge($this->findAllEnseignants(), $this->findAllEntreprises());
    }

    /**
     * Recherche des tuteurs avec des critères
     */
    public function search(array $criteria = []): array
    {
        $sql = "SELECT id, nom, prenom, email, telephone, role AS type, date_creation 
                FROM users 
                WHERE role IN ('enseignant', 'entreprise')";
        $params = [];

        if (!empty($criteria['type'])) {
            $sql .= " AND role = ?";
            $params[] = $criteria['type'];
        }

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

        if (!empty($criteria['q'])) {
            $sql .= " AND (nom LIKE ? OR prenom LIKE ? OR email LIKE ?)";
            $params[] = '%' . $criteria['q'] . '%';
            $params[] = '%' . $criteria['q'] . '%';
            $params[] = '%' . $criteria['q'] . '%';
        }

        $sql .= " ORDER BY nom, prenom";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $tuteurs = [];

        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tuteurs[] = Tuteur::fromArray($data);
        }

        return $tuteurs;
    }

    /**
     * Insère un nouveau tuteur
     */
    public function insert(Tuteur $tuteur, string $motDePasse): int
    {
        $role = $tuteur->isEnseignant() ? 'enseignant' : 'entreprise';
        
        $sql = "INSERT INTO users (role, nom, prenom, email, telephone, mot_de_passe, date_creation) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $role,
            $tuteur->getNom(),
            $tuteur->getPrenom(),
            $tuteur->getEmail(),
            $tuteur->getTelephone(),
            password_hash($motDePasse, PASSWORD_DEFAULT)
        ]);
        
        $userId = (int) $this->pdo->lastInsertId();

        // Si c'est un tuteur entreprise, ajouter l'entrée dans tuteurs_entreprise
        if ($tuteur->isEntreprise() && $tuteur->getEntrepriseId()) {
            $sql2 = "INSERT INTO tuteurs_entreprise (user_id, entreprise_id, fonction) VALUES (?, ?, ?)";
            $stmt2 = $this->pdo->prepare($sql2);
            $stmt2->execute([$userId, $tuteur->getEntrepriseId(), $tuteur->getFonction()]);
        }
        
        return $userId;
    }

    /**
     * Met à jour un tuteur
     */
    public function update(Tuteur $tuteur): bool
    {
        $sql = "UPDATE users 
                SET nom = ?, prenom = ?, email = ?, telephone = ? 
                WHERE id = ? AND role IN ('enseignant', 'entreprise')";
        
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([
            $tuteur->getNom(),
            $tuteur->getPrenom(),
            $tuteur->getEmail(),
            $tuteur->getTelephone(),
            $tuteur->getId()
        ]);

        // Mettre à jour tuteurs_entreprise si nécessaire
        if ($result && $tuteur->isEntreprise()) {
            $sql2 = "UPDATE tuteurs_entreprise SET entreprise_id = ?, fonction = ? WHERE user_id = ?";
            $stmt2 = $this->pdo->prepare($sql2);
            $stmt2->execute([$tuteur->getEntrepriseId(), $tuteur->getFonction(), $tuteur->getId()]);
        }

        return $result;
    }

    /**
     * Supprime un tuteur
     */
    public function delete(int $id): bool
    {
        // Supprimer d'abord de tuteurs_entreprise si présent
        $sql1 = "DELETE FROM tuteurs_entreprise WHERE user_id = ?";
        $stmt1 = $this->pdo->prepare($sql1);
        $stmt1->execute([$id]);

        // Puis supprimer l'utilisateur
        $sql = "DELETE FROM users WHERE id = ? AND role IN ('enseignant', 'entreprise')";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Compte le nombre de tuteurs enseignants
     */
    public function countEnseignants(): int
    {
        $sql = "SELECT COUNT(*) FROM users WHERE role = 'enseignant'";
        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    /**
     * Compte le nombre de tuteurs entreprise
     */
    public function countEntreprises(): int
    {
        $sql = "SELECT COUNT(*) FROM users WHERE role = 'entreprise'";
        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    /**
     * Récupère les tuteurs enseignants avec le nombre de stages suivis
     */
    public function findEnseignantsWithStagesCount(): array
    {
        $sql = "SELECT u.id, u.nom, u.prenom, u.email, u.telephone, u.role AS type, u.date_creation,
                       (SELECT COUNT(*) FROM stages s WHERE s.tuteur_enseignant_user_id = u.id) AS nb_stages,
                       (SELECT COUNT(*) FROM stages s WHERE s.tuteur_enseignant_user_id = u.id AND s.statut = 'en_cours') AS nb_stages_en_cours
                FROM users u
                WHERE u.role = 'enseignant'
                ORDER BY u.nom, u.prenom";
        
        $stmt = $this->pdo->query($sql);
        $result = [];
        
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[] = [
                'tuteur' => Tuteur::fromArray($data),
                'nb_stages' => (int) $data['nb_stages'],
                'nb_stages_en_cours' => (int) $data['nb_stages_en_cours']
            ];
        }
        
        return $result;
    }

    /**
     * Récupère les tuteurs d'une entreprise
     */
    public function findByEntreprise(int $entrepriseId): array
    {
        $sql = "SELECT u.id, u.nom, u.prenom, u.email, u.telephone, u.role AS type, 
                       te.entreprise_id, te.fonction, u.date_creation
                FROM users u
                INNER JOIN tuteurs_entreprise te ON te.user_id = u.id
                WHERE te.entreprise_id = ?
                ORDER BY u.nom, u.prenom";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$entrepriseId]);
        $tuteurs = [];
        
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tuteurs[] = Tuteur::fromArray($data);
        }
        
        return $tuteurs;
    }
}

