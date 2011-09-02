#!/usr/bin/perl

=pod

=head1 NAME

ETVOIP::PBX

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package ETVOIP::PBX;

use strict;
use version;

use PHP::Serialization qw(serialize unserialize);
use ETVA::Utils;
use DB;
use Asterisk::AMI::Common;

use Data::Dumper;
use Time::Local;
use File::Basename;

BEGIN {
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF $AUTOLOAD);
};

# stores freePBX configuration data from /etc/amportal.conf
my %amp_conf = ();
my %default_amp_conf = ('FOPRUN'=> 1,'FOPDISABLE'=> 0);
my $astman; #asterisk manager
my $active_modules; # populated by modulelist
my $order_modules; # populated by modulelist


use constant MODULE_STATUS_ENABLED => 2;


=item init_db_params

    initializa DB and establish connection

=cut
sub init_db_params {
    my $self = shift;
    my %amp_conf = @_;

    my %db_conf = ( 'db_engine'=>$amp_conf{"AMPDBENGINE"},
                    'db_user'=>$amp_conf{"AMPDBUSER"},
                    'db_pass'=>$amp_conf{"AMPDBPASS"},
                    'db_host'=>$amp_conf{"AMPDBHOST"},
                    'db_name'=>$amp_conf{"AMPDBNAME"}
                    );

    if (exists($amp_conf{"AMPDBFILE"})) {
        $db_conf{'db_file'} = $amp_conf{"AMPDBFILE"};        
    }
    
    DB::db_init(%db_conf);    

}

sub active_modules {
    return wantarray() ? %$active_modules : $active_modules;
}

=item load_settings

    populate %amp_conf
    read /etc/amportal.conf and asterisk configuration (parse_asterisk_conf)

=cut
sub load_settings {
    my $self = shift;    

    # if already initialized return.
    if(%amp_conf) {return 1;}    

    %amp_conf = %default_amp_conf;    
    %amp_conf = ETVA::Utils::loadconfigfile("/etc/amportal.conf",\%amp_conf);

    my %asterisk_conf  = $self->parse_asterisk_conf("$amp_conf{'ASTETCDIR'}/asterisk.conf");

    #if asterisk.conf overrides amportal.conf set new values
    for my $key ( keys %asterisk_conf ) {        
        if (exists $amp_conf{uc($key)}) {                        
            $amp_conf{uc($key)} = $asterisk_conf{$key};
        }
    }

    #initialize databse params
    $self->init_db_params(%amp_conf);    
    
}

=item parse_asterisk_conf

    read directories section of asterisk.conf file

