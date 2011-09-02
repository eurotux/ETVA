#!/usr/bin/perl
# Copywrite Eurotux 2011
# 
# CMAR 2011/04/18 (cmar@eurotux.com)

=pod

=head1 NAME

PrimaveraDispatcher - Perl module used to interact with Primavera Software

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package PrimaveraDispatcher;

use strict;

use ETVA::Utils;
use ETVA::ArchiveTar;
use LWP::Simple;
use File::Copy;

BEGIN {
    require WinDispatcher;
    use vars qw( @ISA );
    @ISA = qw( WinDispatcher );
};

$/ = "\r\n";	# for WIN32 set end of line with \r\n

my %CONF = ( 'username'=>"adm", 'password'=>"123", 'sa_username'=>"sa", 'sa_password'=>"sa123", 'instance'=>".\\\\PRIMAVERA", 'INSTALLDIR'=>"C:\\Program Files\\primaveraagentd", 'BACKUPSDIR'=>"C:\\Program Files\\Microsoft SQL Server\\MSSQL10.PRIMAVERA\\MSSQL\\Backup");

my $TMPDIR = 'c:\Temp';

# normalize values
my %NormValues = ( 'true'=>'true', 'false'=>'false' );

=item init_conf

    initialize configuration

=cut

sub init_conf {
    my $self = shift;
    my (%p) = @_;

    %CONF = $self->SUPER::init_conf(%CONF, %p);
    $TMPDIR = $CONF{'tmpdir'} if( $CONF{'tmpdir'} );
    if( ! -e "$TMPDIR" ){
        mkdir $TMPDIR;
    }

    my %BC = $self->primavera_backupconf(%p);
    if( $BC{'DirectoriaBackup'} ){
        $CONF{'BACKUPSDIR'} = $BC{'DirectoriaBackup'};
    }

    return wantarray() ? %CONF : \%CONF;
}

sub primavera_isrunning {
    my $self = shift;
    my %Services = $self->check_services( 'sqlserver' => 'MSSQL\$', 'primavera' => 'PRIMAVERAWindowsService' );
    return ( ( $Services{'primavera'}{'CurrentState'} == WinDispatcher::SERVICE_RUNNING ) && 
		( $Services{'sqlserver'}{'CurrentState'} == WinDispatcher::SERVICE_RUNNING ) );
}

sub primavera_isstopped {
    my $self = shift;
    my %Services = $self->check_services( 'sqlserver' => 'MSSQL\$', 'primavera' => 'PRIMAVERAWindowsService' );
    return ( ( $Services{'primavera'}{'CurrentState'} == WinDispatcher::SERVICE_STOPPED ) && 
		( $Services{'sqlserver'}{'CurrentState'} == WinDispatcher::SERVICE_STOPPED ) );
}

=item primavera_info

    wrapper for all other methods with information about Primavera service: disk usage, services running, backup info, network info and software information - version, language, ...

=cut

sub primavera_info {
    my $self = shift;

    my %i = ();
    $i{'_disk_'} = $self->disk_size();
    $i{'_services_'} = $self->check_services( 'sqlserver' => 'MSSQL\$', 'primavera' => 'PRIMAVERAWindowsService' );
    $i{'_backups_'} = $self->primavera_backupinfo();
    if( $self->primavera_isrunning() ){
        $i{'_primavera_'} = $self->primavera_about();
    }
    ($i{'_network_'}) = grep { $_->{'ipaddr'} eq $CONF{'LocalIP'} } $self->get_ipconfig();

    return wantarray() ? %i : \%i;
}

=item primavera_about

    get information about Primavera software: version, language, ...

=cut

sub primavera_about {
    my $self = shift;
    my (%p) = @_;

    my %st = ();

    my $u = $p{'username'} || $CONF{'username'};
    my $p = $p{'password'} || $CONF{'password'};

    open(P,"primaveraconsole $u $p |");
    while(<P>){

        chomp;
	my ($k,$v) = split(/:/,$_,2);
	my $tv = trim($v);	# trim value
	if( $st{"$k"} ){
	    my @o = (ref($st{"$k"}) eq 'ARRAY')? @{$st{"$k"}} : ($st{"$k"});
	    $st{"$k"} = [ @o, $tv ];
	} else {
	    $st{"$k"} = $tv;
	}
    }
    close(P);
    return wantarray() ? %st : \%st;
}

=item primavera_backup

    do specific database backup from Primavera

=cut

