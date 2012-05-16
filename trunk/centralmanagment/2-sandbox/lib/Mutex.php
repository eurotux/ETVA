<?php
 
    class Mutex
    {
        private $id;
        private $sem_id;
        private $is_acquired = false;
        private $is_windows = false;
        private $filename = '';
        private $filepointer;
 
        function __construct()
        {
            if(substr(PHP_OS, 0, 3) == 'WIN')
                $this->is_windows = true;
        }
 
        public function init($id, $filename = '')
        {
            $this->id = $id;
 
            if($this->is_windows)
            {
                if(empty($filename)){
                    throw new sfException(sprintf('nxMutex: no filename specified'));
                    return false;
                }
                else
                    $this->filename = $filename;
            }
            else
            {
                if(!($this->sem_id = sem_get($this->id, 1))){
                    throw new sfException(sprintf('nxMutex: Error getting semaphore'));
                    return false;
                }
            }
 
            return true;
        }
 
        public function acquire()
        {
            if($this->is_windows)
            {
                if(($this->filepointer = @fopen($this->filename, "w+")) == false)
                {
                    throw new sfException(sprintf('nxMutex: error opening mutex file'));
                    return false;
                }
 
                if(flock($this->filepointer, LOCK_EX) == false)
                {
                    throw new sfException(sprintf('nxMutex: error locking mutex file'));
                    return false;
                }
            }
            else
            {
                if (! sem_acquire($this->sem_id)){
                    throw new sfException(sprintf('nxMutex: error acquiring semaphore'));
                    return false;
                }
            }
 
            $this->is_acquired = true;
            return true;
        }
 
        public function release()
        {
            if(!$this->is_acquired)
                return true;
 
            if($this->is_windows)
            {
                if(flock($this->filepointer, LOCK_UN) == false)
                {
                    throw new sfException(sprintf('nxMutex: error unlocking mutex file'));
                    return false;
                }
 
                fclose($this->filepointer);
            }
            else
            {
                if (! sem_release($this->sem_id)){
                    throw new sfException(sprintf('nxMutex: error releasing semaphore'));
                    return false;
                }
            }
 
            $this->is_acquired = false;
            return true;
        }
 
        public function getId()
        {
            return $this->sem_id;
        }
    }
 
?>

