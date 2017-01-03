<?php

class etvaQueuecmTask extends sfBaseTask
{
  protected $running = true;
  protected $workers = array();
  protected $max_workers = 3;

  protected $main_worker  = 0;
  protected $abort_worker = 0;

  protected $context;
  protected $connection;
  protected $databaseManager;

  protected $last_sec;
  protected $last_min;
  protected $last_hour;
  protected $last_day;

  protected $queueTasks;
  protected $queueTasks_msg_type = 1;
  protected $queueTasks_identifier = 'Q';

  protected $sem_task_base_id = 765;

  protected function configure()
  {
    // // add your own arguments here
    $this->addArguments(array(
      new sfCommandArgument('op', sfCommandArgument::REQUIRED, 'The operation')
    ));

    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'prod'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
      // add your own options here
      new sfCommandOption('num_workers', null, sfCommandOption::PARAMETER_OPTIONAL, 'The number of workers'),
    ));

    $this->namespace        = 'etva';
    $this->name             = 'queue-cm';
    $this->briefDescription = ' Queue Manager';
    $this->detailedDescription = <<<EOF
The [etva:queue-cm|INFO] task does things.
Call it with:

  [php symfony etva:queue-cm|INFO]
EOF;
  }

  // redirect log file
  private function config_logfile()
  {
    $var_log_dir = sfConfig::get('app_cron_log_dir');
    if( !$var_log_dir ) $var_log_dir = "/var/log/etva_etvm";

    $file_logger = new sfFileLogger($this->dispatcher, array( 'file' =>  "$var_log_dir/" . get_class($this) . ".log" ));
    $this->dispatcher->connect('command.log', array($file_logger, 'listenToLogEvent'));
  }

  private function get_pid_file()
  {
    $var_run_dir = sfConfig::get('app_cron_alert_dir');
    if( !$var_run_dir ) $var_run_dir = "/var/run/etva_etvm";

    $pid_file = "$var_run_dir/" . get_class($this) . ".pid";

    return $pid_file;
  }
  private function register_pid()
  {
    $pid_file = $this->get_pid_file();
    file_put_contents($pid_file,getmypid());
  }
  private function unregister_pid()
  {
    $pid_file = $this->get_pid_file();
    unlink($pid_file);
  }

  private function is_running()
  {
    if( file_exists($this->get_pid_file()) )
    {
        $cpid = $this->get_running_pid();
        $pid_fd = "/proc/$cpid";

        if( file_exists($pid_fd) )
        {
            $pid_fcmdline = "/proc/$cpid/cmdline";
            $cmdline = file_get_contents($pid_fcmdline);
            if( preg_match("/php/", $cmdline) )  // only if php
            {
                return true;
            }
        }
    }
    return false;
  }

  private function alarm_handler($delta = 5)  // alarm by minutes (default: 5)
  {
    $alarm = false;
    $ltime = localtime(time(),true);

    //if( ($ltime['tm_sec']!=$this->last_sec) ||
    if( ($ltime['tm_min']!=$this->last_min) ||
            ($ltime['tm_hour']!=$this->last_hour) ||
                ($ltime['tm_mday']!=$this->last_day) )
    {
        if( ($ltime['tm_min'] % $delta) == 0 ) $alarm = true;
    }

    //$this->last_sec  = $ltime['tm_sec'];
    $this->last_min  = $ltime['tm_min'];
    $this->last_hour = $ltime['tm_hour'];
    $this->last_day  = $ltime['tm_mday'];

    return $alarm;
  }

  private function get_running_pid()
  {
    return file_get_contents($this->get_pid_file());
  }

  // connect to database
  private function init_database($arguments,$options)
  {
    // get context
    $this->context = sfContext::createInstance(sfProjectConfiguration::getApplicationConfiguration('app',$options['env'],true));

    // initialize the database connection
    $this->databaseManager = new sfDatabaseManager($this->configuration);
    $this->connection = $this->databaseManager->getDatabase($options['connection'])->getConnection();

  }
  // disconnect database
  private function close_database($arguments,$options)
  {
    $this->databaseManager->shutdown();
    $this->context->shutdown();
  }

  private function launch_asyncJob_worker($arguments, $options)
  {
    if( count($this->workers) < $this->max_workers )
    {
        $cpid = pcntl_fork();

        if( 0 === $cpid )
        {
            $this->log("[INFO] Worker - new with pid=".getmypid());

            // dequeue task
            $aJob_id = $this->dequeue_task();

            if( $aJob_id )
            {
                $sem_lock = $this->init_proctask_lock($aJob_id);
                if( $this->acquire_proctask_lock($sem_lock) )   // acquire lock
                {
                    // init database
                    $this->init_database($arguments,$options);

                    $aJob = EtvaAsynchronousJobPeer::retrieveByPK( $aJob_id );

                    $this->log("[INFO] Worker - check status of request task id=".$aJob->getId()."   task=".$aJob->getTasknamespace().":".$aJob->getTaskname()." pid=".getmypid());
                    $res = $aJob->checkStatus($this->dispatcher);
                    if( isset($res['success']) && (!$res['success']) ){
                        $this->log("[ERROR] Worker - Error execute task id=".$aJob->getId()." pid=".getmypid()." ".$res['error']);
                    } else {
                        //$this->log("[INFO] Worker - Task with id=".$aJob->getId()." pid=".getmypid(). " ... finished");
                    }

                    // close database
                    $this->close_database($arguments,$options);

                    $this->release_proctask_lock($sem_lock);    // release lock
                }
            }
            exit(0);
        }
        else if( -1 === $cpid )
        {
            $this->log("[ERROR] create worker");
        }
        else
        {
            array_push($this->workers, $cpid);
        }
    }
  }

  private function launch_worker($arguments,$options)
  {
    if( !$this->main_worker )
    {
        // launch workers
        for($i=0; $i < $this->max_workers; $i++)
        {
            $this->launch_asyncJob_worker($arguments,$options);
        }

        $cpid = pcntl_fork();

        if( 0 === $cpid )
        {
            pcntl_signal(SIGTERM, array(&$this, 'term_handler'));

            // init database
            $this->init_database($arguments,$options);

            //$this->log("[INFO] Worker - check all asynchronous jobs... ".getmypid());

            // query
            $query_asyncJobs = EtvaAsynchronousJobQuery::create()
                                ->add(EtvaAsynchronousJobPeer::STATUS,array(EtvaAsynchronousJob::QUEUED,EtvaAsynchronousJob::LEASED,EtvaAsynchronousJob::ABORTED,EtvaAsynchronousJob::INVALID,EtvaAsynchronousJob::FINISHED),Criteria::NOT_IN)  // not this cases
                                ->addOr(EtvaAsynchronousJobPeer::STATUS,null, Criteria::ISNULL)
                                ->addAnd(EtvaAsynchronousJobPeer::RUN_AT,null, Criteria::ISNULL)
                                ->addOr(EtvaAsynchronousJobPeer::RUN_AT,time(), CRITERIA::LESS_THAN);

            // find
            $asyncJobs = $query_asyncJobs->find();

            // treat...
            for($i=0; ($i < count($asyncJobs)) && $this->running; $i++)
            {
                $aJob = $asyncJobs[$i];
                if( $aJob->dependsFinished() )  // no dependencies
                {
                    $this->log("[INFO] MainWorker - enqueue task id=".$aJob->getId()." status=".$aJob->getStatus()." pid=".getmypid());

                    $aJob->setStatus(EtvaAsynchronousJob::QUEUED); // mark as queued
                    $aJob->save();

                    $this->enqueue_task($aJob);     // enqueue

                } else {
                    //$this->log("[WARN] Worker - Task with id=".$aJob->getId()." pid=".getmypid()."  depends: ".$aJob->getDepends());
                }
            }
            if( !$this->running )
            {
                $this->log("[INFO] MainWorker exit normally....");
            }

            $this->close_database($arguments,$options);

            exit(0);

        } else if( -1 === $cpid ) {
            $this->log("[ERROR] create main worker");
        } else {
            $this->main_worker = $cpid;    // regist main worker
            sleep(5);
        }
    } else {
        $this->log("[WARN] main worker already running");
    }
  }

  private function launch_worker_abort($arguments,$options)
  {
    if( !$this->abort_worker )
    {
        $cpid = pcntl_fork();
        if( 0 === $cpid )
        {
            pcntl_signal(SIGTERM, array(&$this, 'term_handler'));

            // init database
            $this->init_database($arguments,$options);

            $this->log("[INFO] check expired asynchronous jobs... ");

            $expire_asyncjob_ttl = 24 * 60 * 60;    // expires after 1 day (24 * 60 * 60)

            // query
            $query_asyncJobs = EtvaAsynchronousJobQuery::create()
                                ->add(EtvaAsynchronousJobPeer::STATUS,array(EtvaAsynchronousJob::ABORTED,EtvaAsynchronousJob::INVALID,EtvaAsynchronousJob::FINISHED),Criteria::NOT_IN)  // not this cases
                                ->addOr(EtvaAsynchronousJobPeer::STATUS,null, Criteria::ISNULL)
                                ->addAnd(EtvaAsynchronousJobPeer::ABORT_AT,time(), Criteria::LESS_THAN)
                                ->addOr(EtvaAsynchronousJobPeer::UPDATED_AT,(time()-$expire_asyncjob_ttl), Criteria::LESS_THAN)
                                ->addAnd(EtvaAsynchronousJobPeer::RUN_AT,null, Criteria::ISNULL)
                                ->addOr(EtvaAsynchronousJobPeer::RUN_AT,time(), Criteria::LESS_THAN);

            //$this->log("[DEBUG] abort query = ".$query_asyncJobs->toString());

            // find
            $asyncJobs = $query_asyncJobs->find();

            // treat...
            for($i=0; ($i < count($asyncJobs)) && $this->running; $i++)
            {
                $aJob = $asyncJobs[$i];

                $this->log("[WARN] abort the task id=".$aJob->getId());
                $this->log("   task=".$aJob->getTasknamespace().":".$aJob->getTaskname() );
                $aJob->abort();
            }

            if( !$this->running )
            {
                $this->log("[INFO] Worker exit normally....");
            }

            $this->close_database($arguments,$options);

            exit(0);

        } else if( -1 === $cpid ) {
            $this->log("[ERROR] create abort worker");
        } else {
            $this->abort_worker = $cpid;    // regist abort worker
        }
    }
  }

  // shutdown broker and kill the workers
  private function shutdown_queue_manager()
  {
    foreach( $this->workers as $cpid )
    {
        posix_kill( $cpid , SIGTERM );
        sleep(2);
        pcntl_waitpid(-1,$status, WNOHANG);
        //pcntl_waitpid($status);
    }

    if( $cpid == $this->main_worker )
    {
        posix_kill( $cpid , SIGTERM );
        sleep(2);
        pcntl_waitpid(-1,$status, WNOHANG);
    }
    if( $cpid == $this->abort_worker )
    {
        posix_kill( $cpid , SIGTERM );
        sleep(2);
        pcntl_waitpid(-1,$status, WNOHANG);
    }
  }

  // term signal handler
  public function term_handler()
  {
    $this->running = false;
    $this->log("[INFO] term_handler ".getmypid());
  }

  // chld signal handler
  public function chld_handler()
  {
    $dead_pid = pcntl_waitpid(-1, $status, WNOHANG);
    while($dead_pid > 0){
        //$this->log("[INFO] chld_handler pid=$dead_pid");

        $this->chld_dies_handler($dead_pid);

        $dead_pid = pcntl_waitpid(-1, $status, WNOHANG);
    }
  }

  // inc number of workers
  public function inc_workers()
  {
    $this->max_workers ++;
    $this->log("[INFO] inc number of workers to  ".$this->max_workers);
  }
  // dec number of workers
  public function dec_workers()
  {
    if( $this->max_workers > 1 ) $this->max_workers --; // at least one
    $this->log("[INFO] dec number of workers to  ".$this->max_workers);
  }

  private function chld_dies_handler($dead_pid)
  {
    if( $this->abort_worker == $dead_pid )
    {
        //$this->log("[DEBUG] abort worker dead pid=$dead_pid");
        $this->abort_worker = 0;    // reset
    }
    else if( $this->main_worker == $dead_pid )
    {
        //$this->log("[DEBUG] main worker dead pid=$dead_pid");
        $this->main_worker = 0;    // reset
    }
    else
    {
        //$this->log("[DEBUG] normal worker dead pid=$dead_pid");
        $aux_workers = array();
        foreach($this->workers as $pid)
        {
            if( $pid != $dead_pid ) array_push($aux_workers,$pid);
        }
        $this->workers = $aux_workers;
    }
  }

  private function init_queue_tasks()
  {
    // queue of response
    $this->queueTasks = msg_get_queue(ftok(__FILE__, $this->queueTasks_identifier));
    $this->lockReceiveTask = sem_get(ftok(__FILE__, $this->queueTasks_identifier));
  }
  private function enqueue_task($task)
  {
    msg_send($this->queueTasks, $this->queueTasks_msg_type, $task->getId());
  }
  private function acquire_queue_lock()
  {
    if( sem_acquire($this->lockReceiveTask) ) return true;
    return false;
  }
  private function release_queue_lock()
  {
    return sem_release($this->lockReceiveTask);    // release lock
  }
  private function dequeue_task($desiredType = null, $wait = true)
  {
    if( !$desiredType ) $desiredType = $this->queueTasks_msg_type;
    $option_receive = $wait ? 0 : MSG_IPC_NOWAIT;

    $stats = msg_stat_queue($this->queueTasks);
    $queueMessageSize = $stats['msg_qbytes'];

    if( $this->acquire_queue_lock() )   // acquire lock
    {
        $status = msg_receive($this->queueTasks, $desiredType, $type, $queueMessageSize, $mixed, true, $option_receive);
        $this->release_queue_lock();    // release lock
        if( $status == true ){
            return $mixed;
        }
    }
  }
  private function destroy_queue_tasks()
  {
    msg_remove_queue($this->queueTasks);
    sem_remove($this->lockReceiveTask);
  }

  private function init_proctask_lock($task_id)
  {
    $id = $this->sem_task_base_id + $task_id;
    #$this->log("[DEBUG] init_proctask_lock task_id=$task_id semid=$id");
    return sem_get($id);
  }
  private function acquire_proctask_lock($sem_lock)
  {
    #$this->log("[DEBUG] acquire_proctask_lock sem_lock=$sem_lock");
    if( sem_acquire($sem_lock) ) return true;
    return false;
  }
  private function release_proctask_lock($sem_lock)
  {
    #$this->log("[DEBUG] release_proctask_lock sem_lock=$sem_lock");
    $re = sem_release($sem_lock);
    sleep(1);   // wait some secs
    sem_remove($sem_lock);
    return $res;
  }

  // init the queue manager
  private function start($arguments,$options)
  {
    if( !$this->is_running() )
    {
        if( 0 === pcntl_fork() )
        {
            $this->register_pid();

            $this->log("[INFO] Queue manager init ...");

            pcntl_signal(SIGTERM, array(&$this, 'term_handler'));
            pcntl_signal(SIGCHLD, array(&$this, 'chld_handler'));
            pcntl_signal(SIGUSR1, array(&$this, 'inc_workers'));
            pcntl_signal(SIGUSR2, array(&$this, 'dec_workers'));

            $this->init_queue_tasks();

            while ($this->running)
            {
                if( $this->alarm_handler(5) )   // 5 min
                {
                    // abort worker
                    $this->launch_worker_abort($arguments,$options);
                } else {
                    // worker
                    $this->launch_worker($arguments,$options);
                }

                // wait...
                sleep(5);
            }

            $this->log("[INFO] Queue manager goes exit ...");
            $this->shutdown_queue_manager();
            $this->log("[INFO] ...exit...");

            $this->destroy_queue_tasks();
            $this->unregister_pid();
        }
    } else {
        $this->log("[ERROR] Queue manager already running...");
    }
  }

  private function stop($arguments, $options)
  {
    if( $this->is_running() )
    {
        $run_pid = $this->get_running_pid();
        posix_kill( $run_pid , SIGTERM );
        $this->log("[INFO] Queue manager with pid '$run_pid' stoped.");
    } else {
        $this->log("[INFO] Queue manager is not running.");
    }
  }

  private function status($arguments, $options)
  {
    if( $this->is_running() )
        $this->log("[INFO] Queue manager is running.");
    else 
        $this->log("[INFO] Queue manager is not running.");
  }

  // main execute
  protected function execute($arguments = array(), $options = array())
  {
    // add your code here

    // change log file
    $this->config_logfile();

    if( $options['num_workers'] ) $this->max_workers = $options['num_workers'];

    if( $arguments['op'] == 'start' )
    {
        $this->start($arguments,$options);
    } else if( $arguments['op'] == 'stop' )
    {
        $this->stop($arguments,$options);
    } else if( $arguments['op'] == 'status' )
    {
        $this->status($arguments,$options);
    }

  }
}
