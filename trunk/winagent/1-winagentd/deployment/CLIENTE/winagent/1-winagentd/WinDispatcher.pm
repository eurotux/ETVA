#!/usr/bin/perl
# Copywrite Eurotux 2011
# 
# CMAR 2011/03/24 (cmar@eurotux.com)

=pod

=head1 NAME

WinDispatcher - Perl module for Windows services

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package WinDispatcher;

use strict;

use ETVA::Utils;

use POSIX qw/SIGHUP/;

#use Win32::DirSize;
use Win32::DriveInfo;
use Win32::Service;

use constant SERVICE_CONTINUE_PENDING => 0x00000005;
use constant SERVICE_PAUSE_PENDING    => 0x00000006;
use constant SERVICE_PAUSED           => 0x00000007;
use constant SERVICE_RUNNING          => 0x00000004;
use constant SERVICE_START_PENDING    => 0x00000002;
use constant SERVICE_STOP_PENDING     => 0x00000003;
use constant SERVICE_STOPPED          => 0x00000001;

my %CONF = ( 'backup_dir'=>'c:\\backups' );

=item init_conf

    initialize configuration

=cut

sub init_conf {
    my $self = shift;
    my (%p) = @_;
    %CONF = ( %CONF, %p);
    return wantarray() ? %CONF : \%CONF;
}

=item disk_size

    get information of disk size for c: drive

=cut

sub disk_size {
    my @l = Win32::DriveInfo::DriveSpace('c:');
    my $i = 0;
    my %s = ( 'SectorsPerCluster'=>$l[$i++],
                 'BytesPerSector'=>$l[$i++],
                 'NumberOfFreeClusters'=>$l[$i++],
                 'TotalNumberOfClusters'=>$l[$i++],
                 'FreeBytesAvailableToCaller'=>$l[$i++],
                 'TotalNumberOfBytes'=>$l[$i++],
                 'TotalNumberOfFreeBytes'=>$l[$i++]);
    return wantarray() ? %s : \%s;
}

=item services

    list of Windows services

=cut

sub services {
    my $self = shift;
    my %p = @_;
    my %Services = ();
    Win32::Service::GetServices($p{'hostname'},\%Services);
    my @lServices = ();
    for my $k (keys %Services){
        push(@lServices, { 'desc'=>$k, 'name'=>$Services{"$k"} } );
    }
    return wantarray() ? @lServices : \@lServices;
}

sub filter_services {
    my $self = shift;
    my %p = @_;

    my @lf = grep { $_->{'name'} =~ m/$p{'filter'}/i } $self->services(%p);

    return wantarray() ? @lf : \@lf;
}

=item stop_service

    stop service

=cut

sub stop_service {
    my $self = shift;
    my %p = @_;

    Win32::Service::StopService($p{'hostname'},$p{'servicename'});

    return "ok";
}

=item start_service

    start service

=cut

sub start_service {
    my $self = shift;
    my %p = @_;

    Win32::Service::StartService($p{'hostname'},$p{'servicename'});

    return "ok";
}

sub getstate {
    my ($intstate) = @_;

    if( $intstate == SERVICE_CONTINUE_PENDING ){ return 'SERVICE_CONTINUE_PENDING'; }
    elsif( $intstate == SERVICE_PAUSE_PENDING ){ return 'SERVICE_PAUSE_PENDING'; }
    elsif( $intstate == SERVICE_PAUSED ){ return 'SERVICE_PAUSED'; }
    elsif( $intstate == SERVICE_RUNNING ){ return 'SERVICE_RUNNING'; }
    elsif( $intstate == SERVICE_START_PENDING ){ return 'SERVICE_START_PENDING'; }
    elsif( $intstate == SERVICE_STOP_PENDING ){ return 'SERVICE_STOP_PENDING'; }
    elsif( $intstate == SERVICE_STOPPED ){ return 'SERVICE_STOPPED'; }
    else{ return 'SERVICE_UNKNOWN'; }
}

=item status_service

    get service status

=cut

sub status_service {
    my $self = shift;
    my %p = @_;

    my %Status = ();
    Win32::Service::GetStatus($p{'hostname'},$p{'servicename'},\%Status);
    $Status{'state'} = getstate($Status{'CurrentState'}) if( $Status{'CurrentState'} );

    return wantarray() ? %Status : \%Status;
}

=item list_backups

    list of backups on directory

=cut

sub list_backups {
    my $self = shift;

    my @lbkps = ();
    my $bkp_dir = $CONF{'backup_dir'};
    opendir(D,$bkp_dir);
    my @lf = readdir(D);
    for my $f (@lf){
        next if( $f =~ m/^\./ );
        my %hb = ( 'file'=>$f, 'path'=>"$bkp_dir\\$f" );
        my @s = stat($hb{'path'});
        $hb{'size'}    = $s[7];
        $hb{'changed'} = $s[9];
        push(@lbkps, { %hb } );
    }
    closedir(D);
    return wantarray() ? @lbkps : \@lbkps;
}

=item last_backup

    last backup file from backup directory

=cut

sub last_backup {
    my $self = shift;

    if( my @lbkps = sort { $a->{'changed'} <=> $b->{'changed'} } $self->list_backups() ){
        my $last_bkp = pop @lbkps;
        return wantarray() ? %$last_bkp : $last_bkp;
    }

    return ETVA::Utils::retErr('_ERR_WIN_LAST_BKP_',"Error: no backups.");

}

