<?php

class update
{
    public static function checkDbVersion()
    {
        $etva_data = Etva::getEtvaModelFile();
        $curr = $etva_data['dbversion'];
        $min = floatval(sfConfig::get('app_dbrequired'));
        if($curr < $min){            
            return "Incompatible database version. Please upgrade.";
        }else{
            return "OK";
        }
    }
}
