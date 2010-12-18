<?php
class Etva
{
    

//    public function setJsonError($info, $statusCode = 400)
//    {
//      $error = json_encode($info);
//      $this->getContext()->getResponse()->setHttpHeader("X-JSON", '()');
//      $this->setError($statusCode);
//      return $error;
//
//    }
//
//    public function setError($statusCode = 400)
//    {
//      $this->getContext()->getResponse()->setStatusCode($statusCode);
//
//    }


//    static public function convertfrombytes( $bytes, $to=NULL )
//    {
//      //$float = floatval( $bytes );
//
//          $float = $bytes / pow(2,20);
//
//      return $float;
//    }

    static public function byte_to_MBconvert($bytes, $precision=2) {


	$unit = 0;

//	do {
//		$bytes /= 1024;
//		$unit++;
//	} while ($bytes > 1024);
    $bytes = $bytes / Math.pow(2,20);

	return $bytes;
   }


//   /**
//     * Used to generate random macs
//     * @return array
//     */
//    public function generateUUID_hex()
//    {
//
//        $uuid = "";
//
//
//            $uuid = join(":",array(
//                    sprintf('%02x',0x00),
//                    sprintf('%02x',0x16),
//                    sprintf('%02x',0x3e),
//                    sprintf('%02x',mt_rand(1,127)),
//                    sprintf('%02x',mt_rand(1,255)),
//                    sprintf('%02x',mt_rand(1,255))
//                ));
//            $macs[] = $rmac;
//
//
//
//
//        return $macs;
//
//         my $uuid = "";
//243
//244 	    my @y = qw( 8 9 a b );
//245 	    my $i = int(rand(scalar(@y)));
//246 	    my $uuid = join("-",
//247 	                        substr(md5_hex(rand(time())),0,8),
//248 	                        substr(md5_hex(rand(time())),0,4),
//249 	                        "4".substr(md5_hex(rand(time())),0,3),
//250 	                        $y[$i].substr(md5_hex(rand(time())),0,3),
//251 	                        substr(md5_hex(rand(time())),0,12),
//252 	                        );
//253 	    return $uuid;
//
//    }




    


//      function objectToArray ($object) {
//
//      $arr = array();
//
//      for ($i = 0; $i < count($object); $i++) {
//
//      $arr[] = get_object_vars($object[$i]);
//
//      }
//
//      return $arr;
//
//      }  



   



}
?>