sub primavera_backup {
    my $self = shift;
    my (%p) = @_;

    my $u = $p{'username'} || $CONF{'username'};
    my $p = $p{'password'} || $CONF{'password'};

    my $d = $p{'database'}; # just for testing

    if( !$d ){
        return retErr("_ERR_BACKUP_","Error backup: no database to make backup.");
    }
    my ($e,$m) = cmd_exec("primaveraconsole pricopiaseg $u $p $d");

    unless( $e == 0 ){
        return retErr("_ERR_BACKUP_","Error backup: $m");
    }
    my $B = {};
    ($B->{'file'}) = ( $m =~ m/Backup file: (.+)$/ );

    return retOk("_BACKUP_OK_","Backup success.","_RET_OBJ_",$B);
}

=item primavera_restore

    do specific database restore from Primavera

=cut

sub primavera_restore {
    my $self = shift;
    my (%p) = @_;

    my $u = $p{'username'} || $CONF{'username'};
    my $p = $p{'password'} || $CONF{'password'};

    my $d = $p{'database'}; # just for testing
    my $f = $p{'file'};

    if( !$d ){
        return retErr("_ERR_RESTORE_","Error restore: no database to restore.");
    }
    if( !$f ){
        return retErr("_ERR_RESTORE_","Error restore: no file to restore.");
    }

    my ($e,$m) = cmd_exec("primaveraconsole prirepocopiaseg $u $p $d $f");

    unless( $e == 0 ){
        return retErr("_ERR_RESTORE_","Error restore: $m");
    }

    return retOk("_RESTORE_OK_","Restore success.");
}

=item primavera_masterrestore

    do restore from SQLServer

=cut

sub primavera_masterrestore {
    my $self = shift;
    my (%p) = @_;

    my $u = $p{'username'} || $CONF{'sa_username'};
    my $p = $p{'password'} || $CONF{'sa_password'};
    my $i = $p{'instance'} || $CONF{'instance'};

    my $d = $p{'database'}; # just for testing
    my $f = $p{'file'};

    if( !$d ){
        return retErr("_ERR_MASTERRESTORE_","Error restore: no database to restore.");
    }
    if( !$f ){
        return retErr("_ERR_MASTERRESTORE_","Error restore: no file to restore.");
    }

    my ($e,$m) = cmd_exec("primaveraconsole restore $u $p $i $d $f");

    unless( $e == 0 ){
        return retErr("_ERR_MASTERRESTORE_","Error restore: $m");
    }

    return retOk("_MASTERRESTORE_OK_","Restore success.");
}

=item primavera_listbackups

    get list of backup files

=cut

sub primavera_listbackups {
    my $self = shift;
    my (%p) = @_;

    my $u = $p{'username'} || $CONF{'username'};
    my $p = $p{'password'} || $CONF{'password'};

    my @l = ();
    open(P,"primaveraconsole prilistabkps $u $p |");
    while(<P>){
        chomp;
        my @f = split(/;/,$_);
	if( @f > 2 ){
            push(@l, { 'name'=>$f[0], 'creationtime'=>$f[1], 'lastwritetime'=>$f[2], 'length'=>$f[3], 'fullpath'=>$f[4] });
	}
    }
    close(P);

    return wantarray() ? @l : \@l;
}

=item primavera_lastbackups

    get list of last backup files for each database of Primavera

=cut

sub primavera_lastbackups {
    my $self = shift;
    my (%p) = @_;

    my @l = ();
    my @ld = $self->primavera_listdatabases(%p);
    my @lb = $self->primavera_listbackups(%p);
    for my $D (@ld){
        if( $D->{'name'} =~ m/PRI/ ){
	    my $nb = $D->{'name'};
            my ($B) = grep { $_->{'name'} =~ m/_${nb}_/ } @lb;
	    if( $B ){
	        push(@l, $B );
	    }
	}
    }
    
    return wantarray() ? @l : \@l;
}

=item primavera_listbackupplans

    list of schedules backup plans

=cut

