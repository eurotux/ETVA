#!/usr/bin/perl
# Copywrite Eurotux 2009
# 
# CMAR 2009/04/03 (cmar@eurotux.com)

=pod

=head1 NAME

virtClient.pl - script for interact with agent

=head1 SYNOPSIS

va_addr=10.10.0.4 va_port=7000 perl client.pl create_vm name=etmx network='name=default' disk='path=/srv/etva-vdaemon/storage/etmx_xvda.img,targe=xvda' location=ftp://ftp.nfsi.pt/pub/CentOS/5.4/os/i386/ ram=512 ncpus=1 vnc_listen=any

    perl virtClient.pl method addr=... port=... param1=...

    perl virtClient.pl create_vm name=bla path=/tmp/bla.img location=ftp://ftp.nfsi.pt/pub/CentOS/5.3/os/i386/ ram=512 ncpus=1

    perl virtClient.pl create_vm addr=10.10.20.79 port=7002 name=crx network='name=net1,macaddr=00:16:3e:01:7b:75;bridge=virbr0' disk='path=/tmp/xxx.img,target=/dev/hda;path=/tmp/bla.img,target=/dev/hdb'

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

use strict;

use ETVA::Client::SOAP;

use Data::Dumper;

my $name = "virt-client";

=item main

main func

=over

=item *

    read params from @ARGV with format p1=v1, ..., pn=vn
    and split into %Hash with ETVA::Client->splitOps function

=item *

    create ETVA::Client::SOAP object

    my $Client = ETVA::Client::SOAP->new( PeerAddr=>$addr, PeerPort=>$port, Proto=>$porto );

    my $SOAP_Response = $Client->call( $uri, $method, @params );

    and call method

=item *

    receive response and testing for error message

=back

=cut

sub main {
    my ($method) = my @ops = @ARGV;
    
    my %Args = ETVA::Client->splitOps(@ops);

    if( $Args{'op'} ){
        $method = delete $Args{"op"};
    }

    if( !$method ){
        print help($method,"No method specified...");
    } else {

        my $port = $ENV{'va_port'} || delete $Args{"port"} || 7001;
        my $addr = $ENV{'va_addr'} || delete $Args{"addr"} || "localhost";
        my $uri = $ENV{'va_uri'} || delete $Args{'uri'} || 'http://www.eurotux.com/VirtAgent';
        my $debug = ($Args{'debug'} || $ENV{'va_debug'} || $ENV{'DEBUG'} ) ? 1:0;
        my $blocking = $ENV{'BLOCKING'} || delete $Args{'blocking'} || 0;

        eval {
            my $client = new ETVA::Client::SOAP( address => $addr,
                                            port => $port,
                                            proto=>'tcp',
                                            blocking=>$blocking );

            $client->set_debug() if( $debug );
            
            print STDERR "method=$method\n" if( $debug );
            print STDERR "ops=",Dumper(\%Args),"\n" if( $debug );

            my $result = $client->call($uri,$method, %Args );

            print STDERR "Dump result = ",Dumper($result),"\n" if( $debug );

            if( defined $result->{faultcode} ){
                print STDERR "Err: ",$result->{faultcode}, " ", $result->{faultstring},"\n" if( $debug );
                print STDERR "Detail: ",$result->{detail},"\n" if( $debug );
                print help($method,$result->{detail});
            } else {
                print_result($result);
            }
        };
        if( $@ ){
            print help($method,"Could possible connect to service...: $@");
        }
    }
}
main();
sub print_result {
    # TODO pretty print result
    my ($result) = @_;
    if( ref( $result->{'result'} ) ){
        my $str = Dumper($result->{'result'});
        print $str,"\n";
    } else {
        print $result->{result},"\n";
    }
}
sub print_ref {
    my ($R,$l) = @_;
    $l = 0 if( !$l );
    if( ref($R) eq 'HASH' ){
        for my $k (keys %$R){
            print "\t" x ($l);
            print "$k";
            print "\t" x ($l+1);
            print "\n" if( !$l );
            print_ref($R->{"$k"},$l+1);
            print "\n";
        }
    } elsif( ref($R) eq 'ARRAY' ){
        for my $E (@$R){
            print "\t" x ($l+1);
            print_ref($E,$l+1);
            print "\n";
        }
    } else {
        print $R;
    }
}
sub help {
    my ($method,$msg) = @_;
    if( !$msg ){
        my %Methods = ();
        if( !$method || !$Methods{"$method"} ){
        $msg .= <<_HELP_;
Method not defined.
_HELP_
        }
    }
    $msg .= <<_HELP_;


Usage: $name method [arg1=v1 ... argn=vn]
_HELP_

    return $msg;
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

L<ETVA::Client>, L<ETVA::Client::SOAP>, L<ETVA::Client::SOAP::HTTP>
L<virtd>,
L<VirtAgentInterface>, L<VirtAgent::Disk>, L<VirtAgent::Network>,
L<VirtMachine>
L<Agent>, L<Agent::SOAP>, L<Agent::JSON>
C<http://libvirt.org>

=cut
