<?php
class ETFW_Firewall
{


    function describe_rule_condition($rule){

        $sorted_condition = array();

        foreach($rule as $k=>$v){
            $v = str_replace("!","not",$v);
            switch($k){
                case 'p' :
                           $sorted_condition[0] = 'protocol is <b>'.$v.'</b>';
                           break;
                case 's' :
                           $sorted_condition[1] = 'source is <b>'.$v.'</b>';
                           break;
                case 'd' : $sorted_condition[2] = 'destination is <b>'.$v.'</b>';
                           break;
        case 'icmp-type' : $sorted_condition[3] = 'ICMP type is <b>'.$v.'</b>';
                           break;
       case 'mac-source' : $sorted_condition[4] = 'ethernet address is <b>'.$v.'</b>';
                           break;

                case 'i' :
                           $sorted_condition[5] = 'input interface is <b>'.$v.'</b>';
                           break;
                case 'o' :
                           $sorted_condition[6] = 'output interface is <b>'.$v.'</b>';
                           break;
                case 'f' :
                           $sorted_condition[7] = 'packet is <b>'.$v.'</b> a fragment ';
                           break;
            case 'dports' :
                           $sorted_condition[8] = 'destination ports are <b>'.$v.'</b>';
                           break;
            case 'dport' :
                           $sorted_condition[9] = 'destination port is <b>'.$v.'</b>';
                           break;

            case 'sport' :
                           $sorted_condition[10] = 'source port is <b>'.$v.'</b>';
                           break;

                    //       ! ACK SYN,ACK

        case 'tcp-flags' : $split = split(" ",$v);
                           $split_r = array_reverse($split);
                           $split_r_size = count($split_r);
                           $not = '';
                           $extra = '';
                           if($split_r_size>1){
                               $values = $split_r[0];
                               $values_of = $split_r[1];

                               if($split_r[$split_r_size-1]!=$values_of) $not = 'not';

                               $extra = '<b>'.$values.'</b> (of <b>'.$values_of.'</b>) are <b>'.$not.'</b> set';
                           }
                           $sorted_condition[11] = 'TCP flags '.$extra;
                           break;
       case 'tcp-option' :
                           $found_not = strpos($v,"not");
                           if($found_not===false) $sorted_condition[12] = 'packet uses TCP option <b>'.$v.'</b>';
                           else{
                                $v = str_replace("not","",$v);
                                $sorted_condition[12] = 'packet does not uses TCP option <b>'.$v.'</b>';
                           }

                           break;
            case 'limit' :
                           $found_not = strpos($v,"not");
                           if($found_not===false) $sorted_condition[13] = 'rate is less than <b>'.$v.'</b>';
                           else{
                                $v = str_replace("not","",$v);
                                $sorted_condition[13] = 'rate is more than <b>'.$v.'</b>';
                           }

                           break;
      case 'limit-burst' :
                           $found_not = strpos($v,"not");
                           if($found_not===false) $sorted_condition[14] = 'burst rate is less than <b>'.$v.'</b>';
                           else{
                                $v = str_replace("not","",$v);
                                $sorted_condition[14] = 'burst rate is more than <b>'.$v.'</b>';
                           }

                           break;
            case 'ports' :
                           $sorted_condition[15] = 'source and destination ports are <b>'.$v.'</b>';
                           break;
            case 'state' :
                           $sorted_condition[16] = 'state of connection is <b>'.$v.'</b>';
                           break;
              case 'tos' :
                           $sorted_condition[17] = 'type of service field is <b>'.$v.'</b>';
                           break;
           case 'sports' :
                           $sorted_condition[18] = 'source ports are <b>'.$v.'</b>';
                           break;

                 default : break;

           }



        }

        ksort($sorted_condition);
        if($sorted_condition) $string = "If ".implode(" and ",$sorted_condition);
        else $string = 'Always';
        return $string;
    }

    function describe_rule_chain($chain){
        $chain_string = '';
        switch($chain){
                 case 'INPUT' : $chain_string = 'Incoming packets ('.$chain.')';
                                break;
                case 'OUTPUT' : $chain_string = 'Outgoing packets ('.$chain.')';
                                break;
               case 'FORWARD' : $chain_string = 'Forwarded packets ('.$chain.')';
                                break;
            case 'PREROUTING' : $chain_string = 'Packets before routing ('.$chain.')';
                                break;
           case 'POSTROUTING' : $chain_string = 'Packets after routing ('.$chain.')';
                                break;
            default: $chain_string = 'Chain '.$chain;

        }

        return $chain_string;

    }


    function describe_rule_action($action){
        $action_string = '';
        $default_actions = array('ACCEPT','REJECT','DROP','RETURN','REDIRECT','MASQUERADE');
        switch($action){
            case '' : $action_string = 'Do nothing';
                            break;
            case 'QUEUE' : $action_string = 'Userspace';
                            break;
            case 'RETURN' : $action_string = 'Exit chain';
                            break;
            case 'LOG' : $action_string = 'Log packet';
                            break;
            case 'DNAT' : $action_string = 'Destination NAT';
                         break;
            case 'SNAT' : $action_string = 'Source NAT';
                         break;
            default:
                    if(!in_array($action,$default_actions))
                        $action_string = 'Run chain '.$action;
                    else $action_string = $action;

        }

        return $action_string;

    }




}
?>