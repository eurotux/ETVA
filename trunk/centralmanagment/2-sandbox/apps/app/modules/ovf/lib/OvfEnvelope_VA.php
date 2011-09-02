<?php

class OvfEnvelope_VA{    
    private $url;
    private $name;
    private $description;
    private $vm_os;
    private $vnc_keymap;
    private $disks;
    private $uuid;

    const UUID = 'uuid';
    const URL = 'url';
    const NAME = 'name';
    const DESCRIPTION = 'description';
    const VM_OS = 'vm_os';
    const VNC_KEYMAP = 'vnc_keymap';
    const DISKS = 'disks';
    const NETWORKS = 'networks';

    const _ERR_PARSING_ = 'Could not parse OVF %url%';
    const _ERR_IMPORT_   = 'Could not import OVF. %info%';

    public function OvfEnvelope_VA(){
        $this->disks = array();
    }
    
    public function setDisk($id,$info)
    {
        $disk = array();
        if($info['vg']) $disk['vg'] = $info['vg'];
        if($info['lv']) $disk['lv'] = $info['lv'];
        if($info['size']) $disk['size'] = $info['size'];
        $this->disks[$id] = $disk;

    }
    

    public function setNetwork($info)
    {
        $network = array();
        if($info['macaddr']) $network['macaddr'] = $info['macaddr'];
        if($info['network']) $network['network'] = $info['network'];
        $this->networks[] = $network;

    }
    
    public function _VA()
    {        


        $ovf_VA = array('ovf_url'=>$this->url,
                        'name'=>$this->name,
                        'description'=>$this->description,
                        'vm_os' => $this->vm_os,
                        'uuid'=>$this->uuid,
                        'vnc_keymap'=>$this->vnc_keymap,
                        'vnc_listen'=>'any',
                        'Disks' => $this->disks,
                        'Networks' => $this->networks);
        return $ovf_VA;
    }

    public function fromArray($data)
    {
        if(isset($data[self::URL])){
            $this->url = $data[self::URL];
        }

        if(isset($data[self::UUID])){
            $this->uuid = $data[self::UUID];
        }

        if(isset($data[self::NAME])){
            $this->name = $data[self::NAME];
        }

        if(isset($data[self::DESCRIPTION])){
            $this->description = $data[self::DESCRIPTION];
        }

        if(isset($data[self::VM_OS])){
            $this->vm_os = $data[self::VM_OS];
        }

        if(isset($data[self::VNC_KEYMAP])){
            $this->vnc_keymap = $data[self::VNC_KEYMAP];
        }

        if(isset($data[self::DISKS])){
            foreach($data[self::DISKS] as $id => $info)
            $this->setDisk($id, $info);
        }

        if(isset($data[self::NETWORKS])){
            foreach($data[self::NETWORKS] as $info)
            $this->setNetwork($info);
        }

//        if(isset($data[self::URL])){
//            $this->url = $data[self::URL];
//        }
        

    }


    public function getVirtualSystem(){
        return $this->virtualsystem;
    }

    public function getReferences(){
        return $this->references;
    }

    public function getDiskSection(){
        return $this->disksection;
    }


    public function getDisks(){

        $aux = array();
        $disks = $this->disksection->getDisks();

        foreach($disks as $disk_id => $disk_data){
            $disk_file_ref = $disk_data['file_ref'];
            $ref_file = $this->references->getFile($disk_file_ref);
            $aux[$disk_id] = array_merge($disk_data,$ref_file);
        }
        return $aux;
    }

    public function getNetworks(){

        $aux = array();
        $networks = $this->virtualsystem->getNetworks();

        foreach($networks as $network){                        
            $aux[] = $network->toArray();
        }
        return $aux;
    }

