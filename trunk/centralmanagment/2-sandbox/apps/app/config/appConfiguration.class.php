<?php
//require_once dirname(__FILE__).'/../../../lib/EtvaEventLogger.class.php';
//require_once dirname(__FILE__).'/../../../lib/SoapFileLogger.class.php';

class appConfiguration extends sfApplicationConfiguration
{
    public function configure()
    {
        
        /*
         * added etva custom log file
         */       
            
            
//        sfConfig::set('app_etva_log','etva-centralmanagement.log');
//        /*
//         * setting soap messages log event
//         */
//        sfConfig::set('app_virtsoap_log','virtsoap_messages.log');
//        $etva_logger = new sfFileLogger(new sfEventDispatcher, array('file' => sfConfig::get("sf_log_dir").'/'.sfConfig::get('app_etva_log')));
//
//        //log soap messages
//        $soap_logger = new SoapFileLogger(new sfEventDispatcher, array('file' => sfConfig::get("sf_log_dir").'/'.sfConfig::get('app_virtsoap_log')));
//
//        //log system operations
//        $event_logger = new EtvaEventLogger(new sfEventDispatcher);
//        // Register our listeners
//        $this->dispatcher->connect(sfConfig::get("app_virtsoap_log"), array($soap_logger, 'listenToLogEvent'));
//        $this->dispatcher->connect(sfConfig::get("app_etva_log"), array($etva_logger, 'listenToLogEvent'));
//        $this->dispatcher->connect("event.log", array($event_logger, "listenToLogEvent"));
        
    }

    public function initialize()
    {
        parent::initialize();
        sfConfig::set('app_etva_log','etva-centralmanagement.log');
        /*
         * setting soap messages log event
         */
        sfConfig::set('app_virtsoap_log','virtsoap_messages.log');
        $etva_logger = new sfFileLogger(new sfEventDispatcher, array('file' => sfConfig::get("sf_log_dir").'/'.sfConfig::get('app_etva_log')));

        //log soap messages
        $soap_logger = new SoapFileLogger(new sfEventDispatcher, array('file' => sfConfig::get("sf_log_dir").'/'.sfConfig::get('app_virtsoap_log')));

        //log system operations
        $event_logger = new EtvaEventLogger(new sfEventDispatcher);
        // Register our listeners
        $this->dispatcher->connect(sfConfig::get("app_virtsoap_log"), array($soap_logger, 'listenToLogEvent'));
        $this->dispatcher->connect(sfConfig::get("app_etva_log"), array($etva_logger, 'listenToLogEvent'));
        $this->dispatcher->connect("event.log", array($event_logger, "listenToLogEvent"));
        
    }
    
    public function filterRequestParameters(sfEvent $event, $parameters)
    {
        
    }


}
