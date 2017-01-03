#!/usr/bin/perl
# module util with functions to deal with Cisco IPPhone
#
#  based on exemple of
# Mark Palmer - markpalmer@us.ibm.com
# Must use authentication when POSTING an object to a Cisco IPPhone.
# User should be a user in the global directory associated with the phone
# Can use this script to send messages to IPPhones

package utilciscoipphone;

use strict;

use LWP::UserAgent;
use URI;

sub _prep_xml_cisco_execution {
    my ( $url ) = @_;

    return <<__XML__
<CiscoIPPhoneExecute>
<ExecuteItem URL="$url"/>
</CiscoIPPhoneExecute>
__XML__
}

sub _call {

    my ($ipphone, $user, $passwd, $call) = @_;

    my $ua = new LWP::UserAgent();

    my $POSTURL = "http://${ipphone}/CGI/Execute";

    my $xml = &_prep_xml_cisco_execution($call);

    # Translate non-alpha chars into hex
    $xml = URI::Escape::uri_escape("$xml"); 

    my $request = new HTTP::Request( POST => "$POSTURL" );
    $request->authorization_basic($user, $passwd);
    $request->content("XML=$xml"); # Phone requires parameter named XML
    my $response = $ua->request($request); # Send the POST

    my %response = ();
    if( $response->is_success ){
        $response{'Success'} = 'True';
        my $result = $response->content;
        $response{'Result'} = $result;
        if( $result =~ m/CiscoIPPhoneError Number="(\d+)"/ ){
            $response{'Success'} = 'False';
            my $errno = $1;
            if ($errno == 4) {
                $response{'Error'} = "Authentication error";
            } elsif ($errno == 3) {
                $response{'Error'} = "Internal file error"; 
            } elsif ($errno == 2) {
                $response{'Error'} = "Error framing CiscoIPPhoneResponse object"; 
            } elsif ($errno == 1) {
                $response{'Error'} = "Error parsing CiscoIPPhoneExecute object"; 
            } else {
                $response{'Error'} = "Unknown Error";
            }
        }
    } else {
        $response{'Success'} = 'False';
        $response{'Error'} = "Failure: Unable to POST XML object to phone $ipphone";
        $response{'Status'} = $response->status_line;
    }
    use Data::Dumper;
    #print STDERR "DEBUG Dump=",Dumper(\%response),"\n";
    return wantarray() ? %response : \%response;
}
sub dial {
    my ($ipphone,$number, $user, $passwd) = @_;

    return &_call($ipphone, $user, $passwd, "Dial:$number");
}
sub pushHeadsetKey {
    my ($ipphone, $user, $passwd) = @_;

    return &_call($ipphone, $user, $passwd, "Key:Headset");
}
sub pushSpeakerKey {
    my ($ipphone, $user, $passwd) = @_;

    return &_call($ipphone, $user, $passwd, "Key:Speaker");
}

1;
