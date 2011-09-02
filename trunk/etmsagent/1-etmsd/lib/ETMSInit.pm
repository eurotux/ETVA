#!/usr/bin/perl
use strict;

package ETMSInit;

my $export_file = "/etc/sysconfig/qmail";
my $ldap_script = "/srv/qmail/bin/initialize_ldap.sh";
my $qmail_script = "/srv/qmail/bin/initialize_qmail.sh";
my $webmail_script = "/srv/qmail/bin/initialize_webmail.sh";
my $corier_script = "/srv/qmail/bin/initialize_courier.sh";
my $webmin_root_acl = "/etc/webmin/virtual-server/root.acl";

#print is_initialized();
#print "\n-- Changing environment: \n";
#print &change_evironment;
#p
#print "\n-- Initializing ldap: \n";
#print &init_ldap;
#p
#print "\n-- Initializing qmail: \n";
#print &init_qmail;
#p
#print "\n-- Initializing webmail: \n";
#print &init_webmail;
#p
#print "\n-- Initializing corier: \n";
#print &init_corier;
#p
#print "\n-- Initializing webmin: \n";
#print &init_webmin;
#p
#print "\n-- Creating test domain: \n";
#py $res = `/usr/libexec/webmin/create_domain.sh teste.domain.com password`;
#print $res;
sub is_initialized{
    open (FILE, "<$export_file") or warn "Can't open $export_file: $!\n";
    while(<FILE>){
        if(/.*ETMAILSERVEREDITED=(\d{1})$/){
            return $1;
        }
    }
    return 0;
}

sub change_evironment{
    my $res = "";

    #Open the file and read data
    #Die with grace if it fails
    open (FILE, "<$export_file") or warn "Can't open $export_file: $!\n";
    my @lines = <FILE>;
    close FILE;

    #Open same file for writing, reusing STDOUT
    open (FILE, ">$export_file") or warn "Can't open $export_file: $!\n";

    #Walk through lines, putting into $_, and substitute 2nd away
    for ( @lines ) {
    #   s/(.*?away.*?)away/$1yellow/;
        s/(.*ETMAILSERVEREDITED)=\d{1}$/$1=1/;            
        print FILE;
    }

    #Finish up
    close FILE;

    return "$export_file edited successfully\n";
}

sub init_ldap{
    return `echo -e "y\nn\n" | $ldap_script 2>&1`;
}

sub init_qmail{
    my $res = `$qmail_script 2>&1`;
    if($res =~ /(Please\sedit.*)/){
        return $1;
    }else{
        return "qmail successfully initialized\n";
    }
}

sub init_webmail{
    return `$webmail_script 2>&1`;
}

sub init_corier{
    return `$corier_script 2>&1`;
}

sub init_webmin{
    #Open the file and read data
    #Die with grace if it fails
    unless(-e $webmin_root_acl){
        print "$webmin_root_acl does not exist\n";
        return;
    }

    open (FILE, "<$webmin_root_acl") or warn "Can't open $webmin_root_acl: $!\n";
    my @lines = <FILE>;
    close FILE;

    #Open same file for writing, reusing STDOUT
    open (FILE, ">$webmin_root_acl") or warn "Can't open $webmin_root_acl: $!\n";

    #Walk through lines, putting into $_, and substitute 2nd away
    for ( @lines ) {
        s/(.*noconfig).*/$1=0/;
	s/(.*local).*/$1=1/;
	s/(.*edit).*/$1=1/;
	s/(.*create).*/$1=1/;
	s/(.*stop).*/$1=1/;
	s/(.*domains).*/$1=\*/;

        print FILE;
    }

    #Finish up
    close FILE;

    return "$webmin_root_acl edited successfully\n";
}

1;

