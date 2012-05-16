<div class="help">
<div class="help-section">
    <a id="ovf_import"><h1>OVF Import</h1></a>
    The OVF import wizard (Open Virtualization Format) allows to import a virtual machine described in OVF file.

    The OVF format has meta-data about a virtual machine allowing you to create a package with the virtual machine specifications.
    The OVF import wizard consists of the following steps:
    <br /><br />
    <ul>
        <li><b>Source OVF file -</b>In this step we specify the file to import.<br>
            The URL of the file must be accessible via web in order to be imported.
        </li>
        <li><b>OVF Details -</b> OVF file details. Provides information about the product, version, total size of the files referenced by the OVF package, if available.
        </li>
        <li><b>License -</b> If specified in the OVF file, this step will come with the EULA. Otherwise, this step is omitted.
        </li>
        <li><b>Name and location -</b> This step defines the virtual machine name, the destination node and the type of operating system. The operating system options vary depending on the specification of the node:
            <ul style="list-style-type:square">
                <li>with XEN and hardware virtualization support:
                    <ul style="list-style-type:none">
                        <li>- Linux PV</li>
                        <li>- Linux HVM</li>
                        <li>- Windows</li>
                    </ul>
                </li>
                <li>with XEN and without hardware virtualization support:
                    <ul style="list-style-type:none">
                        <li>- Linux PV</li>
                    </ul>
                </li>
                <li>with KVM
                    <ul style="list-style-type:none">
                        <li>- Linux</li>
                        <li>- Windows</li>
                    </ul>
                </li>
            </ul>
            Before proceeding to the next step, the wizard checks if the node has available memory, required disk drivers, network interfaces. 
    <br><br>
            The supported disk drivers in XEN machines with or without hardware virtualization are: IDE, SCSI and xen. Machines in KVM drivers are: ide, virtio and scsi.
    <br><br>
            The network card drivers supported for HVM and KVM machines are: e1000, virtio and rtl8139. On a machine without XEN virtualization support does not support drivers.
    <br><br>
            If the virtualization server has not enough available memory or does not support the drivers mentioned in the OVF Files, the import can not be performed.
        </li>
        <li><b>Storage -</b> 
            In this step we map any disks with the node volumes. We can specify the name to give the logical volume and select the volume group.
            It is necessary that all disks are mapped to proceed to the next step.
        </li>
        <li><b>Network interfaces -</b> 
            In this stage we map the network interfaces. You can specify new network interfaces.
            It is necessary that all the network interfaces are mapped to proceed to the next step.
        </li>
        <li><b>Finished! -</b> Last step of the wizard. After the confirmation, the collected data (in previous steps) are processed and sent to the virtualization server.
        </li>
    </ul>
    <br>
    <hr>
    <br>
</div>
<div class="help-section">
    <a id="ovf_export"><h1>OVF Export</h1></a>
    The OVF export allows to store a virtual machine configuration in OVF format (Open Virtualization Format).
    To do this the virtual machine must be <em>stopped</em>.
    This format generates a collection of files that are referenced by a .ovf file.
</div>
</div>
