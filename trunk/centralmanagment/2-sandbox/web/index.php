<?php


require_once(dirname(__FILE__).'/../config/ProjectConfiguration.class.php');

$configuration = ProjectConfiguration::getApplicationConfiguration('app', 'prod', false);
$configCache = new sfConfigCache($configuration);
include($configCache->checkConfig('config/config.yml'));
sfContext::createInstance($configuration)->dispatch();
