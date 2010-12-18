<?php
$environment = sfConfig::get('sf_environment');

/*
 * configure environment 'remote' path for EXTJS
 */

if($environment=='remote'){
    sfConfig::set('sf_extjs3_js_dir', 'http://extjs.cachefly.net/ext-3.0.0/');
    sfConfig::set('sf_extjs3_css_dir', 'http://extjs.cachefly.net/ext-3.0.0/resources/css/');
    sfConfig::set('sf_extjs3_images_dir', 'http://extjs.cachefly.net/ext-3.0.0/resources/images/');
}

?>
