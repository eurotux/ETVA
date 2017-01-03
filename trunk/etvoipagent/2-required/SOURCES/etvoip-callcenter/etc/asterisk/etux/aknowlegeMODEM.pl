#!/usr/bin/perl -w
#
# Caches text-to-speech conversions as 8-kHz .WAV files.
use Asterisk::AGI;
use File::Basename;
use Digest::MD5 qw(md5_hex);
use LWP::UserAgent;
use Data::Dumper;
use strict;
# set up communications w/ Asterisk
my $agi = new Asterisk::AGI;
my $debug = 1;
$agi->ReadParse();

my %users = (
        '24860' => 'npf',
	'22652' => 'fapg',
        '30236' => 'pjs',
	'40655' => 'mfm',
	'40667' => 'dfr',
        '33705' => 'jcp',
        );

my %usersAEIOU = (
        '27172' => 'aeiou.phpp',
        '51853' => 'aeiou.shf',
        '03538' => 'aeiou.ramaral',
        '46320' => 'aeiou.rcardoso',
        '85005' => 'aeiou.pfaria',
        '78276' => 'aeiou.masf',
        '85220' => 'aeiou.cmar',
        );

my $texto;
# verificar se e' user aeiou ou tux
if ($usersAEIOU{$ARGV[0]}) {
	$texto = disablealarmAEIOU($usersAEIOU{$ARGV[0]},$ARGV[0]);
} else {
	$texto = disablealarm($users{$ARGV[0]},$ARGV[0]);
}
playtext($texto);
debug2asterisk("fetched $texto");


############### FUNCTIONS ################
sub debug2asterisk {
        my ($log) = @_;
        if ($debug) {
                $agi->verbose($log,1);
        }

}

# funcao para fzer play do texto
sub playtext {
        my ($text_to_say) = @_;
        my $hash = md5_hex($text_to_say);
        my $sounddir = "/var/lib/asterisk/sounds";
        my $wavefile = "$sounddir/say-text-$hash.wav";
        # unless we have already cached this text-to-speed conversion,
        # create the WAV file using Festival's text2wav (expensive)
        unless (-f $wavefile) {    require File::Temp;
            my (undef, $tmpfile) = File::Temp::tempfile(DIR => $sounddir);    my $pid = open my $pipe, "|-";
            die "can't fork: $!" unless (defined $pid);    if (!$pid) { # child
                open STDOUT, ">$tmpfile" or die "can't redir to $tmpfile $!";
                exec qw( text2wave -F 8000 - );  # text->speech conv; 8-kHz WAV output
                die "exec in child failed: $!";
            }
            else { # parent
                print $pipe $text_to_say;
                close $pipe;
                waitpid $pid, 0; # wait until text->speech conv. is done
                rename $tmpfile, $wavefile
                    or die "can't rename $tmpfile to $wavefile $!";
            }
        }
        # stream the WAV file down the phone line
        $agi->stream_file(basename($wavefile, ".wav"));
}

# funcao para traduzir as mensages da pagina para user-friendly
sub translate {
        my ($str) = @_;
        # TODO
        return $str;
}

sub disablealarm {
        my ($username,$password) = @_;
        my $ua = LWP::UserAgent->new;
        $ua->agent("NPF_CheckWebalizer/0.1 ");
        my $url = "http://$username:$password\@modem.eurotux.com?user=ASTERISK";
        my $req = HTTP::Request->new(GET => $url);

        # Pass request to the user agent and get a response back
        my $res = $ua->request($req);

        # Check the outcome of the response
        if ($res->is_success) {
                        return translate("Alert acknowledge");
                } else {
                        return translate("Unable do access modem");
                }
}

sub disablealarmAEIOU {
        my ($username,$password) = @_;
        my $ua = LWP::UserAgent->new;
        $ua->agent("NPF_CheckWebalizer/0.1 ");
        my $url = "http://$username:$password\@modem.eurotux.com/aeiou/modem.cgi?user=ASTERISK";
        my $req = HTTP::Request->new(GET => $url);

        # Pass request to the user agent and get a response back
        my $res = $ua->request($req);

        # Check the outcome of the response
        if ($res->is_success) {
                        return translate("Alert acknowledge");
                } else {
                        return translate("Unable do access modem");
                }
}
