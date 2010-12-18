<?php

//function quote($data){
  //  return json_encode($data);
//}


function build_rra_names($rra_names){

    $result = '';
    $partials = array();
    foreach($rra_names as $rra=>$name){

           $partials[] = json_encode($rra).':'.json_encode($name);
    }


    $result = implode(',',$partials);
    return '{'.$result.'}';
    //return "[{name: 'time', mapping: 't'},{name: 'value0', mapping: 'v0'},{name: 'value1', mapping: 'v1'}]";

    // return "[['da','da da']]";
}

function build_store($stores){

    $result = '';
    $partials = array();
    foreach($stores as $name=>$data){

            $quoted_data = array_map("json_encode",$data);
            $fields = implode(',',$quoted_data);
            //$fields = implode(',',$data);
            $partials[] = json_encode($name).':['.$fields.']';
    }

    // {'network':['v','t'],'networks':['v','t']};

    $result = implode(',',$partials);
    return '{'.$result.'}';
    //return "[{name: 'time', mapping: 't'},{name: 'value0', mapping: 'v0'},{name: 'value1', mapping: 'v1'}]";

    // return "[['da','da da']]";
}


function js_grid_info($table_map,$editable = null,$extraDS = null, $extraCM = null){
 
//foreach($field_list as $key => $value){
//    echo($key);
//
//$result[] = "{header:'".$value."',".
//            "dataIndex:'".$value."', sortable:true".
//
//"}";
//}
//$tt = sfInflector::camelize(strtolower('sort'));
// echo("<script>alert('".$tt."');</script>");
//$column = EtvaServerPeer::translateFieldName(sfInflector::camelize(strtolower('sort')), BasePeer::TYPE_PHPNAME, BasePeer::TYPE_COLNAME);

$result = array();
$i=0;
// $table = Propel::getDatabaseMap()->getTable('nodes');
 $cols = $table_map->getColumns();
 $ds_model = array();
 foreach ($cols as $column)
    {
        
        $attrs = array();
        $ds = array();
        $val = $column->getDefaultValue();
    //    $val = $column->getDefaultSetting();
      //  print_r($val);
    //    $val2 = $column->getPhpDefaultValue();
      //  echo($val.$val2);
      
        $attrs[] = "header:".json_encode($column->getPhpName());
        $attrs[] = "dataIndex:".json_encode($column->getPhpName());
        $attrs[] = "sortable:true";

        if($extraCM && array_key_exists($column->getPhpName(),$extraCM)){
            foreach($extraCM[$column->getPhpName()] as $k=>$v){
                $attrs[] = $k.":".$v;
            }

        }else{

            if($editable)
                if (!$column->isPrimaryKey())
                    $attrs[] = "editor: new Ext.form.TextField({allowBlank: false})";

        }

         if ($column->isPrimaryKey()) $pk = $column->getPhpName();
     //   $result[$i] = "{header:'".."',".
       //     "dataIndex:'".$column->getPhpName()."', sortable:true".

         //       "}";
      


       $ds[] = "name:".json_encode($column->getPhpName());
       // if(!empty($form))
       // $ds_model[] = $column->getPhpName().':'.json_encode($form->getDefault(strtolower($column->getName())));


     $attrs = "{".implode(",", $attrs)."}";
     $ds = "{".implode(",", $ds)."}";
  
     
       $result['cm'][$i] = $attrs;
       $result['ds'][$i] = $ds;       
      $i++;
      //  echo $column->getPhpName();
 }

if(!empty($extraDS))
 foreach ($extraDS as $extracolumn)
    {
$ds = "{name:".json_encode($extracolumn)."}";
$result['ds'][$i] = $ds;
$i++;
    }



 $result['pk'] = $pk;
// $result = implode(",", $attrs);
 //echo($table->getClassname());
 // print_r($table->);

//        foreach (Propel::getDatabaseMap()->getTables() as $table_map)
//{
//    echo $table_map->getName()."<br />";
//}

  // $ds_model = "{".implode(",", $ds_model)."}";

 $result['cm'] = implode(",", $result['cm']);
 $result['ds'] = implode(",", $result['ds']);
// $result['ds_model'] = implode(",", $ds_model);
// $result['ds_model'] = $ds_model;
 
return $result;

    
}

