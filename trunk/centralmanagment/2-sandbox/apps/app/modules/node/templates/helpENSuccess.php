<div class="help">
<a id="help-vmachine-main"><h1>Options for server management</h1></a>
<p>You have the following options for the management of virtual servers:</p>
<ul>
    <li><a href="#help_edit_node">Edit node</a></li>
    <li><a href="#help_virtual_machine_add">Add a new server</a></li>
    <li><a href="#help_vmachine_edit">Edit server</a></li>
    <li><a href="#help-vmachine-remove">Remove server</a></li>
    <li><a href="#help-vmachine-vnc">Connect to the server via VNC</a></li>
    <li><a href="#help-vmachine-startstop">Start/stop virtual machine</a></li>
    <li><a href="#help-vmachine-migrate">Migrate server</a></li>
</ul>
<br/>
<hr/>

<a id="help_edit_node"><h1>Edit node</h1></a>
<p>Editing a node allow us to change the following configurations:</p>

<ul>
    <li><a href="#help-edit-name">Node name</a></li>
    <li><a href="#help-edit-fencing">Enable <em>fencing</em> configuration</a></li>
</ul>
<br/>
<hr/>

<a id="help-edit-name"><h2>Node name</h2></a>
<p>In this form we can change the name of <em>node</em>.</em></p>
<p><b>Note: </b>Then must be start by char and must contain chars, numbers, hyphen and </em>underscore</em>.</p>
<a href="#help_edit_node"><div>Index</div></a>
<hr/>

<a id="help-edit-fencing"><h2>Enable <em>fencing</em> configuration</h2></a>
<p>In this options we can activate <em>fencing</em> device for the node management.
To enable this we need configure the parameters based on the following types of devices: bladecenter, virsh, ilo, ipmilan, rsa.</p>
<a href="#help_edit_node"><div>Index</div></a>
<hr/>

<a id="help_virtual_machine_add"><h1>Add server wizard</h1></a>
<p>The server creation wizard enables you to create virtual machines.
It comprises the following steps:</p>

<ul>
    <li><a href="#help-virtial-machine-name">Virtual server name</a></li>
    <li><a href="#help-virtial-machine-mem">Memory</a></li>
    <li><a href="#help-virtial-machine-cpu">Processor</a></li>
    <li><a href="#help-virtial-machine-storage">Storage</a></li>
    <li><a href="#help-virtial-machine-network">Host network</a></li>
    <li><a href="#help-virtial-machine-start">Startup</a></li>
    <li><a href="#help-virtial-machine-finalization">Finished!</a></li>
</ul>
<br/>
<hr/>

<a id="help-virtial-machine-name"><h2>Virtual server name:</h2></a>
<p>This step defines the virtual machine name and the type of operating system.
The operating system options vary depending on the specification of the node:
</p>
<ul>
    <li>with XEN and hardware virtualization support:
        <ul>
            <li>Linux PV</li>
            <li>Linux HVM</li>
            <li>Windows</li>
        </ul>
    </li>
    <li>with XEN without hardware virtualization support:
        <ul>
            <li>Linux PV</li>
        </ul>
    </li>
    <li>with KVM
        <ul>
            <li>Linux PV</li>
            <li>Linux HVM</li>
            <li>Windows</li>
        </ul>
    </li>
</ul>
<br/>

<a href="#help_virtual_machine_add"><div>Index</div></a>
<hr/>

<a id="help-virtial-machine-mem"><h2>Memory:</h2></a>
<p>Specifies the amount of memory to be used by the virtual machine.</p>

<a href="#help_virtual_machine_add"><div>Index</div></a>
<hr/>

<a id="help-virtial-machine-cpu"><h2>Processor:</h2></a>
<p>Number of cpu cores that can be used by the virtual machine.</p>

<a href="#help_virtual_machine_add"><div>Index</div></a>
<hr/>

<a id="help-virtial-machine-storage"><h2>Storage:</h2></a>
<p>In this step you define the storage type:</p>

<ul>
    <li>use a pre-existing logical volume/file - <em>Existing logical volume</em></li>

    <li>create a new logical volume/file (to create a file with this option, you have to select the volume group <em>__DISK__</em> - <em>New logical volume</em>)</li>
    <li>or if you want to create a file use the <em>New Disk File</em>. It only requires the volume name and size.</li>
</ul>

<p><b>Note </b>If the node does not support physical volumes, the <em>Existing logical volume</em> option is disabled.</p>
<a href="#help_virtual_machine_add"><div>Index</div></a>
<hr/>

