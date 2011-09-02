<?php
/*
 * Class used to log to syslog
 */
class sfSysLogLogger extends sfLogger {

  var $ident = 'ETVA Syslog';

  public function initialize(sfEventDispatcher $dispatcher, $options = array())

  {    
    if(isset($options['ident'])) $this->ident = $options['ident'];
    
    openlog($this->ident, LOG_PID | LOG_PERROR, LOG_KERN);



    return parent::initialize($dispatcher, $options);

  }



  /**

   * Logs a message.

   *

   * @param string $message   Message

   * @param string $priority  Message priority

   */

  protected function doLog($message, $priority)

  {

	  syslog($priority, $message);

  }



  /**

   * Executes the shutdown method.

   */

  public function shutdown()

  {

    closelog();

  }

}