function fieldlist($field_list){
    

//    switch($object){
//        case $object instanceof EtvaNode: $fields = EtvaNodePeer::getFieldNames();
//                                      break;
//        default: $fields = array();
//    }
$result = array();
foreach($field_list as $key => $value){
$result[] = "{name:'".$value."'}";
 
}



$result = implode(",", $result);


// print_r($field_list);
//
//    foreach($widget->getFields() as $key => $object){
//   //     echo($object);
//    }
//
//    foreach ($form->getFormFieldSchema() as $name => $field){
//        echo(strtoupper($field->getName()));
//    }
    echo("<script>alert('aki')</script>");
    return $result;
}

function js_insert_model(sfForm $form, $default_values){
    $fieldSc = $form->getFormFieldSchema();
    $model_items = array();
   
    $widget = $fieldSc->getWidget();
    $validatorSchema = $form->getValidatorSchema();
    $result = array();
    $model_title = '';
   
    foreach($widget->getFields() as $key => $object){
        
        $name = $widget->generateName($key);
        $value = '';
      //  $name = $widget->getDefault($name);
        if($default_values['default']==$key) $model_title = json_encode($name);
        
        
           

            if(isset($validatorSchema[$key]) and $validatorSchema[$key]->getOption('required') == true) {

                $value = $form->getDefault($key);

                if(array_key_exists($key,$default_values['items'])){
                    $value = $default_values['items'][$key];
                }

                

            //$model_items[] = "'".$name."' : '".$value."'";
                $model_items_db[$name] = $value;
         //   $model_items_ds[$key] = '';
              
            }else{
                
                if(array_key_exists($key,$default_values['items'])){
                    $value = $default_values['items'][$key];
                    $model_items_db[$name] = $value;
                }
                
            }
        $model_items_ds[] = sfInflector::camelize($key).':'.json_encode($value);

    }
    
    $model_items_ds = "{".implode(",", $model_items_ds)."}";
    //die();
   // $model_items = "{".implode(",", $model_items)."}";
 //  $model_items = json_encode($model_items);
//   echo($model_items);
  // die();
    
    $result['db'] = $model_items_db;
    $result['ds'] = $model_items_ds;
    $result['title'] = $model_title;
    return $result;
}

function js_form_fields(sfForm $form){


    $fieldSc = $form->getFormFieldSchema();
$loginFormItems = '';
$widget = $fieldSc->getWidget();
$validatorSchema = $form->getValidatorSchema();

foreach($widget->getFields() as $key => $object){
    // echo($key);
    $label = $fieldSc->offsetGet($key)->renderLabelName();
    $type = $object->getOption('type');

    if($type=='text') $type = 'textfield';

    $name = $widget->generateName($key);
    $allowBlank = 'true';
    $extraItem = '';

    if($validatorSchema[$key] instanceOf sfValidatorCSRFToken){
        $csrfToken = $form->getDefault($key);
        $extraItem =  ",value:'".$csrfToken."'";
    }

    if(isset($validatorSchema[$key]) and $validatorSchema[$key]->getOption('required') == true) {
        $allowBlank = 'false';
    }

    $loginFormItems[] = "{id:'".$key."',fieldLabel: '".$label."',name: '".
               $name."',inputType:'".$type."',allowBlank:".
               $allowBlank.$extraItem."}\n\t\t";
}
// die();

$loginFormItems = implode(",", $loginFormItems);
// echo($loginFormItems);
// echo("<script>alert('oi');</script>");
return $loginFormItems;
}

/*
 * Create dynamic form fields from the form schema
 * TODO: create a helper??!!
 */

//$fieldSc = $form->getFormFieldSchema();
//$loginFormItems = '';
//$widget = $fieldSc->getWidget();
//$validatorSchema = $form->getValidatorSchema();
//
//foreach($widget->getFields() as $key => $object){
//    $label = $fieldSc->offsetGet($key)->renderLabelName();
//    $type = $object->getOption('type');
//
//    if($type=='text') $type = 'textfield';
//
//    $name = $widget->generateName($key);
//    $allowBlank = 'true';
//    $extraItem = '';
//
//    if($validatorSchema[$key] instanceOf sfValidatorCSRFToken){
//        $csrfToken = $form->getDefault($key);
//        $extraItem =  ",value:'".$csrfToken."'";
//    }
//
//    if(isset($validatorSchema[$key]) and $validatorSchema[$key]->getOption('required') == true) {
//        $allowBlank = 'false';
//    }
//
//    $loginFormItems[] = "{fieldLabel: '".$label."',name: '".
//               $name."',inputType:'".$type."',allowBlank:".
//               $allowBlank.$extraItem."}\n\t\t";
//}
//
//$loginFormItems = implode(",", $loginFormItems);
