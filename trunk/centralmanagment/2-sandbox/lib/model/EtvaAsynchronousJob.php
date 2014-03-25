<?php



/**
 * Skeleton subclass for representing a row from the 'asynchronous_job' table.
 *
 * 
 *
 * This class was autogenerated by Propel 1.6.3 on:
 *
 * Tue Nov 19 17:37:01 2013
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 * @package    propel.generator.lib.model
 */
class EtvaAsynchronousJob extends BaseEtvaAsynchronousJob {

    const QUEUED = 'queued'; // ??
    const PENDING = 'pending';
    const LEASED = 'leased';
    const FINISHED = 'finished';
    const ABORTED = 'aborted';
    const INVALID = 'invalid';

    const WAITING = 'waiting';

    protected $sfApplication;

    public function getTask($dispatcher = null)
    {
        chdir(sfConfig::get('sf_root_dir')); // Trick plugin into thinking you are in a project directory

        if( !$this->sfApplication )
        {
            if( !$dispatcher ) $dispatcher = sfContext::getInstance()->getEventDispatcher();
            $this->sfApplication = new sfSymfonyCommandApplication( $dispatcher, null, array('symfony_lib_dir' => realpath(dirname(__FILE__).'/..')) );
        }

        $task_namespace = $this->getTasknamespace();
        $task_name = $this->getTaskname();

        $task = $task_namespace . ':' . $task_name;
        return $this->sfApplication->getTaskToExecute($task);
    }


    private function register_runtask()
    {
        $runtask_file = $this->get_runtask_file();
        file_put_contents($runtask_file,getmypid());
    }
    private function unregister_runtask()
    {
        $runtask_file = $this->get_runtask_file();
        unlink($runtask_file);
    }

    private function get_runtask_file()
    {
        $var_run_dir = sfConfig::get('app_cron_alert_dir');
        if( !$var_run_dir ) $var_run_dir = "/var/run/etva_etvm";

        $task_file = "$var_run_dir/" . get_class($this) . "." . $this->getId() . ".task";

        return $task_file;
    }
    private function acquireLock()
    {
        if( file_exists($this->get_runtask_file()) ) return false;
        $this->register_runtask();
        return true;
    }
    private function releaseLock()
    {
        $this->unregister_runtask();    // remove lock
        return true;
    }
    public function checkStatus($dispatcher = null)
    {
        $res = null;
        //$res = array('success'=>false,'error'=>'already processed'); // TODO improve this message
        if( $this->acquireLock() )
        {
            if( !$this->finished() ) $res = $this->handle($dispatcher);
            $this->releaseLock();       // release lock
        }
        return $res;
    }

    private function runTask($dispatcher = null)
    {
        if( !$dispatcher ) $dispatcher = sfContext::getInstance()->getEventDispatcher();

        $arguments = $this->getArguments();
        if( is_string($arguments) ) $arguments = (array)json_decode($arguments);

        $options = $this->getOptions();
        if( is_string($options) ) $options = (array)json_decode($options);
 
        $async_task = $this->getTask($dispatcher);
        return $async_task->run( $arguments, $options );
    }

    private function handle($dispatcher=null)
    {
        if( !$this->dependsSucceeded() ){
            // abort when dependencies not succeeded
            $this->setStatus(self::ABORTED);    // abort
        } else {
            // mark task as leased
            $this->setStatus(self::LEASED);
            $this->setTaskpid(getmypid());  // set pid of task
            $this->save();

            try {
                // run task
                $result = $this->runTask($dispatcher);

                // treat result
                $this->setResult( json_encode($result) );
                if( $result['_request_id'] )
                {
                    $this->updateOptions( array( 'request_id'=>$result['_request_id'] ) );
                    $this->setStatus($result['_request_status']);

                    if( $result['_run_at'] )
                    {
                        // new schedule
                        $this->setRunAt($result['_run_at']);
                    }
                } else {
                    $this->setStatus(self::FINISHED);   // finish
                }
            } catch( Exception $e ){
                $this->setStatus(self::INVALID);    // mark as invalid
                $result = array('success'=>false, 'error'=>$e->getMessage(), 'status'=>self::INVALID);
            }
        }
        $this->save();
        return $result;
    }

