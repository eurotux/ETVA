<div class="help">
<div class="help-section">
    <a id="help-firttimeW-main"><h1>One-Time Setup Wizard</h1></a>
    <p>The initial setup wizard, guides you through the following steps:</p>
    <ul>
        <li><a href="#help-firttimeW-password">Default password change</a></li>
        <li><a href="#help-firttimeW-mac">MAC pool generation</a></li>
        <li><a href="#help-firttimeW-net">System preferences</a></li>
    </ul>
    <br/>
    <hr/>

    <a id="help-firttimeW-password"><h2>Default password change</h2></a>
    <p>For security reasons, on the first time the password should be changed. Fill in the fields and press <em>Set new password!</em>.</p>
    <p>Note that the <em>next</em> button is only available after you change the password.</p>
    <a href="#help-firttimeW-main"><div>Index</div></a>
    <hr/>

    <a id="help-firttimeW-mac"><h2>MAC pool generation</h2></a>
    <p>In this step we can generate a pool of MAC addresses to be assigned to machines' network interfaces.
    Use an approximate value, since it is possible to add addresses later.
    </p>
    <a href="#help-firttimeW-main"><div>Index</div></a>
    <hr/>

    <a id="help-firttimeW-net"><h2>System preferences</h2></a>
    <p>In step <em>System preferences</em> we can change some system preferences such as network management, remote connection, and the system event log.
    </p>
    <p>To change these options press <em>Manage Preferences</em>.
    </p>
    <a href="#help-firttimeW-main"><div>Index</div></a>
    <hr/>
</div>

<div class="help-section">
    <a id="help-left-panel-main"><h1><em><b>Nodes</b></em> (left panel)</h1></a>

    <p>The nodes' panel belongs to the main panel and lists the real machines and virtualization servers (machines associated on each node).</p>
     <p>When selecting a node (virtualization server) the corresponding information is loaded in the main panel (right panel).</p>
     <p>Clicking on the right mouse button over the node brings up the options menu (context menu), where we can to do the following operations: </p>

    <ul>
        <li>Initialization</li>
        <ul>
            <li><a href="#help-left-panel-authorize">Authorize</a></li>
            <li><a href="#help-left-panel-restart">Re-initialize</a></li>
        </ul>
        <li>Node</li>
        <ul>
            <li><a href="#help-left-panel-loadnode">Load node</a></li>
            <li><a href="#help-left-panel-edit">Edit <em>Node</em></a></li>
            <li><a href="#help-left-panel-connectivity">Connectivity settings</a></li>
            <li><a href="#help-left-panel-keymap">Set keymap</a></li>
            <li><a href="#help-left-panel-addperm">Set permissions</a></li>
            <li><a href="#help-left-panel-status">Check node status</a></li>
            <li><a href="#help-left-panel-maintenance">Maintenance/Recover</a></li>
            <li><a href="#help-left-panel-shutdown">Shutdown</a></li>
            <li><a href="#help-left-panel-remove">Remove node</a></li>            
            <li><a href="#help-left-panel-addperm">Set permissions</a></li>
        </ul>
        <li>Server</li>
        <ul>
            <li><a href="#help-left-panel-keymap">Alterar keymap</a></li>
            <li><a href="#help-left-panel-addperm">Set permissions</a></li>
            <li><a href="#help-left-panel-start_stop">Start/Stop</a></li>
        </ul>
    </ul>
    <br/>
    <hr/>

    <a id="help-left-panel-authorize"><h1>Authorize</h1></a>
    <p></p>
    <p>When a node, with the agent virtualization agent installed, is added to the network, the central management is notified of its existence, and the node is listed on the tree node.
    </p>
    <p>Its necessary to authorize the node in order to access the management options.
    </p>
    <a href="#help-left-panel-main"><div>Index</div></a>
    <hr/>

    <a id="help-left-panel-restart"><h1>Re-initialize</h1></a>
    <p>The option <em>Re-initialize</em> restarts the node's virtualization agent.</p>
    <p><b>Note: </b>On existing servers of the node, there are not any kind of modification. </p>
    <a href="#help-left-panel-main"><div>Index</div></a>
    <hr/>

    <a id="help-left-panel-loadnode"><h1>Load node</h1></a>
    <p>By selecting this option a request is sent to Central Management in order to update the node state.
    </p>
    <a href="#help-left-panel-main"><div>Index</div></a>
    <hr/>

    <a id="help-left-panel-hostname"><h1>Change hostname</h1></a>
    <p>The <em>Change hostname</em> option allow us change the name of the virtualization server (nodes).</p>
    <a href="#help-left-panel-main"><div>Index</div></a>
    <hr/>

    <a id="help-left-panel-edit"><h1>Edit Node</h1></a>
    <p><em>Edit Nó</em> allow to change some settings of the virtualization server like host name and <em>fencing</em> configuration.</p>
    <a href="#help-left-panel-main"><div>Index</div></a>
    <hr/>

    <a id="help-left-panel-connectivity"><h1>Connectivity settings</h1></a>
    <p>In <em>Connectivity settings</em>, we can configure the management interface.</p>
    <p>Here its possible to define the IP address and DNS server.</p>
    <p><b>Note: </b>This option is only available on <em>Enterprise</em> version.</p>
    <a href="#help-left-panel-main"><div>Index</div></a>
    <hr/>

    <a id="help-left-panel-keymap"><h1>Set keymap</h1></a>
    <p>Allow us to change the keyboard layout, on selected node/server.</p>
    <a href="#help-left-panel-main"><div>Index</div></a>
    <hr/>

    <a id="help-left-panel-addperm"><h1>Set permissions</h1></a>
    <p>Allow us to setup who has access on the server or to the cluster (if you right click over a node).</p>  
    <p>In the window that appears, select the users/groups you want to give access.</p>
    <a href="#help-left-panel-main"><div>Index</div></a>
    <hr />

    <a id="help-left-panel-start_stop"><h1>Start/Stop</h1></a>
    <p>To start/stop server.</p>    
    <a href="#help-left-panel-main"><div>Index</div></a>
    <hr/>

    <a id="help-left-panel-status"><h1>Check node status</h1></a>
    <p>Force to fetch the node status on the selected node.</p>
    <a href="#help-left-panel-main"><div>Index</div></a>
    <hr/>

    <a id="help-left-panel-maintenance"><h1>Maintenance/Recover</h1></a>
    <p>Put node in maintenance, migrating server to <em>Spare</em> node if is configured.
    In <em>Recover</em>, execute system check on node and recover it to active state.</p>
    <a href="#help-left-panel-main"><div>Index</div></a>
    <hr/>

    <a id="help-left-panel-shutdown"><h1>Desligar</h1></a>
    <p>Execute shutdown on node.</p>
    <a href="#help-left-panel-main"><div>Index</div></a>
    <hr/>

    <a id="help-left-panel-remove"><h1>Remove</h1></a>
    <p>
    To remove a node, choose the option <em>Remove node</em> and answer yes to the confirmation question.
    </p>
    <p>This option does not remove the virtualization agent</p>

    <p><b>Note 1: </b>This option only removes the virtualization server from the central management.</p>
    <p><b>Note 2: </b>None of the storage devices (volumes) are erased.</p>
    <a href="#help-left-panel-main"><div>Index</div></a>
    <hr/>
