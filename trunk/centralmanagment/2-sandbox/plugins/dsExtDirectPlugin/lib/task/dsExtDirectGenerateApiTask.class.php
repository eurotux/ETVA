<?php

/**
 * This file is part of the dsExtDirectPlugin
 *
 * @package   dsExtDirectPlugin
 * @author    Daniel Stevens <danhstevens@gmail.com>
 * @copyright Copyright (c) 2009, Daniel Stevens
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 * @version   SVN: $Id$
 */

/**
 * dsExtDirectGenerateApiTask generates the API Spec JS for Ext.Direct
 *
 * @package    dsExtDirectPlugin
 * @author     Daniel Stevens <danhstevens@gmail.com>
 */

class dsExtDirectGenerateApiTask extends sfGeneratorBaseTask
{

  /**
   * The default environment
   *
   * @var string
   */
  const DEFAULT_ENVIRONMENT = 'extdirect';
  
  /**
   * @see sfTask
   */
  protected function configure()
  {
    $this->namespace        = 'extdirect';
    $this->name             = 'generate-api';
    $this->briefDescription = 'Generates an Ext.Direct js spec file from your marked module actions';
    $this->detailedDescription = <<<EOF
The [extdirect:generate-api|INFO] task generates an Ext.Direct js spec file and from your marked module actions to expose them as a Ext.Direct api method.
Call it with:

  [./symfony extdirect:generate-api yourapp|INFO]

The js file will be created in the [web/js|COMMENT] directory:

  [web/js/extdirect_api.js|INFO]
  
And a yml file will be created in the [apps/yourapp/config|COMMENT] directory:

  [apps/yourapp/config/extdirect_api.yml|INFO]

This task also creates a front controller script in the [web/|COMMENT] directory:

  [web/extdirect.php|INFO]

You can change the name of your environment, front controller, js & yml files by using the [environment|COMMENT] option:

  [./symfony extdirect:generate-api yourapp --environment extdirect|INFO]

  or

  [./symfony extdirect:generate-api yourapp -e extdirect|INFO]

You can enable debugging for the controller by using the [enabledebug|COMMENT] option:

  [./symfony extdirect:generate-api yourapp --enabledebug|INFO]

  or

  [./symfony extdirect:generate-api yourapp -d|INFO]

EOF;

    $this->addArgument('application', sfCommandArgument::REQUIRED, 'The application name');

    $this->addOption('environment', 'e', sfCommandOption::PARAMETER_REQUIRED, 'The environment to use for webservice mode', self::DEFAULT_ENVIRONMENT);
    $this->addOption('enabledebug', 'd', sfCommandOption::PARAMETER_NONE, 'Enables debugging in generated controller');
  }
  
  protected function execute($arguments = array(), $options = array())
  {
    $app  = $arguments['application'];
    $env  = $options['environment'];
    $dbg  = $options['enabledebug'];
    $file = $env;
    
    $this->buildControllerFile($file, $app, $env, $dbg);
    
    $api = array();
    foreach(array_merge($this->getModules(),$this->getPluginModules()) as $module)
    {
      if($this->loadModuleClassFile($module))
      { 
        //now we will use the actual module name as $module instead of its abs path
        $module = substr(strrchr($module,'/'),1);
        
        $class = new ReflectionClass($module.'Actions');
        $class_comments = $class->getDocComment();
        
        foreach($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method)
        {
          $method_comments = $method->getDocComment();
          
          if(strpos($method_comments, '@extdirect-enable') !== false)
          {
            //Get custom action name, if any
            preg_match('/@extdirect-action (?P<action>[_\\w]*)/', $class_comments, $action_name);
            $action_name = isset($action_name['action']) && !empty($action_name['action']) ? $action_name['action'] : $module;
            
            //Get custom method name, if any
            $real_method_name = $this->getShortMethodName($method->getName());
            preg_match('/@extdirect-method (?P<method>[_\\w]*)/', $method_comments, $method_name);
            $method_name = isset($method_name['method']) && !empty($method_name['method']) ? $method_name['method'] : $real_method_name;
            
            preg_match('/@extdirect-len (?P<len>\\d*)/', $method_comments, $method_len);
            $method_len = isset($method_len['len']) && !empty($method_len['len']) ? $method_len['len'] : 0;
            
            $method_fh = strpos($method_comments, '@extdirect-formhandler') !== false ? true : false;
            
            $method_def = array('len' => $method_len, 'formHandler' => $method_fh);
            
            if(!isset($api[$action_name]))
            {
              // 'action' value in array equals the real name of the action ($module) since $action_name can be custom
              $api[$action_name] = array(
              	'action' => $module,
              	'methods' => array($method_name => $method_def),
                'method_map' => array($method_name => $real_method_name));//Maps custom method name to real method name
            }
            else 
            {
              $api[$action_name]['methods'][$method_name] = $method_def;
              $api[$action_name]['method_map'][$method_name] = $real_method_name;
            }
          }
        }
      }
    }
    
    $this->buildApiYaml($file, $api);
    $this->buildApiJs($file, $api);
  }
  
