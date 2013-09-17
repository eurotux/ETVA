#!/usr/bin/perl -w
use strict;
use Net::LDAP;
#use Utils;


package ETMS;
use ETVA::Utils;
use ETVA::ArchiveTar;
use ETMSInit;
#use Utils;
use Data::Dumper;
use LWP::Simple;

my $path = "/etc/webmin/virtual-server";
my $qmailPath = "/srv/qmail/control";
my $domainsPath = "/etc/webmin/virtual-server/domains";
my $aliasFile = "/srv/qmail/control/aliasdomains";
my $webminScripts = "/usr/libexec/webmin/virtual-server";
my $rcptFile = "/srv/qmail/control/rcpthosts";
my $localsFile = "/srv/qmail/control/locals";
my $defaultPath = "/";
my $webmin = "/etc/webmin/";
my $maildirs = "/srv/qmail/maildirs";
my $statePath = "/srv/etva-etms/state";
my $initFile = "init";

##### Domains #######
#print &create_domain("some.domain", "ola123");
#&edit_domain("some.domain", "user.some.domain", "100000000", "500", "descricao sobre o dominio", "1");
#my @domains = &list_domains();
#print Dumper(\@domains);
#print Dumper(&select_domain("some.domain"));
#&delete_domain(" ", 'domain' => "samsung.com");

##### Alias #####
#my @alias = qw(a b c);
#&create_alias("a",'domain' => 'tmn.pt', 'alias' => \@alias);
#&delete_alias("some.domain", "aaa.some.domain", "bbbb.some.domain");
#my @domain_alias = &select_alias("some.domain");
#print "@domain_alias";
#&change_alias("domain" => "tmn.pt","org"=>"a","dst"=>"a.tmn.pt");

##### Users #####
#$mail, $pass, $real, $active, $allowRelay, $quota, $deliveryType, $answer
#print "\ncreating user\n";
#print &createUser('manuel@some.domain', "ola123", 'Manuel Dias', "0", "0", "1215000", "noforward", 'Resposta automatica');
#print &edit_user_alias('manuel@some.domain', 'mfd@some.domain', 'manuelalias@some.domain');
#print &edit_mail_forwarding('manuel@some.domain', 'mfd@tmn.pt', 'manuel_alias@sapo.com');
#&edit_user("manuel");

	
#print Dumper &get_users("aa","domain", "tmn.pt");
#print Dumper(&get_user("tmn.pt", 'bart@tmn.pt'));
#&domainFileName("tmn.pt");
#print Dumper &delete_user(' ', 'user_name' => 'xxxxx@tmn.pt');
#&delete_domain("some.domain");
#&delete_user("", 'user_name' => 'bbbbbbbbbbbbb@tmn.pt');

######### server  #########

# calls first start script
sub initialize{
    my $self = shift;

    # do not change the following message. It is validated in CM
    if(ETMSInit::is_initialized() == 1){
        print "ETMS is already initialized\n";
        return retOk('Initialize', 'Already initialized', 1)
    }
    #return retOk('Initialize', 'Already initialized', 1) if(ETMSInit::is_initialized() == 1);

    #stop the service
    system "svc -kd /service/qmail";

    my $res = "\n-- Changing environment: \n";
    $res .= ETMSInit::change_evironment();

    $res .= "\n-- Initializing ldap: \n";
    $res .= ETMSInit::init_ldap();

    $res .= "\n-- Initializing qmail: \n";
    $res .= ETMSInit::init_qmail();

    $res .= "\n-- Initializing webmail: \n";
    $res .= ETMSInit::init_webmail();

    $res .= "\n-- Initializing corier: \n";
    $res .= ETMSInit::init_corier();

    $res .= "\n-- Initializing webmin: \n";
    $res .= ETMSInit::init_webmin();

    #start the service
    system "svc -u /service/qmail /service/qmail-smtpd";

    $res .= "\n-- Creating test domain: \n";
    my $d = $self->create_domain('name' => 'test.domain.com', 'password' => 'password');
    $res .= Dumper($d);
    $d = $self->delete_domain('domain' => 'test.domain.com');
    $res .= Dumper($d);
   
    print $res; 
    return retOk('Initialize', $res, 1);
}

sub remove_initLog{
    my $self = shift;
    
    #verify if exists a initialization log
    my $init = $statePath."/".$initFile;
    if(-e $init){
        unlink $init or retErr('Unlink', 'Could\'nt delete initializing log message', 1);
    }else{
        retErr('Unlink', 'Initializing log does not exists', 1);
    }
    
    retOk('Remove Init Log', "Log was successfully removed", '1');
}

#========= BACKUP =========
# get_backupconf - get backup of configuration file
sub get_backupconf {
    my $self = shift;
    my %p = @_;
    print "getbackupconf\n";

    my $E = $self->server_backup();
    if( isError($E) ){
        return wantarray() ? %$E : $E;
    }

    my $sock = $p{'_socket'};

    # set blocking for wait to transmission end
    $sock->blocking(1);
    
    if( $p{'_make_response'} ){
        print $sock $p{'_make_response'}->("",'-type'=>'application/x-compressed-tar');
    }

    if( my $c_path = $self->get_etms_backupfile() ){
        if( -e "$c_path" ){
            plog "get_backupconf","c_path=$c_path";
            my $fh;
            open($fh,"$c_path");    # read from file
#            binmode($fh);
            my $buf;
            while (read($fh, $buf, 60*57)) {
                print $sock $buf;   # write to socket
            }
            close($fh);
        } else {
            plog "get_backupconf","backup file '$c_path' doesnt exists.";
            return retErr("_ERR_GETBACKUPCONF_","backup file '$c_path' doesnt exists.");
        }
    } else {
        plog "get_backupconf","No backup file.";
        return retErr("_ERR_GETBACKUPCONF_","No backup file.");
    }
    return;
        
}

