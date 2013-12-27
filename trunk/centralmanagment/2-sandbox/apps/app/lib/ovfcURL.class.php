<?php
/*
 * class used to export virtual machine usin curl in ovf format OVA (.tar)
 */
class ovfcURL
{

    private $curl;
    private $response_h;

    public function __construct($url)
    {
        $this->curl = curl_init($url);
        $this->response_h = sfContext::getInstance()->getResponse();
        curl_setopt($this->curl, CURLOPT_WRITEFUNCTION, array($this, "curl_handler_recv"));
        curl_setopt($this->curl,CURLOPT_FAILONERROR, true);
    }

    /*
     * set filename to use as attachment name
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
    }
    
    public function setOutputFile($filepath)
    {
        $fp = fopen($filepath, 'w');
        curl_setopt($this->curl, CURLOPT_FILE, $fp);
    }

    //set post fields
    public function post($fields)
    {
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $fields);
    }

    //execute curl command and store status code if any
    public function exec()
    {
        curl_exec($this->curl);
        $this->status = curl_getinfo($this->curl,CURLINFO_HTTP_CODE);
        curl_close($this->curl);

    }

    //get status code
    public function getStatus()
    {
        return $this->status;
    }
    
    /*
     *  handles response data
     */
    public function curl_handler_recv($res, $data)
    {
        $len = strlen($data);
        $this->first_bytes = 0;            

        if($this->first_bytes == 0 ){ //first call to this function. send headers to browser

            $this->response_h->clearHttpHeaders();
            $this->response_h->setHttpHeader('Pragma: public', true);
            $this->response_h->setContentType('application/x-tar');
            $this->response_h->setHttpHeader('Content-Disposition','attachment; filename="'.$this->filename.'"');
            $this->response_h->sendHttpHeaders();

            $this->first_bytes = 1;
        }

        echo $data;

        return $len;
    }
       
}

?>
