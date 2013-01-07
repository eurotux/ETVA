<?php
require_once 'SOAP/Client.php';

class soapClient_ extends SOAP_CLient
{
    private $rcv_timeout = false;

    function soapClient_($host,$port)
    {                
        return parent::SOAP_Client($host,false,$port);
    }

    public function set_rcv_timeout($val) //seconds
    {
        $this->rcv_timeout = $val;

    }
    

    function processSoap($method,$params)
    {
        
        if($this->rcv_timeout) $this->setOpt("rcv_timeout",$this->rcv_timeout);

        //sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($this, sfConfig::get('app_clientsoap_log'),array('message' =>sprintf('host=%s port=%s method=%s params=%s',$this->_endpoint,$this->_portName,$method,print_r($params,true)),'priority'=>EtvaEventLogger::DEBUG)));

        $response = $this->call($method,$params);

        //sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($this, sfConfig::get('app_clientsoap_log'),array('message' =>sprintf('host=%s port=%s method=%s response=%s',$this->_endpoint,$this->_portName,$method,print_r($response,true)),'priority'=>EtvaEventLogger::DEBUG)));

        if ($response instanceOf PEAR_Error)
        {                            
            $fault = (array) $response->getFault();
            $error = $fault['faultstring'];
            $faultcode = $fault['faultcode'];
            $faultactor = $fault['faultactor'];
            $error_detail = $response->getDetail();

            $info = array();            
            
            if($faultcode && $faultcode=='TCP') $info[] = sfContext::getInstance()->getI18N()->__('Communication Failure');
            if($error_detail) $info[] = $error_detail;

            $info = join(': ',$info);
            
            if(empty($error_detail)) $error_detail = $error;
            $error_resp = array('success'=>false,'error'=>$error,'info'=>$info,'faultcode'=>$faultcode,'faultactor'=>$faultactor);
            return $error_resp;
        } // end error response

        $response_resp = array('success'=>true,'response'=>$response);

        return $response_resp;

   }
   

    /**
     * extending base PEAR soap Base.php
     * Converts a SOAP_Value object into a PHP value.
     */
    function _decode($soapval)
    {
        
        if (!is_a($soapval, 'SOAP_Value')) {
            return $soapval;
        }

        if (is_array($soapval->value)) {
            $isstruct = $soapval->type != 'Array';
            if ($isstruct) {
                $classname = $this->_defaultObjectClassname;
                if (isset($this->_type_translation[$soapval->tqn->fqn()])) {
                    // This will force an error in PHP if the class does not
                    // exist.
                    $classname = $this->_type_translation[$soapval->tqn->fqn()];
                } elseif (isset($this->_type_translation[$soapval->type])) {
                    // This will force an error in PHP if the class does not
                    // exist.
                    $classname = $this->_type_translation[$soapval->type];
                } elseif ($this->_auto_translation) {
                    if (class_exists($soapval->type)) {
                        $classname = $soapval->type;
                    } elseif ($this->_wsdl) {
                        $t = $this->_wsdl->getComplexTypeNameForElement($soapval->name, $soapval->namespace);
                        if ($t && class_exists($t)) {
                            $classname = $t;
                        }
                    }
                }
                $return = new $classname;
            } else {
                $return = array();
            }

            foreach ($soapval->value as $item) {
                if ($isstruct) {
                    if ($this->_wsdl) {
                        // Get this child's WSDL information.
                        // /$soapval->ns/$soapval->type/$item->ns/$item->name
                        $child_type = $this->_wsdl->getComplexTypeChildType(
                            $soapval->namespace,
                            $soapval->name,
                            $item->namespace,
                            $item->name);
                        if ($child_type) {
                            $item->type = $child_type;
                        }
                    }
                    if ($item->type == 'Array') {
                        if (isset($return->{$item->name}) &&
                            is_object($return->{$item->name})) {
                            $return->{$item->name} = $this->_decode($item);
                        } elseif (isset($return->{$item->name}) &&
                                  is_array($return->{$item->name})) {
                            $return->{$item->name}[] = $this->_decode($item);
                        } elseif (isset($return->{$item->name})) {
                            $return->{$item->name} = array(
                                $return->{$item->name},
                                $this->_decode($item)
                            );
                        } elseif (is_array($return)) {
                            $return[] = $this->_decode($item);
                        } else {
                            $return->{$item->name} = $this->_decode($item);
                        }
                    } elseif (isset($return->{$item->name})) {
                        $d = $this->_decode($item);
                        if (count(get_object_vars($return)) == 1) {
                            $isstruct = false;
                            $return = array($return->{$item->name}, $d);
                        } else {
                            $return->{$item->name} = array($return->{$item->name}, $d);
                        }
                    } else {
                        $return->{$item->name} = $this->_decode($item);
                    }
                    // Set the attributes as members in the class.
                    if (method_exists($return, '__set_attribute')) {
                        foreach ($soapval->attributes as $key => $value) {
                            call_user_func_array(array(&$return,
                                                       '__set_attribute'),
                                                 array($key, $value));
                        }
                    }
                } else {
                    if ($soapval->arrayType && is_a($item, 'SOAP_Value')) {
                        if ($this->_isBase64Type($item->type) &&
                            !$this->_isBase64Type($soapval->arrayType)) {
                            // Decode the value if we're losing the base64
                            // type information.
                            $item->value = base64_decode($item->value);
                        }
                        $item->type = $soapval->arrayType;
                    }
                    $return[] = $this->_decode($item);
                }
            }

            return $return;
        }

        if ($soapval->type == 'boolean') {
            if ($soapval->value != '0' &&
                strcasecmp($soapval->value, 'false') != 0) {
                $soapval->value = true;
            } else {
                $soapval->value = false;
            }
        } elseif ($soapval->type &&
                  isset($this->_typemap[SOAP_XML_SCHEMA_VERSION][$soapval->type])) {
            // If we can, set variable type.
            settype($soapval->value,
                    $this->_typemap[SOAP_XML_SCHEMA_VERSION][$soapval->type]);
        } elseif ($soapval->type == 'Struct') {
            $soapval->value = null;
        }


        /*
         * modified to perform decode on base64binary data
         */
        if($soapval->type=='base64Binary') {
            if(SOAP_DEFAULT_ENCODING == 'UTF-8') return utf8_encode(base64_decode($soapval->value));
            else return base64_decode($soapval->value);
        }

        return $soapval->value;
    }




}
?>