    public function ovfImport($url,$xpath=false)
    {
        $this->url = $url;
        $dom = new DomDocument();
        $dom->load($url);

        
        $xpath = new DomXPath($dom);
        // register the namespace
        $xpath->registerNamespace('envl', 'http://schemas.dmtf.org/ovf/envelope/1');

        $vs_nodes = $xpath->query('//envl:VirtualSystem'); // selects all name element

        $refs_nodes = $xpath->query('//envl:References'); // selects all name element

        $disk_nodes = $xpath->query('//envl:DiskSection'); // selects all name element

        //$vs_nodes = $xpath->query('//envl:References | //envl:DiskSection | //envl:VirtualSystem'); // selects all name element

        //
        //$vs_nodes = $xpath->query('//envl:References/envl:File'); // selects all name element
        //
        //print(" TAMM ".$vs_nodes->length." /TAMM ");
        //
        //$vs_nodes = $xpath->query('//book'); // selects all name element

        $this->buildReferences($refs_nodes->item(0));
        $this->buildDiskSection($disk_nodes->item(0));
        $this->buildVirtualSystem($vs_nodes->item(0));
//$bookNodes = $xpath->query('//DiskSection/Info'); // selects all name element



    }

    public function buildReferences($nodes)
    {
        $this->references = new OvfReferences();

        $newDom = new DOMDocument;
        $newDom->appendChild($newDom->importNode($nodes,true));

        $xpath = new DOMXPath( $newDom );
        $xpath->registerNamespace('envl', 'http://schemas.dmtf.org/ovf/envelope/1');

        $fileNodes = $xpath->query('./envl:File');

//        print(" file nodes ".$fileNodes->length." /file nodes ");

        for($i=0;$i<$fileNodes->length;$i++) {
            $file_info = $fileNodes->item($i);
            $id = $file_info->getAttribute('ovf:id');
            $ref = $file_info->getAttribute('ovf:href');
            $size = $file_info->getAttribute('ovf:size');
            $this->references->setFile($id,$ref,$size);
            
        }

        
//.getAttribute('ovf:id')
    

    


        

       // $refs->addReference($id, $ref);

    //    print_r($refs);


    }


    public function buildDiskSection($nodes)
    {        
        $newDom = new DOMDocument;
        $newDom->appendChild($newDom->importNode($nodes,true));

        $xpath = new DOMXPath( $newDom );
        $xpath->registerNamespace('envl', 'http://schemas.dmtf.org/ovf/envelope/1');

        $info = $xpath->query('./envl:Info')->item(0)->nodeValue; //should be one item only
        $this->disksection = new OvfDiskSection($info);

        $diskNodes = $xpath->query('./envl:Disk');        

        for($i=0;$i<$diskNodes->length;$i++) {
            $disk_info = $diskNodes->item($i);
            $disk_id = $disk_info->getAttribute('ovf:diskId');
            $file_ref = $disk_info->getAttribute('ovf:fileRef');
            $capacity = $disk_info->getAttribute('ovf:capacity');
            $this->disksection->setDisk($disk_id,$file_ref,$capacity);

        }

    }


    public function buildEulaSection($nodes)
    {

        $newDom = new DOMDocument;
        $newDom->appendChild($newDom->importNode($nodes,true));

        $xpath = new DOMXPath($newDom);
        $xpath->registerNamespace('envl', 'http://schemas.dmtf.org/ovf/envelope/1');
        $info = $xpath->query('./envl:Info')->item(0)->nodeValue; //should be one item only
        $license = $xpath->query('./envl:License')->item(0)->nodeValue; //should be one item only


        $eula = new OvfEulaSection();
        $eula->setInfo($info);
        $eula->setLicense($license);

        $this->virtualsystem->setEulaSection($eula);
    }

    public function buildProductSection($nodes)
    {

        $newDom = new DOMDocument;
        $newDom->appendChild($newDom->importNode($nodes,true));

        $xpath = new DOMXPath($newDom);
        $xpath->registerNamespace('envl', 'http://schemas.dmtf.org/ovf/envelope/1');
        $info = $xpath->query('./envl:Info')->item(0)->nodeValue; //should be one item only
        $product = $xpath->query('./envl:Product')->item(0)->nodeValue; //should be one item only
        $vendor = $xpath->query('./envl:Vendor')->item(0)->nodeValue; //should be one item only
        $version = $xpath->query('./envl:Version')->item(0)->nodeValue; //should be one item only
        $productUrl = $xpath->query('./envl:ProductUrl')->item(0)->nodeValue; //should be one item only
        $vendorUrl = $xpath->query('./envl:VendorUrl')->item(0)->nodeValue; //should be one item only
        

        $ps = new OvfProductSection();
        $ps->setInfo($info);
        $ps->setProduct($product);
        $ps->setVendor($vendor);
        $ps->setVersion($version);
        $ps->setProductUrl($productUrl);
        $ps->setVendorUrl($vendorUrl);

        $this->virtualsystem->setProductSection($ps);
    }


