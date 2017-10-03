-- MySQL dump 10.16  Distrib 10.1.26-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: test_noi_2_db
-- ------------------------------------------------------
-- Server version	10.1.26-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

DROP DATABASE IF EXISTS `concaeventi_test_db`;
CREATE DATABASE IF NOT EXISTS `concaeventi_test_db` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `concaeventi_test_db`;

--
-- Table structure for table `Appartiene`
--

DROP TABLE IF EXISTS `Appartiene`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Appartiene` (
  `idUtente` int(11) NOT NULL,
  `idAssociazione` int(11) NOT NULL,
  PRIMARY KEY (`idUtente`,`idAssociazione`),
  KEY `fk_Evento_Utente1_idx` (`idUtente`) USING BTREE,
  KEY `fk_Evento_AssPrimaria` (`idAssociazione`) USING BTREE,
  CONSTRAINT `fk_Appartiene_Associazione` FOREIGN KEY (`idAssociazione`) REFERENCES `Associazione` (`idAssociazione`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_Appartiene_Utente` FOREIGN KEY (`idUtente`) REFERENCES `Utente` (`idUtente`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Appartiene`
--

LOCK TABLES `Appartiene` WRITE;
/*!40000 ALTER TABLE `Appartiene` DISABLE KEYS */;
INSERT INTO `Appartiene` (`idUtente`, `idAssociazione`) VALUES ('1', '1'), ('2', '1');
/*!40000 ALTER TABLE `Appartiene` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Associazione`
--

DROP TABLE IF EXISTS `Associazione`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Associazione` (
  `idAssociazione` int(11) NOT NULL AUTO_INCREMENT,
  `nomeAssociazione` varchar(512) NOT NULL,
  `logo` varchar(256) NOT NULL DEFAULT '',
  `telefono` varchar(15) NOT NULL DEFAULT '',
  `stile` varchar(7) NOT NULL DEFAULT '#000000',
  PRIMARY KEY (`idAssociazione`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Associazione`
--

LOCK TABLES `Associazione` WRITE;
/*!40000 ALTER TABLE `Associazione` DISABLE KEYS */;
INSERT INTO `Associazione` VALUES (1,'Comune','','','#000000'),(2,'Circolo NOI','','','#ff5c00'),(3,'Pro Loco','','','');
/*!40000 ALTER TABLE `Associazione` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Evento`
--

DROP TABLE IF EXISTS `Evento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Evento` (
  `idEvento` int(11) NOT NULL AUTO_INCREMENT,
  `titolo` varchar(200) NOT NULL,
  `immagine` varchar(256) NOT NULL DEFAULT '',
  `descrizione` text NOT NULL,
  `istanteCreazione` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `istanteInizio` datetime NOT NULL,
  `istanteFine` datetime NOT NULL,
  `pagina` text NOT NULL,
  `revisionato` tinyint(1) NOT NULL DEFAULT '0',
  `idUtente` int(11) NOT NULL,
  `idAssPrimaria` int(11) NOT NULL,
  PRIMARY KEY (`idEvento`),
  KEY `fk_Evento_Utente1_idx` (`idUtente`),
  KEY `fk_Evento_AssPrimaria` (`idAssPrimaria`),
  CONSTRAINT `fk_Evento_AssPrimaria` FOREIGN KEY (`idAssPrimaria`) REFERENCES `Associazione` (`idAssociazione`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_Evento_Utente1` FOREIGN KEY (`idUtente`) REFERENCES `Utente` (`idUtente`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Finanziamento`
--

DROP TABLE IF EXISTS `Finanziamento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Finanziamento` (
  `idEvento` int(11) NOT NULL,
  `idSponsor` int(11) NOT NULL,
  `importo` decimal(11,2) DEFAULT '0.00',
  `dataFinanziamento` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idEvento`,`idSponsor`),
  KEY `fk_Finanziamento_Sponsor_idx` (`idSponsor`) USING BTREE,
  KEY `fk_Finanziamento_Evento_idx` (`idEvento`) USING BTREE,
  CONSTRAINT `fk_Evento_has_Sponsor_Evento1` FOREIGN KEY (`idEvento`) REFERENCES `Evento` (`idEvento`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_Evento_has_Sponsor_Sponsor1` FOREIGN KEY (`idSponsor`) REFERENCES `Sponsor` (`idSponsor`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Log`
--

DROP TABLE IF EXISTS `Log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Log` (
  `idLog` int(11) NOT NULL AUTO_INCREMENT,
  `istanteCreazione` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `messaggio` text NOT NULL,
  `priorita` varchar(45) NOT NULL,
  `idUtente` int(11) NOT NULL,
  `livelloPriorita` int(11) NOT NULL,
  PRIMARY KEY (`idLog`),
  KEY `fk_Log_Priorita1_idx` (`livelloPriorita`),
  KEY `idUtente` (`idUtente`),
  CONSTRAINT `fk_Log_Priorita1` FOREIGN KEY (`livelloPriorita`) REFERENCES `Priorita` (`livello`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_Log_Utente` FOREIGN KEY (`idUtente`) REFERENCES `Utente` (`idUtente`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Log`
--

LOCK TABLES `Log` WRITE;
/*!40000 ALTER TABLE `Log` DISABLE KEYS */;
/*!40000 ALTER TABLE `Log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Priorita`
--

DROP TABLE IF EXISTS `Priorita`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Priorita` (
  `livello` int(11) NOT NULL,
  `nome` varchar(70) NOT NULL,
  PRIMARY KEY (`livello`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Priorita`
--

LOCK TABLES `Priorita` WRITE;
/*!40000 ALTER TABLE `Priorita` DISABLE KEYS */;
INSERT INTO `Priorita` VALUES (1,'Critica'),(2,'Alta'),(3,'Normale'),(4,'Bassa');
/*!40000 ALTER TABLE `Priorita` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Proporre`
--

DROP TABLE IF EXISTS `Proporre`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Proporre` (
  `idEvento` int(11) NOT NULL,
  `idAssociazione` int(11) NOT NULL,
  PRIMARY KEY (`idEvento`,`idAssociazione`),
  KEY `fk_Proporre_Associazione` (`idAssociazione`),
  KEY `fk_Proporre_Evento_idx` (`idEvento`),
  CONSTRAINT `fk_Proporre_Associazione` FOREIGN KEY (`idAssociazione`) REFERENCES `Associazione` (`idAssociazione`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_Proporre_Evento` FOREIGN KEY (`idEvento`) REFERENCES `Evento` (`idEvento`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Ruolo`
--

DROP TABLE IF EXISTS `Ruolo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Ruolo` (
  `ruolo` varchar(70) NOT NULL,
  PRIMARY KEY (`ruolo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Ruolo`
--

LOCK TABLES `Ruolo` WRITE;
/*!40000 ALTER TABLE `Ruolo` DISABLE KEYS */;
INSERT INTO `Ruolo` VALUES ('Amministratore'),('Direttore'),('Editore'),('Publisher');
/*!40000 ALTER TABLE `Ruolo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Sponsor`
--

DROP TABLE IF EXISTS `Sponsor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Sponsor` (
  `idSponsor` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(500) NOT NULL,
  `logo` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`idSponsor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Utente`
--

DROP TABLE IF EXISTS `Utente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Utente` (
  `idUtente` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(120) NOT NULL,
  `cognome` varchar(120) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(150) NOT NULL,
  `ruolo` varchar(70) NOT NULL,
  PRIMARY KEY (`idUtente`),
  UNIQUE KEY `email_UNIQUE` (`email`),
  KEY `fk_Utente_Ruolo_idx` (`ruolo`),
  CONSTRAINT `fk_Utente_Ruolo` FOREIGN KEY (`ruolo`) REFERENCES `Ruolo` (`ruolo`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Utente`
--

LOCK TABLES `Utente` WRITE;
/*!40000 ALTER TABLE `Utente` DISABLE KEYS */;
INSERT INTO `Utente` VALUES (1,'Utente','Test','$2y$10$6xlMVGNjARLKHAylFM/l9.dkMrDYPhR9BbEDgl1lMDaCodCXatOyK','mail@mail.com','Direttore'),
  (2, 'Pinco', 'Pallino', '$2y$10$X9VFzKsHcD6hD7vL1qZW/ueHVyFPH3Ttxmr5I.hc4EsLePuHEBEbO', 'mail2@mail.com', 'Editore');
/*!40000 ALTER TABLE `Utente` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-09-26 14:59:42
