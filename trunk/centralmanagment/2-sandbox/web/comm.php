<?php
require_once(dirname(__FILE__).'/../config/ProjectConfiguration.class.php');

/*
 * this environment 'comm' is used on appliance restore process to check state
 */
$configuration = ProjectConfiguration::getApplicationConfiguration('app', 'comm', true);
sfContext::createInstance($configuration)->dispatch();