sub primavera_listbackupplans {
    my $self = shift;
    my (%p) = @_;

    my $u = $p{'username'} || $CONF{'username'};
    my $p = $p{'password'} || $CONF{'password'};

    my $P = {};
    my $S = {};
    my $C = {};
    my @l = ();
    my $msg = "";
    open(P,"primaveraconsole prilistaplanoscopiaseguranca $u $p |");
    while(<P>){
        $msg .= $_;
        chomp;

	if( /PlanoCopiasSeg_id: (.+)$/ ){
            my ($id) = ($1);
            if( %$P ){
                push(@l, $P);
                $P = {};
            }
            $P->{'id'} = $id;
        } elsif( %$P ){
	    if( /schedule id: (.+)$/ ){
                my ($id) = ($1);
                $S = { 'id'=>$id };
	        push(@{$P->{'schedule'}},$S);
	    } elsif( /schedule_/ ){
                my ($k,$v) = map { trim($_) } split(/:/,$_,2);
	        $k =~ s/schedule_//;
	        $S->{"$k"} = $v;
	    } elsif( /company_key: (.+)$/ ){
                my ($key) = ($1);
                $C = { 'key'=>$key };
	        push(@{$P->{'companies'}},$C);
	    } elsif( /company_/ ){
                my ($k,$v) = map { trim($_) } split(/:/,$_,2);
                $k =~ s/company_//;
	        $C->{"$k"} = $v;
	    } else {
                my ($k,$v) = map { trim($_) } split(/:/,$_,2);
	        $P->{"$k"} = $v;
	    }
	}
    }
    close(P);

    push(@l, $P ) if( %$P );

    if(!@l){
        if( $msg =~ m/Exception/gs ){
            return retErr("_ERR_PRI_LISTBACKUPPLANS_","Error: $msg");
        }
    }

    return wantarray() ? @l : \@l;
}

=item primavera_insertbackupplan

    create backup plan

=cut

sub primavera_insertbackupplan {
    my $self = shift;
    my (%p) = @_;

    my $u = $p{'username'} || $CONF{'username'};
    my $p = $p{'password'} || $CONF{'password'};

    my $name = $p{'name'};

    if( !$name ){
        return retErr("_ERR_PRI_INSERTBACKUPPLAN_","Error insert backup plan: need a name!");
    }

    my $companiesByComma = $p{'companiesByComma'};
    if( ref($p{'companies'}) ){
        my $lcompanies = $p{'companies'};
	for my $C (@$lcompanies){
            $companiesByComma .= ";" if( $companiesByComma );
            $companiesByComma .= "$C->{'key'},$C->{'name'}";
    	}
    }

    if( !$companiesByComma ){
        return retErr("_ERR_PRI_INSERTBACKUPPLAN_","Error insert backup plan: need a specify companies!");
    }

    my $verify = ( $p{'verify'} && ($p{'verify'} ne 'false') ) ? "true" : "false";
    my $incremental = ( $p{'incremental'} && ($p{'incremental'} ne 'false') ) ? "true" : "false";
    my $overwrite = ( $p{'overwrite'} && ($p{'overwrite'} ne 'false') ) ? "true" : "false";
    my $periodo = $p{'periodo'} || "diario";

    my ($e,$m) = cmd_exec("primaveraconsole priinsereplanocopiaseguranca $u $p \"$name\" \"$verify\" \"$incremental\" \"$overwrite\" \"$companiesByComma\" \"$periodo\"");

    unless( $e == 0 ){
        return retErr("_ERR_PRI_INSERTBACKUPPLAN_","Error insert backup plan: $m");
    }

    return retOk("_PRI_INSERTBACKUPPLAN_OK_","Insert backup plan ok.");
}

=item primavera_removebackupplan

    remove backup plan

=cut

sub primavera_removebackupplan {
    my $self = shift;
    my (%p) = @_;

    my $u = $p{'username'} || $CONF{'username'};
    my $p = $p{'password'} || $CONF{'password'};

    my $id = $p{'id'};

    if( !$id ){
        return retErr("_ERR_PRI_REMOVEBACKUPPLAN_","Error remove backup plan: need a id!");
    }

    my ($e,$m) = cmd_exec("primaveraconsole priremoveplanocopiaseguranca $u $p \"$id\"");

    unless( $e == 0 ){
        return retErr("_ERR_PRI_REMOVEBACKUPPLAN_","Error insert backup plan: $m");
    }

    return retOk("_PRI_REMOVEBACKUPPLAN_OK_","Backup plan removed.");
}

=item primavera_listusers

    get list of users of Primavera

=cut

sub primavera_listusers {
    my $self = shift;
    my (%p) = @_;

    my $u = $p{'username'} || $CONF{'username'};
    my $p = $p{'password'} || $CONF{'password'};

    my $U = {};
    my @l = ();
    open(P,"primaveraconsole prilistautilizadores $u $p |");
    while(<P>){
        chomp;
	if( /Utilizador: (.+)$/ ){
            my ($cod) = ($1);
            if( %$U ){
                push(@l, $U );
                $U = {};
            }
	    $U->{'cod'} = $cod;
        } else {
            my ($k,$v) = map { trim($_) } split(/:/,$_,2);
            $U->{"$k"} = $NormValues{lc($v)} || $v;
	}
    }
    close(P);

    push(@l, $U ) if( %$U );

    return wantarray() ? @l : \@l;
}