<a id="help-virtial-machine-network"><h2>Host network:</h2></a>
<p>
Specification of network interfaces on the server. If there are no available MAC addresses, you can create new ones through the <em> Pool Management </em> button. Also, you can add new networks with the <em> Add network</em> button.
</p>

<a href="#help_virtual_machine_add"><div>Index</div></a>
<hr/>

<a id="help-virtial-machine-start"><h2>Startup:</h2></a>
<p>Specifies the startup parameters of the virtual machine. The options in this step vary depending on the type system defined in step <a href="#help-virtial-machine-name"><em>Virtual server name</em></a>:</p>
<ul>
    <li><em>Linux PV</em>
        <ul>
            <li>Network installation - url.</li>
        </ul>
    </li>
    <li><em>Others</em>
        <ul>
            <li>Network boot (PXE)</li>
            <li>CD-ROM (ISO)</li>
        </ul>
    </li>
</ul>

<br/>
<a href="#help_virtual_machine_add"><div>Index</div></a>
<hr/>

<a id="help-virtial-machine-finalization"><h2>Finished:</h2></a>
<p>Final step of the wizard. After confirmation, the data collected in previous steps are processed and sent to the virtualization server. Later in the <em>Servers</em> pane, the virtual machine can be started.</p>
<a href="#help_virtual_machine_add"><div>Index</div></a>

<!-- SERVER EDIT -->

<hr/>
<hr/>


<a id="help_vmachine_edit"><h1>Edit server</h1></a>
<p>Editing a virtual machine allow us change the following configurations:</p>

<ul>
    <li><a href="#help-vmachine-general">General options</a></li>
    <li><a href="#help-vmachine-net">Network interfaces</a></li>
    <li><a href="#help-vmachine-disks">Disks</a></li>
</ul>
<br/>
<hr/>

<a id="help-vmachine-general"><h2>General options:</h2></a>
<p>
In this panel we can change the name, memory, keymap options, and boot parameters. The boot parameters vary depending on the virtual machine type.
</p>
<a href="#help_vmachine_edit"><div>Index</div></a>
<hr/>

<a id="help-vmachine-net"><h2>Network interfaces:</h2></a>
<p>Add/remove network interfaces. You can change the type of driver to use (if the virtual machine is HVM and KVM).
</p>
<a href="#help_vmachine_edit"><div>Index</div></a>
<hr/>

<a id="help-vmachine-disks"><h2>Disks:</h2></a>
<p>Add/remove disks. You can change the type of driver to use (if the virtual machine is HVM and KVM).</p>
<p><b>Note: </b>The boot disk is the first of the table.</p>
<a href="#help_vmachine_edit"><div>Index</div></a>
<hr/>

<!-- REMOVE SERVER -->
<a id="help-vmachine-remove"><h1>Remove server</h1></a>
<p>To remove a server, select the pretended server and click on the <em>Remove server</em> button.</p>
<p>The <em>Keep disk</em> option allow us to keep the virtual machine disk.</p>
<a href="#help-vmachine-main"><div>Index</div></a>
<hr/>

<!-- VNC -->
<a id="help-vmachine-vnc"><h1>Connect to the server via VNC</h1></a>
<p>By selecting a server and then clicking on the <em>Open console</em> you can establish a VNC connection to the server.</p>


<p><b>Note: </b>If the keyboard is mangled you can change the keymap through the <em>Change vnc keymap</em> option from the context menu of the nodes' panel.
</p>
<a href="#help-vmachine-main"><div>Index</div></a>
<hr/>

<!-- START STOP -->
<a id="help-vmachine-startstop"><h1>Start/stop server</h1></a>
<p>At the startup the virtual machine you can choose one of the following boot parameter:</p>
<p><b>Disk: </b>Start from the associated disk.</p>
<p><b>PXE: </b>Start by PXE (only available if the virtual machine is not <em> PV Linux</em>).</p>
<p><b>Location URL: </b>Start from the url (only available if the virtual machine is <em> PV Linux</em>).</p>
<p><b>CD-ROM: </b>Boot from CD-ROM image (only available if the virtual machine is not <em> PV Linux</em>).</p>
<a href="#help-vmachine-main"><div>Index</div></a>
<hr/>

<!-- MIGRAR MÁQUINA VIRTUAL -->
<a id="help-vmachine-migrate"><h1>Server migrate</h1></a>
<p>
Selecting a server and then clicking <em>Migrate server</em> is possible to migrate a machine from one node to another since they share the same storage. The migration of a virtual machine is made in offline mode.
</p>
<p><b>Note: </b>This option is only available on <em>Enterprise</em> version.
</p>
<a href="#help-vmachine-main"><div>Index</div></a>
<hr/>
</div>