    private function updateOptions( $p_arr )
    {
        $options = $this->getOptions();

        if( is_string($options) ) $options = (array)json_decode($options);

        $options = array_merge($options, $p_arr);

        $options_str = json_encode($options);
        $this->setOptions( $options_str );
    }
    private function initialized()
    {
        return (!$this->getStatus()) ? true : false;
    }
    public function finished()
    {
        if( $this->getStatus() == self::FINISHED ) return true;
        if( $this->getStatus() == self::INVALID )  return true;
        if( $this->getStatus() == self::ABORTED )  return true;
        return false;
    }
    public function hadSuccess()
    {
        $result = $this->getResult();
        if( $result )
        {
            $resObj = (array)json_decode($result);
            if( $resObj['success'] ) return true;
        }
        return false;
    }

    public function dependsSucceeded()
    {
        $depends = $this->getDepends();
        if( $depends )
        {
            $depends_arr = explode(',',$depends);
            foreach( $depends_arr as $s_dJob )
            {
                // depends can be success(15) or 16 or success(15),16 or success(15),16,finished(17)
                $dJob = intval($s_dJob);
                if( preg_match("/success\((\d+)\)/", $s_dJob, $matches ) )  // only if depends success
                {
                    $dJob = intval($matches[1]);
                }
                if( $dJob )
                {
                    $oJob = EtvaAsynchronousJobPeer::retrieveByPK($dJob);
                    if( !$oJob || !$oJob->hadSuccess() ) return false;
                }
            }
        }
        return true;
    }
    public function dependsFinished()
    {
        $depends = $this->getDepends();
        if( $depends )
        {
            $depends_arr = explode(',',$depends);
            foreach( $depends_arr as $s_dJob )
            {
                $dJob = $s_dJob;
                if( preg_match("/\w+\((\d+)\)/", $s_dJob, $matches ) )
                {
                    $dJob = $matches[1];
                }
                if( $oJob = EtvaAsynchronousJobPeer::retrieveByPK($dJob) )
                {
                    if( !$oJob->finished() ) return false;
                }
            }
        }
        return true;
    }
    public function calcDepends($depends = null, $dispatcher = null)
    {
        $task = $this->getTask($dispatcher);
        if( $task && method_exists($task, 'dependsOnIt' ) )     // check jobs dependencies
        {
            $aJobs_query = EtvaAsynchronousJobQuery::create()
                                ->addAnd(EtvaAsynchronousJobPeer::STATUS,array(self::ABORTED,self::INVALID,self::FINISHED),Criteria::NOT_IN)  // not this cases
                                ->addOr(EtvaAsynchronousJobPeer::STATUS,null, Criteria::ISNULL);

            $depends_asyncJobs = $aJobs_query->find();
            foreach($depends_asyncJobs as $aJob)
            {
                if( $task->dependsOnIt($this, $aJob) )
                {
                    if( $depends ) $depends .= ",";
                    $depends .= sprintf('finished(%d)',$aJob->getId());
                }
            }
            return $depends;
        }
        return $depends;
    }

    /*
     * abort task
     */
    private function kill()
    {
        if( $pid = $this->getTaskpid() ){
            $pid_fd = "/proc/$cpid";

            if( file_exists($pid_fd) )
            {
                $pid_fcmdline = "/proc/$cpid/cmdline";
                $cmdline = file_get_contents($pid_fcmdline);
                if( preg_match("/php/", $cmdline) )  // only if from php cron task
                {
                    posix_kill( $pid , SIGKILL );
                    return true;
                }
            }
        }
        return false;
    }
    public function abort()
    {

        // kill the task
        $this->kill();

        $this->setStatus( self::ABORTED );
        $this->save();

    }
} // EtvaAsynchronousJob
