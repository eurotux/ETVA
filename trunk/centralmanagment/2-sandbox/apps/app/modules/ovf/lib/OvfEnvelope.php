<?php

class OvfEnvelope
{
    private $references;
    private $disksection;
    private $virtualsystem;    

    public function __construct()
    {
        $this->references = new OvfReferences();
        $this->disksection = new OvfDiskSection();
        $this->virtualsystem = new OvfVirtualSystem();

    }
   
    public function getVirtualSystem()
    {
        return $this->virtualsystem;
    }

    public function getReferences()
    {
        return $this->references;
    }

    public function getDiskSection()
    {
        return $this->disksection;
    }

    /*
     * get disk info together
     */
    public function getDisks()
    {

        $aux = array();
        $disks = $this->disksection->getDisks();

        $disks_details = $this->virtualsystem->getDisks();

        foreach($disks as $disk_id => $disk_data){
            $disk_file_ref = $disk_data['file_ref'];
            $ref_file = $this->references->getFile($disk_file_ref);
            $disk_bus = isset($disks_details[$disk_id]) ? $disks_details[$disk_id] : '';
            $disk_details_array = $disk_bus ? $disk_bus->toArray() : array();
            $aux[$disk_id] = array_merge($disk_data,$ref_file,$disk_details_array);
        }
        return $aux;
    }

    public function getMemory($units = 'B')
    {
        $mem_array = $this->virtualsystem->getMemory();
        $all_mem = array_sum($mem_array);
        switch($units){
            case 'B' :
                        $all_mem = Etva::MB_to_Byteconvert($all_mem);
                        break;
            default :
                        break;

        }
        return $all_mem;
    }

    /*
     * get networks info together
     */
    public function getNetworks()
    {

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
        $loaded = $dom->load($url);
        if(!$loaded) return $loaded;

        $xpath = new DomXPath($dom);        
        // register the namespace
        $xpath->registerNamespace('envl', 'http://schemas.dmtf.org/ovf/envelope/1');

        $vs_nodes = $xpath->query('//envl:VirtualSystem'); // selects all name element

        $refs_nodes = $xpath->query('//envl:References'); // selects all name element

        $disk_nodes = $xpath->query('//envl:DiskSection'); // selects all name element        

        $this->buildReferences($refs_nodes->item(0));
        $this->buildDiskSection($disk_nodes->item(0));
        $this->buildVirtualSystem($vs_nodes->item(0));

        return true;

    }

    /*
     * build ovf references obj
     */
    private function buildReferences($nodes)
    {        

        $newDom = new DOMDocument;
        $newDom->appendChild($newDom->importNode($nodes,true));

        $xpath = new DOMXPath( $newDom );
        $xpath->registerNamespace('envl', 'http://schemas.dmtf.org/ovf/envelope/1');

        $fileNodes = $xpath->query('./envl:File');

        for($i=0;$i<$fileNodes->length;$i++) {
            $file_info = $fileNodes->item($i);
            $id = $file_info->getAttribute('ovf:id');
            $ref = $file_info->getAttribute('ovf:href');
            $size = $file_info->getAttribute('ovf:size');
            $this->references->setFile($id,$ref,$size);
            
        }

    }

    /*
     * build ovf disksection obj
     */
    private function buildDiskSection($nodes)
    {        
        $newDom = new DOMDocument;
        $newDom->appendChild($newDom->importNode($nodes,true));

        $xpath = new DOMXPath( $newDom );
        $xpath->registerNamespace('envl', 'http://schemas.dmtf.org/ovf/envelope/1');

        $info = $xpath->query('./envl:Info')->item(0)->nodeValue; //should be one item only
        $this->disksection->setInfo($info);

        $diskNodes = $xpath->query('./envl:Disk');        

        for($i=0;$i<$diskNodes->length;$i++) {
            $disk_info = $diskNodes->item($i);
            $disk_id = $disk_info->getAttribute('ovf:diskId');
            $file_ref = $disk_info->getAttribute('ovf:fileRef');
            $capacity = $disk_info->getAttribute('ovf:capacity');
            $this->disksection->setDisk($disk_id,$file_ref,$capacity);

        }

    }

    /*
     * build ovf eulasection obj
     */
    private function buildEulaSection($nodes)
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

    /*
     * build ovf eulasection obj
     */
    private function buildProductSection($nodes)
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