=item primavera_viewuser

    get information of one user

=cut

sub primavera_viewuser {
    my $self = shift;
    my (%p) = @_;

    my ($U) = grep { $_->{'cod'} eq $p{'cod'} } $self->primavera_listusers();
    if( $U ){
        return wantarray() ? %$U : $U;
    }
    return retErr("_ERR_PRI_VIEWUSER_","User '$p{'cod'}' not found!");
}

=item primavera_listempresas

    get list of empresas of Primavera

=cut

sub primavera_listempresas {
    my $self = shift;
    my (%p) = @_;

    my $u = $p{'username'} || $CONF{'username'};
    my $p = $p{'password'} || $CONF{'password'};

    my @l = ();
    open(P,"primaveraconsole prilistaempresas $u $p |");
    while(<P>){
        chomp;
	if( /name: (.+) description: (.+)$/ ){
	    push(@l, { 'name'=>$1, 'description'=>$2 });
	}
    }
    close(P);

    return wantarray() ? @l : \@l;
}

=item primavera_listdatabases

    get list of databases of Primavera

=cut

sub primavera_listdatabases {
    my $self = shift;
    my (%p) = @_;

    my $u = $p{'username'} || $CONF{'username'};
    my $p = $p{'password'} || $CONF{'password'};

    my @l = ();
    open(P,"primaveraconsole prilistabasesdados $u $p |");
    while(<P>){
        chomp;
	if( /name: (.+)$/ ){
	    push(@l, { 'name'=>$1 });
	}
    }
    close(P);

    return wantarray() ? @l : \@l;
}

=item primavera_insertuser

    create user on Primavera

=cut

sub primavera_insertuser {
    my $self = shift;
    my (%p) = @_;

    my $u = $p{'username'} || $CONF{'username'};
    my $p = $p{'password'} || $CONF{'password'};

    my $u_cod = $p{'u_cod'};
    my $u_name = $p{'u_name'};
    my $u_email = $p{'u_email'};
    my $u_password = $p{'u_password'};
    my $u_admin = $p{'u_admin'} ? 1 : 0;
    my $u_suadmin = $p{'u_suadmin'} ? 1: 0;
    my $u_tecnico = $p{'u_tecnico'} ? 1: 0;

    my ($e,$m) = cmd_exec("primaveraconsole priinsereutilizador $u $p \"$u_cod\" \"$u_name\" \"$u_email\" \"$u_password\" \"$u_admin\" \"$u_suadmin\" \"$u_tecnico\"");

    unless( $e == 0 ){
        if( $m =~ m/Cannot insert duplicate key/gs ){
		return retErr("_ERR_PRI_INSERTUSER_","User already exists.");
	}
        return retErr("_ERR_PRI_INSERTUSER_","Error insert user: $m");
    }

    return retOk("_PRI_INSERTUSER_OK_","Insert user ok.");
}

=item primavera_updateuser

    Change information about one user of Primevera

=cut

sub primavera_updateuser {
    my $self = shift;
    my (%p) = @_;

    my $u = $p{'username'} || $CONF{'username'};
    my $p = $p{'password'} || $CONF{'password'};

    my $u_cod = $p{'u_cod'};
    my $u_name = $p{'u_name'};
    my $u_email = $p{'u_email'};
    my $u_password = $p{'u_password'};
    my $u_admin = $p{'u_admin'} ? 1 : 0;
    my $u_suadmin = $p{'u_suadmin'} ? 1: 0;
    my $u_tecnico = $p{'u_tecnico'} ? 1: 0;

    my ($e,$m) = cmd_exec("primaveraconsole priactualizautilizador $u $p \"$u_cod\" \"$u_name\" \"$u_email\" \"$u_password\" \"$u_admin\" \"$u_suadmin\" \"$u_tecnico\"");

    unless( $e == 0 ){
        return retErr("_ERR_PRI_UPDATEUSER_","Error update user: $m");
    }

    return retOk("_PRI_UPDATEUSER_OK_","Update user ok.");
}

=item primavera_deleteuser

    Delete user from Primavera

=cut

