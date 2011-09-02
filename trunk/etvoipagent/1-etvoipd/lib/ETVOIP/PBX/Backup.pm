#!/usr/bin/perl

=pod

=head1 NAME

ETVOIP::PBX::Backup

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=cut

package ETVOIP::PBX::Backup;

use ETVA::ArchiveTar;
use strict;
use Data::Dumper;
use ETVA::Utils;

BEGIN {
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF $AUTOLOAD);
    @ISA = ('ETVOIP::PBX');
};

use constant MODULE_PRIORITY => 8;

sub new{
    my $class = shift;
    my $self = {@_};
    bless $self, $class;
    return $self;
}

#get files from backup dir
sub backup_list_files {
    my ($self, $dir) = @_;
    my @files;
           
    opendir(my $DH, $dir) or die "Error opening $dir: $!";
    while (defined (my $file = readdir($DH))) {
        my $path = $dir . '/' . $file;

        next unless (-f $path);           # ignore non-files - automatically does . and ..
        push(@files, [ stat(_), $path ]); # re-uses the stat results from '-f'
    }
    closedir($DH);
    return @files;
}

#get path where to save restore archive
sub get_etvoip_archive {
    my $self = shift;
    my $dir = $self->{'amp_conf'}{'ASTVARLIBDIR'}."/backups/etvoip";

    return $dir.'/etvoip.tar.gz';
}

#get last backup file
sub backup_get_etvoip {
    my $self = shift;
    my $dir = $self->{'amp_conf'}{'ASTVARLIBDIR'}."/backups/etvoip";
    my @all_files = $self->backup_list_files($dir); # get backup dir file list
        
    my @sorted_files = sort { $b->[9] <=> $a->[9] } @all_files; # sort files by modified time
    
    if(@sorted_files){
        return $sorted_files[0][13];
    }
    return;

}

#create backup
sub backup_etvoip {
    my $self = shift;
    my $script = $self->{'amp_conf'}{'ASTVARLIBDIR'}.'/bin/ampbackup.php';
    my $command = "0 0 0 0 0 ".$script;
    
    my $sql = "SELECT * FROM backup WHERE command = \"$command\" AND method='now' AND name='etvoip'";
    my ($sth, $result) = DB::db_sql($sql);
    my $res = $sth->fetchrow_hashref;
    my $latest;

    # not found insert it
    if (!$res)
    {
        $sql = "INSERT INTO backup (admin, command, configurations, emailmaxsize, emailmaxtype, method, name) VALUES (\"yes\", \"$command\", \"yes\", \"25\", \"MB\", \"now\",\"etvoip\")";
        DB::db_sql($sql);

        ($sth, $result) = DB::db_sql('select last_insert_id() AS lid');
        $res = $sth->fetchrow_hashref;
        $latest = $res->{'lid'};

    }
    else
    {
        $latest = $res->{'id'};
    }
   
    my $backup_script = $script.' '.$latest;    
    my ($e,$output) = cmd_exec($backup_script);

    if($e){
        return retErr('_ERR_BACKUP_SAVE_',"Backup failed because ncountered an error: $output");
    }else{
        return retOk("_OK_BACKUP_SAVE_","Backup save successfully.");
    }    
}


# restore
sub restore_etvoip {
    my ($self, $file) = @_;    

    my $b_path = $self->{'amp_conf'}{'ASTVARLIBDIR'}."/backups/etvoip/".$file;
    my $fileholder;

    my $next = ETVA::ArchiveTar->iter( $b_path, 1); # iterate through files in archive to check timestamp

    while( my $f = $next->() ) {
        my $fname = $f->name;
        
        if($fname =~ m/\/tmp\/ampbackups\.([0-9.]+)\/(.+)/)
        {            
            $fileholder = $1;                     
        }
    }

    my $tar = '/bin/tar';
    my $dir = $b_path;    
    
    cmd_exec("/bin/rm -rf /tmp/ampbackups.$fileholder 2>&1");
    my $tar_cmd = "$tar -PxvOz -f \"$dir\" /tmp/ampbackups.$fileholder/configurations.tar.gz | $tar -Pxvz";
    plog($tar_cmd);    
    cmd_exec($tar_cmd);
    
    $tar_cmd = "$tar -Pxvz -f \"$dir\" /tmp/ampbackups.$fileholder/asterisk.sql /tmp/ampbackups.$fileholder/astdb.dump";
    plog($tar_cmd);
    cmd_exec($tar_cmd);
			
    my $sql_cmd = "mysql -u ".$self->{'amp_conf'}{'AMPDBUSER'}." -p".$self->{'amp_conf'}{'AMPDBPASS'}." < /tmp/ampbackups.$fileholder/asterisk.sql";
    plog($sql_cmd);    
    cmd_exec($sql_cmd);
    cmd_exec($self->{'amp_conf'}{'AMPBIN'}."/restoreastdb.php $fileholder 2>&1");			    

    cmd_exec("/bin/rm -rf /tmp/ampbackups.$fileholder 2>&1");


    return retOk("_OK_BACKUP_RESTORE_","Backup restored successfully.");

}
1;