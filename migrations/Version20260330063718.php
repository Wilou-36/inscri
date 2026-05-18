<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260330063718 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE dossier (id INT AUTO_INCREMENT NOT NULL, etape_actuelle SMALLINT NOT NULL, statut VARCHAR(20) NOT NULL, nom VARCHAR(100) DEFAULT NULL, prenom VARCHAR(100) DEFAULT NULL, date_naissance DATE DEFAULT NULL, genre VARCHAR(20) DEFAULT NULL, nationalite VARCHAR(100) DEFAULT NULL, telephone VARCHAR(20) DEFAULT NULL, adresse VARCHAR(255) DEFAULT NULL, code_postal VARCHAR(10) DEFAULT NULL, ville VARCHAR(100) DEFAULT NULL, dernier_diplome VARCHAR(100) DEFAULT NULL, mention_bac VARCHAR(50) DEFAULT NULL, annee_obtention SMALLINT DEFAULT NULL, etablissement_origine VARCHAR(255) DEFAULT NULL, specialite_bac VARCHAR(100) DEFAULT NULL, experience_pro LONGTEXT DEFAULT NULL, bts_choisi VARCHAR(50) DEFAULT NULL, bts_choisi2 VARCHAR(50) DEFAULT NULL, lettre_motivation LONGTEXT DEFAULT NULL, regime_appr TINYINT DEFAULT NULL, pieces JSON DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, soumis_at DATETIME DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_3D48E037A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE piece_justificative (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL, nom_fichier VARCHAR(255) NOT NULL, chemin_fichier VARCHAR(255) NOT NULL, mime_type VARCHAR(50) NOT NULL, taille INT NOT NULL, uploaded_at DATETIME NOT NULL, dossier_id INT NOT NULL, INDEX IDX_F73DDA3611C0C56 (dossier_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, is_verified TINYINT NOT NULL, email_verification_token VARCHAR(100) DEFAULT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE dossier ADD CONSTRAINT FK_3D48E037A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE piece_justificative ADD CONSTRAINT FK_F73DDA3611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dossier DROP FOREIGN KEY FK_3D48E037A76ED395');
        $this->addSql('ALTER TABLE piece_justificative DROP FOREIGN KEY FK_F73DDA3611C0C56');
        $this->addSql('DROP TABLE dossier');
        $this->addSql('DROP TABLE piece_justificative');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
