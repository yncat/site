USE `actlab`;

DROP TABLE IF EXISTS `informations`;
CREATE TABLE `informations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` char(255) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `url` char(255) DEFAULT NULL,
  `flag` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4;
INSERT INTO `informations` VALUES (1,'test','2020-01-01',NULL,0);

DROP TABLE IF EXISTS `members`;
CREATE TABLE `members` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` char(32) NOT NULL,
  `email` char(255) DEFAULT NULL,
  `password_hash` char(255) DEFAULT NULL,
  `introduction` text,
  `twitter` char(32) DEFAULT NULL,
  `url` char(255) DEFAULT NULL,
  `github` char(32) DEFAULT NULL,
  `updated` int(10) unsigned NOT NULL DEFAULT '0',
  `flag` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;

INSERT INTO `members` VALUES (1,'test','test@test.test','$2y$10$FHkVgcgYCiGHjygZkHxX/epO.hrDIoe275./.nChMr8sJZx9wYtwK','test cat!',NULL,NULL,'yncat',1593933185,0);

DROP TABLE IF EXISTS `software_versions`;
CREATE TABLE `software_versions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `software_id` int(10) unsigned NOT NULL,
  `major` tinyint(4) NOT NULL,
  `minor` tinyint(4) NOT NULL,
  `patch` tinyint(4) NOT NULL,
  `hist_text` text NOT NULL,
  `package_URL` char(255) DEFAULT NULL,
  `updater_URL` char(255) DEFAULT NULL,
  `updater_hash` char(128) DEFAULT NULL,
  `update_min_Major` tinyint(4) DEFAULT NULL,
  `update_min_minor` tinyint(4) DEFAULT NULL,
  `released_at` date DEFAULT NULL,
  `flag` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `software_id` (`software_id`),
  CONSTRAINT `software_versions_ibfk_1` FOREIGN KEY (`software_id`) REFERENCES `softwares` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4;

INSERT INTO `software_versions` VALUES (1,1,0,0,0,'first version','https://',NULL,NULL,NULL,NULL,'2020-01-01',0);

DROP TABLE IF EXISTS `softwares`;
CREATE TABLE `softwares` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` char(255) DEFAULT NULL,
  `keyword` char(32) DEFAULT NULL,
  `description` text NOT NULL,
  `features` text,
  `gitHubURL` char(255) NOT NULL,
  `snapshotURL` char(255) DEFAULT NULL,
  `staff` int(10) unsigned NOT NULL,
  `flag` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `keyword` (`keyword`),
  KEY `staff` (`staff`),
  CONSTRAINT `softwares_ibfk_1` FOREIGN KEY (`staff`) REFERENCES `members` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;

INSERT INTO `softwares` VALUES (1,'test','TES','just test','test\\ttest is awesome, cats are cute!','actlaboratory/site','https://github.com/actlaboratory/site',1,0);

DROP TABLE IF EXISTS `updaterequests`;
CREATE TABLE `updaterequests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `requester` int(10) unsigned NOT NULL,
  `type` char(32) NOT NULL,
  `identifier` char(32) DEFAULT NULL,
  `value` mediumblob NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;