my $backupFile = "backup.tgz";

#retorna path+nome_ficheiro
sub get_etms_backupfile{
    my $self = shift;
    my %p = @_;
    print "get_etms_backupfile\n";

    my $dir = `pwd`;
    chomp $dir;
    return $dir."backup.tgz";

}


sub server_backup{
    shift;
    print "server_backup\n";

    #identificar onde guardar o backup    
    my $dir = `pwd`;
    print "SERVER BACKUP: $dir \n";

    chdir "/" or return retErr('Backup Failed', "Cannot change dir to $qmailPath", 1);
    #backup do ldap
    `ldapsearch -x -D cn=Manager,dc=domain,dc=com -wpassword -h localhost -LLL -b dc=domain,dc=com > backup_ldap.ldif`;

    #backup do qmail
    `echo -e "/backup_ldap.ldif\n/srv/qmail/control/aliasdomains\n/srv/qmail/control/locals\n/srv/qmail/control/rcpthosts" > backup_files.txt`;
    `ls /etc/webmin/*.acl >> backup_files.txt`;
    `ls /etc/webmin/virtual-server/*.acl >> backup_files.txt`;
    `ls /etc/webmin/virtual-server/domains/* >> backup_files.txt`;
    `tar -cvzf $backupFile -T backup_files.txt`;

    unlink "backup_ldap.ldif";
    unlink "backup_files.txt";

    chdir $dir;
}

#============ RESTORE =============

sub server_restore{ 
    my $self = shift;
    my $filename = shift;   
 
    my $pwd = `pwd`;
    print "pwd: $pwd\n";
    print `tar -xzvf $filename` or retErr("ETMS Restore", "Cannot untar file: $!", '1');
#    `ldapadd -x -D cn=Manager,dc=domain,dc=com -w password -h localhost -f -f backup_ldap.ldif -n` or retErr("ETMS Restore", "Cannot restore ldap: $!", '1');
    print `ldapmodify -x -D cn=Manager,dc=domain,dc=com -w password -h localhost -a -c -f backup_ldap.ldif` or retErr("ETMS Restore", "Cannot restore ldap: $!", '1');
    unlink "backup_ldap.ldif";
    print `make -C /srv/qmail/control/` or retErr("ETMS Restore", "Cannot run make on qmail configuration files: $!", '1');

    print "restarting server\n";
    $self->server_restart();
    return retOk('server', 'SERVER RESTORE CALLED', '1');
}

