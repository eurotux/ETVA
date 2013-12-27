#!/usr/bin/perl
#

use strict;

use IO::Select;
use Device::SerialPort;

use Time::HiRes qw(gettimeofday);

my %queue_write = ();
sub main {
    my $serialport = "/dev/ttyS0";
    #my $port = new Device::SerialPort("/dev/ttyS0");
    my $port = tie (*COM, 'Device::SerialPort', $serialport)
                                   || die "Can't tie: $!\n";             ## TIEHANDLE ##
    my $sockport = *COM;

    $port->user_msg(1);
    $port->baudrate(115200);
    $port->parity("none");
    $port->databits(8);
    $port->stopbits(1);
    $port->handshake("xoff");
    $port->write_settings;
    $port->lookclear;

    $port->read_char_time(0);
    $port->read_const_time(100);


    my $buffer;
    my $request;

    while(1){
        #print $sockport rand(time()) x 4096;
        my $t0 = Time::HiRes::gettimeofday();
        print $sockport 0 x 115200;
        my $t1 = Time::HiRes::gettimeofday();
        my $secs = $t1 - $t0;
        print STDERR "[DEBUG] send in $secs secs","\n";

    }
=com
    # default select timeout reads
    my $select_timeout = 0.020; # value for select timeout reads

    my $select = new IO::Select( );
    $select->add( $sockport );
    my $i=0;
    while(1){
        my @ready_ra = $select->can_read($select_timeout);
        foreach my $sock (@ready_ra) {
            print STDERR "ready read sock=$sock","\n";
            #$buffer = <$sock>;
            #$buffer = $port->read(9600);

            my $rd = read($sock,$buffer,4096);
            
            print STDERR "read buff=$buffer","\n";
            if( length( $buffer )) {
                $request .= $buffer;

                if( $request ){
                    chomp($request);
                    my $response = $request." ".(++$i)."\r\n";
                    #$port->write( "$response" );
                    #print $sock $response;
                    push(@{$queue_write{"$sock"}}, $response);
                    #syswrite($sock,$response);
                    $request = "";
                }
                $buffer = "";
            }
        }
        #$buffer = $port->read(9600);
        my @ready_wa = $select->can_write($select_timeout);
        foreach my $sock (@ready_wa) {
            #print STDERR "ready write sock=$sock","\n";

            if( $queue_write{"$sock"} ){
                my $msg = shift(@{$queue_write{"$sock"}});
                if( $msg ){
                    print STDERR "ready write sock=$sock msg=$msg","\n";
                    print $sock $msg;
                }
            }
        }
    }
=cut
    close(COM);
    $port->close;
    undef $port;
    untie *COM;
}

&main();

1;
