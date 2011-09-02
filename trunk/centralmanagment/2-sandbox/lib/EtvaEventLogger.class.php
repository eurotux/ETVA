<?php

class EtvaEventLogger extends sfLogger
{
  protected    
    //$format     = '%time% %type% [%priority%] %message%%EOL%',
    $format     = '%message%%EOL%',
    //$timeFormat = '%b %d %H:%M:%S';
    $timeFormat = '';



  /**
   * Logs a message.
   *
   * @param string $message   Message
   * @param string $priority  Message priority
   */
  public function log($message, $priority = self::INFO)
  {    
    return $this->doLog($message, $priority);
  }
    
  /**
   * Logs a message.
   *
   * @param string $message   Message
   * @param string $priority  Message priority
   */
  protected function doLog($message, $priority)
  {
      $log = new EtvaEvent();
      $msg = strtr($this->format, array(                  
        //'%priority%' => $this->getPriority($priority),
        '%message%'  => $message,
        '%EOL%'      => PHP_EOL
      ));


      $log->setLevel($priority);
      //$log->setTime(strftime($this->timeFormat));
      $log->setMessage($msg);
           
      $log->save();

  }

  /**
   * Returns the priority string to use in log messages.
   *
   * @param  string $priority The priority constant
   *
   * @return string The priority to use in log messages
   */


  static public function getPriority($priority)
    {
        static $levels  = array(
            self::EMERG   => 'emerg',
            self::ALERT   => 'alert',
            self::CRIT    => 'crit',
            self::ERR     => 'error',
            self::WARNING => 'warning',
            self::NOTICE  => 'notice',
            self::INFO    => 'info',
            self::DEBUG   => 'debug',
        );

        if (!isset($levels[$priority]))
        {
            throw new sfException(sprintf('The priority level "%s" does not exist.', $priority));
        }

        return $levels[$priority];
    }





 public function listenToLogEvent(sfEvent $event)
  {
      
    $priority = isset($event['priority']) ? $event['priority'] : self::INFO;

    $subject  = $event->getSubject();
    $subject  = is_object($subject) ? get_class($subject) : (is_string($subject) ? $subject : 'main');
    foreach ($event->getParameters() as $key => $message)
    {
      if ('priority' === $key)
      {
        continue;
      }
      $this->log(sprintf('{%s} %s', $subject, $message), $priority);
      //$this->log(sprintf('%s', $message), $priority);
    }
  }


  
}
