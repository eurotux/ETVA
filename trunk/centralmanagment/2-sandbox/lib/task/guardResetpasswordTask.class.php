<?php

class guardResetpasswordTask extends sfBaseTask
{
  protected function configure()
  {
    // // add your own arguments here
    $this->addArguments(array(
      new sfCommandArgument('username', sfCommandArgument::REQUIRED, 'The user name'),
      new sfCommandArgument('password', sfCommandArgument::OPTIONAL, 'The password'),
    ));

    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
      // add your own options here
    ));

    $this->namespace        = 'guard';
    $this->name             = 'reset-password';
    $this->briefDescription = 'Reset user password';
    $this->detailedDescription = <<<EOF
The [guard:reset-password|INFO] task does things.
Call it with:

  [php symfony guard:reset-password|INFO]
EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {
    // initialize the database connection
    $databaseManager = new sfDatabaseManager($this->configuration);
    $connection = $databaseManager->getDatabase($options['connection'])->getConnection();

    // add your code here

    $user = sfGuardUserPeer::retrieveByUsername($arguments['username']);
    if (!$user)
    {
      throw new sfCommandException(sprintf('User "%s" does not exist.', $arguments['username']));
    }

    $password = $arguments['password'];
    if (!$password)
    {
      //generate random password and use it as input to create ftp system account
      $password = exec("sh ".dirname(__FILE__)."/../../utils/genpwd.sh",$outgen,$status);
    }

    $user->setPassword($arguments['password']);
    $user->save();

    $this->logSection('guard', sprintf('Reset user "%s" password to "%s"', $arguments['username'], $password));
  }
}
