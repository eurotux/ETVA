#!/usr/bin/perl -w
#
# Caches text-to-speech conversions as 8-kHz .WAV files.
use Asterisk::AGI;
use File::Basename;
use File::Temp;
use Digest::MD5 qw(md5_hex);
use LWP::UserAgent;
#use LWP::Debug qw(+);
use CGI;
use Data::Dumper;

use strict;
my $debug = 0;
# set up communications w/ Asterisk
my $agi = new Asterisk::AGI;
my %input = $agi->ReadParse();

my %users = (
	'24860' => ['npf', 919475933 ],
	'57133' => ['anm', 910946267 ],
	'30236' => ['pjs', 913456210 ],
        '22652' => ['fapg',919475932 ],
        '22673' => ['luciano',918924359],
        '40655' => ['mfm',913456209],
        '40667' => ['dfr',918924347],
	);
my $texto = fetchalarm($users{$ARGV[0]}[0],$ARGV[0]);
print sendsms($users{$ARGV[0]}[1],$texto);
debug2asterisk("sms sent: $texto");

############################# FUNCTIONS
sub debug2asterisk {
	my ($log) = @_;
	if ($debug) {
		$agi->verbose($log,1);
	}
		
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

sub sendsms {
	my ($number,$text) = @_;
	my $ua = LWP::UserAgent->new( protocols_allowed => ["https"] );;
	#$ua->agent("NPF_CheckWebalizer/0.1 ");
	my $txt = CGI::escape($text);
	my $url = "https://www.voipbuster.com/myaccount/sendsms.php?username=eurotuxvoip&password=qfrgusa9&from=eurotuxvoip&to=351$number&text=$txt";
	my $req = HTTP::Request->new(GET => $url);

	# Pass request to the user agent and get a response back
	my $res = $ua->request($req);

	# Check the outcome of the response
	if ($res->is_success) {
			return translate("No problem");
		} else {
			return translate("Unable do access voipbuster");
		}
}