</div>


<!-- ISO MANAGER -->
<div class="help-section">
    <a id="help-iso-main"><h1>ISO Manager</h1></a>
    <p>
        The ISOs manager is a tool that allows you to manage the images to use when creating new virtual machines.
    </p>
    <p>
        The <em>ISO Manager</em> panel consists of a central grid, which contains the images already loaded, and the options bar (above).
        The available options are:
    </p>

    <ul>
        <li><a href="#help-iso-upload"><em>Upload applet (FTP mode)</a></li>
        <li><a href="#help-iso-download"><em>Download</em></a></li>
        <li><a href="#help-iso-rename">Rename</a></li>
        <li><a href="#help-iso-remove">Remove</a></li>
    </ul>
    <p><b>Note: </b>The maximum number of allowed images is limited by the available space in the central management.
    </p>
    <br/>
    <hr/>

    <a id="help-iso-upload"><h1>Upload applet (FTP mode)</a></h1></a>
    <p>
    To send images to the Central Management, we should follow the following steps:
    </p>
    <ol>
        <li>Select the option <em>Upload applet (FTP mode)</em>;</li>
        <li>Select the desired image(s) by pressing the <em>Search</em> button, and then <em>Open</em>;</li>
        <li>Check the list of images to be sent (if you want you can use the buttons to remove undesired files);</li>
        <li>Press send</li>
    </ol>

    <p><b>Note 1: </b>After sending, the images should appear in the list.</p>
    <p><b>Note 2: </b>To run the applet you must have the Java plug-in installed.</p>
    <a href="#help-iso-main"><div>Index</div></a>
    <hr/>

    <a id="help-iso-download"><h1>Download</h1></a>
    <p>
    We can download any of the central management images, just click on the desired line (image) and press the <em>download</em> button.
    </p>
    <a href="#help-iso-main"><div>Index</div></a>
    <hr/>

    <a id="help-iso-rename"><h1>Rename</h1></a>
    <p>
    To change the name of an image that is in the Central Management, select the desired line and press the <em>Rename</em> button.
    A window shall appear where you can enter the new name. After editing press <em> Save</em>.
    </p>
    <a href="#help-iso-main"><div>Index</div></a>
    <hr/>

    <a id="help-iso-remove"><h1>Remove</h1></a>
    <p>
    To delete an image select the desired line and press <em>Remove</em>, then answer yes to the confirmation question.
    </p>
    <a href="#help-iso-main"><div>Index</div></a>
    <hr/>
</div>

<div class="help-section">
    <a id="help-bottom-panel-main"><h1>Info Panel</h1></a>
    <p>
    The information panel belongs to the main panel and shows the success of system changes.
    </p>
    <hr/>
</div>
</div>
