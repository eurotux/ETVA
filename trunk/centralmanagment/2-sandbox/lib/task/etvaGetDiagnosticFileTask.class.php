<?php

//require_once(dirname(__FILE__).'/../config/ProjectConfiguration.class.php');

class etvaGetDiagnosticFileTask extends sfBaseTask
{
  protected function configure()
  {
    // // add your own arguments here
    // $this->addArguments(array(
    //   new sfCommandArgument('my_arg', sfCommandArgument::REQUIRED, 'My argument'),
    // ));
    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'prod'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
      //new sfCommandOption('cluster_id', null, sfCommandOption::PARAMETER_OPTIONAL, 'The cluster id, to associate networks'),
      // add your own options here
    ));

    $this->namespace        = 'etva';
    $this->name             = 'vadiagnostic';
    $this->briefDescription = 'Load to DB some sysconfig data';
    $this->detailedDescription = <<<EOF
The [etva:getVADiagnostic|INFO] task does things.
Call it with:

  [php symfony etva:getVADiagnostic|INFO]
EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {   
    // initialize the database connection
    $databaseManager = new sfDatabaseManager($this->configuration);
    $con = $databaseManager->getDatabase($options['connection'])->getConnection();
    $this->log("[INFO] ".date("d/m/y : H:i:s",time()));

    $context = sfContext::createInstance(sfProjectConfiguration::getApplicationConfiguration('app','dev',true));
    $response = diagnostic::getAgentFiles('diagnostic');
    $this->log("[INFO] ".date("d/m/y : H:i:s",time()));
    return;
  }
}
