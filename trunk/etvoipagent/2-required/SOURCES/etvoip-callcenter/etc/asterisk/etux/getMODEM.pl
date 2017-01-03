#!/usr/bin/perl -w
#
# Caches text-to-speech conversions as 8-kHz .WAV files.
use Asterisk::AGI;
use File::Basename;
use File::Temp;
use Digest::MD5 qw(md5_hex);
use LWP::UserAgent;
use Data::Dumper;

use strict;
my $debug = 0;
my $debugtofile = 0;
my $debugfile = '/tmp/modem.log';

# set up communications w/ Asterisk
my $agi = new Asterisk::AGI;
my %input = $agi->ReadParse();

my %users = (
        '24860' => 'npf',
        '22652' => 'fapg',
        '30236' => 'pjs',
	'33705' => 'jcp',
        '40655' => 'mfm',
        '40667' => 'dfr',
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
	$texto = fetchalarmAEIOU($usersAEIOU{$ARGV[0]},$ARGV[0]);
} else {
	$texto = fetchalarm($users{$ARGV[0]},$ARGV[0]);
}
playtext($texto);
debug2asterisk("fetched $texto");
debug2file("fetched $texto");
if ($texto eq translate("No problem")) {
		$agi->set_variable('modemok',1); 
	}

############################# FUNCTIONS
sub debug2asterisk {
	my ($log) = @_;
	if ($debug) {
		$agi->verbose($log,1);
	}
}

sub debug2file {
	my ($log) = @_;
	if ($debugtofile) {
		open FH, ">>$debugfile" or return 1;
		print FH $log;
		close FH;
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

sub fetchalarm {
	my ($username,$password) = @_;
	my $ua = LWP::UserAgent->new;
	$ua->agent("NPF_CheckWebalizer/0.1 ");
	my $url = "http://$username:$password\@modem.eurotux.com/lite.cgi";
	my $req = HTTP::Request->new(GET => $url);

	# Pass request to the user agent and get a response back
	my $res = $ua->request($req);

	# Check the outcome of the response
	if ($res->is_success) {
		my $conteudo = $res->content;
		$conteudo =~ s/(.*\n.*)*<pre>//g; # remove o header html
		$conteudo =~ s/<\/pre><\/body><\/html>//g; # remove o trailer html
		$conteudo =~ s/.*\s:\s//g; # remove as horas
		$conteudo =~ m/^(.*)\n/; $conteudo = $1; # vai buscar a primeira linha
			if ($conteudo =~ m/atendeu/) {
				return translate("No problem");
			} else {
				return translate($conteudo);
			}
		} else {
			return translate("Unable do access modem");
		}
}

sub fetchalarmAEIOU {
	my ($username,$password) = @_;
	my $ua = LWP::UserAgent->new;
	$ua->agent("NPF_CheckWebalizer/0.1 ");
	my $url = "http://$username:$password\@modem.eurotux.com/aeiou/modem.cgi";
	my $req = HTTP::Request->new(GET => $url);

	# Pass request to the user agent and get a response back
	my $res = $ua->request($req);

	# Check the outcome of the response
	if ($res->is_success) {
		my $conteudo = $res->content;
		$conteudo =~ s/(.*\n.*)*<pre>//g; # remove o header html
		$conteudo =~ s/<\/pre><\/body><\/html>//g; # remove o trailer html
		$conteudo =~ s/.*\s:\s//g; # remove as horas
		$conteudo =~ m/^(.*)\n/; $conteudo = $1; # vai buscar a primeira linha
			if ($conteudo =~ m/atendeu/) {
				return translate("No problem");
			} else {
				return translate($conteudo);
			}
		} else {
			return translate("Unable do access modem");
		}
}
