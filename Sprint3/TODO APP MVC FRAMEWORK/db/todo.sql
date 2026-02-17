-- Create and use the todo database
CREATE DATABASE IF NOT EXISTS `todo`;
USE `todo`;

-- Items table
CREATE TABLE IF NOT EXISTS `items` (
  `id`        int(11)      NOT NULL AUTO_INCREMENT,
  `item_name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Seed data
INSERT INTO `items` (`id`, `item_name`) VALUES
(1, 'Get Milk'),
(2, 'Buy Application');
