<?php
/*
 * Class used to log soap messages
 *
 * listenToLogEvent expects as params
 *      request => array(...)
 *      response => array(...)
 *
 */
class SoapFileLogger extends sfFileLogger
{
  protected
    $type       = 'symfony',
    $format     = '%time% %type% [%priority%] %message%%EOL%',
    $timeFormat = '%b %d %H:%M:%S',
    $fp         = null;

  

  /**
   * Logs a message.
   *
   * @param string $message   Message
   * @param string $priority  Message priority
   */
  protected function doLog($message, $priority)
  {
   
    flock($this->fp, LOCK_EX);
    fwrite($this->fp, strtr($this->format, array(
      '%type%'     => $this->type,   
      '%message%'  => $message,
      '%time%'     => strftime($this->timeFormat),
      '%priority%' => $this->getPriority($priority),
      '%EOL%'      => PHP_EOL,
    )));
    flock($this->fp, LOCK_UN);
  }


  public function listenToLogEvent(sfEvent $event)
  {

    $priority = isset($event['priority']) ? $event['priority'] : self::INFO;
    $request = var_export($event['request'],true);
    $response = var_export($event['response'],true);
    $subject  = $event->getSubject();
    $subject  = is_object($subject) ? get_class($subject) : (is_string($subject) ? $subject : 'main');
  
    $this->log(sprintf('{%s} REQUEST => %s RESPONSE => %s', $subject, $request, $response), $priority);

  }

}