    public function buildVirtualHardwareSection($nodes)
    {

        $newDom = new DOMDocument;
        $newDom->appendChild($newDom->importNode($nodes,true));

        $xpath = new DOMXPath($newDom);
        $xpath->registerNamespace('envl', 'http://schemas.dmtf.org/ovf/envelope/1');
        $info = $xpath->query('./envl:Info')->item(0)->nodeValue; //should be one item only

        $vhs = new OvfVirtualHardwareSection();
        $vhs->setInfo($info);
        $itemNodes = $xpath->query('./envl:Item');


        
        for($i=0;$i<$itemNodes->length;$i++) {
            $item_info = $itemNodes->item($i);
//print_r($item_info);
  //          print(" TAMM ".$item_info->length." /TAMM ");

            $vhs->buildItem($item_info);           
        }       
        
        $this->virtualsystem->setVirtualHardwareSection($vhs);

    }

    public function buildVirtualSystem($nodes)
    {        
        
        
        $bookList  = array();
      //  print("TAM ".$nodes->length."/TAM");
//for($i=0;$i<$nodes->length;$i++) {
    //$domNode = $nodes->item($i);
$domNode = $nodes;

    $newDom = new DOMDocument;
	$newDom->appendChild($newDom->importNode($domNode,true));

	$xpath = new DOMXPath( $newDom );
    $xpath->registerNamespace('envl', 'http://schemas.dmtf.org/ovf/envelope/1');
//    $nodess = $xpath->query('.name');
//    print("TIM ".$nodess->length."/TIM");
//
//    $title = $nodess->item(0)->nodeValue;
//    echo($title);

    //$xpath->query('./envl:Info | ./envl:Info');

    $dom_info = $xpath->query('./envl:Info');
    $dom_name = $xpath->query('./envl:Name');
    
 //   print("TIM ".$dom_info->length.' : '.$dom_name->length."/TIM");

    $info = $dom_info->item(0)->nodeValue;
    $name = $dom_name->item(0)->nodeValue;
    $this->virtualsystem->setInfo($info);
    $this->virtualsystem->setName($name);

    $dom_product = $xpath->query('./envl:ProductSection')->item(0);
   // print("pSection ".$dom_product->length." /pSection ");
    $this->buildProductSection($dom_product);

    $dom_hardware = $xpath->query('./envl:VirtualHardwareSection')->item(0);
   // print("pSection ".$dom_product->length." /pSection ");
    $this->buildVirtualHardwareSection($dom_hardware);
    
    $dom_eula = $xpath->query('./envl:EulaSection')->item(0); //process only one EULA
    $this->buildEulaSection($dom_eula);




    
    
    
    //$this->virtualsystem->addProductSection($ps);

       
    

	//$title = trim($xpath->query("/name")->item(0)->nodeValue);
//	$price = trim($xpath
//                   ->query("//li[@class='price']/span[@class='value']")
//                   ->item(0)->nodeValue);


    
 
//}
//print_r($this->virtualsystem);

    }
}


//f( $n eq 'References' ){
//936 	                for my $ref_ch ($ch->getChildNodes()){
//937 	                    if( $ref_ch->getNodeName() eq 'File' ){
//938 	                        my $file_id = $ref_ch->getAttributeNode('id')->getValue();
//939 	                        my $path = $ref_ch->getAttributeNode('href')->getValue();
//940 	                        if( $file_id && $path ){
//941 	                            $file_refs->{"$file_id"} = $path;
//942
//943 	                            # TODO need get disk file
//944 	                        }
//945 	                    }
//946 	                }


?>