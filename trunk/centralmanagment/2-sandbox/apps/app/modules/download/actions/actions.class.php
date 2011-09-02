<?php

/*
 * Module used to download files throught symfony
 */
class downloadActions extends sfActions
{
    public function prepareDownload($file)
    {
        $response = $this->getResponse();
        $response->clearHttpHeaders();
        $response->addCacheControlHttpHeader('Cache-control','must-revalidate, post-check=0, pre-check=0');
        $response->setContentType('application/octet-stream',TRUE);
        $response->setHttpHeader('Content-Transfer-Encoding', 'binary', TRUE);
        $response->setHttpHeader('Content-Disposition','attachment; filename='.$file, TRUE);
        $response->sendHttpHeaders();

    }

    public function executeFile(sfWebRequest $request)
    {
        $file = $request->getParameter('filename');        
        $type = $request->getParameter('type');        

        switch($type){
            case 'backup' : $apl = new Appliance();
                            $base_dir = $apl->get_archive_base_dir();
                            break;
            default:
                            break;
        }

        
        if(!$base_dir) return sfView::NONE;
                
        $path = realpath($base_dir .'/'. $file);        
        
        // if base_dir isn't at the front 0==strpos, most likely hacking attempt
        if(strpos($path, $base_dir)) {
            die('Invalid Path');
        }
        elseif(file_exists($path)) {                
                $this->prepareDownload($file);
                readfile($path);
            }else die('Invalid Path');
        
        return sfView::NONE;
    }
        
}
