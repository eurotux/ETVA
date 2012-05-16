<div class="help">
<div class="help-section">
<a id="help-network-list-main"><h1>List of server interfaces</h1></a>
<p>
The management interfaces window consists of a grid that lists the existing interfaces for a given server, and indicates the corresponde <em> MAC Address</em> and the network to which it is connected.
</p>

<p>The following options are available on the network interfaces, which can be accessed via the context menu:</p>
<ul>
    <li><a href="#help-network-list-manage">Manage network interfaces</a></li>
    <li><a href="#help-network-list-remove">Remove network interfaces</a></li>
</ul>
<p><b>Note: </b>For the changes to take effect, the virtualization agent must be running.</p>
<br/>
<hr/>

<a id="help-network-list-manage"><h1>Manage network interfaces</h1></a>
<p>This operation is a shortcut that opens the network management interface. You can see more information <a href="#help-network-main">here</a>.</p>
<hr/>

<a id="help-network-list-remove"><h1>Remove network interfaces</h1></a>
<p>This operation is a shortcut that removes the selected network interface. You can see more information <a href="#help-network-delete">here</a>.</p>
<hr/>
</div>

<div class="help-section">
<a id="help-network-main"><h1>Attach/detach network interfaces for a server</h1></a>

<p>
The management interfaces window consists of a grid that lists the existing interfaces for a given server, and indicates the corresponding <em> MAC Address</em> and the network to which it is connected.
</p>

<p>The following options are available:</p>
<ul>
    <li><a href="#help-network-add">Add interface</a></li>
    <li><a href="#help-network-edit">Edit interface</a></li>
    <li><a href="#help-network-delete">Remove interface</a></li>
    <li><a href="#help-network-moveupdown">Move up/down</a></li>
</ul>
<p><b>Note: </b>For the changes to take effect, the virtualization agent must be running.</p>
<br/>
<hr/>


<a id="help-network-add"><h1>Add interface</h1></a>
<p>To create a network interface, select <em>Add interface </em> in the top options bar.
A line is added to the list of interfaces. Then you must select a network and the driver to use.
</p>

<p>
To select the network you double click on the text <em>Select a network</em>, which appears in the column <em> Network </em>
</p>
<p>The procedure required to choose the driver is analogous. Just make double click on the text <em>Select Model</em>
</p>
<a href="#help-network-main"><div>Index</div></a>
<hr/>

<a id="help-network-edit"><h1>Edit interface</h1></a>
<p>
To edit a network interface, select <em>Edit interface</em> in the top options bar.
Then you can select a network to which the interface will be associated.
In addition to this option is also possible to choose the type of driver to use.
</p>

<p><b>Note 1: </b>
Alternatively you can choose to select the interface you want to change and make double click in the column <em> Network </em>.
<p><b>Note 2: </b>
On KVM virtual machines, it is recommended to use the virtio driver. However, this option requires the installation of its driver in the virtual machine </p>
<a href="#help-network-main"><div>Index</div></a>
<hr/>

<a id="help-network-delete"><h1>Remove interface</h1></a>
<p>
To remove a network interface, select the interface in question and choose <em>Delete interface </em> in the top options bar.
</p>
<a href="#help-network-main"><div>Index</div></a>
<hr/>

<a id="help-network-moveupdown"><h1>Move up/down</h1></a>
<p>
This option lets you change the order of network interfaces.
</p>
<a href="#help-network-main"><div>Index</div></a>
<hr/>

</div>
</div>
