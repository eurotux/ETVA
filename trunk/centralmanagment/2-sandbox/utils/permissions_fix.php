<?php
require_once(dirname(__FILE__).'/../config/ProjectConfiguration.class.php');

$configuration = ProjectConfiguration::getApplicationConfiguration('app', 'dev', true);

//fix RRA dir permissions
$rra_dir = sfConfig::get("app_rra_dir");

if(is_dir($rra_dir)){
    //chmod
    chmod(sfConfig::get('app_rra_dir'), 0777);
}else{
    mkdir(sfConfig::get('app_rra_dir'));
    chmod(sfConfig::get('app_rra_dir'), 0777);

}



//isos fix

$isos_dir = sfConfig::get("config_isos_dir");

if(is_dir($isos_dir)){
    //chmod
    chmod(sfConfig::get('config_isos_dir'), 0777);
}else{
    mkdir(sfConfig::get('config_isos_dir'));
    chmod(sfConfig::get('config_isos_dir'), 0777);

}


?>
