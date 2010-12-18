<?php

require_once(dirname(__FILE__).'/../config/ProjectConfiguration.class.php');

/*
 * this environment 'remote' uses remote EXTJS lib from cachefly defined in view config
 */
$configuration = ProjectConfiguration::getApplicationConfiguration('app', 'remote', true);
sfContext::createInstance($configuration)->dispatch();
