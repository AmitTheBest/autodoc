CREATE TABLE `sourcefile` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `last_imported` date NOT NULL,
  `contents` text COLLATE utf8_unicode_ci NOT NULL,
  `doc_location` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `sourcefile` (`id`, `file`, `last_imported`, `contents`, `doc_location`) VALUES
(1, 'SQL/Model', '2014-08-01', '', 'model/sql'),
(2, 'Lister', '0000-00-00', '', 'views/lister');