=cut
sub parse_asterisk_conf {
    my $self = shift;
    my ($filename) = @_;            
    my %asterisk_conf = ();

    #parse file
    open(ASTERISKCONF, $filename);
    while(<ASTERISKCONF>){
        if($_ =~ m/^\s*([a-zA-Z0-9]+)\s* => \s*(.*)\s*([;#].*)?/){
            $asterisk_conf{$1} = $2;
        }
    }
    close(ASTERISKCONF);    
    return wantarray() ? %asterisk_conf : \%asterisk_conf;        
}

=item

    Write the voicemail.conf file
    This is called by saveVoicemail()
    It's important to make a copy of $vmconf before passing it. Since this is a recursive function, has to
    pass by reference. At the same time, it removes entries as it writes them to the file, so if you don't have
    a copy, by the time it's done $vmconf will be empty.

=cut
sub write_voicemail_conf {
    my $self = shift;
    my ($filename, $voicemail_conf, $section ,$iteration) = @_;
    $iteration ||= 0;
    my @options;
    my @existing_sections;
    my @output;
    my $comment;

    if($iteration == 0){ $section = ''; }

    # if the file does not, copy if from the template.	
	if(! -e $filename) {        
		if (!copy( $amp_conf{"ASTETCDIR"}."/voicemail.conf.template", $filename )){
			return 0;
		}
	}

    my $fh;
    open($fh, $filename);
    while(<$fh>){

        if($_ =~ m/^(\s*)(\d+)(\s*)=>(\s*)(\d*),(.*),(.*),(.*),(.*)(\s*[;#].*)?$/){        
            # "mailbox=>password,name,email,pager,options"
            # this is a voicemail line
            
            # make sure we have something as a comment
            $comment = $10;
            if(!$comment) { $comment = '';}

            # $1 $3 and $4 are to preserve indents/whitespace, we add these back in
            
            if($voicemail_conf->{$section}{$2}){

                # we have this one loaded
                # repopulate from our version
                my %temp = %{$voicemail_conf->{$section}{$2}};                

                my @options = ();
                foreach my $item (keys %{$temp{"options"}} ) {
                    push(@options,$item.'='.$temp{"options"}{$item});
                }
                
                push(@output, $1.$temp{"mailbox"}.$3."=>".$4.$temp{"pwd"}.",".$temp{"name"}.",".$temp{"email"}.",".$temp{"pager"}.",". join("|",@options).$comment);

                # remove this one from $vmconf
                delete($voicemail_conf->{$section}{$2});

            }else{

                # we don't know about this mailbox, so it must be deleted
                # (and hopefully not JUST added since we did read_voiceamilconf)

                # do nothing
                
            }            
        }
        elsif($_ =~ m/^(\s*)#include(\s+)["\']{0,1}([^"\']*)["\']{0,1}(\s*[;#].*)?$/){
            
            # include another file
            # make sure we have something as a comment
            $comment = $4;
            if(!$comment) {$comment = '';}

            my $include_filename;
            if (substr($3,0,1) eq "/") {
                # absolute path
                $include_filename = trim($3);
            } else {
                # relative path
                $include_filename =  dirname($filename)."/".trim($3);
            }

            push(@output, trim($_));            
            
            $self->write_voicemail_conf($include_filename, $voicemail_conf, $section, $iteration+1);
                        
        }
        elsif($_ =~ m/^(\s*)\[(.+)\](\s*[;#].*)?$/){
            # section name
            $comment = $3;
            # make sure we have something as a comment
            if(!$comment) {$comment = '';}

            # check if this is the first run (section is empty)
            if ($section) {

                # we need to add any new entries here, before the section changes
				
                if($voicemail_conf->{$section}){ #need this, or we get an error if we unset the last items in this section - should probably automatically remove the section/context from voicemail.conf
                
                    foreach my $item (keys %{$voicemail_conf->{$section}}) {
                        if ( ref($voicemail_conf->{$section}{$item}) eq 'HASH') {
                            # mailbox line

                            my %temp = %{$voicemail_conf->{$section}{$item}};

                            @options = ();
                            foreach my $item (keys %{$temp{"options"}}) {
                                push(@options,$item.'='.$temp{"options"}{$item});
                            }
                            my $mb_string = $temp{"mailbox"}." => ".$temp{"pwd"}.",".$temp{"name"}.",".$temp{"email"}.",".$temp{"pager"}.",". join("|",@options);
                            push(@output, $mb_string);

                            # remove this one from $vmconf
                            delete($voicemail_conf->{$section}{$item});

                        } else {
                            # option line

                            push(@output, $item."=".$voicemail_conf->{$section}{$item});

                            # remove this one from $vmconf
                            delete($voicemail_conf->{$section}{$item});
                        }
                    }
                }
                
            }

            $section = lc($2);
            push(@output, $1."[".$section."]".$comment);
			push(@existing_sections, $section); #remember that this section exists            

        }
        elsif($_ =~ m/^(\s*)([a-zA-Z0-9-_]+)(\s*)=(\s*)(.*?)(\s*[;#].*)?$/){
            # name = value
            # option line

            # make sure we have something as a comment
            $comment = $6;
            if(!$comment) {$comment = '';}
            
            if($voicemail_conf->{$section}{$2}){

                push(@output, $1.$2.$3."=".$4.$voicemail_conf->{$section}{$2}.$comment);
                
                # remove this one from $vmconf
                delete($voicemail_conf->{$section}{$2});                
            }            
        }
        else{

            # unknown other line -- probably a comment or whitespace
            push(@output, s/\n\r//);
        }
    }

    if ($iteration == 0 ) {        
        
        # we need to add any new entries here, since it's the end of the file
        foreach my $section (keys %$voicemail_conf){
            

            if(!(grep $_ eq $section, @existing_sections)) { # If this is a new section, write the context label
                push(@output, "[".$section."]");                
            }            

            foreach my $item (keys %{$voicemail_conf->{$section}}){

                if ( ref($voicemail_conf->{$section}{$item}) eq 'HASH') {                    

                    # mailbox line
                    my %temp = %{$voicemail_conf->{$section}{$item}};
                    

                    @options = ();
                    foreach my $item (keys %{$temp{"options"}}) {
                        push(@options,$item.'='.$temp{"options"}{$item});
                    }

                    push(@output, $temp{"mailbox"}." => ".$temp{"pwd"}.",".$temp{"name"}.",".$temp{"email"}.",".$temp{"pager"}.",". join("|",@options));                    

                    # remove this one from $vmconf
                    delete($voicemail_conf->{$section}{$item});
                }
                else{
                    # option line
                    push(@output, $item."=".$voicemail_conf->{$section}{$item});

                    # remove this one from $vmconf
                    delete($voicemail_conf->{$section}{$item});
                    
                }
            }
        }
    }
    close($fh);

    # write this file back out        

    open($fh,">",$filename);
    print $fh join("\n",@output)."\n";
    close($fh);       
}



=item parse_voicemail_conf
    Recursively read voicemail.conf (and any included files)    
=cut
sub parse_voicemail_conf {
    my $self = shift;    
    my ($filename, $voicemail_conf, $section) = @_;        
    $section ||= "general";    

    my $fh;
    open($fh, $filename);
    while(<$fh>){
        
        if($_ =~ m/^\s*(\d+)\s*=>\s*(\d*),(.*),(.*),(.*),(.*)\s*([;#].*)?/){
            
            # "mailbox=>password,name,email,pager,options"
            # this is a voicemail line            
            $voicemail_conf->{$section}{$1} = {"mailbox"=>$1,
                                               "pwd"=>$2,
                                               "name"=>$3,
                                               "email"=>$4,
                                               "pager"=>$5,
                                               "options"=> {map { split(/\=/,$_) } split(/\|/,$6)} };
                                   
        }
        elsif($_ =~ m/^(?:\s*)#include(?:\s+)["\']{0,1}([^"\']*)["\']{0,1}(\s*[;#].*)?$/){
            #include another file
            
            if (substr($1,0,1) eq "/") {
                # absolute path
                $filename = $1;
            }
            else{
                # relative path
                $filename = dirname($filename)."/".$1;
            }            
          
            $self->parse_voicemail_conf($filename, $voicemail_conf, $section);
                                               
        }
        elsif($_ =~ m/^\s*\[(.+)\]/) {
            # section name            
            $section = lc($1);
                    
        }elsif ($_ =~ m/^\s*([a-zA-Z0-9-_]+)\s*=\s*(.*?)\s*([;#].*)?$/) {         
            # name = value
            # option line            
            $voicemail_conf->{$section}{$1} = $2;
        }


    }    
    close($fh);    
}

sub astman_conn {
    my $self = shift;

    # if already initialized return.
    if($astman && $astman->connected()) {return 1;}

    # attempt to connect to asterisk manager proxy
    if (defined $amp_conf{"ASTMANAGERPROXYPORT"} ) {

        $astman = Asterisk::AMI::Common->new(
                                        PeerAddr => $amp_conf{"ASTMANAGERHOST"},
                                        PeerPort => $amp_conf{"ASTMANAGERPROXYPORT"},
                                        Username => $amp_conf{"AMPMGRUSER"},
                                        Secret   => $amp_conf{"AMPMGRPASS"}
                                                );

    }else {

        $astman = Asterisk::AMI::Common->new(
                                        PeerAddr => $amp_conf{"ASTMANAGERHOST"},
                                        PeerPort => $amp_conf{"ASTMANAGERPORT"},
                                        Username => $amp_conf{"AMPMGRUSER"},
                                        Secret   => $amp_conf{"AMPMGRPASS"}
                                                );

    }

    unless( $astman ){
        plog("Asterisk connection failure");
        return 0;
    }

    plog("Asterisk connection ok");
    return 1;    
    
}

=item
    Restore pbx backup file
=cut
sub restoreconf {
    my ($self,$file) = @_;
    
    my $ast_conn = $self->astman_conn();
    if(!$ast_conn ){return retErr("_ERR_BACKUP_RESTORE_","Unable to connect to asterisk");}

    my $backup_module = ETVOIP::PBX::Backup->new(amp_conf=>\%amp_conf,astman=>$astman);
    my $response = $backup_module->restore_etvoip($file);

    return wantarray() ? %$response : $response;
}

=item
    Backup pbx
=cut
sub backupconf {
    my $self = shift;    
    my $ast_conn = $self->astman_conn();
    if(!$ast_conn ){return retErr("_ERR_BACKUP_SAVE_","Unable to connect to asterisk");}

    my $backup_module = ETVOIP::PBX::Backup->new(amp_conf=>\%amp_conf,astman=>$astman);
    my $response = $backup_module->backup_etvoip();

    return wantarray() ? %$response : $response;
}

=item
    Get last backup file path
=cut
sub get_backupconf_file {
    my $self = shift;    
    my $ast_conn = $self->astman_conn();
    if(!$ast_conn ){return retErr("_ERR_BACKUP_","Unable to connect to asterisk");}

    my $backup_module = ETVOIP::PBX::Backup->new(amp_conf=>\%amp_conf,astman=>$astman);
    my $response = $backup_module->backup_get_etvoip();

    return $response;
    
}

=item
    Get backup restore archive path
=cut
sub get_backup_archive {
    my $self = shift;    
    my $backup_module = ETVOIP::PBX::Backup->new(amp_conf=>\%amp_conf);
    my $response = $backup_module->get_etvoip_archive();
    return $response;

}


=item get_extensions

    query freePBX DB for extensions

=cut
sub get_extensions {
    my $self = shift;

    my $q = "SELECT u.extension, u.name, u.voicemail, d.tech FROM users u LEFT JOIN devices d ON u.extension = d.user";
    my ($sth, $result) = DB::db_sql($q);

    if(!$sth){ return retErr("_ERR_GET_EXTENSIONS_","Can't get extensions. DB problem. $DBI::errstr ."); }

    my $get_res = $sth->fetchall_arrayref({});
    my @sorted_res = sort { $a->{'extension'} <=> $b->{'extension'}} @$get_res;
    my $result = $self->add_response_reload(\@sorted_res);    
    return $result;        
}

=item add_extension

    ARGS: extension - extension number (required)
          tech - sip (required)
          devinfo_dial -
          devinfo_channel -
          emergency_cid -
          description -
          name - display name
          password -
          outboundcid - 
          gateway - gateway
          voicemail -
          ringtimer -
          noanswer -
          cid_masquerade -
          call_screen - 0 || nomemory || memory
          callwaiting - enabled || disabled
          pinless - enabled || disabled
          sipname -
          record_out - Adhoc || Always || Never
          record_in - Adhoc || Always || Never

          devinfo_secret -
          devinfo_dtmfmode -
          devinfo_canreinvite -
          devinfo_context -
          devinfo_host -
          devinfo_type -
          devinfo_nat -
          devinfo_port -
          devinfo_qualify -
          devinfo_callgroup -
          devinfo_pickupgroup -
          devinfo_disallow -
          devinfo_allow -
          devinfo_dial -
          devinfo_accountcode -
          devinfo_mailbox -
          devinfo_deny -
          devinfo_permit -

=cut

sub add_extension {
    my $self = shift;
    my (%p) = @_;
    my $extension = trim($p{'extension'});

    unless( $extension ){
        return retErr("_ERR_ADD_EXTENSION_","Need extension number");
    }

    my $ast_conn = $self->astman_conn();
    if(!$ast_conn ){return retErr("_ERR_ADD_EXTENSION_","Unable to connect to asterisk");}

    my $response = $self->process("extensions_add",\%p);

    if($response && !isError($response)) {
        return retOk("_OK_ADD_EXTENSION_","Added successfully.");
    }
    else{
        return wantarray() ? %$response : $response;
    }   

}


sub edit_extension {
    my $self = shift;
    my (%p) = @_;
    my $extension = trim($p{'extension'});

    unless( $extension ){
        return retErr("_ERR_EDIT_EXTENSION_","Need extension number");
    }

    my $ast_conn = $self->astman_conn();
    if(!$ast_conn ){return retErr("_ERR_EDIT_EXTENSION_","Unable to connect to asterisk");}

    my $response = $self->process("extensions_edit",\%p);

    if($response && !isError($response)) {
        return retOk("_OK_EDIT_EXTENSION_","Edited successfully.");
    }
    else{
        return wantarray() ? %$response : $response;
    }

}


sub get_extension {
    my ($self,%p) = @_;
    my ($extension) = $p{'extension'} || '';

    unless( $extension ){
        return retErr("_ERR_GET_EXTENSION_","Need extension number");
    }

    my $ast_conn = $self->astman_conn();
    if(!$ast_conn ){return retErr("_ERR_GET_EXTENSION_","Unable to connect to asterisk");}


    my $core_module = ETVOIP::PBX::Core->new(amp_conf=>\%amp_conf,astman=>$astman);
    my $response = $core_module->core_extensions_get(\%p);           

    if($response && !isError($response)) {

        if($active_modules->{'languages'}){
            my $lang_module = ETVOIP::PBX::Languages->new(amp_conf=>\%amp_conf,astman=>$astman);
            my $langcode = $lang_module->languages_user_get($extension);
            $response->{'langcode'} = $langcode;
        }
        
        
        if($active_modules->{'dictate'}){
            my $dictate_module = ETVOIP::PBX::Dictate->new(amp_conf=>\%amp_conf,astman=>$astman);
            my $dibox = $dictate_module->dictate_get($extension);
            
            $response->{'dictenabled'} = $dibox->{'enabled'};
            $response->{'dictformat'} = $dibox->{'format'};
            $response->{'dictemail'} = $dibox->{'email'};
        }
        
        
        if($active_modules->{'voicemail'}){
            my $vm_module = ETVOIP::PBX::Voicemail->new(amp_conf=>\%amp_conf,astman=>$astman);
            my $vmbox = $vm_module->voicemail_mailbox_get($extension);


            plog("\n\n\n\nVBOXXXXX\n\n\n\n",Dumper($vmbox));
            if(%{$vmbox}) {
                $response->{'vm'} = 'enabled';
                $response->{'vmpwd'} = $vmbox->{'pwd'};
                $response->{'email'} = $vmbox->{'email'};
                $response->{'pager'} = $vmbox->{'pager'};
                $response->{'vmcontext'} = $vmbox->{'vmcontext'} || 'default';
                $response->{'options'} = $vmbox->{'options'};

                
                $response->{'attach'} = $response->{'options'}{'attach'};
                delete($response->{'options'}{'attach'});
                
                $response->{'saycid'} = $response->{'options'}{'saycid'};
                delete($response->{'options'}{'saycid'});

                $response->{'envelope'} = $response->{'options'}{'envelope'};
                delete($response->{'options'}{'envelope'});

                $response->{'delete'} = $response->{'options'}{'delete'};
                delete($response->{'options'}{'delete'});

                $response->{'imapuser'} = $response->{'options'}{'imapuser'};
                delete($response->{'options'}{'imapuser'});

                $response->{'imappassword'} = $response->{'options'}{'imappassword'};
                delete($response->{'options'}{'imappassword'});

                #create string key1=val1 | key2=val2 | key3=val3
                my $options = join("|",map{ "$_=$response->{'options'}{$_}" } keys %{$response->{'options'}});
                $response->{'options'} = $options;

            }else{
                $response->{'vm'} = 'disabled';
                $response->{'vmcontext'} = 'default';
            }
            
                        
        }
        

        plog("GET EXTENSION",Dumper($response));
        return wantarray() ? %$response : $response;
        #my @values = values %$response;
        #return wantarray() ? @values : \@values;
    }
    else{
        return wantarray() ? %$response : $response;
    }

}


=item del_extension

=cut
sub del_extension {
    my $self = shift;
    my (%p) = @_;
    my $extension = trim($p{'extension'});

    unless( $extension ){
        return retErr("_ERR_DEL_EXTENSION_","Need extension number");
    }

    my $ast_conn = $self->astman_conn();
    if(!$ast_conn ){return retErr("_ERR_DEL_EXTENSION_","Unable to connect to asterisk");}

    my $response = $self->process("extensions_del",\%p);

    if($response && !isError($response)) {
        return retOk("_OK_DEL_EXTENSION_","Deleted successfully.");
    }
    else{
        return wantarray() ? %$response : $response;
    }
}


=item need_reload

    Tells elastix we need reload asterisk

=cut
sub need_reload{
    my $self = shift;	
	my $sql = "UPDATE admin SET value = 'true' WHERE variable = 'need_reload'";
    my ($sth, $result) = DB::db_sql($sql);
}


sub check_reload_needed {
    my $self = shift;
	my $sql = "SELECT value FROM admin WHERE variable = 'need_reload'";
    my ($sth, $result) = DB::db_sql($sql);
    my $res = $sth->fetchrow_hashref;

    return ($res->{'value'} eq 'true') ? 1 : 0;    
}


sub add_response_reload{
    my ($self,$response) = @_;
=item
    if( ref($response) eq 'ARRAY' ){
        push(@$response, {'need_reload' => $self->check_reload_needed() });
    } elsif( ref($response) eq 'HASH' ){
        $response->{'need_reload'} = $self->check_reload_needed();
    }
=cut
    my $result = {'need_reload' => $self->check_reload_needed()};
    if($response) {$result->{'data'} = $response;}
    
    return wantarray() ? \$result : $result;
}

=item check_extension_usage
    Checks in all modules if an extension is at use
    Call existent modules _check_extension method
    SEE TODO
=cut
sub check_extension_usage {
    my ($self,$extension) = @_;
    my $exten_usage = {};

    my $response = $self->process("check_extension",\$extension);

    if($response && !isError($response)) {
        $exten_usage = $response;
        my $exten_matches = {};
        for my $mod (keys %$exten_usage){
            if(exists($exten_usage->{$mod}{$extension})){                
                $exten_matches->{$mod}{$extension} = $exten_usage->{$mod}{$extension};
            }
        }
        return wantarray() ? %$exten_matches : $exten_matches;
    }
    else{
        return wantarray() ? %$response : $response;
    }

}


#
#
#
#   TRUNKS
#
#
sub add_trunk {
    my $self = shift;
    my (%p) = @_;

plog("TRUN PARAMS ",Dumper(\%p));

    my $core_module = ETVOIP::PBX::Core->new(amp_conf=>\%amp_conf,astman=>$astman);
    my $response = $core_module->core_trunks_addtrunk(\%p);

    if($response && !isError($response)) {
        plog("ADD TRUNK",Dumper($response));
        return wantarray() ? %$response : $response;
        #my @values = values %$response;
        #return wantarray() ? @values : \@values;
    }
    else{
        return wantarray() ? %$response : $response;
    }          
}

sub edit_trunk {
    my $self = shift;
    my (%p) = @_;
    my $trunknum = $p{'trunknum'};

    plog("TRUN PARAMS ",Dumper(\%p));

    unless( $trunknum ){
        return retErr("_ERR_EDIT_TRUNK_","Need trunk id");
    }

    my $core_module = ETVOIP::PBX::Core->new(amp_conf=>\%amp_conf,astman=>$astman);
    my $response = $core_module->core_trunks_edittrunk(\%p);

    if($response && !isError($response)) {
        plog("EDIT TRUNK",Dumper($response));
        return wantarray() ? %$response : $response;
        #my @values = values %$response;
        #return wantarray() ? @values : \@values;
    }
    else{
        return wantarray() ? %$response : $response;
    }
}



sub del_trunk {
    my $self = shift;
    my (%p) = @_;
    my $trunknum = $p{'trunknum'};

    plog("TRUN PARAMS ",Dumper(\%p));

    unless( $trunknum ){
        return retErr("_ERR_DEL_TRUNK_","Need trunk id");
    }

    my $core_module = ETVOIP::PBX::Core->new(amp_conf=>\%amp_conf,astman=>$astman);
    my $response = $core_module->core_trunks_deltrunk($trunknum);

    if($response && !isError($response)) {
        plog("DELETE TRUNK",Dumper($response));
        return wantarray() ? %$response : $response;
        #my @values = values %$response;
        #return wantarray() ? @values : \@values;
    }
    else{
        return wantarray() ? %$response : $response;
    }
}


sub get_trunk {
    my ($self,%p) = @_;
    my ($trunknum) = $p{'trunknum'};

    unless( $trunknum ){
        return retErr("_ERR_GET_TRUNK_","Need trunk id");
    }

    print STDERR "\n TRUNKNUM ($trunknum)\n";

    my $ast_conn = $self->astman_conn();
    if(!$ast_conn ){return retErr("_ERR_GET_EXTENSION_","Unable to connect to asterisk");}


    my $core_module = ETVOIP::PBX::Core->new(amp_conf=>\%amp_conf,astman=>$astman);
    my $response = $core_module->core_trunks_get($trunknum);

    if($response && !isError($response)) {
        plog("GET TRUNK ",Dumper($response));
        return wantarray() ? %$response : $response;
        #my @values = values %$response;
        #return wantarray() ? @values : \@values;
    }
    else{
        return wantarray() ? %$response : $response;
    }

}

sub get_trunks {
    my $self = shift;
        
    my $core_module = ETVOIP::PBX::Core->new(amp_conf=>\%amp_conf);
    my $response = $core_module->core_trunks_getDetails();

    if($response && !isError($response)) {
        plog("GET TRUNKS",Dumper($response));
        my $result = $self->add_response_reload($response);
        plog("STUF\n\n\n",Dumper($result));
        return $result;
       
    }
    else{        
        return wantarray() ? %$response : $response;
    }    

}





#
#
#
#   OUTBOUND ROUTES
#
#


sub get_outboundroute {
    my ($self,%p) = @_;
    my $name = $p{'routename'};


    my $core_module = ETVOIP::PBX::Core->new(amp_conf=>\%amp_conf);
    #my $response =
    
    

    #return wantarray() ? @trunks_list : \@trunks_list;


    my $response = $core_module->core_routing_get($name);

    if($response && !isError($response)) {

        
        $response->{'priorities'} = $core_module->core_trunks_list(0);

        my $music_module = ETVOIP::PBX::Music->new(amp_conf=>\%amp_conf);
        $response->{'moh'} = $music_module->music_list();


    


        plog("GET TRUNK ",Dumper($response));
        return wantarray() ? %$response : $response;
        #my @values = values %$response;
        #return wantarray() ? @values : \@values;
    }
    else{
        return wantarray() ? %$response : $response;
    }






}

sub add_outboundroute {
    my $self = shift;
    my (%p) = @_;

plog("ROUTE PARAMS ",Dumper(\%p));

    my $core_module = ETVOIP::PBX::Core->new(amp_conf=>\%amp_conf,astman=>$astman);
    my $response = $core_module->core_routing_addroute(\%p);

    if($response && !isError($response)) {
        plog("ADD ROUTE",Dumper($response));
        return wantarray() ? %$response : $response;
        #my @values = values %$response;
        #return wantarray() ? @values : \@values;
    }
    else{
        return wantarray() ? %$response : $response;
    }
}



sub edit_outboundroute {
    my $self = shift;
    my (%p) = @_;

    my $response = $self->process("routing_editroute",\%p);

    if($response && !isError($response)) {
        return retOk("_OK_EDIT_OUTBOUNDROUTE_","Edited successfully.");
    }
    else{
        return wantarray() ? %$response : $response;
    }

}



sub del_outboundroute {
    my ($self, %p) = @_;
    my $name = $p{'routename'};

    my $response = $self->process("routing_delroute",$name);

    if($response && !isError($response)) {
        return retOk("_OK_DEL_OUTBOUNDROUTE_","Deleted successfully.");
    }
    else{
        return wantarray() ? %$response : $response;
    }

}


sub get_outboundroutes {
    my $self = shift;

    my $core_module = ETVOIP::PBX::Core->new(amp_conf=>\%amp_conf);
    my $response = $core_module->core_routing_getroutenames();

    if($response && !isError($response)) {
        plog("GET OUTBOUND ROUTES",Dumper($response));
        my $result = $self->add_response_reload($response);     
        return $result;

    }    
    else{
        return wantarray() ? %$response : $response;
    }

}





#
#   INBOUND ROUTES
#
#
=item
    list all inboundroutes
=cut
sub get_inboundroutes {
    my $self = shift;

    my $core_module = ETVOIP::PBX::Core->new(amp_conf=>\%amp_conf);
    my $response = $core_module->core_did_list();

    if($response && !isError($response)) {
        my $result = $self->add_response_reload($response);
        return $result;
    }

    return wantarray() ? %$response : $response;    
}



sub get_inboundroute {
    my ($self,%p) = @_;
    my $extdisplay = $p{'extdisplay'};
    my $response = {};


    my $core_module = ETVOIP::PBX::Core->new(amp_conf=>\%amp_conf);
    if($extdisplay) {
        my @extarray = split(/\//,$extdisplay,2);
        $response = $core_module->core_did_get($extarray[0],$extarray[1]);

        if(isError($response)) {
            return wantarray() ? %$response : $response;
        }
    }
    my $destinations = $self->get_destinations();
    $response->{'destinations'} = $destinations->{'destinations'};
    
    my $cidlookup_module = ETVOIP::PBX::Cidlookup->new();    
    $response->{'cidlookup_id'} = $cidlookup_module->cidlookup_did_get($extdisplay);    

    return wantarray() ? %$response : $response;
}


sub add_inboundroute {
    my $self = shift;
    my (%p) = @_;
    my $response = $self->process("did_process_add",\%p);

    return wantarray() ? %$response : $response;    
}


sub edit_inboundroute {
    my $self = shift;
    my (%p) = @_;    

    my $response = $self->process("did_process_edit",\%p);

    if($response && !isError($response)) {
        return retOk("_OK_EDIT_INBOUNDROUTE_","Edited successfully.");
    }
    else{
        return wantarray() ? %$response : $response;
    }

}



sub del_inboundroute {
    my ($self, %p) = @_;
    my $ext = $p{'extdisplay'};

    unless( $ext ){
        return retErr("_ERR_DEL_INBOUNDROUTE_","Need extension");
    }

    my $response = $self->process("did_process_del",\%p);

    return wantarray() ? %$response : $response;    
}



sub process {
    my ($self,$action,$p) = @_;
    my $module_usage;
    my $module_result = {};



    for my $priority ( sort keys %$order_modules ) {




        for my $mod ( keys %{$order_modules->{$priority}} ) {

            my $mod_name = "ETVOIP::PBX::".ucfirst($mod);

            print STDERR "\nPROCESSING $mod_name FOR ACTION $action"."\n";

            my $module = $mod_name->new(amp_conf=>\%amp_conf,astman=>$astman);
            my $funct = $mod."_".$action;
            my $sub_exists = $module->can($funct);

            if(!$sub_exists){ print STDERR "Method $funct does not exist"."\n";}
            else{
                
                $module_usage = $module->$funct($p);

                plog("\n\n\n$module\n");
                plog(Dumper($module_usage));
                
                if( (((ref($module_usage)) eq 'HASH') && %$module_usage) || (((ref($module_usage)) eq 'ARRAY') && @$module_usage)){
                        $module_result->{$mod} = $module_usage;
                }

                if(isError($module_usage)) {
                    return wantarray() ? %$module_usage : $module_usage;
                }

            }

        }






    }
    

    return $module_result;
    
}

sub get_config {
    my $self = shift;
    my ($mod_alpha, $order);
    $self->load_settings();
    

    $self->module_get_info();

    for my $mod ( keys %$active_modules ) {

        # replace - with _
        $mod_alpha = $mod;
        $mod_alpha =~ s/-/_/g;
        my $mod_name = "ETVOIP::PBX::".ucfirst($mod_alpha);


        eval "require $mod_name;";
        if( !$@ ){
           $order = $mod_name->MODULE_PRIORITY;           
           #    push(@build_array)
           #push($existent_modules->[$order],($mod_alpha => $active_modules->{$mod}));
           #push(@{$existent_modules->{$order}},{$mod_alpha => $active_modules->{$mod}});
           
           #FUNCA
           #push(@{$existent_modules->{$order}},{$mod_alpha => "oi"});

           $order_modules->{$order}{$mod_alpha} = $mod_name;

       #$existent_modules->[$order}{$mod_alpha} = $active_modules->{$mod};
           plog("Module $mod_name is available");

        }
        
        
    }
    return;

    
    
}





sub engine_getinfo{
    my $self = shift;
    
    my $engine = $amp_conf{'AMPENGINE'};
    print STDERR "Engine $engine";
    for($engine) {
        if(/asterisk/) {
           my $actionid = $astman->send_action({Action => 'Command', Command => 'core show version'});
           my $response = $astman->get_response($actionid);
           plog("RESPONSE ",Dumper($response));

           my $cmd = $response->{'CMD'}[0];
           plog("Asterisk ",Dumper($cmd));
            

            if($cmd =~ m/Asterisk (\d+(\.\d+)*)(-?(\S*))/){

                my $version = $1;
                return ('engine'=>'asterisk', 'version' => $version);
            }

            
        }
           #$astman->db_del("AMPUSER",$extension."/screen");}
 #       elsif(/nomemory/) {$astman->db_put("AMPUSER",$extension."/screen","\"nomemory\"");}
 #           elsif(/memory/) {$astman->db_put("AMPUSER",$extension."/screen","\"memory\"");}
    }
    
}


sub do_reload {
    my $self = shift;

    $self->load_settings();    
    my $ast_conn = $self->astman_conn();
    if(!$ast_conn ){return retErr("_ERR_RELOAD_","Unable to connect to asterisk");}
    
    my $retrieve =  $amp_conf{"AMPBIN"}.'/retrieve_conf 2>&1';
	# exec($retrieve.'&>'.$asterisk_conf['astlogdir'].'/freepbx-retrieve.log', $output, $exit_val);
	#exec($retrieve, $output, $exit_val);
    my %return = ('num_errors'=>0);

    my %engine_version = $self->engine_getinfo();
    my $version = $engine_version{'version'};

    my $exit_val = 1;
    my ($e,$output) = cmd_exec($retrieve);

    if($e){        
        return retErr('_ERR_RELOAD_',"Reload failed because retrieve_conf encountered an error: $e");
        
    }

    #reload MOH to get around 'reload' not actually doing that.
    my $actionid = $astman->send_action({Action => 'Command', Command => 'moh reload'});
    my $response = $astman->get_response($actionid);
    

    #reload asterisk
    my $v1 = version->parse('v1.4');
    my $v2 = "v".version->parse($version);
    
    if($v2 > $v1){    
        $actionid = $astman->send_action({Action => 'Command', Command => 'module reload'});
        $response = $astman->get_response($actionid);
    }
    else{
        $actionid = $astman->send_action({Action => 'Command', Command => 'reload'});
        $response = $astman->get_response($actionid);
    }
    


    if ($amp_conf{'FOPRUN'} && !$amp_conf{'FOPDISABLE'}) {
        
		#bounce op_server.pl
        my $wOpBounce = $amp_conf{'AMPBIN'}.'/bounce_op.sh';
        my ($e,$output) = cmd_exec_errh($wOpBounce.' &>'.$amp_conf{'ASTLOGDIR'}.'/freepbx-bounce_op.log');

        if($e){
            return retErr('_ERR_RELOAD_',"Could not reload the FOP operator panel server using the bounce_op.sh script.");
        }
	}
    	
    #store asterisk reloaded status
	my $sql = "UPDATE admin SET value = 'false' WHERE variable = 'need_reload'";    
    my ($sth, $result) = DB::db_sql($sql);

    if(!$result) { return retErr("_ERR_RELOAD_","Successful reload, but could not clear reload flag due to a database error.");}	

    return $self->add_response_reload();

    
}


=item module_get_info
    looks through the modules directory and modules database and sets all available
=cut
sub module_get_info {
    my $self = shift;
    # initialize list with "builtin" module
	my @module_list = ('builtin');
    $self->modulelist();

    if(!$active_modules){

        # TODO if not in DB build from files dir
        
    }    
            

}

=item modulelist
    set global active_modules
=cut
sub modulelist {

    if($active_modules) { return 1;}
    
    my ($sth, $return) = DB::db_sql("SELECT `data` FROM `module_xml` WHERE `id` = 'mod_serialized' LIMIT 1");
    my $module_serialized = $sth->fetchrow_array();
    
    $active_modules = unserialize($module_serialized);

    for my $key ( keys %$active_modules ) {

        if ($active_modules->{$key}{'status'} ne MODULE_STATUS_ENABLED) {
            delete($active_modules->{$key});
        }        
    }        
}

=item
    get possible routing destinations
=cut
sub get_destinations {
    my $self = shift;

    my $ast_conn = $self->astman_conn();
    if(!$ast_conn ){return retErr("_ERR_GET_DESTINATIONS_","Unable to connect to asterisk");}

    my $response = $self->process("destinations");


    my $exten_usage = {};
    my $all_destinations = {};
    

    if($response && !isError($response)) {
        $exten_usage = $response;
        my $exten_matches = {};

        # ADD manually directory info (no need to create module yet)
        $exten_usage->{'pbdirectory'} = [{'destination' => 'app-pbdirectory,pbdirectory,1', 'description' => 'Phonebook Directory'}];

        for my $mod (keys %$exten_usage){
            print STDERR "\n MOD ($mod)";
            plog("Ciclo ",Dumper($exten_usage->{$mod}));

            my $mod_Data = $exten_usage->{$mod};

            for my $dest (@$mod_Data){
                my $cat = $dest->{'category'};
                print STDERR "CAT ($cat)";

                if(!$cat) {
                    $cat = $active_modules->{$mod}{'displayname'};
                    $dest->{'category'} = $cat;
                }                


                push(@{$all_destinations->{$cat}},$dest);

            }

            #foreach ($destArray as $dest) {
		#			$cat = (isset($dest['category']) ? $dest['category'] : $module['displayname']);
	#				$all_destinations[$cat][] = $dest;
#					$module_hash[$cat] = $rawmod;
				#}

#            if(exists($exten_usage->{$mod}{$extension})){
#                $exten_matches->{$mod}{$extension} = $exten_usage->{$mod}{$extension};
#            }
        }
        my $res = {};
        plog("ALL DESTS ",Dumper($all_destinations));
        $res->{'destinations'} = $all_destinations;

        return wantarray() ? %$res : $res;
    }
    else{
        return wantarray() ? %$response : $response;
    }

}
1;