  protected function buildControllerFile($controller, $application, $environment, $debug)
  {
    $template = sfConfig::get('sf_symfony_lib_dir').'/task/generator/skeleton/app/web/index.php';
    $pathname = sprintf('%s/%s.php', sfConfig::get('sf_web_dir'), $controller);

    if(file_exists($pathname))
    {
      $this->getFilesystem()->remove($pathname);
    }
    $this->getFilesystem()->copy($template, $pathname);
    $this->getFilesystem()->replaceTokens($pathname, '##', '##', array(
      'IP_CHECK'    => '',
      'APP_NAME'    => $application,
      'ENVIRONMENT' => $environment,
      'IS_DEBUG'    => $debug ? 'true' : 'false',
    ));
  }
  
  protected function buildApiYaml($file, $api)
  {
    $head = "# DO NOT MODIFY\n# Generated by extdirect:generate-api task\n";
    $path = sfConfig::get('sf_app_config_dir').'/'.$file.'_api.yml';
    file_put_contents($path, $head.sfYaml::dump($api));
    $this->logSection('extdirect', 'Generated YAML API Spec: '.$path);
  }
  
  protected function buildApiJs($file, $api)
  {
    $actions = array();
    
    foreach($api as $actionName => $action)
    {
      $methods = array();
      
      foreach ($action['methods'] as $methodName => $method)
      {
        $methodDef = array(
          'name' => $methodName,
          'len' => isset($method['len']) ? (int)$method['len'] : 0
        );
        
        if(isset($method['formHandler']) && $method['formHandler'])
        {
          $methodDef['formHandler'] = true;
        }
        
        $methods[] = $methodDef;
      }
      $actions[$actionName] = $methods;
    }
    
    $config = array(
      'url' => sfConfig::get('app_ds_ext_direct_plugin_router_url', "/$file.php/dsExtDirect/router"),
      'type' => sfConfig::get('app_ds_ext_direct_plugin_provider_type', 'remoting'),
      'actions' => $actions
    );
    
    $js = sfConfig::get('app_ds_ext_direct_plugin_js_var', 'Ext.app.'.strtoupper($file).'_API') . ' = ' . json_encode($config) . ';';
    
    $path = sfConfig::get('sf_web_dir').'/js/'.$file.'_api.js';
    file_put_contents($path, $js);
    $this->logSection('extdirect', 'Generated JS API Spec: '.$path);
  }
  
  protected function getModules()
  {
    return sfFinder::type('directory')->name('*')->maxdepth(0)->in(sfConfig::get('sf_app_module_dir'));
  }
  
  protected function getPluginModules()
  {
    $plugins = sfFinder::type('directory')->name('*')->maxdepth(0)->in(sfConfig::get('sf_plugins_dir'));
    $modules = array();

    foreach($plugins as $plugin)
    {
      $modules = array_merge($modules,sfFinder::type('directory')->name(sfConfig::get('sf_enabled_modules'))->maxdepth(0)->in($plugin.'/modules'));
    }

    return $modules;
  }
  
  protected function loadModuleClassFile($module)
  {
    $module_classfile = $module.'/actions/actions.class.php';

    return file_exists($module_classfile) && !preg_match('/class(.*)Actions(.*)extends(.*)auto/', file_get_contents($module_classfile)) ? require_once($module_classfile) : false;
  }
  
  protected function loadModuleConfigFile($module)
  {
    $module_configfile = $this->getModuleConfigFilePath($module);

    return file_exists($module_configfile) ? sfYaml::load($module_configfile) : array();
  }
  
  protected function getModuleConfigFilePath($module)
  {
    return sfConfig::get('sf_app_module_dir').'/'.$module.'/config/module.yml';
  }
  
  protected function getShortMethodName($method_name)
  {
    if(strpos($method_name, 'execute') === 0)
    {
      $method_name = substr($method_name, 7);
    }
    
    return strtolower($method_name{0}).substr($method_name, 1);
  }
}

?>