sub set_backupconf {
    my $self = shift;
    my (%p) = @_;

    my %init_res = $self->initialize();
    #if( isOk($init_res) ){
    my $i = $init_res{'_okmsg_'};
    unless($i =~ /already initialized/i){
        unless(-d $statePath){
            `mkdir -p $statePath`;
        }

        open(INIT, "> $statePath/$initFile");
        print INIT $init_res{'_okmsg_'};
        close(INIT);
    }
    #}

    print "create previous backup\n";
    # create previous backup
    my $E = $self->server_backup();
    if( isError($E) ){
        return wantarray() ? %$E : $E;
    }


    if( my $bf = $self->get_etms_backupfile() ){
        
        my $oribf = "$bf.recoverbackupconf";
        my @lbf = split(/\//,$oribf);
        my $fn = pop(@lbf); # get file name
        print "filename: $fn\n";
        
        print "URL: $p{'_url'} \n";
        if( $p{'_url'} ){
            my $rc = LWP::Simple::getstore("$p{'_url'}","$oribf");
            if( is_error($rc) || !-e "$oribf" ){
                return retErr('_ERR_SET_BACKUPCONF_',"Error get backup file ($oribf status=$rc) ");
            }
        } else {
            my $sock = $p{'_socket'};

            # set blocking for wait to transmission end
            $sock->blocking(1);

            my $tar = ETVA::ArchiveTar->new();
            $tar->read($sock);
            plog "set_backupconf files=",$tar->list_files();
            $tar->write($oribf);
        }

        print "server_restore started\n";
        my $S = $self->server_restore($fn);
        print "server restore ended\n";

        if( isError($S) ){
            return wantarray() ? %$S : $S;
        }
        
    } else {
        return retErr("_ERR_SETBACKUPCONF_","No media to backup file.");
    }

    return;
}



#========================== SERVER ACTIONS ============================

#Retorna informação sobre o estado do servico. Caso encontre o ficheiro com o log 
# da iniciação, retorna também o seu conteúdo.
sub server_info{
    
        my $out = `svstat /service/qmail`;
        chomp $out;

        my $res = ObjQmailStatus->new();
        # EX: /service/qmail: up (pid 11339) 2744997 seconds
        if($out =~ /\/service\/qmail:\s*(\w+)\s*\(.*\)\s*(\d+)/){
                $res->{"STATE"} = $1;
                $res->{"UPTIME"} = $2;
        #/service/qmail: down 12 seconds, normally up
        }elsif($out =~ /\/service\/qmail\s*:\s*(\w+)\s(\d+)/){
                $res->{"STATE"} = $1;
                $res->{"UPTIME"} = $2;
        }

        $res->{"NRDOMAINS"} = scalar &domainFiles;
        $res->{"NRMAILBOXES"} = &nr_mailboxes;

     #verify if exists a initialization log
    my $init = $statePath."/".$initFile;

    if(-e $init){
        open(INIT, "< $init");
        my $initLog = "";
        while(<INIT>){
            $initLog .= $_;
        }
        $res->{"INITLOG"} = $initLog;
    }
  
    print Dumper $res;

	my @server;
	unshift @server, $res;

	return wantarray() ? @server: \@server;
}

sub server_restart{
        system "svc -d /service/qmail /service/qmail-smtpd";
        sleep(1);
        system "svc -u /service/qmail /service/qmail-smtpd";
        return &server_info;
}

sub server_kill{
        system "svc -kd /service/qmail /service/qmail-smtpd";
        sleep(1);
        return &server_info;
}

sub server_start{
        system "svc -u /service/qmail /service/qmail-smtpd";
        sleep(1);
        return &server_info;
}

sub server_stop{
        system "svc -d /service/qmail /service/qmail-smtpd";
        sleep(1);
        return &server_info;
}

sub nr_mailboxes{
        my $ldap = Net::LDAP->new( 'localhost' ) or die "$@";
        my $mesg = $ldap->bind( 'cn=Manager,dc=domain,dc=com',
                      password => 'password'
                    );

        $mesg = $ldap->search(          # perform a search
                               base   => "dc=domain,dc=com",
                               filter => "(objectClass=qmailUser)"
                             );
        $mesg->code && die $mesg->error;
        print("Mailboxes search: ".$mesg->error."\n");
        print("Number of ldap entries: ".$mesg->count()."\n");
        return $mesg->count();
}

sub domains_occupied_space{
    shift;
    my @domains = &list_domains;
    my @res;
    foreach my $domain(@domains){
        my @occ = &occupied_Space('', 'domain' => $domain->{'name'});
        unshift @res, $occ[0];
    }
    print Dumper \@res;
    return wantarray() ? @res : \@res;
}

sub users_occupied_space{
    shift;
    my %params = @_;
    my @res;
    my @users = &get_users('', 'domain' => $params{'domain'});
    foreach my $user(@users){
        my @occ = &occupied_Space('', 'mailbox' => $user->{'user_name'});
        unshift @res, $occ[0];
    }
    print Dumper \@res;
    return wantarray() ? @res : \@res;
}

sub occupied_Space{
    shift;
    my %params = @_;
    if($params{'mailbox'}){
        if($params{'mailbox'} =~ /(\w+)@([a-zA-Z_.]+)/){
            my @resp;
            my $domain = $2;
            my $user = $1;
            my $path = $maildirs.'/'.$domain.'/'.$user.'/Maildir';
            my %res;
            $res{'mail'} = $params{'mailbox'};
            $res{'new'} = $1 if(`du -sh $path/new` =~ /^([^\s]+)/);
            $res{'cur'} = $1 if(`du -sh $path/cur` =~ /^([^\s]+)/);
            unshift @resp, \%res;
            return wantarray() ? @resp : \@resp;
        }else{
            return retErr('occupied_Space', 'Supplied mail did not match the regular expression', '1');
        }
    }else{
        my $space;
        #else: retorna o espaco ocupado no servidor
        #alert($params{'domain'});
        my @res;
        if($params{'domain'} ne ''){
            $space = `du -sh $maildirs/$params{'domain'}`;
            chomp $space;
            $space =~ s/\s+(\/(\w|\.)+)+//;
            unshift @res, {'domain' => $params{'domain'}, 'space'  => $space};
        }else{
            $space = `du -sh $maildirs`;
            chomp $space;
            $space =~ s/\s+(\/\w+)+//;
            unshift @res, {'space' => $space};
        }
#        ($params{'domain'} ne '') ? $space = `du -sh $maildirs/$params{'domain'}` : $space = `du -sh $maildirs`;
        return wantarray() ? @res : \@res;
    }
}

########## domains ##########

sub list_domains{
	my @domains;
	my @files = &domainFiles();
	foreach(@files){
		my $domainRef = &readDomain($_);
		$domainRef->{'mailbox'} = &get_nr_mailboxes($domainRef->{'name'});
		unshift @domains, $domainRef;
	}
	#print STDERR Dumper(@domains);
	#my %de = ("dsaa"=>"dsdsds");
	#return wantarray() ? %de : \%de;
	return wantarray() ? @domains: \@domains;
}

sub select_domain{
	shift;
	my %params = @_;	
	my $name = $params{'name'};
	my @domains = &list_domains();
	
	foreach my $domain (@domains){
		if($domain->{"name"} eq $name){
			return $domain;
		}
	}
	
#	return undef;
	return retErr('domain', "cannot find the specified domain: $name", 1);
}

sub domainFiles{
	chdir "$path/domains" or retErr("Domain Files", "cannot chdir $!", 1);
	my @filenames = glob "*";
	chdir $defaultPath or warn "cannot chdir $!";
	return @filenames;
}

sub readDomain{
	my $filename = shift;
	chdir $domainsPath or retErr("Read Domains", "cannot chdir $!", 1);
	my $domainRef = ObjDomain->new();
	print "Reading domain: $filename\n";
	open FILE, "< $filename";
	while(<FILE>){
		if(/^dom=(.*)$/i){
			$domainRef->{"name"} = $1;	
		}
		if(/^user=(.*)$/i){
			$domainRef->{"user"} = $1;
		}
		if(/^qquota=(.*)$/i){
			$domainRef->{"server_quota"} = $1;
		}
		if(/^mailboxlimit=(.*)$/i){
			$domainRef->{"max_mailboxes"} = $1;
		}
		if(/^owner=(.*)$/i){
			$domainRef->{"description"} = $1;
		}
		if(/^active=(.*)$/i){
			$domainRef->{"isActive"} = $1;
		}
	}
	close FILE;
	$domainRef->{"FILENAME"} = $filename;
	
	chdir $defaultPath or warn "cannot chdir $!";
	return $domainRef;
}

# verifica se determinado nome já esta registado. (dominio/alias)
sub check_exists{
    shift;
    my %params = @_;
  
    print Dumper \%params; 
    #verificar se existe algum alias ou dominio com o mesmo nome.
    my @domains = &list_domains;
    foreach my $domain (@domains){
        #verificar se o domain já existe
        if($params{'name'} eq $domain->{"name"}){
            print "Domain with the same name already exists\n";
            return 'Domain with the same name already exists';
        }
        my $objAlias = ObjAlias->new();
        $objAlias->fill('domain' => $domain->{'name'});
        my $aliasRef = $objAlias->{'alias'};

        print Dumper $aliasRef;
        if($objAlias->{'alias'}){
            foreach my $a (@$aliasRef){
                print "$params{'name'} eq $a\n";
                if($params{'name'} eq $a){
                    print "An alias with the same name already exists\n";
                    return 'An alias with the same name already exists';
                }
            }
        }
    }
    return '0';
}
	
sub create_domain{
	shift;
	my %params = @_;
	#verificar se o dominio já existe
	#my $dom = &select_domain("name" => $params{'name'});

    #verificar se existe algum alias ou dominio com o mesmo nome.
    my $res = &check_exists('', %params);
    #print "Res $res\n";
    return retErr('Create Domain', $res, 1) unless($res eq '0');

	print "create_domain called\n";
	$res = `/usr/libexec/webmin/create_domain.sh $params{'name'} $params{'password'}`;
	return retErr('domain', 'Cannot create domain. (It maybe already exists)', 1) if($res eq "");
    `make -C /srv/qmail/control/`;
	&edit_domain($_, %params);

	return retOk('domain', 'Domain created successful', '1');
}

sub edit_domain{
	shift;
	my %params = @_;

	my $domainRef = &select_domain("", 'name' => $params{'name'});
	my $filename = $domainRef->{"FILENAME"};
	chdir $domainsPath or warn "cannot chdir $!";
	open IN, "< $filename";
	open OUT, "> $filename.TMP";
	
	while(<IN>){
		next if(/^user=(.*)$/i);
		next if(/^qquota=(.*)$/i);
		next if(/^mailboxlimit=(.*)$/i);
		next if(/^owner=(.*)$/i);
		next if(/^active=(.*)$/i);
		print OUT $_;
	}
	
	print OUT "user=$params{'user'}\n";
	print OUT "qquota=$params{'server_quota'}\n";
	print OUT "mailboxlimit=$params{'max_mailboxes'}\n";	
	print OUT "owner=$params{'description'}\n";
	print OUT "active=$params{'isActive'}\n";
   
    &change_accounts_status(%params); 
	
	close IN;
	close OUT;
	
	#mover
	unlink $filename;
	rename "$filename.TMP", $filename;
	
	chdir $defaultPath or warn "cannot chdir $!";
}

sub change_accounts_status{
    my %params = @_;
    my($domain) = $params{'name'};

    my @users;
    my $ldap = Net::LDAP->new( 'localhost' ) or die "$@";

    my $mesg = $ldap->bind( 'cn=Manager,dc=domain,dc=com',
                      password => 'password'
                    );
    $mesg = $ldap->search(      # perform a search
                           base   => "dc=domain,dc=com",
                           filter => "(objectClass=qmailUser)"
                         );

    $mesg->code && die $mesg->error;

    foreach my $entry ($mesg->entries) {
#        $entry->dump;  
        $_ = $entry->get_value('uid');
        s/[^@]+@(.+)/$1/;
        my $entryDomain = $_;

        print "ENTRY DOMAIN: $entryDomain eq DOMAIN: $domain\n";
        if($entryDomain ne $domain){
            print "not equal\n";
            next;
        }
        
        print "ISACTIVE: $params{'isActive'}\n";
        if($params{'isActive'} == "1"){
            $entry->replace(accountStatus => 'active') or warn "$!";
            print "Changing account status to active\n";
        }else{
            $entry->replace(accountStatus => 'noaccess') or warn "$!";
            print "Changing account status to noaccess\n";
        }

        $entry->dump;
        $entry->update($ldap);

    }

    $mesg = $ldap->unbind;   # take down session
    return wantarray() ? @users : \@users;
}

#por testar
sub delete_domain{
	shift;
	my %params = @_;
	my $domain = $params{'domain'};
		
	#carregar o uid dos utilizadores e remover
	my @users = &get_users(" ", 'domain' => $domain);
		
	if(scalar(@users) > 0){
		print "delete\n\n";
		foreach my $user(@users){	#chamar o destrutor de utilizadores
			my $mail = $user->{"user_name"};
			print "$mail to delete\n";
			&delete_user(" ", 'user_name' => $mail);
		}
	}	
	
	#remover alias de domínios
	my @alias = &select_alias(" ", 'domain' => $domain);
	&delete_alias(" ", 'domain' => $domain, 'alias' => \@alias);
		
	#remover o dominio (webmin e qmail)
	my @files = &domainFiles();
	print "@files";
	
	foreach my $file (@files){
		print "readDomain $file";
		my $domainRef = &readDomain($file);
		if($domainRef->{"name"} eq $domain){
			my $pwd = `pwd`;
			chdir "$path/domains";
			`rm $file`;			
			
			chdir $qmailPath;
			
			#editar locals		
			open (LOCALS, "< $localsFile");
			open LOCALSTMP, "> $localsFile.tmp";
			while (<LOCALS>){
				chomp;
				print LOCALSTMP "$_\n" unless (/$domain/);
			}
			close LOCALS;
			close LOCALSTMP;
			
			unlink "$localsFile";
			rename "$localsFile.tmp", "$localsFile";
			
			#editar rcpt
			open (RCPT, "< $rcptFile");
			open RCPTTP, "> $rcptFile.tmps";
			while (<RCPT>){
				chomp;
				print RCPTTP "$_\n" unless (/$domain/);
			}
			close RCPT;
			close RCPTTP;
			
			unlink "$rcptFile";
			rename "$rcptFile.tmps", "$rcptFile";
			
			chdir $pwd;					
		}
 	
	}
	
	print "removing webmin user: $domain\n";
	#remover utilizador de gestao (webmin)
	&remove_from_file("/etc/webmin/miniserv.users", $domain);
	&remove_from_file("/etc/webmin/webmin.acl", $domain);
	&remove_from_file("/etc/webmin/config", $domain);
    unlink "/etc/webmin/$domain.acl";
    unlink "/etc/webmin/virtual-server/$domain.acl";

    `make -C /srv/qmail/control/` or retErr('Domain Delete', '$!', '1');    
#	my $curdir = `pwd`;	#todo: validar erro e reeniciar o servico
#	chdir $qmailPath or warn "$!";
#	`make`;
#	print "$?\n";
#	chdir $curdir;

    #delete mail folders
    `rm -rf $maildirs/$domain`;

	return retOk('domain', 'Domain, Alias and Mailboxes successfully removed', '1');
}

sub remove_from_file(){
	my ($file, $pattern) = @_;
	
        open IN, "< $file" or return return retErr('Remove from file', "Cannot open file: $file", '1');
        open OUT, "> $file.tmp" or return retErr('Remove from file', "Cannot create temp file: $file.tmp", '1');
        while (<IN>){
             chomp;
             print OUT "$_\n" unless (/$pattern/);
        }
        close IN;
        close OUT;
        unlink $file;
        rename $file.'.tmp', $file;
    return '0';
}

########### Alias de Domains ###########
sub change_alias{
	my $self = shift;
	my %params = @_;
	my $domain = $params{'domain'};

	return retErr("change_alias", "Invalid domain", 1) unless(defined $domain);
	print "domain $domain"	;
	unshift my @org, $params{'org'};
	print Dumper @org;
	unshift my @dst, $params{'dst'};
	print Dumper @dst;
	my $res = &create_alias($self, 'domain' => $domain, 'alias' => \@dst);
    return $res unless($res eq '0');
	&delete_alias('', 'domain' => $domain, 'alias' => \@org);
	return retOk('alias', 'Alias successful modified', '1');
}


#verifica se existe o dominio
sub create_alias{
	shift;
    my %params = @_;

    my $domain = $params{'domain'};
    return retErr("Create Alias", "Invalid domain", 1) unless(defined $domain);

	my $newAliasRef = $params{'alias'};
        my @newAlias = @$newAliasRef;
    
    # NOTA: so valida o primeiro (o manager nao suporta vários, por isso np)
    my $res = &check_exists('', 'name' => $newAlias[0]);
    return retErr('Create Alias', $res, 1) unless($res eq '0');

	#recolher os existentes e acrescentar os novos
	my @alias = &select_alias($_, 'domain' => $domain);

	open FILE, ">> $aliasFile";
	
	foreach my $new(@newAlias){
		if ( grep { $_ eq $new} @alias ){
			next;	#já tem o alias
		}else{
			print FILE "$new:$domain\n";		#escrever para o ficheiro	
		}
	}
	close FILE;

    #acrescentar ao locals e ao rcpthosts
    open (LOCALS, ">> $localsFile") or retErr('Create Alias', "Cannot open file $localsFile", '1');
    print LOCALS $newAlias[0];
    close LOCALS;
    open (RCPT, ">> $rcptFile") or retErr('Create Alias', "Cannot open file $rcptFile", '1');
    print RCPT $newAlias[0];
    close RCPT;

    return '0';
}

sub delete_alias{
	shift;
	my %params = @_;
	my $domain = $params{'domain'};
	return retErr("delete_alias", "Invalid domain", 1) unless(defined $domain);

	my $aliasRef = $params{'alias'};
	my @alias = @$aliasRef;

	open FILE, "+< $aliasFile";
	chdir $qmailPath;
	
	open OUT, "> AliasOut";
	
	while (<FILE>){
		my $flag = 0; 
		foreach my $a(@alias){
			print "$a:$domain -> $_\n";
			if(s/^$a:$domain\n//){
				$flag = 1;
				last;
			}
		}	
		unless($flag){
			print OUT $_;
		}
	}
	close FILE;
	close OUT;
	
	unlink $aliasFile;
	rename "AliasOut", "aliasdomains";

    # remover de locals e rcpt
    my $res = &remove_from_file($localsFile, $alias[0]);
    return $res unless($res eq '0');
    $res = &remove_from_file($rcptFile , $alias[0]);
    return $res unless($res eq '0');
}


#retorna uma lista de alias, para um determinado domain 
sub select_alias{
	shift;
	my(%params) = @_;
	my $domain = $params{'domain'};
	print "select alias from dominio: ".$domain."\n";

	my @res;
	open FILE, "< $aliasFile";
	while(<FILE>){
		if(/^([^:]+):(.+)$/){
			if($2 eq $domain){
				push @res, $1;
			}
		}
	}
	close FILE;
	return wantarray() ? @res: \@res;
}

############ users #############
sub create_user{
	shift;
	my %params = @_;
	
	#my ($mail, $pass, $real, $active, $allowRelay, $quota, $deliveryType, $answer) = @_;
	#my ($domain, $user);	

	#if($mail =~ /^([^@]+)@(.+)$/){
	#	$domain = $2;
	#	print $domain."\n";
	#	$user = $1;
	#	print $user."\n";
	#}else{
	#	return retErr('user', 'Incorrect email format', 1);
	#}
	
	#validar os parametros

	return retErr('user', 'Password error', 1) unless(defined $params{'password'} && $params{'password'} ne '');
	
	my $cmd = "perl $webminScripts/create-user.pl --domain '$params{'domain'}' --user '$params{'user_name'}' --pass '$params{'password'}' --qmail-quota $params{'mailbox_quota'}"; # --real $real --qmail-quota $quota ";
	plog "$cmd\n";
	#my $res = `$cmd 2>&1`;        
    my ($e,$res) = cmd_exec($cmd);
	plog $res;
	plog "Exit code: $e\n";
	unless($e == 0){
		if( $res !~ m/User \S+ created successfully/ ){
		    my $msg_err = $res;
		    if( $res =~ m/Error: (.+)\r?\n/ ){
			$msg_err = $1;
		    }
		    return retErr("create_user", $msg_err);
		}
	}

	if($res =~ /(error.*)/i){
		return retErr('user', $1);
	}
	
#	print Dumper %params;
	
	plog "Edit User \n\n";

#	my $user_name = $params{'user_name'}.'@'.$params{'domain'};
#	print $user_name."\n";
#	&edit_user("",	'user_name' 	=> $user_name, 
#			'password' 	=> $params{'password'}, 
#			'real_name'	=> $params{'real_name'}, 
#			'isActive'	=> $params{'isActive'}, 
#			'allowExternalSend' => $params{'allowExternalSend'}, 
#			'mailbox_quota'	=> $params{'mailbox_quota'}, 
#			'delivery_type' => $params{'delivery_type'}, 
#			'automatic_answer'  => $params{'automatic_answer'}
#	);
	
	$params{'user_name'} = $params{'user_name'}.'@'.$params{'domain'};
	&edit_user("", %params);

	return retOk('user', 'Mailbox created successful');
}

sub get_user{
    my $self = shift;
	my($domain, $mail, $user_name) = my %params = @_;
    if( $params{'domain'}  || $params{'mail'} ){
        $domain = $params{'domain'};
        $mail = $params{'mail'};
        $user_name = $params{'user_name'};
        if( $user_name ){
            $mail = join('@',${user_name},${domain});
        }
    }
    print STDERR "get_user mail=$mail domain=$domain","\n";
    if( $mail ){
        my @users = $self->get_users('domain'=>$domain);
        
        foreach my $userRef (@users){
            if( ($userRef->{"MAIL"} eq $mail) || 
                    ($userRef->{"user_name"} eq $mail) ){
    #			print Dumper($userRef);
                return $userRef;
            }
        }
	}
}

sub get_nr_mailboxes{
	my $domain = shift;
	my $res = 0;
	my $ldap = Net::LDAP->new( 'localhost' ) or die "$@";

        my $mesg = $ldap->bind( 'cn=Manager,dc=domain,dc=com',
                     password => 'password'
                );

        $mesg = $ldap->search(          # perform a search
                               base   => "dc=domain,dc=com",
                               filter => "(objectClass=qmailUser)"
                             );

        $mesg->code && die $mesg->error;

        foreach my $entry ($mesg->entries) {
#                $entry->dump;  
                $_ = $entry->get_value('uid');
	        s/[^@]+@(.+)/$1/;
        	my $entryDomain = $_;
        
		if($entryDomain ne $domain){
                	next;
	        }
		$res += 1;
        }

        $mesg = $ldap->unbind;   # take down session
        return $res;
}

#print Dumper &get_users("",'domain'=>'tmn.pt');
#ldapsearch -x -D cn=Manager,dc=domain,dc=com -wpassword -h localhost -LLL -b dc=domain,dc=com -s sub '(&(objectClass=qmailUser)(uid=teste@teste.local))'
sub get_users{
	shift;
	my %params = @_;
	my($domain) = $params{'domain'};

	my @users;
	my $ldap = Net::LDAP->new( 'localhost' ) or die "$@";

	my $mesg = $ldap->bind( 'cn=Manager,dc=domain,dc=com',
                      password => 'password'
                    );
	$mesg = $ldap->search( 		# perform a search
	                       base   => "dc=domain,dc=com",
	                       filter => "(objectClass=qmailUser)"
	                     );
	
	$mesg->code && die $mesg->error;
	
	foreach my $entry ($mesg->entries) {
#		 $entry->dump;	
		$_ = $entry->get_value('uid');
        s/[^@]+@(.+)/$1/;
        my $entryDomain = $_;
        print $entryDomain."\n";
        
        if($entryDomain ne $domain){
                print "not equal\n";
                next;
        }
		
		#processar os dados do utilizador
		my $userRef = ObjUser->new();
		$userRef->{"UID"} = $entry->get_value('uid');
		$userRef->{"real_name"} = $entry->get_value('cn');
		$userRef->{"DN"} = $entry->dn();
		$userRef->{"USERPASSWORD"} = $entry->get_value('userPassword');
		$userRef->{"user_name"} = $entry->get_value('mail');
		$userRef->{"automatic_answer"} = $entry->get_value('mailReplyText');						 
		$userRef->{"isActive"} = $entry->get_value('accountStatus');
		$userRef->{"mailbox_quota"} = $entry->get_value('mailQuotaSize');
		$userRef->{"delivery_type"} = $entry->get_value('deliveryMode');
		$userRef->{"allowExternalSend"} = $entry->get_value('allowRelay');

		my @alias = $entry->get_value('mailAlternateAddress');	#array
		$userRef->{"nr_mail_alias"} = @alias;
		$userRef->{"mail_alias"} = \@alias;

		my @forward = $entry->get_value('mailForwardingAddress');	#array
		$userRef->{"nr_redirect_emails"} = @forward; 
		$userRef->{"redirect_emails"} = \@forward;
		#print Dumper($entry->get_value('mailAlternateAddress'));
		#print Dumper($entry->get_value('mailForwardingAddress'));
		 
		unshift @users, $userRef;
	}

	$mesg = $ldap->unbind;   # take down session
	return wantarray() ? @users : \@users;
}

my @alias = qw(alias1@tmn.pt alias2@tmn.pt);
my @forward = qw(fw1@tmn.pt fw2@tmn.pt);

#&edit_user(' ', 'mail' => 'bart@tmn.pt', 'password' => 'ola123', 'real' => 'Barty', 'active' => 'noaccess',
#	'allowRelay' => 1, 'quota' => 100, 'deliveryType' => 'reply', 'answer' => 'Some answer', 'alias' => \@alias, 'forward' => \@forward);

#NOTA: o mail nã:o pode ser alterado
#ldapsearch -x -D cn=Manager,dc=domain,dc=com -wpassword -h localhost -LLL -b dc=domain,dc=com -s sub '(&(objectClass=qmailUser)(uid=teste@teste.local))'
sub edit_user{
	shift;
    print "EDIT_USER: called\n";
	my %params = @_;
	print Dumper %params;
	#my ($mail, $password, $real, $active, $allowRelay, $quota, $deliveryType, $answer) = @_;
	
	my $ldap = Net::LDAP->new( 'localhost' ) or die "$@";
	my $mesg = $ldap->bind( 'cn=Manager,dc=domain,dc=com',
                      password => 'password'
                    );
                    
	$mesg = $ldap->search( 		# perform a search
	                       base   => "dc=domain,dc=com",
	                       filter => "(&(objectClass=qmailUser)(uid=$params{'user_name'}))"
	                     );
	
	$mesg->code && die $mesg->error;
	#return "User not found ($params{'user_name'})." if($mesg->count eq 0);
	return retErr("edit user", "User not found ($params{'user_name'})", "1") if($mesg->count eq 0);
	
	#preprocessar os dados
	if($params{'active'} eq "1"){
		$params{'active'} = "active";
	}else{
		$params{'active'} = "noaccess";
	} 
	
	#actualizar
	my @entries = $mesg->entries;
	my $ldap_user = $entries[0];
	
	$ldap_user->dump;
    retErr('','','');
	my $alias = $params{'alias'};
	my $forward = $params{'forward'};
	
	$ldap_user->replace (
		cn 		=> $params{'real_name'},
		accountStatus 	=> $params{'isActive'},
		allowRelay	=> $params{'allowExternalSend'},
		mailQuotaSize	=> $params{'mailbox_quota'},
		deliveryMode	=> $params{'delivery_type'},
		mailForwardingAddress => $forward
 	);
    
    if(defined $params{'automatic_answer'} && $params{'automatic_answer'} ne ''){
        $ldap_user->replace(mailReplyText => $params{'automatic_answer'});
    }elsif($params{'automatic_answer'} eq ''){
        $ldap_user->replace(mailReplyText => ' ');
    }
	
	if(defined $alias){
		$ldap_user->replace(mailAlternateAddress => $alias);
	}
	
	if(defined $forward){
		$ldap_user->replace(mailForwardingAddress => $forward);
	}

	#verificar se e necessário alterar a password
	if($params{'password'} && $params{'password'} ne ""){
		$ldap_user->replace (
			userPassword	=> $params{'password'},
		);
	}

	$ldap_user->dump;
	$ldap_user->update($ldap) or warn $!;
	$mesg = $ldap->unbind;   # take down session
	return retOk('user', 'User edited with success', '1');
}

sub edit_user_alias{
	my $mail = shift;
	my @alias = @_;
	
	my $ldap = Net::LDAP->new( 'localhost' ) or die "$@";
	
	my $mesg = $ldap->bind( 'cn=Manager,dc=domain,dc=com',
                      password => 'password'
                    );
                    
    $mesg = $ldap->search( 		# perform a search
	                       base   => "dc=domain,dc=com",
	                       filter => "(&(objectClass=qmailUser)(uid=$mail))"
	                     );
	
	$mesg->code && die $mesg->error;
	return "User not found ($mail)." if($mesg->count eq 0);
	
	#actualizar
	my @entries = $mesg->entries;
	my $ldap_user = $entries[0];

	$ldap_user->replace(mailAlternateAddress => \@alias);	
	$ldap_user->update($ldap);
	$mesg = $ldap->unbind;   # take down session
	
	return "Alias successful created: @alias";
}

sub edit_mail_forwarding{
	my $mail = shift;
	my @forward = @_;
	
	my $ldap = Net::LDAP->new( 'localhost' ) or die "$@";
	
	my $mesg = $ldap->bind( 'cn=Manager,dc=domain,dc=com',
                      password => 'password'
                    );
                    
    $mesg = $ldap->search( 		# perform a search
	                       base   => "dc=domain,dc=com",
	                       filter => "(&(objectClass=qmailUser)(uid=$mail))"
	                     );
	
	$mesg->code && die $mesg->error;
	return "User not found ($mail)." if($mesg->count eq 0);
	
	#actualizar
	my @entries = $mesg->entries;
	my $ldap_user = $entries[0];

	$ldap_user->replace(mailForwardingAddress => \@forward);	
	$ldap_user->update($ldap);
	$mesg = $ldap->unbind;   # take down session
	
	return "Forward emails successful added: @forward";
}

sub delete_user{
	shift;
	my %params = @_;

	my $mail = $params{'user_name'};
#	my $deleteMails = shift;
	print "Remove mail address: $mail\n";	

	my $ldap = Net::LDAP->new( 'localhost' ) or die "$@";
        my $mesg = $ldap->bind( 'cn=Manager,dc=domain,dc=com',
                      password => 'password'
                    );

        $mesg = $ldap->search(          # perform a search
                               base   => "dc=domain,dc=com",
                               filter => "(&(objectClass=qmailUser)(uid=$params{'user_name'}))"
                             );


	print("Mailboxes search: ".$mesg->error."\n");
	print("Number of ldap entries: ".$mesg->count()."\n");
	$mesg->code && die $mesg->error;
	return retErr("delete user", "ldap - response undefined", "1")unless(defined $mesg);
	return retErr("delete user", "ldap - search error", "1") unless($mesg->error eq 'Success');
	return retErr("delete user", "ldap - user not found", "1") if($mesg->count() eq 0);

	my $entry = $mesg->entry(0);
	#my $dirToRemove = $entry->get_value('mailMessageStore');
	my $dirToRemove2 = $entry->get_value('homeDirectory');
	#print "Removing folder: $dirToRemove\n";	
	print "Removing folder: $dirToRemove2\n";	

	#if(-e $dirToRemove){
	#	print `rm -rf $dirToRemove 2>&1`;# or warn "Error on remove maildir operation ";	
	#}		
	if(-d $dirToRemove2){
		print `rm -rf $dirToRemove2 2>&1`;# or warn "Error on remove home maildir operation";	
	}		  


	print "Deleting ldap user\n";
	my $result = $ldap->delete("uid=$mail,ou=Users,dc=domain,dc=com");
	$result->code && warn "failed to delete entry: ", $result->error ;	
	$mesg = $ldap->unbind;   # take down session
	
	return retOk('mailbox', $result->code, '1');	
}


package ObjAlias;

sub new{
    my($class)  = shift;
    my(%params) = @_;

    bless {
        "domain"    => $params{'domain'},
        "alias"      => $params{'alias'}
    }, $class;
}

#recebe uma referência para uma lista de strings. Compara com os alias existentes no objeto.
sub exists{
    my ($self, $aliasRef) = @_;
    my @alias = @$aliasRef;    
    my @objAlias = $self->{'alias'};
    foreach my $a(@alias){
        foreach my $oA (@objAlias){
            return 1 if($a eq $oA);
        } 
    }
    return 0;
}

# preenche o objeto com os alias do dominio passado por parâmetro. Substitui também o atributo domínio.
sub fill{
    my $self = shift;
    my %params = @_;
   
    my $alias = ETMS::select_alias(%params); 

    $self->{'domain'} = $params{'domain'};
    $self->{'alias'} = $alias;
}

package ObjDomain;
	
sub new{
	my($class)  = shift;
#	my(%params) = @_;
	
	bless {
		"name"    => "",
		"user"		=> "",
		"server_quota"		=> 	"0",
		"max_mailboxes"	=> "0",
		"description"	=> "",
		"isActive"		=> "1",
		"FILENAME"		=> "",
	}, $class;
}

package ObjUser;

sub new{
	my($class) = shift;
		
	bless {
		"UID"  			=> "",
		"real_name"		=> "",
		"DN"			=> "",
		"user_name"		=> "",
		"USERPASSWORD"		=> "",
		"automatic_answer" 	=> "",
		"isActive"		=> "",
		"mailbox_quota"		=> 	0,
		"delivery_type"		=> "",
		"allowExternalSend"	=> 0,
		"nr_mail_alias" 	=> [],
		"mail_alias"		=> [],
		"nr_redirect_emails" 	=> [],
		"redirect_emails"	=> [],
	}, $class;
}

package ObjQmailStatus;

sub new{
        my($class)  = shift;
#       my(%params) = @_;

        bless {
                "STATE"         => "",
                "UPTIME"        => "",
                "NRDOMAINS"     => "",
                "NRMAILBOXES"   => "",
                "INITLOG"       => "",
        }, $class;
}

1;