=item check_services

    check services status

=cut

sub check_services {
    my $self = shift;
    my %p = @_;

    my %check = ();
    if( %p ){
        my @ls = $self->services(%p);
        for my $k (keys %p){
            my $fl = $p{"$k"} || $k;

            my ($S) = grep { $_->{'name'}  =~ m/$fl/i } @ls;
            if( $S->{'name'} ){
                my %status = $self->status_service('servicename'=>$S->{'name'});
                $check{"$k"} = { %$S, %status };
            }
            $check{"$k"} = {} if( !$check{"$k"} );
        }
    }
    return wantarray() ? %check : \%check;
}

=item get_ipconfig

    get network interfaces information

=cut

sub get_ipconfig {
    my $self = shift;

    my $k;
    my $ifn;
    my %NET = ();
    open(IC,"ipconfig /all |");
    while(<IC>){
        if( /^(\S.+):\r\n$/ ){
            $ifn = $1;
        } elsif( /^\s+(.+\w)[ .]*:\s+(.+)?\r\n$/ ){
            if( $ifn ){
                ($k, my $val) = ($1,$2);
                $NET{"$ifn"}{"$k"} = $val;
            }
	} elsif( /^\s+(.+)\r\n$/ ){
            if( $ifn && $k ){
                my ($val) = ($1);
                $NET{"$ifn"}{"$k"} = ( ref($NET{"$ifn"}{"$k"}) eq 'ARRAY' ) ? [ @{$NET{"$ifn"}{"$k"}}, $val ]: [ $NET{"$ifn"}{"$k"}, $val ];
            }
        }
    }
    close(IC);

    my @lifs = ();
    for my $def_ifn (grep { /^Ethernet/i } keys %NET){

        my $I = $NET{"$def_ifn"};

        my %IF = ();
        $IF{'description'} = $def_ifn;
        $IF{'name'} = $def_ifn;
        $IF{'name'} =~ s/^Ethernet adapter //;
        $IF{'macaddr'} = $I->{'Physical Address'};
        $IF{'macaddr'} =~ s/-/:/g;
        $IF{'dhcp'} = ( $I->{'DHCP Enabled'} eq 'Yes' ) ? 1 : 0;
        $IF{'ipaddr'} = $I->{'IPv4 Address'};
        $IF{'ipaddr'} =~ s/[ a-zA-Z()]//gs;
        $IF{'netmask'} = $I->{'Subnet Mask'};
        $IF{'gateway'} = $I->{'Default Gateway'};
        $IF{'dhcpserver'} = $I->{'DHCP Server'};
        $IF{'dnsservers'} = $I->{'DNS Servers'};
        $IF{'dnssufix'} = $I->{'Connection-specific DNS Suffix'};
        $IF{'model'} = $I->{'Description'};
        $IF{'auto'} = ( $I->{'Autoconfiguration Enabled'} eq 'Yes' ) ? 1 : 0;

        push(@lifs, \%IF);
    }

    return wantarray() ? @lifs : \@lifs;
}

=item set_ipconfig

    change network interface configuration

=cut

sub set_ipconfig {
    my $self = shift;
    my (%p) = @_;

    if( $p{'name'} ){
        my $args = "";
        if( $p{'dhcp'} ){
            $args = "source=dhcp";
        } else {
            $args = "source=static address=$p{'ipaddr'}";
            if( $p{'netmask'} ){
                $args .= " mask=$p{'netmask'}";
            } else { $args .= " mask=255.255.255.0"; }
            $args .= " gateway=$p{'gateway'}" if( $p{'gateway'} );
        }
        my ($e,$m) = ETVA::Utils::cmd_exec("netsh interface ip set address name=\"$p{'name'}\" $args store=persistent");

        # wait a few seconds for dhcp update
        sleep 5;

        unless( $e == 0 ){
                return retErr("_ERR_SET_IPCONFIG_","Error setting IP configuration: $m");
        }
        return retOk("_SET_IPCONFIG_OK_","IP configuration set ok.");
    } else {
        return retErr("_ERR_NOINTERFACE_","No interface set.");
    }
}

=item change_ip

    change interface IP

=cut

sub change_ip {
    my $self = shift;
    my %p = @_;

    if( !$p{'name'} ){
        my ($I) = $self->get_ipconfig();
        $p{'name'} = $I->{'name'};
    }

    # change ip
    my $E = $self->set_ipconfig(%p);

    my $need_write = 0;

    my ($I) = grep { $_->{'name'} eq $p{'name'} } $self->get_ipconfig();
    if( $I ){
        $CONF{'IP'} = $CONF{'LocalIP'} = $I->{'ipaddr'};
    }

    # change cm uri
    if( $p{'cm_uri'} ){
        $CONF{'cm_uri'} = $p{'cm_uri'};
    }

    # write change on config file
    ETVA::Utils::set_conf($CONF{'CFG_FILE'},%CONF);

    # wait a few seconds for sync config file
    sleep 5;

    if( !isError($E) ){
        # restart
        kill SIGHUP, $$;
    }

    return wantarray() ? %$E : $E;
}

1;

=back

=pod

=head1 BUGS

...

=head1 AUTHORS

...

=head1 COPYRIGHT

...

=head1 LICENSE

...

=head1 SEE ALSO

=cut

