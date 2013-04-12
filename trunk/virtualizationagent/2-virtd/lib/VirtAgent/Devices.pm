#!/usr/bin/perl
package VirtAgent::Devices;

use strict;
use Data::Dumper;
use Sys::Virt;

use constant USBDB => '/usr/share/hwdata/usb.ids';
use constant PCIDB => '/usr/share/hwdata/pci.ids';

#my $USBDB = '/usr/share/hwdata/usb.ids';

#my @devs = &usb_dev_list;
#print Dumper \@devs;
#
#@devs = &pci_dev_list;
#print Dumper \@devs;

#######
# Public methods
#######
#&attach_usb('win2k8srv', '0x0529', '0x0001');

sub attach_usb {
    my ($vm_name,$vendor,$product) = @_;
 
    die "VM name not specified!\n" if( !$vm_name );
    die "USB vendor or product not specified!\n" if( !$vendor || !$product );

    my $vmm = Sys::Virt->new();

    eval {
        my $dom = $vmm->get_domain_by_name($vm_name);

        die "VM name not found!\n" if( !$dom );

        my $xml_dom = $dom->get_xml_description();

        die "USB already attached to this domain\n"
                    if( $xml_dom =~ m#<vendor id='$vendor'/># &&
                            $xml_dom =~ m#<product id='$product'/># );

        print "Attach to domain ", $dom->get_id, " ", $dom->get_name, " USB with vendor=$vendor and product=$product\n";
        my $xml_device = <<__XML_DEVICE__;
    <hostdev mode='subsystem' type='usb'>
      <source>
        <vendor id='$vendor'/>
        <product id='$product'/>
      </source>
    </hostdev>
__XML_DEVICE__

        $xml_dom =~ s#</devices>#$xml_device</devices>#;
        #print "xml_dom: $xml_dom\n";
        #$dom->attach_device($xml_device);
        $vmm->define_domain($xml_dom);
    };
    if( $@ ){
        die "Error: $@\n";
    }
}

sub dettach_usb {
    my ($vm_name,$vendor,$product) = @_;
    die "VM name not specified!\n" if( !$vm_name );
    die "USB vendor or product not specified!\n" if( !$vendor || !$product );

    my $vmm = Sys::Virt->new();

    eval {
        my $dom = $vmm->get_domain_by_name($vm_name);

        die "VM name not found!\n" if( !$dom );

        my $xml_dom = $dom->get_xml_description();

        # TODO remove device here
                
    }
}

# Returns a array of hashs with the usb devices info
sub pci_dev_list{
    my $devs = `lspci -nn -mm`;
    my @devices;

    foreach my $line (split "\n", $devs){
        if($line =~ /(\d{2}).(\d{2})\.(\d)\s+\"([^"]*)"\s"([^"]*)"\s"([^"]*)"/){
            my %res = ();
            $res{'bus'} = $1;
            $res{'slot'} = $2;
            $res{'function'} = $3;
            $res{'tostring'} = $4.$5.$6;
            my $idvendor = $5;
            my $idproduct = $6;
            $res{'tostring'} =~ s/\[[A-Fa-f0-9]{4}]*//g;
            $idvendor =~ s/.*\[(.*)\]/$1/;  #(\[\x{4}\])
            $idproduct =~ s/.*\[(.*)\]/$1/;
            $res{'idvendor'} = $idvendor;
            $res{'idproduct'} = $idproduct;
            $res{'type'} = 'pci';
            unshift @devices, \%res;
        }
    }
    return wantarray() ? @devices : \@devices;
}

# Returns a array of hashs with the usb devices info
sub usb_dev_list{
    my @files = &usb_devs_dir;
    my @devices;
    
    foreach my $file (@files){
        my $str = '';            

        my %res = ();
        my $idvendor = `cat $file/idVendor`;
        chomp $idvendor;
#        $str .= "$idvendor:";
        $res{'idvendor'} = $idvendor;

        my $idproduct = `cat $file/idProduct`;
        chomp $idproduct;
#        $str .= "$idproduct - ";
        $res{'idproduct'} = $idproduct;
        $res{'directory'} = $file;

        # get device description
        $str .= &dev_description($idvendor, $idproduct, 'usb');        
        $res{'tostring'} = $str;
        $res{'type'} = 'usb';
        unshift @devices, \%res;
    }
    return wantarray() ? @devices : \@devices;
}

# Returns a array of hashs with the pci devices info
# TODO implement
sub get_pci_dev_info{

    my $info = `lspci -nn -mm`;

}


#######
# Package methods
#######

# Return an array with the usb device's folder
sub usb_devs_dir{
    my @devdirs;
    my $usbdevs = `find /sys/bus/usb/devices/usb*/ -name idVendor -print -exec 'cat' {} \\;`;
    my @rows = split("\n", $usbdevs);
    
    foreach my $row (@rows){
        chomp $row;
        if($row =~ /^(\/.*)\/.*/){
            push @devdirs, $1;
        }else{
            if($row == 0){
                pop @devdirs;
            }
        }
    }

    return wantarray() ? @devdirs : \@devdirs;
}

# Param devtype => usb or pci
# Returns a string with the device description
sub dev_description{
    my ($idvendor, $idproduct, $devtype) = @_;
    
    my $filename;

    if($devtype == 'usb'){
        $filename = USBDB;
    }elsif($devtype == 'pci'){
        $filename = PCIDB;
    }

    open FILE, "<$filename";
    my $str = "";

    OUT: while(<FILE>){

        # Find vendorid
        if(/^$idvendor(.*)/){
            $str .= $1;

            # Find productid
            while(<FILE>){
                if(/^\t$idproduct\s*(.*)/){
                    $str .= " $1";
                    next OUT;
                }
            }
        }
    }

#    my $tmp = `cat $filename | egrep -e '^$idvendor'`;
#    chomp $tmp;
#    $tmp =~ s/^$idvendor\s*//;
#    my $description = $tmp;
#
#    $tmp = `cat $filename | egrep -e '^$idproduct'`;
#    chomp $tmp;
#    $tmp =~ s/^\t$idproduct\s*//;
#    $description .= " $tmp";

    return $str;
}

1;