    /*
     * build ovf virtualhardwaresection obj
     */
    private function buildVirtualHardwareSection($nodes)
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
            $vhs->buildItem($item_info);           
        }       
        
        $this->virtualsystem->setVirtualHardwareSection($vhs);

    }

    
    /*
     * build ovf virtualsystem obj
     */
    private function buildVirtualSystem($nodes)
    {                
        $domNode = $nodes;

        $newDom = new DOMDocument;
        $newDom->appendChild($newDom->importNode($domNode,true));

        $xpath = new DOMXPath( $newDom );
        $xpath->registerNamespace('envl', 'http://schemas.dmtf.org/ovf/envelope/1');

        $dom_info = $xpath->query('./envl:Info');
        $dom_name = $xpath->query('./envl:Name');

        $info = $dom_info->item(0)->nodeValue;
        $name = $dom_name->item(0)->nodeValue;
        $this->virtualsystem->setInfo($info);
        $this->virtualsystem->setName($name);        

        $dom_product = $xpath->query('./envl:ProductSection')->item(0);
        if($dom_product) $this->buildProductSection($dom_product);

        $dom_hardware = $xpath->query('./envl:VirtualHardwareSection')->item(0);
        if($dom_hardware) $this->buildVirtualHardwareSection($dom_hardware);
    
        $dom_eula = $xpath->query('./envl:EulaSection')->item(0); //process only one EULA        
        if($dom_eula) $this->buildEulaSection($dom_eula);

    }
}


/*
 *  ovf eula section obj
 */
class OvfEulaSection
{
    private $info;
    private $license;


    function toArray()
    {
		$result = array('info'      => $this->info,
                        'license'   => $this->license);

		return $result;
    }

    function setInfo($info)
    {
        $this->info = $info;
    }

    function setLicense($license)
    {
        $this->license = $license;
    }

}


/*
 *  ovf virtual hardware section obj
 */
class OvfVirtualHardwareSection
{
    private $info;
    private $items;
    private $bus; //stores bus intances ids => types of bus
    
    const DEVICE_CPU = 3;
    const DEVICE_MEMORY = 4;
    const DEVICE_IDE_BUS = 5;
    const DEVICE_SCSI_BUS = 6;
    const DEVICE_ETHERNET = 10;
    const DEVICE_DISK = 17;
    const DEVICE_GRAPHICS = 24;     

    function setInfo($info)
    {
        $this->info = $info;
    }

    function OvfVirtualHardwareSection()
    {
        $this->bus = array();
        $this->items = array(self::DEVICE_CPU => array(),
                            self::DEVICE_MEMORY => array(),
                            self::DEVICE_IDE_BUS => array(),
                            self::DEVICE_SCSI_BUS => array(),
                            self::DEVICE_ETHERNET => array(),
                            self::DEVICE_DISK => array(),
                            self::DEVICE_GRAPHICS => array());
    }

    /*
     * returns items based on device type
     */
    public function getItems($type){
        return $this->items[$type];
    }

    /*
     * builds hardware objects based on node xml
     */
    public function buildItem($node)
    {

        $newDom = new DOMDocument;
        $newDom->appendChild($newDom->importNode($node,true));


        $xpath = new DOMXPath($newDom);
        $xpath->registerNamespace('envl', 'http://schemas.dmtf.org/wbem/wscim/1/cim-schema/2/CIM_ResourceAllocationSettingData');
        $rtype = $xpath->query('./envl:ResourceType')->item(0)->nodeValue; //should be one item only

        switch($rtype){
            case self::DEVICE_MEMORY   :
                                        $memory_amount = $xpath->query('./envl:VirtualQuantity')->item(0)->nodeValue; //should be one item only
                                        $this->items[self::DEVICE_MEMORY][] = $memory_amount;
                                        
                                        break;
            case self::DEVICE_ETHERNET :
                                        $type = $xpath->query('./envl:ResourceSubType')->item(0)->nodeValue; //should be one item only
                                        $ovf_ether = new OvfItemEthernet();
                                        $ovf_ether->setType(strtolower($type));
                                        $this->items[self::DEVICE_ETHERNET][] = $ovf_ether;
                                        break;

            case self::DEVICE_IDE_BUS :
                                        $instance_id = $xpath->query('./envl:InstanceID')->item(0)->nodeValue; //should be one item only
                                        $ovf_bus = new OvfItemIdeBus();
                                        $ovf_bus->setInstanceId($instance_id);

                                        $this->items[self::DEVICE_IDE_BUS][$instance_id] = $ovf_bus;
                                        $this->bus[$instance_id] = $ovf_bus;
                                        break;

           case self::DEVICE_SCSI_BUS :
                                        $instance_id = $xpath->query('./envl:InstanceID')->item(0)->nodeValue; //should be one item only
                                        $ovf_bus = new OvfItemScsiBus();
                                        $ovf_bus->setInstanceId($instance_id);

                                        $this->items[self::DEVICE_SCSI_BUS][$instance_id] = $ovf_bus;
                                        $this->bus[$instance_id] = $ovf_bus;
                                        break;

               case self::DEVICE_DISK :
                                        $host_resource = $xpath->query('./envl:HostResource')->item(0)->nodeValue; //should be one item only
                                        
                                        preg_match('/ovf:\/disk\/(.*)/',$host_resource,$matches);

                                        $disk_id = $matches[1];                                        

                                        $parent_id = $xpath->query('./envl:Parent')->item(0)->nodeValue; //should be one item only
                                        $ovf_disk = new OvfItemDisk();
                                        $ovf_disk->setParent($this->bus[$parent_id]);

                                        $this->items[self::DEVICE_DISK][$disk_id] = $ovf_disk;
                                        break;

                              default :
                                        break;

        }//end switch resourceType
    }
    
}


