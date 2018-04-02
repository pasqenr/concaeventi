SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

CREATE DATABASE IF NOT EXISTS `concaeventi_db` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `concaeventi_db`;

DROP TABLE IF EXISTS `Appartiene`;
CREATE TABLE `Appartiene` (
  `idUtente` int(11) NOT NULL,
  `idAssociazione` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `Associazione`;
CREATE TABLE `Associazione` (
  `idAssociazione` int(11) NOT NULL,
  `nomeAssociazione` varchar(512) NOT NULL,
  `logo` varchar(256) NOT NULL DEFAULT '',
  `telefono` varchar(15) NOT NULL DEFAULT '',
  `stile` varchar(7) NOT NULL DEFAULT '#000000'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `Evento`;
CREATE TABLE `Evento` (
  `idEvento` int(11) NOT NULL,
  `titolo` varchar(200) NOT NULL,
  `immagine` varchar(256) NOT NULL DEFAULT '',
  `descrizione` text NOT NULL,
  `istanteCreazione` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `istanteInizio` datetime NOT NULL,
  `istanteFine` datetime NOT NULL,
  `pagina` text NOT NULL,
  `revisionato` tinyint(1) NOT NULL DEFAULT '0',
  `idUtente` int(11) NOT NULL,
  `idAssPrimaria` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `Finanziamento`;
CREATE TABLE `Finanziamento` (
  `idEvento` int(11) NOT NULL,
  `idSponsor` int(11) NOT NULL,
  `importo` decimal(11,2) DEFAULT '0.00',
  `dataFinanziamento` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `Log`;
CREATE TABLE `Log` (
  `idLog` int(11) NOT NULL,
  `istanteCreazione` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `messaggio` text NOT NULL,
  `priorita` varchar(45) NOT NULL,
  `idUtente` int(11) NOT NULL,
  `livelloPriorita` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `Priorita`;
CREATE TABLE `Priorita` (
  `livello` int(11) NOT NULL,
  `nome` varchar(70) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `Proporre`;
CREATE TABLE `Proporre` (
  `idEvento` int(11) NOT NULL,
  `idAssociazione` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `Ruolo`;
CREATE TABLE `Ruolo` (
  `ruolo` varchar(70) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

TRUNCATE TABLE `Ruolo`;
INSERT INTO `Ruolo` (`ruolo`) VALUES
('Amministratore'),
('Direttore'),
('Editore'),
('Publisher');

DROP TABLE IF EXISTS `Sponsor`;
CREATE TABLE `Sponsor` (
  `idSponsor` int(11) NOT NULL,
  `nome` varchar(500) NOT NULL,
  `logo` varchar(300) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `Utente`;
CREATE TABLE `Utente` (
  `idUtente` int(11) NOT NULL,
  `nome` varchar(120) NOT NULL,
  `cognome` varchar(120) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(150) NOT NULL,
  `ruolo` varchar(70) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

TRUNCATE TABLE `Utente`;
INSERT INTO `Utente` (`idUtente`, `nome`, `cognome`, `password`, `email`, `ruolo`) VALUES
(1, 'Admin', 'Dev', '$2y$10$WhDD17jjna5nB0XST0j40OXokYa0S82l1DOgw9P7QSP2bNjMJLma2', 'mail@mail.com', 'Amministratore');


ALTER TABLE `Appartiene`
  ADD PRIMARY KEY (`idUtente`,`idAssociazione`),
  ADD KEY `fk_Evento_Utente1_idx` (`idUtente`) USING BTREE,
  ADD KEY `fk_Evento_AssPrimaria` (`idAssociazione`) USING BTREE;

ALTER TABLE `Associazione`
  ADD PRIMARY KEY (`idAssociazione`);

ALTER TABLE `Evento`
  ADD PRIMARY KEY (`idEvento`),
  ADD KEY `fk_Evento_Utente1_idx` (`idUtente`),
  ADD KEY `fk_Evento_AssPrimaria` (`idAssPrimaria`);

ALTER TABLE `Finanziamento`
  ADD PRIMARY KEY (`idEvento`,`idSponsor`),
  ADD KEY `fk_Finanziamento_Sponsor_idx` (`idSponsor`) USING BTREE,
  ADD KEY `fk_Finanziamento_Evento_idx` (`idEvento`) USING BTREE;

ALTER TABLE `Log`
  ADD PRIMARY KEY (`idLog`),
  ADD KEY `fk_Log_Priorita1_idx` (`livelloPriorita`),
  ADD KEY `idUtente` (`idUtente`);

ALTER TABLE `Priorita`
  ADD PRIMARY KEY (`livello`);

ALTER TABLE `Proporre`
  ADD PRIMARY KEY (`idEvento`,`idAssociazione`),
  ADD KEY `fk_Proporre_Associazione` (`idAssociazione`),
  ADD KEY `fk_Proporre_Evento_idx` (`idEvento`);

ALTER TABLE `Ruolo`
  ADD PRIMARY KEY (`ruolo`);

ALTER TABLE `Sponsor`
  ADD PRIMARY KEY (`idSponsor`);

ALTER TABLE `Utente`
  ADD PRIMARY KEY (`idUtente`),
  ADD UNIQUE KEY `email_UNIQUE` (`email`),
  ADD KEY `fk_Utente_Ruolo_idx` (`ruolo`);


ALTER TABLE `Associazione`
  MODIFY `idAssociazione` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `Evento`
  MODIFY `idEvento` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `Log`
  MODIFY `idLog` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `Sponsor`
  MODIFY `idSponsor` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `Utente`
  MODIFY `idUtente` int(11) NOT NULL AUTO_INCREMENT;


ALTER TABLE `Appartiene`
  ADD CONSTRAINT `fk_Appartiene_Associazione` FOREIGN KEY (`idAssociazione`) REFERENCES `Associazione` (`idAssociazione`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_Appartiene_Utente` FOREIGN KEY (`idUtente`) REFERENCES `Utente` (`idUtente`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `Evento`
  ADD CONSTRAINT `fk_Evento_AssPrimaria` FOREIGN KEY (`idAssPrimaria`) REFERENCES `Associazione` (`idAssociazione`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_Evento_Utente1` FOREIGN KEY (`idUtente`) REFERENCES `Utente` (`idUtente`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `Finanziamento`
  ADD CONSTRAINT `fk_Evento_has_Sponsor_Evento1` FOREIGN KEY (`idEvento`) REFERENCES `Evento` (`idEvento`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_Evento_has_Sponsor_Sponsor1` FOREIGN KEY (`idSponsor`) REFERENCES `Sponsor` (`idSponsor`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `Log`
  ADD CONSTRAINT `fk_Log_Priorita1` FOREIGN KEY (`livelloPriorita`) REFERENCES `Priorita` (`livello`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_Log_Utente` FOREIGN KEY (`idUtente`) REFERENCES `Utente` (`idUtente`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `Proporre`
  ADD CONSTRAINT `fk_Proporre_Associazione` FOREIGN KEY (`idAssociazione`) REFERENCES `Associazione` (`idAssociazione`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_Proporre_Evento` FOREIGN KEY (`idEvento`) REFERENCES `Evento` (`idEvento`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `Utente`
  ADD CONSTRAINT `fk_Utente_Ruolo` FOREIGN KEY (`ruolo`) REFERENCES `Ruolo` (`ruolo`) ON DELETE NO ACTION ON UPDATE NO ACTION;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
