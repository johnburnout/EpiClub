-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost
-- Généré le : mer. 02 juil. 2025 à 20:25
-- Version du serveur : 8.0.42
-- Version de PHP : 8.3.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `epi`
--

-- --------------------------------------------------------

--
-- Structure de la table `categorie`
--

CREATE TABLE `categorie` (
  `id` int NOT NULL,
  `libelle` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `fabricant`
--

CREATE TABLE `fabricant` (
  `id` int NOT NULL,
  `libelle` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `facture`
--

CREATE TABLE `facture` (
  `id` int NOT NULL,
  `vendeur` varchar(30) NOT NULL,
  `date_facture` date DEFAULT NULL,
  `reference` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `fiche`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `fiche` (
`id` int
,`ref` varchar(8)
,`libelle` varchar(30)
,`categorie` varchar(20)
,`categorie_id` int
,`fabricant` varchar(50)
,`fabricant_id` int
,`lieu` varchar(15)
,`lieu_id` int
,`vendeur` varchar(30)
,`facture_id` int
,`date_facture` date
,`username` varchar(16)
,`utilisateur_id` int
,`nb_elements` int
,`date_max` date
,`date_debut` date
,`verification_id` int
,`date_verification` date
,`remarques` text
,`photo` varchar(50)
,`nb_elements_initial` int
);

-- --------------------------------------------------------

--
-- Structure de la table `lieu`
--

CREATE TABLE `lieu` (
  `id` int NOT NULL,
  `libelle` varchar(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `liste`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `liste` (
`id` int
,`ref` varchar(8)
,`libelle` varchar(30)
,`fabricant` varchar(50)
,`categorie` varchar(20)
,`categorie_id` int
,`lieu` varchar(15)
,`lieu_id` int
,`nb_elements` int
,`date_verification` date
,`date_max` date
);

-- --------------------------------------------------------

--
-- Structure de la table `matos`
--

CREATE TABLE `matos` (
  `id` int NOT NULL,
  `reference` varchar(8) NOT NULL DEFAULT '00XXX000' COMMENT 'Référence du lot',
  `libelle` varchar(30) NOT NULL,
  `categorie_id` int NOT NULL,
  `fabricant_id` int NOT NULL,
  `photo` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT 'null.jpeg',
  `lieu_id` int NOT NULL,
  `en_service` tinyint(1) GENERATED ALWAYS AS (((`nb_elements` > 0) and (`libelle` <> _utf8mb3'HS'))) VIRTUAL NOT NULL,
  `facture_id` int NOT NULL,
  `date_debut` date NOT NULL,
  `date_max` date DEFAULT NULL,
  `nb_elements_initial` int NOT NULL,
  `nb_elements` int DEFAULT NULL,
  `verification_id` int DEFAULT '1',
  `utilisateur_id` int NOT NULL DEFAULT '1' COMMENT 'Vérificateur par défaut à l''achat',
  `remarques` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Déclencheurs `matos`
--
DELIMITER $$
CREATE TRIGGER `nombre_elements` BEFORE INSERT ON `matos` FOR EACH ROW SET 
	NEW.nb_elements = NEW.nb_elements_initial,
    NEW.date_max = DATE_ADD(NEW.date_debut, INTERVAL 10 YEAR)
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur`
--

CREATE TABLE `utilisateur` (
  `id` int NOT NULL,
  `username` varchar(16) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(32) NOT NULL,
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `verification`
--

CREATE TABLE `verification` (
  `id` int NOT NULL,
  `date_heure_verification` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_verification` date GENERATED ALWAYS AS (cast(`date_heure_verification` as date)) VIRTUAL,
  `utilisateur_id` int NOT NULL,
  `remarques` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `categorie`
--
ALTER TABLE `categorie`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_UNIQUE` (`id`);

--
-- Index pour la table `fabricant`
--
ALTER TABLE `fabricant`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_UNIQUE` (`id`);

--
-- Index pour la table `facture`
--
ALTER TABLE `facture`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_UNIQUE` (`id`);

--
-- Index pour la table `lieu`
--
ALTER TABLE `lieu`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `NOM_LIEU_UNIQUE` (`libelle`),
  ADD UNIQUE KEY `id_UNIQUE` (`id`);

--
-- Index pour la table `matos`
--
ALTER TABLE `matos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ID_Materiel_UNIQUE` (`id`),
  ADD UNIQUE KEY `reference` (`reference`),
  ADD KEY `fk_Matériel_Catégorie_idx` (`categorie_id`),
  ADD KEY `fk_MAT_LIEU1_idx` (`lieu_id`),
  ADD KEY `fk_matos_facture1_idx` (`facture_id`),
  ADD KEY `fk_matos_verification1_idx` (`verification_id`),
  ADD KEY `fk_matos_utilisateur1_idx` (`utilisateur_id`),
  ADD KEY `fk_matos_fabricant1_idx` (`fabricant_id`);

--
-- Index pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_UNIQUE` (`id`);

--
-- Index pour la table `verification`
--
ALTER TABLE `verification`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_UNIQUE` (`id`),
  ADD KEY `fk_verification_utilisateur1_idx` (`utilisateur_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `categorie`
--
ALTER TABLE `categorie`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `fabricant`
--
ALTER TABLE `fabricant`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `facture`
--
ALTER TABLE `facture`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `lieu`
--
ALTER TABLE `lieu`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `matos`
--
ALTER TABLE `matos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `verification`
--
ALTER TABLE `verification`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- Structure de la vue `fiche`
--
DROP TABLE IF EXISTS `fiche`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `fiche`  AS SELECT `matos`.`id` AS `id`, `matos`.`reference` AS `ref`, `matos`.`libelle` AS `libelle`, `categorie`.`libelle` AS `categorie`, `categorie`.`id` AS `categorie_id`, `fabricant`.`libelle` AS `fabricant`, `fabricant`.`id` AS `fabricant_id`, `lieu`.`libelle` AS `lieu`, `lieu`.`id` AS `lieu_id`, `facture`.`vendeur` AS `vendeur`, `facture`.`id` AS `facture_id`, `facture`.`date_facture` AS `date_facture`, `utilisateur`.`username` AS `username`, `matos`.`utilisateur_id` AS `utilisateur_id`, `matos`.`nb_elements` AS `nb_elements`, `matos`.`date_max` AS `date_max`, `matos`.`date_debut` AS `date_debut`, `verification`.`id` AS `verification_id`, `verification`.`date_verification` AS `date_verification`, `matos`.`remarques` AS `remarques`, `matos`.`photo` AS `photo`, `matos`.`nb_elements_initial` AS `nb_elements_initial` FROM ((((((`matos` join `categorie`) join `fabricant`) join `lieu`) join `facture`) join `verification`) join `utilisateur`) WHERE ((0 <> `matos`.`en_service`) AND (`matos`.`categorie_id` = `categorie`.`id`) AND (`matos`.`fabricant_id` = `fabricant`.`id`) AND (`matos`.`verification_id` = `verification`.`id`) AND (`matos`.`utilisateur_id` = `utilisateur`.`id`) AND (`matos`.`lieu_id` = `lieu`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure de la vue `liste`
--
DROP TABLE IF EXISTS `liste`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `liste`  AS SELECT `matos`.`id` AS `id`, `matos`.`reference` AS `ref`, `matos`.`libelle` AS `libelle`, `fabricant`.`libelle` AS `fabricant`, `categorie`.`libelle` AS `categorie`, `matos`.`categorie_id` AS `categorie_id`, `lieu`.`libelle` AS `lieu`, `matos`.`lieu_id` AS `lieu_id`, `matos`.`nb_elements` AS `nb_elements`, `verification`.`date_verification` AS `date_verification`, `matos`.`date_max` AS `date_max` FROM ((((`matos` join `fabricant`) join `categorie`) join `lieu`) join `verification`) WHERE ((0 <> `matos`.`en_service`) AND (`matos`.`categorie_id` = `categorie`.`id`) AND (`matos`.`fabricant_id` = `fabricant`.`id`) AND (`matos`.`verification_id` = `verification`.`id`) AND (`matos`.`lieu_id` = `lieu`.`id`)) ;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `matos`
--
ALTER TABLE `matos`
  ADD CONSTRAINT `fk_matos_cat` FOREIGN KEY (`categorie_id`) REFERENCES `categorie` (`id`),
  ADD CONSTRAINT `fk_matos_fabricant1` FOREIGN KEY (`fabricant_id`) REFERENCES `fabricant` (`id`),
  ADD CONSTRAINT `fk_matos_facture` FOREIGN KEY (`facture_id`) REFERENCES `facture` (`id`),
  ADD CONSTRAINT `fk_matos_lieu` FOREIGN KEY (`lieu_id`) REFERENCES `lieu` (`id`),
  ADD CONSTRAINT `fk_matos_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur` (`id`),
  ADD CONSTRAINT `fk_matos_verification` FOREIGN KEY (`verification_id`) REFERENCES `verification` (`id`);

--
-- Contraintes pour la table `verification`
--
ALTER TABLE `verification`
  ADD CONSTRAINT `fk_verification_utilisateur1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