/*
 *  ovf ethernet item obj
 */
class OvfItemEthernet
{
    private $type; //model type of interface

    function setType($type)
    {
        $this->type = $type;
    }

    function toArray()
    {
		$result = array('IntfModel' => $this->type);
		return $result;
    }
}


/*
 *  ovf item bus obj
 */
class OvfItemBus
{
    private $instance_id;

    function setInstanceId($id)
    {
        $this->instance_id = $id;
    }
}

/*
 *  ovf item bus scsi obj
 */
class OvfItemScsiBus extends OvfItemBus
{

    function getBus()
    {
        return 'scsi';
    }
}

/*
 *  ovf item bus ide obj
 */
class OvfItemIdeBus extends OvfItemBus
{

    function getBus()
    {
        return 'ide';
    }
}


/*
 *  ovf item disk obj
 */
class OvfItemDisk
{
    private $parent;

    function setParent($parent)
    {
        $this->parent = $parent;
    }

    function toArray()
    {
        $bus = $this->parent->getBus();
		$result = array('bus' => $bus);
		return $result;
    }
}


/*
 *  ovf product section obj
 */
class OvfProductSection
{
    private $info;
    private $product;
    private $vendor;
    private $version;
    private $productUrl;
    private $vendorUrl;

    function toArray()
    {
		$result = array('info' => $this->info,
                        'product' => $this->product,
                        'vendor' => $this->vendor,
                        'version' => $this->version,
                        'productUrl' => $this->productUrl,
                        'vendorUrl' => $this->vendorUrl
		);
		return $result;
    }

    function setInfo($info)
    {
        $this->info = $info;
    }
    
    function setProduct($product)
    {
        $this->product = $product;        
    }

    function setVendor($vendor)
    {
        $this->vendor = $vendor;
    }
    
    function setVersion($version)
    {
        $this->version = $version;
    }

    function setProductUrl($productUrl)
    {
        $this->productUrl = $productUrl;
    }
    
    function setVendorUrl($vendorUrl)
    {
        $this->vendorUrl = $vendorUrl;
    }

}


/*
 *  ovf references obj
 */
class OvfReferences
{
    private $files;
    private $totalsize = 0;

    function setFile($id,$ref,$size)
    {
        $this->totalsize += $size;
        $this->files[$id] = array('id'=>$id,'href'=>$ref,'size'=>$size);
    }

    function getFile($id)
    {
        return $this->files[$id];
    }


    function getTotalSize()
    {
        return $this->totalsize;
    }
}


/*
 *  ovf disk section obj
 */
class OvfDiskSection
{
    private $disks;
    private $info;

    function setDisk($disk_id,$file_ref,$capacity)
    {        
        $this->disks[$disk_id] = array('disk_id'=>$disk_id,'file_ref'=>$file_ref,'capacity'=>$capacity);
    }

    function setInfo($info)
    {
        $this->info = $info;
    }

    function getDisks()
    {
        return $this->disks;
    }

}


/*
 *  ovf virtual system obj
 */
class OvfVirtualSystem
{
	private $info;
	private $name;
    /**
	 * @var        OvfProductSection
	 */
	private $productSection;
    private $virtualHardwareSection;
    private $eulaSection;

    function __construct()
    {
        $this->productSection = new OvfProductSection();
        $this->virtualHardwareSection = new OvfVirtualHardwareSection();
        $this->eulaSection = new OvfEulaSection();
    }

    function setInfo($info)
    {
        $this->info = $info;
    }

    function setName($name)
    {
        $this->name = $name;
    }

    function getName()
    {
        return $this->name;
    }


    function getMemory()
    {
        $vhs = $this->virtualHardwareSection;
        $items = $vhs->getItems(OvfVirtualHardwareSection::DEVICE_MEMORY);
        return $items;
    }


    function getNetworks()
    {
        $vhs = $this->virtualHardwareSection;
        $items = $vhs->getItems(OvfVirtualHardwareSection::DEVICE_ETHERNET);
        return $items;
    }

    function getDisks()
    {
        $vhs = $this->virtualHardwareSection;
        $items = $vhs->getItems(OvfVirtualHardwareSection::DEVICE_DISK);
        return $items;
    }

    function toArray()
    {
		$result = array('info' => $this->info,
                        'name' => $this->name);
		return $result;
    }


    function getProductSection()
    {
        return $this->productSection;
    }

    /*
     * adds OvfProductSection object
     */
    function setProductSection(OvfProductSection $ps)
    {
        $this->productSection = $ps;
    }

    /*
     * adds OvfVirtualHardwareSection object
     */
    function setVirtualHardwareSection(OvfVirtualHardwareSection $vhs)
    {
        $this->virtualHardwareSection = $vhs;
    }

    /*
     * adds OvfEulaSection object
     */
    function setEulaSection(OvfEulaSection $eula)
    {
        $this->eulaSection = $eula;
    }

    function getEulaSection()
    {
        return $this->eulaSection;
    }
}

?>