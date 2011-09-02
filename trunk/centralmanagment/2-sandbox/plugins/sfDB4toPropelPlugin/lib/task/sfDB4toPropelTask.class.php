<?php

/**
 * Task Transforms the DB Designer4 XML schema to a valid Propel schema.xml file.
 * 
 * Automatically transforms:
 *  - boolean
 *  - i18n tables
 *  - object name (table comment in the db4 application)
 *  - database Propel name (model name in the model options of db4)
 *  - model package directory
 * 
 * @author  loic.vernet - COil - <loic@sensio.com> 
 * @since   1.0.0 - 30 july 08
 */

class sfDB4toPropelTask extends sfBaseTask
{
  // Default env options
  const DEFAULT_ENV_OPTION        = 'cli';
  const DEFAULT_DEBUG_OPTION      = true;

  // Default task options
  const DEFAULT_FILE_DIR_OPTION        = '/doc/database';
  const DEFAULT_FILE_OPTION            = 'db4.xml';
  const DEFAULT_OUTPUT_OPTION          = 'schema';
  const DEFAULT_OUTPUT_DIR_OPTION      = '/config';
  const DEFAULT_PACKAGE_OPTION         = 'lib.model';
  const DEFAULT_EXTERNAL_TABLES_OPTION = '';

  /**
   * @see sfTask
   */
  protected function configure()
  {
    $this->addArguments(array(
      new sfCommandArgument('application', sfCommandArgument::REQUIRED, 'The application name'),
    ));
    
    $this->addOptions(array(
      new sfCommandOption('env',        null, sfCommandOption::PARAMETER_OPTIONAL, 'The environment name', self::DEFAULT_ENV_OPTION),
      new sfCommandOption('debug',      null, sfCommandOption::PARAMETER_OPTIONAL, 'Enable debug', self::DEFAULT_DEBUG_OPTION),
      new sfCommandOption('file_dir',   null, sfCommandOption::PARAMETER_OPTIONAL, 'The base path for the db4 schema', self::DEFAULT_FILE_DIR_OPTION),
      new sfCommandOption('file',       null, sfCommandOption::PARAMETER_OPTIONAL, 'The name of the db4 file', self::DEFAULT_FILE_OPTION),
      new sfCommandOption('output',     null, sfCommandOption::PARAMETER_OPTIONAL, 'Output base path', self::DEFAULT_OUTPUT_OPTION),
      new sfCommandOption('output_dir', null, sfCommandOption::PARAMETER_OPTIONAL, 'Output base file name, to have schema_client.yml, put "schema_client" for this parameter', self::DEFAULT_OUTPUT_DIR_OPTION),
      new sfCommandOption('package',    null, sfCommandOption::PARAMETER_OPTIONAL, 'Package to use if you want to store model files in another directory than lib.model', self::DEFAULT_PACKAGE_OPTION),
      new sfCommandOption('external_tables', null, sfCommandOption::PARAMETER_OPTIONAL, 'Coma separated list of tables that will be excluded from the final generated schema, useful for using FK to a plugin schema table for exemple', self::DEFAULT_EXTERNAL_TABLES_OPTION),
    ));

    $this->namespace = 'propel';
    $this->name = 'db4-to-propel';
    $this->briefDescription = 'Transforms a DB Designer4 XML schema to a valid Propel schema file';

    $this->detailedDescription = <<<EOF
The [propel:db4-to-propel|INFO] transforms a DB Designer4 XML schema to a valid Propel schema file.

All parameters are optionals. Path are relative from the project root directory.

  Quick version:
  --> [./symfony propel:db4-to-propel frontend|INFO]
  
  Full version:
  --> [./symfony propel:db4-to-propel frontend --env=cli --debug=1 --file_dir=/doc/database --file=db4.xml --output_dir=/config --output=schema --package=lib.model.forums --external_tables=sf_guard_user|INFO]

EOF;
  }

  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    // Chek parameters
    $this->setClassOptions($arguments, $options);
    $this->checkParameters();
    
    // 0 - Start
    $this->logSection('START');
    $message = ' - Start time: '. $this->getCurrentDateTime();
    $this->log($message);

    // 1 - Convert schema to xml
    $this->doConvertSchema();

    // 2 - Write new shema.xml
    $this->writeSchema();
        
    // 3 - Convert to yml
    $this->convertSchemaToYml();
    
