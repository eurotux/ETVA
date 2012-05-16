<?php
define('CHUNK_SIZE', 1024*1024);

class IOFile
{
    /*
     * return file extension based on mime types
     * 
     */
    function get_mime_extension($mime)
    {        
        $mime_extensions = array('application/x-tar'=>'tar',
                                 'application/x-compressed-tar'=>'tar.gz');

        if(array_key_exists($mime,$mime_extensions))
            return $mime_extensions[$mime];
        else return '';
        
    }


    function get_file_inDir($dir,$file, $check_ext = true)
    {        
        $dirpath = $dir;
        $found = false;

        if(!is_dir($dirpath)) return false;

        $dh = opendir($dirpath);
        while (false !== ($file_aux = readdir($dh)) && !$found) {
            //dont list subdirectories
            if (!is_dir("$dirpath/$file_aux")) {
                $file_name = $file_aux;
                if(!$check_ext) $file_name = preg_replace('/\..*$/', '', $file_aux);
                if($file_name == $file) return $file_aux;                                
            }
        }
        closedir($dh);        
    }


    /**
     * remove files from dir
     *
     * @param string $dir    Directory to delete content
     * @param boolean $deleteRootToo    Delete directory too
     *
     */
    function unlinkRecursive($dir, $deleteRootToo)
    {
        if(!$dh = @opendir($dir))
        {
            return;
        }
        while (false !== ($obj = readdir($dh)))
        {
            if($obj == '.' || $obj == '..')
            {
                continue;
            }

            if (!@unlink($dir . '/' . $obj))
            {
                self::unlinkRecursive($dir.'/'.$obj, true);
            }
        }

        closedir($dh);

        if ($deleteRootToo)
        {
            @rmdir($dir);
        }

        return;
    }

    

    // Read a file and display its content chunk by chunk
    function readfile_chunked($filename, $retbytes = false)
    {

        $buffer = '';
        $cnt =0;
        
        $handle = fopen($filename, 'rb');
        if ($handle === false) {
            return false;
        }
        while (!feof($handle)) {
            $buffer = fread($handle, CHUNK_SIZE);
            echo $buffer;
            ob_flush();
            flush();
            if ($retbytes) {
                $cnt += strlen($buffer);
            }
        }

        $status = fclose($handle);
        if ($retbytes && $status) {
            return $cnt; // return num. bytes delivered like readfile() does.
        }
        
    }

   
}
?>
