#!/usr/bin/perl

=pod

=head1 NAME

ETVOIP::PBX::Voicemail

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4


TODO:
    voicemail support ?????

=cut

package ETVOIP::PBX::Voicemail;
use strict;
use Data::Dumper;
use ETVA::Utils;

BEGIN {
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF $AUTOLOAD);
    @ISA = ('ETVOIP::PBX');
};

use constant MODULE_PRIORITY => 1;

sub new{
    my $class = shift;
    my $self = {@_};
    bless $self, $class;
    return $self;
}

=item
    called on add extension
=cut
sub voicemail_extensions_add {
    my ($self,$p) = @_;
    my $extension = trim($p->{'extension'});        

    unless( $extension ){
        return retErr("_ERR_ADD_EXTENSION_VOICEMAIL_","Need extension number");
    }

    my $usage_arr = $self->check_extension_usage($extension);    
    if(%$usage_arr){
        plog("Extension $extension in use");
        return retErr("_ERR_ADD_EXTENSION_VOICEMAIL_","Extension $extension in use");
    }

    my $added_user_vm = $self->voicemail_mailbox_add($p);
    if($added_user_vm && !isError($added_user_vm)) {
        return retOk("_OK_ADD_EXTENSION_VOICEMAIL_","Added voicemail successfully.");
                
    }
    else{
        return wantarray() ? %$added_user_vm : $added_user_vm;
    }    
}



=item
    called on edit extension
=cut
sub voicemail_extensions_edit {
    my ($self,$p) = @_;
    my $extension = trim($p->{'extension'});
    my $vm = trim($p->{'vm'});

    unless( $extension ){
        return retErr("_ERR_EDIT_EXTENSION_VOICEMAIL_","Need extension number");
    }    

    $self->voicemail_mailbox_del($extension);
    if ( $vm ne 'disabled' ){
        $self->voicemail_mailbox_add($p);
    }

    return retOk("_OK_EDIT_EXTENSION_VOICEMAIL_","Updated voicemail successfully.");
}


=item
    called on del extension
=cut
sub voicemail_extensions_del {
    my ($self,$p) = @_;
    my $extension = trim($p->{'extension'});

    unless( $extension ){
        return retErr("_ERR_DEL_EXTENSION_VOICEMAIL_","Need extension number");
    }
    
    my $mb_rm = $self->voicemail_mailbox_remove($extension);
    if($mb_rm && !isError($mb_rm)) {

        my $mb_del = $self->voicemail_mailbox_del($extension);
        if($mb_del && !isError($mb_del)) {
            return retOk("_OK_DEL_EXTENSION_VOICEMAIL_","Removed voicemail successfully.");
        }
        else{         
            return wantarray() ? %$mb_del : $mb_del;
        }                
    }
    else{        
        return wantarray() ? %$mb_rm : $mb_rm;
    }

}


sub voicemail_mailbox_del {
    my ($self, $mbox) = @_;
    my $uservm = $self->voicemail_getVoicemail();

    foreach my $vmcontext (keys %$uservm){

        if($uservm->{$vmcontext}->{$mbox}){

            delete $uservm->{$vmcontext}->{$mbox};


            plog("VOICEMAIL DEL Data to save",Dumper($uservm));


            $self->voicemail_saveVoicemail($uservm);            
        }
    }
    return 1;
}

sub voicemail_mailbox_remove {
    my ($self, $mbox) = @_;
    my $return = 1;

    my $uservm = $self->voicemail_getVoicemail();

    foreach my $vmcontext (keys %$uservm){

        if($uservm->{$vmcontext}->{$mbox}){

            my $vm_dir = $self->{'amp_conf'}{'ASTSPOOLDIR'}."/voicemail/$vmcontext/$mbox";

            my ($e,$output) = cmd_exec("rm -rf $vm_dir");
            if($e){
                $return = 0;
                return retErr('_ERR_RELOAD_',"Failed to delete vmbox: $mbox@$vmcontext");
            }
        }

    }
    return $return;
}


