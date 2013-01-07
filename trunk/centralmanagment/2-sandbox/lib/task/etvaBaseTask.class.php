<?php

declare(ticks = 1);

class etvaBaseTask extends sfBaseTask
{
    protected function configure()
    {
//        $this->addOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'app', 'prod');

    }

    protected function execute($arguments = array(), $options = array()){

        $this->removeAlarm();

        // initialize the database connection
        // $databaseManager = new sfDatabaseManager($this->configuration);
        // $connection = $databaseManager->getDatabase($options['connection'])->getConnection();

        $this->log("redifine stdout");
        $this->redefineStdOut();
        $this->setSigAlarm();
    
    }

    /*
     * Returns the timeout in seconds
     */
    protected function getSigAlarmTimeout(){
        return 300;
    }

    protected function redefineStdOut(){
        $file = $this->getLogName('.log');
        $this->log($file);
        $file_logger = new sfFileLogger($this->dispatcher, array(
            'file' => $file
        ));
  
        $this->dispatcher->connect('command.log', array($file_logger, 'listenToLogEvent'));
    }

    protected function setSigAlarm(){
        pcntl_signal(SIGALRM, array(&$this, 'sigAlarmCallback'));
        $sec = $this->getSigAlarmTimeout();
        if($sec > -1){
            pcntl_alarm($this->getSigAlarmTimeout());
        }        
    }

    public function sigAlarmCallback(){
        $alarmFile = $this->getLogName('.alert');
  
        $this->log("[INFO] The alarm file is ".$alarmFile);
        $handle = fopen($alarmFile, 'a') or die('[ERROR] Cannot open file:  '.$alarmFile);
  
        $data = '[ERROR] Task with name '.$this->name.' is running for '.$this->getSigAlarmTimeout()." seconds. The process is going to die.\n";
        fwrite($handle, $data);
        fclose($handle);
        
        exit("Time exceeded");
    }

    protected function removeAlarm(){
        $file = sfConfig::get('app_cron_alert_dir');
        $file .= '/';
        $file .= get_class($this);
        $file .= '.alert';
        print $file;
        if(file_exists($file)){
            unlink($file);
        }
    }

    protected function getLogName($extension){
        switch($extension){
            case '.alert':
                $file = sfConfig::get('app_cron_alert_dir');
                break;
            case '.log':
                $file = sfConfig::get('app_cron_log_dir');
                break;
            default:
                $file = sfConfig::get('app_cron_log_dir');
                break;
        }
        $file .= '/';
        $file .= get_class($this);
        $file .= $extension;
        return $file;
    }
}