sub primavera_deleteuser {
    my $self = shift;
    my (%p) = @_;

    my $u = $p{'username'} || $CONF{'username'};
    my $p = $p{'password'} || $CONF{'password'};

    my $u_cod = $p{'u_cod'};

    my ($e,$m) = cmd_exec("primaveraconsole priremoveutilizador $u $p \"$u_cod\" ");

    unless( $e == 0 ){
        return retErr("_ERR_PRI_DELETEUSER_","Error delete user: $m");
    }

    return retOk("_PRI_DELETEUSER_OK_","Delete user ok.");
}

=item primavera_backupinfo

    get information about backups of primavera: databases, backups and empresas

=cut

sub primavera_backupinfo {
    my $self = shift;
    my (%p) = @_;

    my @l = ();

    my @ld = $self->primavera_listdatabases(%p);
    my @lb = $self->primavera_listbackups(%p);
    my @le = $self->primavera_listempresas(%p);
    for my $E (@le){
        my $n = $E->{'name'};
        my ($D) = grep { $_->{'name'} eq "PRI$n" } @ld;
        if( $D ){
            my $nb = $D->{'name'};
            my @bkps = grep { $_->{'name'} =~ m/_${nb}_/ } @lb;
            push(@l, { %$E, 'DATABASE'=>{%$D}, 'BACKUPS'=>[@bkps] });
	    }
    }

    return wantarray() ? @l : \@l;
}

=item primavera_backupconf

    get configuration about backups: backup dir

=cut

sub primavera_backupconf {
    my $self = shift;
    my (%p) = @_;

    my %st = ();

    my $u = $p{'username'} || $CONF{'username'};
    my $p = $p{'password'} || $CONF{'password'};

    open(P,"primaveraconsole priconfigbackups $u $p |");
    while(<P>){

        chomp;
	my ($k,$v) = split(/:/,$_,2);
	my $tv = trim($v);	# trim value
	if( $st{"$k"} ){
	    my @o = (ref($st{"$k"}) eq 'ARRAY')? @{$st{"$k"}} : ($st{"$k"});
	    $st{"$k"} = [ @o, $tv ];
	} else {
	    $st{"$k"} = $tv;
	}
    }
    close(P);
    return wantarray() ? %st : \%st;
}

=item primavera_fullbackup

    do full backup of all databases of Primavera

=cut

sub primavera_fullbackup {
    my $self = shift;
    my (%p) = @_;

    my @l = ();
    my @ld = $self->primavera_listdatabases(%p);
    for my $D (@ld){
        if( $D->{'name'} =~ m/PRI/ ){
            my $E = $self->primavera_backup(%p, 'database'=>$D->{'name'} );
	    unless( !isError($E) ){
                return wantarray() ? %$E : $E;
	    }
	    push(@l, { 'DATABASE'=>$D, 'BACKUP'=>$E->{'_obj_'} } );
	}
    }
    my $B = { 'backups'=>\@l };
    
    return retOk("_FULLBACKUP_OK_","Full Backup success.","_RET_OBJ_",$B);
}

=item primavera_fullrestore

    do full restore of all databases of Primavera

=cut

sub primavera_fullrestore {
    my $self = shift;
    my (%p) = @_;

    my @l = ();
    my @ld = $self->primavera_listdatabases(%p);
    my @lb = $self->primavera_listbackups(%p);
    for my $D (@ld){
        if( $D->{'name'} =~ m/PRI/ ){
	    my $nb = $D->{'name'};
            my ($B) = grep { $_->{'name'} =~ m/_${nb}_/ } @lb;
	    if( $B ){
                my $E = $self->primavera_masterrestore(%p, 'database'=>$D->{'name'}, 'file'=>$B->{'name'} );
	        unless( !isError($E) ){
                    return wantarray() ? %$E : $E;
	        }
	        push(@l, { 'DATABASE'=>$D, 'BACKUP'=>$B } );
            } else {
                return retErr("_ERR_FULLRESTORE_","Error restore database '$D->{'name'}: No backup found.");
	    }
	}
    }
    my $B = { 'backups'=>\@l };
    
    return retOk("_FULLRESTORE_OK_","Full RESTORE success.","_RET_OBJ_",$B);
}

=item primavera_stop

    stop Primavera service

=cut

