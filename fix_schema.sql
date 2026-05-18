-- ============================================================
-- fix_schema.sql — Script de correction du schéma BTS Fulbert
-- À exécuter UNE SEULE FOIS directement dans phpMyAdmin ou
-- via : mysql -u root -p bts_fulbert < fix_schema.sql
-- ============================================================

-- Active les erreurs silencieuses pour les colonnes déjà existantes
SET sql_notes = 0;

-- ── Table dossier : ajout des colonnes manquantes ─────────────────────

ALTER TABLE `dossier`
    ADD COLUMN IF NOT EXISTS `nom`                   VARCHAR(100)  DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `prenom`                VARCHAR(100)  DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `date_naissance`        DATE          DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `genre`                 VARCHAR(20)   DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `nationalite`           VARCHAR(100)  DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `telephone`             VARCHAR(20)   DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `adresse`               VARCHAR(255)  DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `code_postal`           VARCHAR(10)   DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `ville`                 VARCHAR(100)  DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `dernier_diplome`       VARCHAR(100)  DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `mention_bac`           VARCHAR(50)   DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `annee_obtention`       SMALLINT      DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `etablissement_origine` VARCHAR(255)  DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `specialite_bac`        VARCHAR(100)  DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `experience_pro`        LONGTEXT      DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `bts_choisi`            VARCHAR(50)   DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `bts_choisi2`           VARCHAR(50)   DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `lettre_motivation`     LONGTEXT      DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `regime_appr`           TINYINT(1)    DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `pieces`                JSON          DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `soumis_at`             DATETIME      DEFAULT NULL;

-- ── Table piece_justificative : création si absente ───────────────────

CREATE TABLE IF NOT EXISTS `piece_justificative` (
    `id`             INT          AUTO_INCREMENT NOT NULL,
    `dossier_id`     INT          NOT NULL,
    `type`           VARCHAR(50)  NOT NULL,
    `nom_fichier`    VARCHAR(255) NOT NULL,
    `chemin_fichier` VARCHAR(255) NOT NULL,
    `mime_type`      VARCHAR(50)  NOT NULL,
    `taille`         INT          NOT NULL,
    `uploaded_at`    DATETIME     NOT NULL,
    INDEX IDX_F73DDA3611C0C56 (`dossier_id`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4;

-- ── Table user : création si absente ─────────────────────────────────

CREATE TABLE IF NOT EXISTS `user` (
    `id`                       INT          AUTO_INCREMENT NOT NULL,
    `email`                    VARCHAR(180) NOT NULL,
    `roles`                    JSON         NOT NULL,
    `password`                 VARCHAR(255) NOT NULL,
    `is_verified`              TINYINT(1)   NOT NULL DEFAULT 0,
    `email_verification_token` VARCHAR(100) DEFAULT NULL,
    `created_at`               DATETIME     NOT NULL,
    UNIQUE INDEX UNIQ_8D93D649E7927C74 (`email`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4;

-- ── Table messenger_messages : création si absente ───────────────────

CREATE TABLE IF NOT EXISTS `messenger_messages` (
    `id`           BIGINT   AUTO_INCREMENT NOT NULL,
    `body`         LONGTEXT NOT NULL,
    `headers`      LONGTEXT NOT NULL,
    `queue_name`   VARCHAR(190) NOT NULL,
    `created_at`   DATETIME NOT NULL,
    `available_at` DATETIME NOT NULL,
    `delivered_at` DATETIME DEFAULT NULL,
    INDEX IDX_75EA56E0FB7336F0 (`queue_name`, `available_at`, `delivered_at`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4;

-- ── Clés étrangères (ignorées si déjà présentes) ─────────────────────

-- dossier.user_id → user.id
ALTER TABLE `dossier`
    ADD COLUMN IF NOT EXISTS `user_id` INT DEFAULT NULL;

-- On ne peut pas faire "ADD FOREIGN KEY IF NOT EXISTS" en MySQL standard,
-- donc on utilise une procédure ou on le fait manuellement si besoin.
-- Voici la FK à ajouter si elle n'existe pas encore :
--   ALTER TABLE dossier ADD CONSTRAINT FK_3D48E037A76ED395
--   FOREIGN KEY (user_id) REFERENCES user (id);

-- piece_justificative.dossier_id → dossier.id
--   ALTER TABLE piece_justificative ADD CONSTRAINT FK_F73DDA3611C0C56
--   FOREIGN KEY (dossier_id) REFERENCES dossier (id);

-- ── Remise à zéro du mode silencieux ──────────────────────────────────
SET sql_notes = 1;

-- ── Vérification finale ───────────────────────────────────────────────
-- Lance cette requête pour vérifier que tout est OK :
-- DESCRIBE dossier;