    // 4 - End
    $this->logSection('END');
    $message = ' - End time: '. $this->getCurrentDateTime();
    $this->log($message);
  }

  /**
   * Check parameters and raise at the first incorrect one.
   * 
   * @return Exception
   */
  protected function checkParameters($arguments = array(), $options = array())
  {
    // Check DB4 input file
    $this->file_path = sfConfig::get('sf_root_dir'). $this->file_dir. '/'. $this->file;
    
    if (!file_exists($this->file_path) || !is_readable($this->file_path))
    {
      throw new InvalidArgumentException(sprintf('The db4 file can\'t be found at %s, please check the path and correct the file_dir and file options.', $this->file_path));
    }

    // Check xlst
    if (!class_exists('xsltprocessor'))
    {
      throw new sfException('PHP XSL extension must be installed and enabled on your system to use this task.');
    }
  }

  /**
   * Transforms the DB4 schema to a Propel one.
   */
  function doConvertSchema()
  {    
    // XML  
    $xml = new DomDocument();
    $xml->loadXML(file_get_contents($this->file_path));
    
    // DB4 xsl
    $db4_transformation_file = dirname(__FILE__). '/../vendor/db2/dbd2propel.xsl';
    $xsl = new DomDocument();
    $xsl->load($db4_transformation_file);
    
    // Attach the xsl rules
    $proc = new xsltprocessor();
    $proc->importStyleSheet($xsl);        
    $xmlstr = $proc->transformToXML($xml);
    
    // Automatised i18n
    $i18nlist = array();
    preg_match_all('#\<table name=\"(.+)_i18n\"#', $xmlstr, $i18nlist);
  
    if (is_array($i18nlist) && isset($i18nlist[1]) && is_array($i18nlist[1]))
    {
      foreach ($i18nlist[1] as $tableName)
      {
        $xmlstr = str_replace(
          '<table name="'.$tableName.'"',
          '<table name="'.$tableName.'" isI18N="true" i18nTable="'.$tableName.'_i18n"',
          $xmlstr
        );
      }
    }

    // Others replacments
    $xmlstr = str_replace(
      array (
      // Add culture attribute    
      'name="culture"',

      // Model package
      'package="lib.model"'
      ),
      array (
        'name="culture" isCulture="true"',
        sprintf('package="%s"', $this->package),
      ),
      $xmlstr
    );

    // Delete external tables
    if (!empty($this->external_tables))
    {
      $this->external_tables_array = explode(',' , $this->external_tables);

      $this->log(' - Removing external tables');
      foreach ($this->external_tables_array as $external_table)
      {
        $this->log('   > '. $external_table);
        $reg_exp = '/(<table name="'. trim($external_table). ')(((.)*(\s)*)*?)(<\/table>)/';
        $xmlstr = preg_replace($reg_exp, '', $xmlstr);
      }
    }

    // Xml file save
    $this->xmlstr = $xmlstr;
    $this->output_path = sfConfig::get('sf_root_dir'). $this->output_dir. '/'. $this->output. '.xml'; 
  }

  /**
   * Save xml schema.
   */
  public function writeSchema()
  {
    $filesystem = $this->getFilesystem();
    $filesystem->touch($this->output_path);
    @file_put_contents($this->output_path, $this->xmlstr);
    $this->log(' - Database xml schema saved.');
  }

  /**
   * Transform xml schema to yml schema and remove xml one.
   */
  protected function convertSchemaToYml()
  {
    // Don't convert if the output file is not /config
    if ($this->output_dir == self::DEFAULT_OUTPUT_DIR_OPTION)
    {
      $filesystem = $this->getFilesystem();
      $cmd = 'php '. sfConfig::get('sf_root_dir'). '/symfony propel:schema-to-yml';
      $filesystem->execute($cmd);
      $this->log(' - Database xml schema converted to yml.');
      $filesystem->remove($this->output_path);
      $this->log(' - Database xml schema removed.');
    }
  }
  
  /**
   * Return current formated date time to check task length.
   *
   * @return String
   */
  protected function getCurrentDateTime()
  {
    return date('Y-m-d H:i:s');
  }

  /**
   * Assign options to class to make them available everywhere.
   */
  protected function setClassOptions($arguments = array(), $options = array())
  {
    foreach ($options as $option => $value)
    {
      if (empty($option))
      {
        continue;
      }
      $this->$option = $options[$option];
    }
  }
}