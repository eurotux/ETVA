#!/usr/bin/perl
# perl-Mail-Sender.noarch
package EmailLogs;

use strict;
use Email::Sender::Transport::SMTP;
use Email::Sender::Simple qw(sendmail);
use Email::Simple;
use MIME::Entity;
use Data::Dumper;

unless($#ARGV == 5 || $#ARGV == 7){
    print "[ERROR] Invalid number of parameters. FILE, STMP SERVER, PORT, TO, SECURITY TYPE, USE AUTH, [USERNAME, PASSWORD].";
    exit 1;
}
# $command = "perl $scriptfile $filepath $smtpServer $email $security_type $useauth $username $password";
        
my $app_dir = $ENV{'etva_project_dir'} || "/srv/etva-centralmanagement";
my $diagnostic_tarball = $ARGV[0];
my $smtp = $ARGV[1];
my $port = $ARGV[2];
my $to = $ARGV[3]; 
my $security_type = $ARGV[4];
my $useauth = $ARGV[5];

my ($username, $key);
if($useauth){
    $username = $ARGV[6];
    $key = $ARGV[7];
}
chdir $app_dir or die "$!";

#die "$!" unless(-e $diagnostic_tarball);
my $model = `grep '  acronym: ' apps/app/config/config.yml | sed 's/  acronym: //g'`;
chomp $model;
my $email = lc($model);
chomp $email;
$email .= '@';
$email .= `hostname`;
chomp $email;

eval{
    my $attachment = &encryptData($diagnostic_tarball);
    print "SECURITY ".$security_type."\n"; 

    my $ssl = 0;
    $ssl = 1 if($security_type =~ /(ssl|tls)/i);

    my %cfg = (
            host    => $smtp,
            port    => $port,
            timeout => 30,
            ssl     => $ssl,
            attachment => $attachment,
            to      => $to,
            model   => $model,
            email   => $email,            
    );
    if($useauth == 1){
        $cfg{'sasl_username'} = $username;
        $cfg{'sasl_password'} = $key;
    }
    
    print Dumper \%cfg;


    &send_email(%cfg);
} or &retErr("[ERROR] Error sending mail: $@\n");

sub send_email{
    my %p = @_;
    print STDERR "PARAMS";
    print STDERR Dumper \%p;
    my $transport = Email::Sender::Transport::SMTP->new(\%p);

    my $email = MIME::Entity->build(
        From    => $p{'email'},
        To      => $p{'to'},
        Subject => $p{'model'}.' Diagnostic info',
        Data    =>  <<'*END*'
This message contains state information about the Central Management and Virtualization Agents. 
Please see the attached file.

Many thanks, ETVM/ETVA team.
*END*
    ); 

    ### Attach stuff to it:
    $email->attach(
        Path     => $p{'attachment'},
        Type     => "application/x-zip-encoded",
        Encoding => "base64"
    );

    ### Sign it:
    #$email->sign;

    ### Output it:
    #$email->print(\*STDOUT);
    sendmail($email, { transport => $transport });
}

sub retErr{
    my $msg = shift;
    print $msg;
    exit 1;
}

sub encryptData{
    my $file = shift;
    my $gpg_dir = $app_dir.'/data/.gpg';
    mkdir $gpg_dir unless(-d $app_dir.'/data/.gpg');

    # encrypting the file
    my $err = `gpg --homedir $app_dir/data/.gpg --import config/etvm.gpg`; # or &retErr("[ERROR] $!");
    &retErr("[ERROR] $err") if($err);

    my $email = 'etvm@eurotux.com';
    my $err1 = `gpg --homedir $app_dir/data/.gpg --no-permission-warning --yes --trust-model always -r $email -a -e $diagnostic_tarball 2>&1 >/dev/null`;
    &retErr("[ERROR] $err1") if($err1);
    return $diagnostic_tarball.'.asc';
}


exit 0;
        
