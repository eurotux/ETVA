<?php


class etfwComponents extends sfComponents
{
    public function executeETFW_webmin_iframe()
    {
        $params = $this->etva_service->getParams();

        $params_decoded = json_decode($params,true);

        $this->url = $params_decoded['url'];

    }

    public function executeETFW_dhcp_main()
    {
        $this->clientOptionsForm = new DhcpClientoptionsForm();
        $this->subnetForm = new DhcpSubnetForm();
        $this->sharednetworkForm = new DhcpSharednetworkForm();
        $this->poolForm = new DhcpPoolForm();
        $this->hostForm = new DhcpHostForm();
        $this->groupForm = new DhcpGroupForm();
    }
    

}
