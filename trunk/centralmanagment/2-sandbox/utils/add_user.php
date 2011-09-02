<?php
/*
 * script to add etva ftp user
 * rewrites app/config/config.yml
 * executes adduser.sh
 */

require_once(dirname(__FILE__).'/../config/ProjectConfiguration.class.php');

$configuration = ProjectConfiguration::getApplicationConfiguration('app', 'prod', false);

$configCache = new sfConfigCache($configuration);
include($configCache->checkConfig('config/config.yml'));

$config_yml = sfYaml::load(sfConfig::get('sf_app_config_dir').'/config.yml');

$etva_user = sfConfig::get('config_isos_user');
$etva_homedir = sfConfig::get('config_isos_dir');

//generate random password and use it as input to create ftp system account
$gen_password = exec("sh ".dirname(__FILE__)."/genpwd.sh",$outgen,$status);

if($status==0){

    $config_yml['all']['isos']['password'] = $gen_password;

    $config_dump = sfYaml::dump($config_yml,3);

    exec("sh ".dirname(__FILE__)."/adduser.sh $etva_user $gen_password $etva_homedir",$output,$status);
    
    //if all went ok put password in config.yml file
    if($status==0) file_put_contents(sfConfig::get('sf_app_config_dir').'/config.yml', $config_dump);

    foreach($output as $outline) echo($outline."\n");

}else foreach($outgen as $outline) echo($outline."\n");
?>
