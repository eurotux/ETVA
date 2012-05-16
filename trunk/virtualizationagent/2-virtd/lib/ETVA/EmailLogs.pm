#!/usr/bin/perl
# perl-Mail-Sender.noarch
package ETVA::EmailLogs;

use strict;
use Mail::Sender;



sub send_diagnostic{
    my ($to, $smtp, $diagnostic_tarball) = @_;
    
    my $app_dir = $ENV{'agent_dir'} || "/srv/etva-vdaemon";
    my $currDir = `pwd`;
    
    chdir $app_dir or die "$!";
    #die "$!" unless(-e $diagnostic_tarball);
    my $email = 'etva_agent@'.`hostname`;
    chomp $email;
    
    my $attachment = &encryptData($diagnostic_tarball, $app_dir);
    unless($attachment){
        print STDERR "[ERROR] encryption failed\n";
        return 0;
    }
 
    eval {
        (new Mail::Sender {on_errors => 'die'})
            ->OpenMultipart({
                smtp    => $smtp, 
                to      => $to,
                subject => 'ETVA Virtual Agent Diagnostic info',
                from    => $email 
            })
            ->Body({ 
            msg => <<'*END*' })
This message contains state information about a Virtualization Agent. 
Please see the attached file.

Many thanks, ETVM/ETVA team.
*END*
            ->Attach({
                 description    => 'File',
                 ctype          => 'application/x-zip-encoded',
                 encoding       => 'Base64',
    #             disposition    => 'attachment; filename="/tmp/unavailable.php"; type="TAR archive"',
                 file           => $attachment
            })
           ->Close();
    } or return 0;

    chdir $currDir; 
    return 1;
}

sub encryptData{
    my ($diagnostic_tarball, $app_dir) = @_;
    my $gpg_dir = $app_dir.'/data/.gpg';
    mkdir $gpg_dir unless(-d $app_dir.'/data/.gpg');

    # encrypting the file
    #print "DIR ".`pwd`."\n";
    my $err = `gpg --import $app_dir/etvm.gpg`; 
    if($err){ 
        print STDERR "[ERROR] $err\n";
        return undef;
    }

    my $email = 'etvm@eurotux.com';
    my $err1 = `gpg --no-permission-warning --yes --trust-model always -r $email -a -e $diagnostic_tarball 2>&1 >/dev/null`;
    if($err1){
        print STDERR "[ERROR] $err1\n" if($err1);
        return undef;
    }
    return $diagnostic_tarball.'.asc';
}
1; 