sub voicemail_mailbox_add{
    my ($self,$p) = @_;
    my $extension = $p->{'extension'};
    my $options = $p->{'options'};
    my $imapuser = $p->{'imapuser'};
    my $imappassword = $p->{'imappassword'};
    my $attach = $p->{'attach'};
    my $saycid = $p->{'saycid'};
    my $envelope = $p->{'envelope'};
    my $delete = $p->{'delete'};
    my $vmcontext = $p->{'vmcontext'};    
    my $vmpwd = $p->{'vmpwd'};
    my $name = $p->{'name'};
    my $email = $p->{'email'};
    my $pager = $p->{'pager'};
    my $vmx_state = $p->{'vmx_state'};
    my $vm = $p->{'vm'};

    my $vmoptions = {};

 
    
    my $vmx_option_0_system_default = $p->{'vmx_option_0_system_default'};
    my $vmx_option_0_number = $p->{'vmx_option_0_number'};    


    #check if VM box already exists
    my %vm_get = $self->voicemail_mailbox_get($extension);
	if ( %vm_get ) {
		return retErr("_ERR_MAILBOX_ADD_","Voicemail mailbox '$extension' already exists, call to voicemail_maibox_add failed");
	}

    my $uservm = $self->voicemail_getVoicemail();


    if ($vm ne 'disabled')
	{
		# need to check if there are any options entered
		if ($options){
            $vmoptions = {map { split(/\=/,$_) } split(/\|/,$options)};
		}
        
		if ($imapuser && $imappassword) {
			$vmoptions->{'imapuser'} = $imapuser;
			$vmoptions->{'imappassword'} = $imappassword;
		}
        		
        $vmoptions->{'attach'} = $attach;
        $vmoptions->{'saycid'} = $saycid;
        $vmoptions->{'envelope'} = $envelope;
        $vmoptions->{'delete'} = $delete;        

		$uservm->{$vmcontext}->{$extension} = {
			'mailbox' => $extension,
			'pwd' => $vmpwd,
			'name' => $name,
			'email' => $email,
			'pager' => $pager,
			'options' => $vmoptions
        };
		
	}

    plog("VOICEMAIL ADD Data to save",Dumper($uservm));

    $self->voicemail_saveVoicemail($uservm);



    # Operator extension can be set even without VmX enabled so that it can be
	# used as an alternate way to provide an operator extension for a user
	# without VmX enabled.
	#
=item

	if ($vmx_option_0_system_default) {
		$self->vmx_setMenuOpt("",0,'unavail');
		$self->vmx_setMenuOpt("",0,'busy');
	} else {

        if (!$vmx_option_0_number) {
            $vmx_option_0_number = '';
        }

        $vmx_option_0_number =~ s/[^0-9]//g;
        
        $self->vmx_setMenuOpt($extension,$vmx_option_0_number,0,'unavail');
		$self->vmx_setMenuOpt($extension,$vmx_option_0_number,0,'busy');
	}


    if ($vmx_state) {

		#TODO need to take care of vmx
        
	} else {
		if ($self->vmx_isInitialized($extension)) {
			$self->vmx_disable($extension);
		}
	}

=cut

    return 1;
}


sub voicemail_saveVoicemail {
    my ($self,$vmconf) = @_;
    my $section;
    $self->write_voicemail_conf($self->{'amp_conf'}{"ASTETCDIR"}."/voicemail.conf", $vmconf, $section);
}


sub voicemail_mailbox_get {
    my ($self, $mbox) = @_;
    my %vmbox = ();
    
    my $uservm = $self->voicemail_getVoicemail();       
    
    foreach my $vmcontext (keys %$uservm){
        
        if($uservm->{$vmcontext}->{$mbox}){
            $vmbox{'vmcontext'} = $vmcontext;
			$vmbox{'pwd'} = $uservm->{$vmcontext}->{$mbox}{'pwd'};
			$vmbox{'name'} = $uservm->{$vmcontext}->{$mbox}{'name'};
			$vmbox{'email'} = $uservm->{$vmcontext}->{$mbox}{'email'};
			$vmbox{'pager'} = $uservm->{$vmcontext}->{$mbox}{'pager'};
			$vmbox{'options'} = $uservm->{$vmcontext}->{$mbox}{'options'};
        }        
    }    

    return wantarray() ? %vmbox : \%vmbox;
}

sub voicemail_getVoicemail{	
    my $self = shift;
    my %vmconf = ();
    my $section;    
    
    $self->parse_voicemail_conf($self->{'amp_conf'}{"ASTETCDIR"}."/voicemail.conf", \%vmconf, $section);    
    return wantarray() ? %vmconf : \%vmconf;    
}

sub vmx_setMenuOpt{
    my $self = shift;
    my $extension = shift;
    my $opt = shift || "";
    my $digit = shift || "0";
    my $mode = shift || "unavail";
    my $context = shift || "from-internal";
    my $priority = shift || "1";
        
    my $astman = $self->{'astman'};


    if ($astman && ($mode eq "unavail" || $mode eq "busy")) {
        if ($opt =~ m/^\d+$/) {
            $astman->db_put("AMPUSER", $extension."/vmx/$mode/$digit/ext", $opt);
            $astman->db_put("AMPUSER", $extension."/vmx/$mode/$digit/context", $context);
            $astman->db_put("AMPUSER", $extension."/vmx/$mode/$digit/pri", $priority);
        } else {
            $astman->db_deltree("AMPUSER/".$extension."/vmx/$mode/$digit");
        }
        return 1;
    } else {
        return 0;
    }    

}


sub vmx_isInitialized{
    my $self = shift;
    my $extension = shift;
    my $mode = shift || "unavail";
        
    my $astman = $self->{'astman'};

    if ($astman && ($mode eq "unavail" || $mode eq "busy")) {
        my $vmx_state = trim($astman->db_get("AMPUSER",$extension."/vmx/$mode/state"));
        
        if (($vmx_state eq 'enabled' || $vmx_state eq 'disabled') || $vmx_state eq 'blocked') {
            return 1;
        } else {
            return 0;
        }
    }            
}


sub vmx_disable{
    my $self = shift;
    my $extension = shift;
    
    my $ret = $self->vmx_setState($extension,'blocked','unavail');
    return $self->vmx_setState('blocked','busy') && $ret;                
}


sub vmx_setState{
    my $self = shift;
    my $extension = shift;
    my $state = shift || "enabled";
    my $mode = shift || "unavail";
    
    my $astman = $self->{'astman'};

    if ($astman && ($mode eq "unavail" || $mode eq "busy")) {
        $astman->db_put("AMPUSER", $extension."/vmx/$mode/state", "$state");
        return 1;
    } else {
        return 0;
    }
}
1;