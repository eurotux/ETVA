<?php
/*
 *
 *
 * Class to manipulate curl requests.
 * Uses apc cache to store upload/download progrss
 * 
 * apc class defined bellow
 *
 */
class cURL
{
    const PROGRESS_UPLOAD_TOTAL = 'up_total';
    const PROGRESS_UPLOAD_NOW = 'up_now';

    const PROGRESS_DOWNLOAD_TOTAL = 'dl_total';
    const PROGRESS_DOWNLOAD_NOW = 'dl_now';

    private $curl;
    private $response;

    

    public function __construct($url)
    {
        $this->curl = curl_init($url);        
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
    }

    public function get_socket()
    {
        return $this->curl;

    }

    //set post fields
    public function post($fields = null)
    {
        curl_setopt($this->curl, CURLOPT_POST, true);
        if($fields) curl_setopt($this->curl, CURLOPT_POSTFIELDS, $fields);
    }


    public function setopt($option,$values)
    {
        curl_setopt($this->curl, $option, $values);
    }

    public function cookies($name)
    {        
        curl_setopt($this->curl, CURLOPT_COOKIEJAR, sfConfig::get('sf_cache_dir')."/$name");
        curl_setopt($this->curl, CURLOPT_COOKIEFILE, sfConfig::get('sf_cache_dir')."/$name");

    }

    //execute curl command and store status code if any
    public function exec()
    {
        $this->response = curl_exec($this->curl);
        $this->info = curl_getinfo($this->curl);
        $this->status = $this->info['http_code'];
        $this->error = curl_error($this->curl);
        
    }

    public function get_info()
    {
        return $this->info;
    }

    public function close()
    {
        curl_close($this->curl);
    }

    public function get_response()
    {
        return $this->response;
    }

    //get status code
    public function get_status()
    {
        return $this->status;
    }
    
    public function get_error()
    {
        return $this->error;
    }

    public function progress($uuid)
    {        
        if($uuid) $this->cache_uuid = $uuid;
        else $this->cache_uuid = uniqid();

        $this->cache = new apc_CACHE($this->cache_uuid);
        
        $this->cache->del(self::PROGRESS_UPLOAD_TOTAL);
        $this->cache->del(self::PROGRESS_UPLOAD_NOW);

        $this->cache->del(self::PROGRESS_DOWNLOAD_TOTAL);
        $this->cache->del(self::PROGRESS_DOWNLOAD_NOW);


        curl_setopt($this->curl,CURLOPT_NOPROGRESS,false);
        curl_setopt($this->curl,CURLOPT_PROGRESSFUNCTION,array($this,'curl_handler_progress'));


    }

    public function curl_handler_progress($dl_size, $dl, $up_size, $up)
    {                
        $this->cache->add(self::PROGRESS_UPLOAD_TOTAL,$up_size);
        $this->cache->add(self::PROGRESS_UPLOAD_NOW,$up);

        $this->cache->add(self::PROGRESS_DOWNLOAD_TOTAL,$dl_size);
        $this->cache->add(self::PROGRESS_DOWNLOAD_NOW,$dl);                
    }
}



class apc_CACHE
{
    private $cache_id;
    
    public function __construct($id)
    {
        $this->cache_id = $id;              
    }
    
    public function add($k, $v)
    {
        apc_store($this->cache_id.'_'.$k,$v);
    }

    public function get($k)
    {
        $val = apc_fetch($this->cache_id.'_'.$k);        
        return $val;
    }

    public function del($k)
    {
        apc_delete($this->cache_id.'_'.$k);

    }

    static function clear()
    {
        return apc_clear_cache();

    }

}

?>