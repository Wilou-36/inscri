<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration corrective — synchronise la table dossier avec l'entité.
 * Ajoute toutes les colonnes potentiellement absentes (ADD COLUMN IF NOT EXISTS).
 * Compatible MySQL 8+ et MariaDB 10.3+.
 */
final class Version20260330120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Correction du schéma : ajoute les colonnes manquantes dans dossier';
    }

    public function up(Schema $schema): void
    {
        // ADD COLUMN IF NOT EXISTS : idempotent — ne plante pas si la colonne existe déjà.
        // On regroupe tout en un seul ALTER pour être atomique.
        $this->addSql('
            ALTER TABLE dossier
                ADD COLUMN IF NOT EXISTS nom                   VARCHAR(100)  DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS prenom                VARCHAR(100)  DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS date_naissance        DATE          DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS genre                 VARCHAR(20)   DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS nationalite           VARCHAR(100)  DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS telephone             VARCHAR(20)   DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS adresse               VARCHAR(255)  DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS code_postal           VARCHAR(10)   DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS ville                 VARCHAR(100)  DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS dernier_diplome       VARCHAR(100)  DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS mention_bac           VARCHAR(50)   DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS annee_obtention       SMALLINT      DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS etablissement_origine VARCHAR(255)  DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS specialite_bac        VARCHAR(100)  DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS experience_pro        LONGTEXT      DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS bts_choisi            VARCHAR(50)   DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS bts_choisi2           VARCHAR(50)   DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS lettre_motivation     LONGTEXT      DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS regime_appr           TINYINT(1)    DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS pieces                JSON          DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS soumis_at             DATETIME      DEFAULT NULL
        ');

        // S'assurer que la table piece_justificative existe
        $this->addSql('
            CREATE TABLE IF NOT EXISTS piece_justificative (
                id             INT          AUTO_INCREMENT NOT NULL,
                dossier_id     INT          NOT NULL,
                type           VARCHAR(50)  NOT NULL,
                nom_fichier    VARCHAR(255) NOT NULL,
                chemin_fichier VARCHAR(255) NOT NULL,
                mime_type      VARCHAR(50)  NOT NULL,
                taille         INT          NOT NULL,
                uploaded_at    DATETIME     NOT NULL,
                INDEX IDX_F73DDA3611C0C56 (dossier_id),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        ');
    }

    public function down(Schema $schema): void
    {
        // Rollback volontairement vide : supprimer des colonnes risquerait
        // d'effacer des données. Utiliser doctrine:schema:drop --force si besoin.
        $this->addSql('SELECT 1');
    }
}
