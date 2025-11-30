<?php
/**
 * Classe StageDAO (Data Access Object)
 * Gère l'accès aux données des stages via PDO
 * 
 * @package IntranetStages
 * @author Projet BTS SIO
 */

require_once __DIR__ . '/Stage.php';
require_once __DIR__ . '/Eleve.php';
require_once __DIR__ . '/Entreprise.php';
require_once __DIR__ . '/Tuteur.php';

class StageDAO
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
     * Récupère un stage par son ID
     */
    public function findById(int $id): ?Stage
    {
        $sql = "SELECT * FROM stages WHERE id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? Stage::fromArray($data) : null;
    }

    /**
     * Récupère un stage avec toutes ses relations (étudiant, entreprise, tuteurs)
     */
    public function findByIdWithRelations(int $id): ?Stage
    {
        $sql = "SELECT s.*,
                       ue.nom AS etu_nom, ue.prenom AS etu_prenom, ue.email AS etu_email,
                       e.nom AS entreprise_nom, e.siret, e.adresse AS entreprise_adresse, e.site_web,
                       ut.nom AS tuteur_nom, ut.prenom AS tuteur_prenom, ut.email AS tuteur_email
                FROM stages s
                JOIN users ue ON ue.id = s.etudiant_user_id
                JOIN entreprises e ON e.id = s.entreprise_id
                LEFT JOIN users ut ON ut.id = s.tuteur_enseignant_user_id
                WHERE s.id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }

        $stage = Stage::fromArray($data);

        // Hydrater l'étudiant
        $etudiant = new Eleve(
            (int)$data['etudiant_user_id'],
            $data['etu_nom'],
            $data['etu_prenom'],
            $data['etu_email']
        );
        $stage->setEtudiant($etudiant);

        // Hydrater l'entreprise
        $entreprise = new Entreprise(
            (int)$data['entreprise_id'],
            $data['entreprise_nom'],
            $data['siret'] ?? null,
            $data['entreprise_adresse'] ?? null
        );
        $stage->setEntreprise($entreprise);

        // Hydrater le tuteur enseignant si présent
        if (!empty($data['tuteur_enseignant_user_id'])) {
            $tuteur = new Tuteur(
                (int)$data['tuteur_enseignant_user_id'],
                $data['tuteur_nom'] ?? '',
                $data['tuteur_prenom'] ?? '',
                $data['tuteur_email'] ?? '',
                null,
                Tuteur::TYPE_ENSEIGNANT
            );
            $stage->setTuteurEnseignant($tuteur);
        }

        return $stage;
    }

    /**
     * Récupère tous les stages
     */
    public function findAll(): array
    {
        $sql = "SELECT * FROM stages ORDER BY date_debut DESC, id DESC";
        
        $stmt = $this->pdo->query($sql);
        $stages = [];
        
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stages[] = Stage::fromArray($data);
        }
        
        return $stages;
    }

    /**
     * Recherche des stages avec des critères multiples
     */
    public function search(array $criteria = []): array
    {
        $sql = "SELECT s.*,
                       ue.nom AS etu_nom, ue.prenom AS etu_prenom, ue.email AS etu_email,
                       e.nom AS entreprise_nom,
                       ut.nom AS tuteur_nom, ut.prenom AS tuteur_prenom
                FROM stages s
                JOIN users ue ON ue.id = s.etudiant_user_id
                JOIN entreprises e ON e.id = s.entreprise_id
                LEFT JOIN users ut ON ut.id = s.tuteur_enseignant_user_id
                WHERE 1=1";
        $params = [];

        // Recherche textuelle globale
        if (!empty($criteria['q'])) {
            $sql .= " AND (CONCAT(ue.prenom, ' ', ue.nom) LIKE ? OR e.nom LIKE ? OR s.sujet LIKE ?)";
            $params[] = '%' . $criteria['q'] . '%';
            $params[] = '%' . $criteria['q'] . '%';
            $params[] = '%' . $criteria['q'] . '%';
        }

        // Filtre par statut
        if (!empty($criteria['statut']) && in_array($criteria['statut'], Stage::STATUTS_VALIDES)) {
            $sql .= " AND s.statut = ?";
            $params[] = $criteria['statut'];
        }

        // Filtre par entreprise
        if (!empty($criteria['entreprise_id'])) {
            $sql .= " AND s.entreprise_id = ?";
            $params[] = (int) $criteria['entreprise_id'];
        }

        // Filtre par tuteur enseignant
        if (!empty($criteria['tuteur_enseignant_id'])) {
            $sql .= " AND s.tuteur_enseignant_user_id = ?";
            $params[] = (int) $criteria['tuteur_enseignant_id'];
        }

        // Filtre par étudiant
        if (!empty($criteria['etudiant_id'])) {
            $sql .= " AND s.etudiant_user_id = ?";
            $params[] = (int) $criteria['etudiant_id'];
        }

        // Filtre par date de début minimum
        if (!empty($criteria['date_min'])) {
            $sql .= " AND s.date_debut >= ?";
            $params[] = $criteria['date_min'];
        }

        // Filtre par date de fin maximum
        if (!empty($criteria['date_max'])) {
            $sql .= " AND s.date_fin <= ?";
            $params[] = $criteria['date_max'];
        }

        // Filtre par année scolaire
        if (!empty($criteria['annee'])) {
            $sql .= " AND YEAR(s.date_debut) = ?";
            $params[] = (int) $criteria['annee'];
        }

        $sql .= " ORDER BY s.date_debut DESC, s.id DESC";

        // Pagination
        if (!empty($criteria['limit'])) {
            $sql .= " LIMIT " . (int) $criteria['limit'];
            if (!empty($criteria['offset'])) {
                $sql .= " OFFSET " . (int) $criteria['offset'];
            }
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $stages = [];

        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stage = Stage::fromArray($data);
            
            // Hydrater les relations
            $etudiant = new Eleve(
                (int)$data['etudiant_user_id'],
                $data['etu_nom'],
                $data['etu_prenom'],
                $data['etu_email']
            );
            $stage->setEtudiant($etudiant);

            $entreprise = new Entreprise((int)$data['entreprise_id'], $data['entreprise_nom']);
            $stage->setEntreprise($entreprise);

            if (!empty($data['tuteur_enseignant_user_id'])) {
                $tuteur = new Tuteur(
                    (int)$data['tuteur_enseignant_user_id'],
                    $data['tuteur_nom'] ?? '',
                    $data['tuteur_prenom'] ?? ''
                );
                $stage->setTuteurEnseignant($tuteur);
            }

            $stages[] = $stage;
        }

        return $stages;
    }

    /**
     * Insère un nouveau stage
     */
    public function insert(Stage $stage): int
    {
        $sql = "INSERT INTO stages 
                (etudiant_user_id, entreprise_id, tuteur_enseignant_user_id, tuteur_entreprise_user_id,
                 date_debut, date_fin, sujet, description, statut, date_creation) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $stage->getEtudiantUserId(),
            $stage->getEntrepriseId(),
            $stage->getTuteurEnseignantUserId(),
            $stage->getTuteurEntrepriseUserId(),
            $stage->getDateDebut(),
            $stage->getDateFin(),
            $stage->getSujet(),
            $stage->getDescription(),
            $stage->getStatut()
        ]);
        
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Met à jour un stage
     */
    public function update(Stage $stage): bool
    {
        $sql = "UPDATE stages 
                SET etudiant_user_id = ?, entreprise_id = ?, tuteur_enseignant_user_id = ?,
                    tuteur_entreprise_user_id = ?, date_debut = ?, date_fin = ?,
                    sujet = ?, description = ?, statut = ?
                WHERE id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $stage->getEtudiantUserId(),
            $stage->getEntrepriseId(),
            $stage->getTuteurEnseignantUserId(),
            $stage->getTuteurEntrepriseUserId(),
            $stage->getDateDebut(),
            $stage->getDateFin(),
            $stage->getSujet(),
            $stage->getDescription(),
            $stage->getStatut(),
            $stage->getId()
        ]);
    }

    /**
     * Met à jour le statut d'un stage
     */
    public function updateStatut(int $id, string $statut): bool
    {
        if (!in_array($statut, Stage::STATUTS_VALIDES)) {
            return false;
        }

        $sql = "UPDATE stages SET statut = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$statut, $id]);
    }

    /**
     * Affecte un tuteur enseignant à un stage
     */
    public function affecterTuteurEnseignant(int $stageId, ?int $tuteurId): bool
    {
        $sql = "UPDATE stages SET tuteur_enseignant_user_id = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$tuteurId, $stageId]);
    }

    /**
     * Supprime un stage
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM stages WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Compte le nombre total de stages
     */
    public function count(array $criteria = []): int
    {
        $sql = "SELECT COUNT(*) FROM stages s
                JOIN users ue ON ue.id = s.etudiant_user_id
                JOIN entreprises e ON e.id = s.entreprise_id
                WHERE 1=1";
        $params = [];

        if (!empty($criteria['statut'])) {
            $sql .= " AND s.statut = ?";
            $params[] = $criteria['statut'];
        }

        if (!empty($criteria['entreprise_id'])) {
            $sql .= " AND s.entreprise_id = ?";
            $params[] = (int) $criteria['entreprise_id'];
        }

        if (!empty($criteria['tuteur_enseignant_id'])) {
            $sql .= " AND s.tuteur_enseignant_user_id = ?";
            $params[] = (int) $criteria['tuteur_enseignant_id'];
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Récupère les statistiques globales
     */
    public function getStatistiques(): array
    {
        $sql = "SELECT 
                    COUNT(*) AS total,
                    SUM(statut = 'préparation') AS preparation,
                    SUM(statut = 'en_cours') AS en_cours,
                    SUM(statut = 'terminé') AS termines,
                    SUM(statut = 'rupture') AS ruptures
                FROM stages";
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les stages d'un étudiant
     */
    public function findByEtudiant(int $etudiantId): array
    {
        return $this->search(['etudiant_id' => $etudiantId]);
    }

    /**
     * Récupère les stages d'un tuteur enseignant
     */
    public function findByTuteurEnseignant(int $tuteurId): array
    {
        return $this->search(['tuteur_enseignant_id' => $tuteurId]);
    }

    /**
     * Récupère les stages d'une entreprise
     */
    public function findByEntreprise(int $entrepriseId): array
    {
        return $this->search(['entreprise_id' => $entrepriseId]);
    }

    /**
     * Export des stages en tableau pour CSV
     */
    public function exportData(array $criteria = []): array
    {
        $stages = $this->search($criteria);
        $data = [];

        foreach ($stages as $stage) {
            $data[] = [
                'ID' => $stage->getId(),
                'Étudiant' => $stage->getEtudiant() ? $stage->getEtudiant()->getNomComplet() : '',
                'Email étudiant' => $stage->getEtudiant() ? $stage->getEtudiant()->getEmail() : '',
                'Entreprise' => $stage->getEntreprise() ? $stage->getEntreprise()->getNom() : '',
                'Tuteur enseignant' => $stage->getTuteurEnseignant() ? $stage->getTuteurEnseignant()->getNomComplet() : '',
                'Date début' => $stage->getDateDebut(),
                'Date fin' => $stage->getDateFin(),
                'Durée (semaines)' => $stage->getDureeSemaines(),
                'Sujet' => $stage->getSujet() ?? '',
                'Statut' => $stage->getStatutLibelle()
            ];
        }

        return $data;
    }
}