sub primavera_stop {
    my $self = shift;

    my %Services = $self->check_services( 'sqlserver' => 'MSSQL\$', 'primavera' => 'PRIMAVERAWindowsService' );

    if( $Services{'primavera'}{'CurrentState'} == WinDispatcher::SERVICE_RUNNING ){
        $self->stop_service( 'servicename'=>$Services{'primavera'}{'name'} );
    }
    if( $Services{'sqlserver'}{'CurrentState'} == WinDispatcher::SERVICE_RUNNING ){
        $self->stop_service( 'servicename'=>$Services{'sqlserver'}{'name'} );
    }

    return retOk("_STOP_OK_","Primavera stopped.");
}

=item primavera_start

    start Primavera service

=cut

sub primavera_start {
    my $self = shift;

    my %Services = $self->check_services( 'sqlserver' => 'MSSQL\$', 'primavera' => 'PRIMAVERAWindowsService' );

    if( $Services{'sqlserver'}{'CurrentState'} == WinDispatcher::SERVICE_STOPPED ){
        $self->start_service( 'servicename'=>$Services{'sqlserver'}{'name'} );
    }
    if( $Services{'primavera'}{'CurrentState'} == WinDispatcher::SERVICE_STOPPED ){
        $self->start_service( 'servicename'=>$Services{'primavera'}{'name'} );
    }

    return retOk("_START_OK_","Primavera started.");
}

=item get_backupconf

    method for get backup for appliance

=cut

sub get_backupconf {
    my $self = shift;
    my (%p) = @_;

    my $sock = $p{'_socket'};

    $sock->blocking(1);

    if( $p{'_make_response'} ){
        print $sock $p{'_make_response'}->("", '-type'=>'application/x-tar');
    }

    my $tar = new ETVA::ArchiveTar( 'handle'=>$sock );

    my $c_path = $CONF{'CFG_FILE'};
    $tar->add_file( 'name'=>"$c_path", 'path'=>"$c_path" );

    my @lb = $self->primavera_lastbackups(%p);
    for my $B (@lb){
        my $fp = $B->{'fullpath'};
        $tar->add_file( 'name'=>"$B->{'name'}", 'path'=>"$fp" );
    }

    $tar->write();

    return;
}

=item set_backupconf

    method for recover appliance

=cut

sub set_backupconf {
    my $self = shift;
    my (%p) = @_;

    my $tmpbf = tmpfile("${TMPDIR}\\primaveraagentd-setbkpconf-tmpfile");
    if( $p{'_url'} ){
	my $rc = LWP::Simple::getstore("$p{'_url'}","$tmpbf");
	if( is_error($rc) || ! -e "$tmpbf" ){
            return retErr('_ERR_SET_BACKUPCONF_',"Error get backup file ($tmpbf status=$rc)");
	}
    } else {
        my $sock = $p{'_socket'};

        $sock->blocking(1);

	my $buf;
	open(F,">$tmpbf");
	while(read($sock,$buf,60*57)){
            print F $buf;
	}
	close(F);
    }

    my ($INSTALLDIR,$BACKUPSDIR) = ($CONF{'INSTALLDIR'},$CONF{'BACKUPSDIR'});

    my $tmpdir = tmpdir("$TMPDIR\\primaveraagentd-setbkpconf-tmpdir");
    my $tmpfn = $tmpbf;
    $tmpfn =~ s/^.+(primaveraagentd-setbkpconf-tmpfile\.\w+)$/$1/g;

    my $cmdpipe = 'cd '.$tmpdir.'; tar xvf ../'.$tmpfn.' |';
    $cmdpipe =~ s/\\/\\\\/g;
    plog "cmdpipe = $cmdpipe ";
    open(P,$cmdpipe);
    my $bkps = $/;
    $/ = "\n";
    while(<P>){
	chomp;
	my $fp = $tmpdir . "\\" . $_;
	my $destdir = ( /\.ini/ ) ? $INSTALLDIR : $BACKUPSDIR;

	# using cygwin path
	$fp =~ s/(\w):\\/\/cygdrive\/$1\//;
	$fp =~ s/\\/\//g;

	$destdir =~ s/(\w):\\/\/cygdrive\/$1\//;
	$destdir =~ s/\\/\//g;

        plog "move($fp,$destdir)";
        my $mvok = move($fp,$destdir);
	if( !$mvok ){
            return retErr("_ERR_SET_BKPCONF_","Error Set backup conf: error moving file '$fp' - $!");
	}
    }
    $/ = $bkps;
    close(P);
    rmdir $tmpdir;
    unlink $tmpbf;

    # full restore
    $self->primavera_fullrestore(%p);

    return retOk("_SET_BKPCONF_OK_","Set Backup conf success.");
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

=cut
