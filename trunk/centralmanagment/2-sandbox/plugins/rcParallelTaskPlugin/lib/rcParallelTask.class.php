<?php
/**
 * Abstract class for all tasks.
 *
 * @package    rcParallelTask
 * @subpackage task
 * @author     Romain Cambien <romain@cambien.net>
 * @version    SVN: $Id: sfTask.class.php 21875 2009-09-11 05:54:39Z fabien $
 */
abstract class rcParallelTask extends sfBaseTask {

    private $childNumber;

    private $parent;

    private $pid;

    private $resourceIdentifier = 'A';

    private $queueResource;

    private $queueMessageSize;

    private $lockResource;


    /**
     * The class destructor.
     */
    public function  __destruct() {
        if ($this->iAmParent()) {
            $this->destroyAndWaitForChildren();
        }
    }


    /**
     * @see sfTask
     */
    public function logSection($section, $message, $size = null, $style = 'INFO') {

        parent::logSection($this->pid.' : '.$section, $message, $size, $style);

    }


    /**
     * Return true if we are the parent process.
     *
     * @return boolean
     */
    protected function iAmParent() {

        return (bool)$this->parent;

    }


    /**
     * Return the process PID.
     *
     * @return integer
     */
    protected function getMyPid() {

        return $this->pid;

    }


    /**
     * Start all the children process.
     *
     * @param integer $number The number of child to start
     */
    protected function startChildren($number) {

        if(!function_exists('pcntl_fork')) {
            throw new sfException('You need the PCNTL module to use this plugin');
        }

        $this->parent      = true;
        $this->childNumber = intval($number);

        // Create Child
        for ($i = 0; $i <= $this->childNumber; $i++) {
            $this->pid = pcntl_fork();
            if (0 === $this->pid) {
                $this->parent = false;
                $this->pid    = getmypid();
                break;
            }
        }

        // Manage Crtl+C signal for clean shutdown
        if ($this->iAmParent()) {
            declare(ticks = 1);
            pcntl_signal(SIGINT, array($this, 'signalHandler'));
        }

    }


    /**
     * Destroy every resource and wait for zombies.
     */
    protected function destroyAndWaitForChildren() {

        msg_remove_queue($this->getQueueManager());

        sem_remove($this->getLockManager());

        for ($i = 0; $i <= $this->childNumber; $i++) {
            pcntl_wait($status);
        }

    }


    /**
     * Add a new message to the process queue
     *
     * @param mixed $mixed The data to add
     * @param integer $type The message type
     *
     * @return boolean
     */
    protected function addToQueue($mixed, $type = 1) {

        return msg_send($this->getQueueManager(), $type, $mixed);
        
    }


    /**
     * Retrieve a message from the process queue
     *
     * @param integer $desiredType The disired message type, see http://php.net/msg_receive
     * @param boolean $wait Wait until a new message is available
     *
     * @return mixed The message data if $desiredType > 0, else an stdClass with propertype 'type' with message type and 'message' with message data
     */
    protected function getFromQueue($desiredType = 1, $wait = true) {

        $status = msg_receive($this->getQueueManager(), $desiredType, $type, $this->queueMessageSize, $mixed, true, ($wait?0:MSG_IPC_NOWAIT));

        if (false === $status || MSG_ENOMSG === $status) {
            return false;
        } else {
            if ($desiredType < 0) {
                $message        = new stdClass();
                $message->type  = $type;
                $message->value = $mixed;

                return $message;
            } else {
                return $mixed;
            }
        }

    }


    /**
     * Active waiting for empty queue.
     *
     * @param integer $waitInterval The sleep time between two queue check
     */
    protected function waitForEmptyQueue($waitInterval = 1) {
        while ($this->getQueueLength()) {
            // Ugly, pseudo active waiting, but I don't have any other solution.
            sleep($waitInterval);
        }
    }


    /**
     * Return the number of waiting element in the queue
     *
     * @return integer
     */
    protected function getQueueLength() {
        $stats = msg_stat_queue($this->getQueueManager());
        return $stats['msg_qnum'];
    }


    /**
     * Try to get a lock.
     *
     * @return boolean
     */
    protected function getLock($blocking = true) {

        return sem_acquire($this->getLockManager());

    }


    /**
     * Release the lock.
     *
     * @return boolean
     */
    protected function releaseLock() {

        return sem_release($this->getLockManager());

    }


    /**
     * Change the resource identifier for the lock and queue system.
     *
     * Allow to use different queue and lock inside the same task.
     *
     * @param string $identifier One letter ressource identifier
     *
     * @return rcParallelTask
     *
     * @throws sfException 
     */
    protected function setResourceIdentifier($identifier) {

        if (preg_match('/^[a-zA-Z]$/', $identifier)) {
            $this->resourceIdentifier = $identifier;
        } else {
            throw new sfException('Resource identifier must be one letter string.');
        }

        return $this;

    }


    /**
     * Get or create the process queue.
     *
     * @return resource
     */
    private function getQueueManager() {

        if (empty($this->queueResource[$this->resourceIdentifier])) {
            // Creating new queue
            $this->queueResource[$this->resourceIdentifier] = msg_get_queue(ftok(__FILE__, $this->resourceIdentifier));

            $stats = msg_stat_queue($this->queueResource[$this->resourceIdentifier]);

            $this->queueMessageSize = $stats['msg_qbytes'];
        }

        return $this->queueResource[$this->resourceIdentifier];

    }


    /**
     * Get or create the lock system.
     *
     * @return resource
     */
    private function getLockManager() {

        if (empty($this->lockResource[$this->resourceIdentifier])) {
            $this->lockResource[$this->resourceIdentifier] = sem_get(ftok(__FILE__, $this->resourceIdentifier));
        }

        return $this->lockResource[$this->resourceIdentifier];

    }


    /**
     * Internal signal handler.
     *
     * @param integer $signal The signal number
     */
    public function signalHandler($signal) {
        
        // Broadcast signal to children
        posix_kill(0, $signal);
        exit;

    }

}
