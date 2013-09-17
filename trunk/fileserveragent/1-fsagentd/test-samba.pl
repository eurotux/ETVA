#!/usr/bin/perl


use strict;

use Samba;

use ETVA::Utils;

use Data::Dumper;

sub main {

    my $smb = new Samba();

    plog Dumper [ $smb->list_shares() ];

    $smb->create_share( 'name'=>'test1', 'opt1'=>'val1' );
    
    plog Dumper [ $smb->list_shares() ];

    $smb->create_share( 'name'=>'test2', 'opt1'=>'val1', 'opt2'=>'val2' );
    
    plog Dumper [ $smb->list_shares() ];

    $smb->create_share( 'name'=>'test3', 'opt1'=>'val1' );
    
    plog Dumper [ $smb->list_shares() ];

    $smb->delete_share( 'name'=>'test2', 'opt1'=>'val1' );
    
    plog Dumper [ $smb->list_shares() ];

    $smb->create_share( 'name'=>'test3', 'opt1'=>'val2' );
    
    plog Dumper [ $smb->list_shares() ];

}

&main();
1;

__END__
