USE asterisk;

-- INSERT DISA outbound_routes

INSERT INTO outbound_routes (route_id,name) VALUES (0,'DISA');

-- Create external to internal numbers

CREATE TABLE `etux_ext2int` (
  `phone` varchar(80) NOT NULL,
  `extension` varchar(80) NOT NULL,
  `start` datetime NOT NULL,
  `end` datetime NOT NULL,
  `descr` varchar(80) NOT NULL,
  KEY `phone` (`phone`,`start`,`end`)
);

CREATE TABLE `etux_extnumbers` (
  `phone` varchar(80) NOT NULL,
  `active` int(1) NOT NULL,
  KEY `phone` (`phone`)
);

CREATE TABLE `etux_ext2intlog` (
  `phone` varchar(80) NOT NULL,
  `extension` varchar(80) NOT NULL,
  `start` datetime NOT NULL,
  `end` datetime NOT NULL,
  `created` datetime NOT NULL,
  `operation` varchar(80) NOT NULL,
  `user` varchar(80) NOT NULL,
  KEY `phone` (`phone`)
);

CREATE DATABASE sap2elastix;

USE sap2elastix;

CREATE TABLE `contacts` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `client_id` varchar(15) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `name` varchar(255) NOT NULL,
  `callerid` varchar(50) NOT NULL,
  PRIMARY KEY  (`id`)
);

USE asteriskcdrdb;

CREATE TABLE `etux_callentry` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `extension` varchar(80) NOT NULL,
  `phone` varchar(80) NOT NULL,
  `created` datetime NOT NULL,
  `status` varchar(50) NOT NULL,
  `agendaid` varchar(50) NOT NULL,
  `uniqueid` varchar(50) NOT NULL,
  PRIMARY KEY  (`id`)
);
ALTER TABLE etux_callentry ADD INDEX (created);
ALTER TABLE etux_callentry ADD INDEX (uniqueid);

CREATE TABLE `etux_missedcalls` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `phone` varchar(80) NOT NULL,
  `start_date` datetime NOT NULL,
  `status` varchar(50) NOT NULL,
  `uniqueid` varchar(50) NOT NULL,
  `queue` varchar(50) NOT NULL,
  `duration_wait` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
);
ALTER TABLE etux_missedcalls ADD INDEX (start_date);
ALTER TABLE etux_missedcalls ADD INDEX (uniqueid);

