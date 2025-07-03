-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jul 03, 2025 at 11:42 AM
-- Server version: 10.11.11-MariaDB-0+deb12u1
-- PHP Version: 8.2.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `epi`
--

-- --------------------------------------------------------

--
-- Table structure for table `categorie`
--

CREATE TABLE `categorie` (
  `id` int(11) NOT NULL,
  `libelle` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fabricant`
--

CREATE TABLE `fabricant` (
  `id` int(11) NOT NULL,
  `libelle` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `facture`
--

CREATE TABLE `facture` (
  `id` int(11) NOT NULL,
  `vendeur` varchar(30) NOT NULL,
  `date_facture` date DEFAULT NULL,
  `reference` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `fiche`
-- (See below for the actual view)
--
CREATE TABLE `fiche` (
`id` int(11)
,`ref` varchar(8)
,`libelle` varchar(60)
,`categorie` varchar(20)
,`categorie_id` int(11)
,`fabricant` varchar(50)
,`fabricant_id` int(11)
,`lieu` varchar(15)
,`lieu_id` int(11)
,`vendeur` varchar(30)
,`facture_id` int(11)
,`date_facture` date
,`username` varchar(16)
,`utilisateur_id` int(11)
,`nb_elements` int(11)
,`date_max` date
,`date_debut` date
,`verification_id` int(11)
,`date_verification` date
,`remarques` text
,`photo` varchar(50)
,`nb_elements_initial` int(11)
);

-- --------------------------------------------------------

--
-- Table structure for table `lieu`
--

CREATE TABLE `lieu` (
  `id` int(11) NOT NULL,
  `libelle` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `liste`
-- (See below for the actual view)
--
CREATE TABLE `liste` (
`id` int(11)
,`ref` varchar(8)
,`libelle` varchar(60)
,`fabricant` varchar(50)
,`categorie` varchar(20)
,`categorie_id` int(11)
,`lieu` varchar(15)
,`lieu_id` int(11)
,`nb_elements` int(11)
,`date_verification` date
,`date_max` date
);

-- --------------------------------------------------------

--
-- Table structure for table `matos`
--

CREATE TABLE `matos` (
  `id` int(11) NOT NULL,
  `reference` varchar(8) DEFAULT '00XXX000' COMMENT 'Référence du lot',
  `libelle` varchar(60) DEFAULT NULL,
  `categorie_id` int(11) DEFAULT NULL,
  `fabricant_id` int(11) DEFAULT NULL,
  `photo` varchar(50) DEFAULT 'null.jpeg',
  `lieu_id` int(11) DEFAULT NULL,
  `en_service` tinyint(1) GENERATED ALWAYS AS (`nb_elements` > 0 and `libelle` <> _utf8mb3'HS') VIRTUAL,
  `facture_id` int(11) DEFAULT NULL,
  `date_debut` date DEFAULT NULL,
  `date_max` date DEFAULT NULL,
  `nb_elements_initial` int(11) DEFAULT NULL,
  `nb_elements` int(11) DEFAULT NULL,
  `verification_id` int(11) DEFAULT 1,
  `utilisateur_id` int(11) DEFAULT 1 COMMENT 'Vérificateur par défaut à l`achat',
  `remarques` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Triggers `matos`
--
DELIMITER $$
CREATE TRIGGER `nombre_elements` BEFORE INSERT ON `matos` FOR EACH ROW SET 
	NEW.nb_elements = NEW.nb_elements_initial,
    NEW.date_max = DATE_ADD(NEW.date_debut, INTERVAL 10 YEAR)
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `utilisateur`
--

CREATE TABLE `utilisateur` (
  `id` int(11) NOT NULL,
  `username` varchar(16) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(32) DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `verification`
--

CREATE TABLE `verification` (
  `id` int(11) NOT NULL,
  `date_heure_verification` timestamp NULL DEFAULT current_timestamp(),
  `date_verification` date GENERATED ALWAYS AS (cast(`date_heure_verification` as date)) VIRTUAL,
  `utilisateur_id` int(11) DEFAULT NULL,
  `remarques` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categorie`
--
ALTER TABLE `categorie`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_UNIQUE` (`id`);

--
-- Indexes for table `fabricant`
--
ALTER TABLE `fabricant`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_UNIQUE` (`id`);

--
-- Indexes for table `facture`
--
ALTER TABLE `facture`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_UNIQUE` (`id`);

--
-- Indexes for table `lieu`
--
ALTER TABLE `lieu`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `NOM_LIEU_UNIQUE` (`libelle`),
  ADD UNIQUE KEY `id_UNIQUE` (`id`);

--
-- Indexes for table `matos`
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
-- Indexes for table `utilisateur`
--
ALTER TABLE `utilisateur`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_UNIQUE` (`id`);

--
-- Indexes for table `verification`
--
ALTER TABLE `verification`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_UNIQUE` (`id`),
  ADD KEY `fk_verification_utilisateur1_idx` (`utilisateur_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categorie`
--
ALTER TABLE `categorie`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fabricant`
--
ALTER TABLE `fabricant`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `facture`
--
ALTER TABLE `facture`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lieu`
--
ALTER TABLE `lieu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `matos`
--
ALTER TABLE `matos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `utilisateur`
--
ALTER TABLE `utilisateur`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `verification`
--
ALTER TABLE `verification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- Structure for view `fiche`
--
DROP TABLE IF EXISTS `fiche`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `fiche`  AS SELECT `matos`.`id` AS `id`, `matos`.`reference` AS `ref`, `matos`.`libelle` AS `libelle`, `categorie`.`libelle` AS `categorie`, `categorie`.`id` AS `categorie_id`, `fabricant`.`libelle` AS `fabricant`, `fabricant`.`id` AS `fabricant_id`, `lieu`.`libelle` AS `lieu`, `lieu`.`id` AS `lieu_id`, `facture`.`vendeur` AS `vendeur`, `facture`.`id` AS `facture_id`, `facture`.`date_facture` AS `date_facture`, `utilisateur`.`username` AS `username`, `matos`.`utilisateur_id` AS `utilisateur_id`, `matos`.`nb_elements` AS `nb_elements`, `matos`.`date_max` AS `date_max`, `matos`.`date_debut` AS `date_debut`, `verification`.`id` AS `verification_id`, `verification`.`date_verification` AS `date_verification`, `matos`.`remarques` AS `remarques`, `matos`.`photo` AS `photo`, `matos`.`nb_elements_initial` AS `nb_elements_initial` FROM ((((((`matos` join `categorie`) join `fabricant`) join `lieu`) join `facture`) join `verification`) join `utilisateur`) WHERE 0 <> `matos`.`en_service` AND `matos`.`categorie_id` = `categorie`.`id` AND `matos`.`fabricant_id` = `fabricant`.`id` AND `matos`.`verification_id` = `verification`.`id` AND `matos`.`utilisateur_id` = `utilisateur`.`id` AND `matos`.`lieu_id` = `lieu`.`id` ;

-- --------------------------------------------------------

--
-- Structure for view `liste`
--
DROP TABLE IF EXISTS `liste`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `liste`  AS SELECT `matos`.`id` AS `id`, `matos`.`reference` AS `ref`, `matos`.`libelle` AS `libelle`, `fabricant`.`libelle` AS `fabricant`, `categorie`.`libelle` AS `categorie`, `matos`.`categorie_id` AS `categorie_id`, `lieu`.`libelle` AS `lieu`, `matos`.`lieu_id` AS `lieu_id`, `matos`.`nb_elements` AS `nb_elements`, `verification`.`date_verification` AS `date_verification`, `matos`.`date_max` AS `date_max` FROM ((((`matos` join `fabricant`) join `categorie`) join `lieu`) join `verification`) WHERE 0 <> `matos`.`en_service` AND `matos`.`categorie_id` = `categorie`.`id` AND `matos`.`fabricant_id` = `fabricant`.`id` AND `matos`.`verification_id` = `verification`.`id` AND `matos`.`lieu_id` = `lieu`.`id` ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `matos`
--
ALTER TABLE `matos`
  ADD CONSTRAINT `fk_matos_cat` FOREIGN KEY (`categorie_id`) REFERENCES `categorie` (`id`),
  ADD CONSTRAINT `fk_matos_fabricant1` FOREIGN KEY (`fabricant_id`) REFERENCES `fabricant` (`id`),
  ADD CONSTRAINT `fk_matos_facture` FOREIGN KEY (`facture_id`) REFERENCES `facture` (`id`),
  ADD CONSTRAINT `fk_matos_lieu` FOREIGN KEY (`lieu_id`) REFERENCES `lieu` (`id`),
  ADD CONSTRAINT `fk_matos_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur` (`id`),
  ADD CONSTRAINT `fk_matos_verification` FOREIGN KEY (`verification_id`) REFERENCES `verification` (`id`);

--
-- Constraints for table `verification`
--
ALTER TABLE `verification`
  ADD CONSTRAINT `fk_verification_utilisateur1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
