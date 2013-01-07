<div class="help">
<div class="help-section">
    <a id="help-clusterWz-main"><h1>Cluster Setup Wizard</h1></a>
    <p>
        The cluster creation wizard, guides you through the following steps:
    </p>

    <ul>
        <li><a href="#help-clusterWz-name">Cluster name</a></li>
        <li><a href="#help-clusterWz-net">Network setup</a></li>
    </ul>
    <br/>
    <hr/>

    <a id="help-clusterWz-name"><h2>Cluster name</h2></a>
    <p>
    In this step you create the new datacenter. Must indicate the name you want and select the option to <em>create</em></p>
    <p>
    A message with operation success will appear and if the data center has been created, the next option will become available.
    </p>
    <a href="#help-clusterWz-main"><div>Index</div></a>
    <hr/>

    <a id="help-clusterWz-net"><h2>Network setup</h2></a>
    <p>
    In this step you can add networks. There may be some pre-defined networks.
    </p>
    <p>
    After the required changes press the <em>next</em> button.
    </p>
    <a href="#help-clusterWz-main"><div>Index</div></a>
    <hr/>


    <a id="help-edit"><h1>Edit virtual datacenter</h1></a>
    <p>Editing a virtual datacenter allow us to change the following configurations:</p>

    <ul>
        <li><a href="#help-edit-name">Datacenter name</a></li>
        <li><a href="#help-edit-nodeha">Node High availability</a></li>
    </ul>
    <br/>
    <hr/>

    <a id="help-edit-name"><h2>Datacenter name</h2></a>
    <p>In this form we can change the name of <em>virtual datacenter</em>.</em></p>
    <p><b>Note: </b>Then must be start by char and must contain chars, numbers, hyphen and </em>underscore</em>.</p>
    <a href="#help-edit"><div>Index</div></a>
    <hr/>

    <a id="help-edit-nodeha"><h2>Node High availability</h2></a>
    <p>In this option we can activate high availability for the <em>datacenter</em> nodes according of one of this options:</p>
    <p><b>Host failures tolerates: </b>number of hosts in failure that we can garantee high availability with restrictions of resources allocation;</p>
    <p><b>Percentage of resources reserved to failover: </b>percentage of resources reserved to garantee the high availability of critical services;</p>
    <p><b>Spare node: </b>it’s define one <em>spare node</em> that will be used for garantee the high availability of one of the others. This <em>spare node</em> should have necessary resources to ensure the availability of critical virtual servers of fail node.</p>
    <p><b>Note: </b> The option <em>Node High availability</em> will be enable only if fencing configuration is defined for all node.</p>
    <a href="#help-edit"><div>Index</div></a>
    <hr/>
</div>

