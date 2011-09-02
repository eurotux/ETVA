#!/usr/bin/perl

=pod

=head1 NAME

ETVOIP::PBX::Core

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package ETVOIP::PBX::Core;
use strict;
use Data::Dumper;
use ETVA::Utils;

BEGIN {
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF $AUTOLOAD);
    @ISA = ('ETVOIP::PBX');
};

use constant MODULE_PRIORITY => 5;

sub new{
    my $class = shift;
    my $self = {@_};
    bless $self, $class;
    return $self;
}


=item core_extensions_add

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
sub core_extensions_add {
    my ($self,$p) = @_;
    my $extension = trim($p->{'extension'});
    
    unless( $extension ){
        return retErr("_ERR_ADD_EXTENSION_","Need extension number");
    }

    my $usage_arr = $self->check_extension_usage($extension);            
    if(%$usage_arr){
        plog("Extension $extension in use");
        return retErr("_ERR_ADD_EXTENSION_","Extension $extension in use");
    }

    my $added_user = $self->core_users_add($p);    
    if($added_user && !isError($added_user)) {
        $self->need_reload();
        my $added_device = $self->core_devices_add($p);
        if($added_device && !isError($added_device)) {
            return retOk("_OK_ADD_EXTENSION_","Added successfully.");
        }
        else{
            return wantarray() ? %$added_device : $added_device;
        }
    }
    else{
        return wantarray() ? %$added_user : $added_user;
    }    

}


sub core_extensions_get {
    my ($self,$p) = @_;    
    my $extension = trim($p->{'extension'});
        
    my $dev = $self->core_devices_get($extension);

    my $devinfo;

    foreach my $item ( keys %$dev) {        
        $devinfo->{'devinfo_'.$item} = $dev->{$item};
    }

    my $userinfo = $self->core_users_get($extension);
    my $merged = {%$devinfo,%$userinfo};       

    return wantarray() ? %$merged : $merged;    
}


sub core_extensions_edit {
    my ($self,$p) = @_;
    my $extension = trim($p->{'extension'});

    unless( $extension ){
        return retErr("_ERR_EDIT_EXTENSION_","Need extension number");
    }       

    my $edited_user = $self->core_users_edit($p);
    if($edited_user && !isError($edited_user)) {
        $self->need_reload();
        $self->core_devices_del($extension,1);
        my $added_device = $self->core_devices_add($p,1);
        if($added_device && !isError($added_device)) {
            return retOk("_OK_EDIT_EXTENSION_","Edited successfully.");
        }
        else{
            return wantarray() ? %$added_device : $added_device;
        }
    }
    else{
        return wantarray() ? %$edited_user : $edited_user;
    }

}

sub core_extensions_del {

    my ($self,$p) = @_;
    my $extension = trim($p->{'extension'});

    unless( $extension ){
        return retErr("_ERR_DEL_EXTENSION_","Need extension number");
    }

    my $del_user = $self->core_users_del($extension);
    if($del_user && !isError($del_user)) {
        $self->core_users_cleanastdb($extension);
        $self->need_reload();
        my $del_device = $self->core_devices_del($extension);        
        if($del_device && !isError($del_device)) {
            return retOk("_OK_DEL_EXTENSION_","Deleted successfully.");
        }
        else{
            return wantarray() ? %$del_device : $del_device;
        }
    }
    else{
        return wantarray() ? %$del_user : $del_user;
    }

}


=item core_users_add

    ARGS: extension - extension number (required)
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
          newdid
          newdid_name - DID description
          newdidcid
          sipname

=cut
sub core_users_add {
    my ($self, $p, $editmode) = @_;
    $editmode ||=  0;
    my $astman = $self->{'astman'};    
    
    my $extension = $p->{'extension'};
    my $password = $p->{'password'};
    my $name = $p->{'name'};
    my $outboundcid = $p->{'outboundcid'};    
    my $ringtimer = $p->{'ringtimer'};
    my $noanswer = $p->{'noanswer'};
    my $cid_masquerade = $p->{'cid_masquerade'};
    my $call_screen = $p->{'call_screen'};
    my $callwaiting = $p->{'callwaiting'};
    my $pinless = $p->{'pinless'};
    my $sipname = $p->{'sipname'};
    my $device = $p->{'device'};
    my $newdid = trim($p->{'newdid'});
    my $newdid_name = $p->{'newdid_name'};
    my $newdidcid = $p->{'newdidcid'};
    my $sipname = trim($p->{'sipname'});    

    $newdid =~ s/[^0-9._XxNnZz\[\]\-\+]//;    

    if($newdidcid !~ m/^priv|^block|^unknown|^restrict|^unavail|^anonym/i){
        $newdidcid =~ s/[^0-9._XxNnZz\[\]\-\+]//;           
    }   
    
	if ($newdid || $newdidcid) {
		my $existing = $self->core_did_get($newdid, $newdidcid);        
        
		if($existing) {            
			return retErr("_ERR_ADD_EXTENSION_","A route with this DID/CID (".$existing->{'extension'}."/".$existing->{'cidnum'}.") already exists.");
		}
	}

    $sipname =~ s/\s//;    

    if (!$self->core_sipname_check($sipname,$extension)) {
        return retErr("_ERR_ADD_EXTENSION_","Sipname ".$sipname." is already in use");	
	}    

    #build the recording variable
    my $recording = "out=".$p->{'record_out'}."|in=".$p->{'record_in'};
    

    #if voicemail is enabled, set the box@context to use
    my $vmbox;
    my $voicemail;
    if($self->active_modules()->{'voicemail'}){
        my $vm_module = ETVOIP::PBX::Voicemail->new(amp_conf=>$self->{'amp_conf'});
        $vmbox = $vm_module->voicemail_mailbox_get($extension);
        if(%{$vmbox})
        {
            $voicemail = $vmbox->{'vmcontext'};
        }else{
            $voicemail = 'novm';
        }
    }
    $voicemail = $voicemail || 'default';

    plog('MY VMBOX ',Dumper($vmbox));

    my $q = "INSERT INTO users (extension,password,name,voicemail,ringtimer,noanswer,recording,outboundcid,sipname) values ".
            "(\"$extension\", \"$password\", ".DB::db_quote($name).", \"$voicemail\", \"$ringtimer\", \"$noanswer\", \"$recording\", ".DB::db_quote($outboundcid).", \"$sipname\")";


    my ($sth, $result) = DB::db_sql($q);

    if(!$sth){ return retErr("_ERR_ADD_EXTENSION_","Error adding user extension. DB problem."); }

    #write to astdb
	if ($astman) {
		$cid_masquerade = trim($cid_masquerade) || $extension;
		$astman->db_put("AMPUSER", $extension."/password", $password);
		$astman->db_put("AMPUSER", $extension."/ringtimer", $ringtimer);
		$astman->db_put("AMPUSER", $extension."/noanswer", $noanswer);
		$astman->db_put("AMPUSER", $extension."/recording", $recording);
		$astman->db_put("AMPUSER", $extension."/outboundcid", $outboundcid ? $outboundcid : '');
		$astman->db_put("AMPUSER", $extension."/cidname", $name ? $name : '');
		$astman->db_put("AMPUSER", $extension."/cidnum", $cid_masquerade);
		$astman->db_put("AMPUSER", $extension."/voicemail", "\"".$voicemail ? $voicemail : ''."\"");
    

        for($call_screen) {
            if(/0/) {$astman->db_del("AMPUSER",$extension."/screen");}
            elsif(/nomemory/) {$astman->db_put("AMPUSER",$extension."/screen","nomemory");}
                elsif(/memory/) {$astman->db_put("AMPUSER",$extension."/screen","memory");}
        }

        plog("EDITMODE (VALUE = $editmode)");
        
        if (!$editmode) {
            plog("ENTER HERE ON ADD (editmode=$editmode)");
			$astman->db_put("AMPUSER",$extension."/device","\"".$device ? $device: ''."\"");
		}               

        if (trim($callwaiting) eq 'enabled') {
            $astman->db_put("CW", $extension, "ENABLED");            
        } elsif (trim($callwaiting) eq 'disabled') {
            $astman->db_del("CW", $extension);
        }

        if (trim($pinless) eq 'enabled') {
            $astman->db_put("AMPUSER", $extension."/pinless", "NOPASSWD");
        } elsif (trim($pinless) eq 'disabled') {
            $astman->db_del("AMPUSER", $extension."/pinless");
        }        
        
    } else {
		plog("Cannot connect to Asterisk Manager with ".$self->{'amp_conf'}{"AMPMGRUSER"}."/".$self->{'amp_conf'}{"AMPMGRPASS"});
        return retErr("_ERR_ADD_EXTENSION_","Cannot connect to Asterisk Manager");
	}


    # Now if $newdid is set we need to add the DID to the routes
	#
    my $did_vars = {};
    my $did_dest;
	if ($newdid || $newdidcid) {
		$did_dest                 = 'from-did-direct,'.$extension.',1';
		$did_vars->{'extension'}    = $newdid;
		$did_vars->{'cidnum'}       = $newdidcid;
		$did_vars->{'privacyman'}   = '';
		$did_vars->{'alertinfo'}    = '';
		$did_vars->{'ringing'}      = '';
		$did_vars->{'mohclass'}     = 'default';
		$did_vars->{'description'}  = $newdid_name;
		$did_vars->{'grppre'}       = '';
		$did_vars->{'delay_answer'} = '0';
		$did_vars->{'pricid'}       = '';
		$self->core_did_add($did_vars, $did_dest);
	}
        
    return retOk("_OK_ADD_EXTENSION_","Added ok.");

}


sub core_users_get{
    my ($self,$extension) = @_;
    my $astman = $self->{'astman'};
    my $results = {};

	my $sql = "SELECT * FROM users WHERE extension = '$extension'";
    my ($sth, $result) = DB::db_sql($sql);
    $results = $sth->fetchrow_hashref;

    if (!$results) {
		return wantarray() ? %$results : $results;
	}

    #explode recording vars
    my @recording = split(/\|/,$results->{'recording'});
    if (defined($recording[1])) {
		my $recout = substr($recording[0],4);
		my $recin = substr($recording[1],3);
		$results->{'record_in'} = $recin;
        $results->{'record_out'} = $recout;
    }else{
        $results->{'record_in'} = 'Adhoc';
		$results->{'record_out'} = 'Adhoc';
    }    	

    if ($astman) {
		my $cw = $astman->db_get("CW",$extension);
		$results->{'callwaiting'} = (trim($cw) eq 'ENABLED') ? 'enabled' : 'disabled';
		my $cid_masquerade = $astman->db_get("AMPUSER",$extension."/cidnum");
		$results->{'cid_masquerade'} = (trim($cid_masquerade) ne "") ? $cid_masquerade : $extension;

		my $call_screen = $astman->db_get("AMPUSER",$extension."/screen");
		$results->{'call_screen'} = (trim($call_screen) ne "") ? $call_screen : '0';

		my $pinless = $astman->db_get("AMPUSER",$extension."/pinless");
		$results->{'pinless'} = (trim($pinless) eq 'NOPASSWD') ? 'enabled' : 'disabled';

    } else {
		plog("Cannot connect to Asterisk Manager with ".$self->{'amp_conf'}{"AMPMGRUSER"}."/".$self->{'amp_conf'}{"AMPMGRPASS"});
        return retErr("_ERR_GET_EXTENSION_","Cannot connect to Asterisk Manager");
	}

    return wantarray() ? @$results : $results;
    

}


=item core_devices_add

    ARGS: extension - extension number (required)
          name - display name
          tech - sip (required)
          devinfo_dial -
          devinfo_channel -
          emergency_cid -          
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
sub core_devices_add {
    my ($self, $p, $editmode) = @_;
    $editmode ||=  0;
    
    my $astman = $self->{'astman'};
    my $tech = $p->{'tech'};

    my $extension = $p->{'extension'};
    my $name = $p->{'name'};
    my $dial = $p->{'devinfo_dial'};
    my $devinfo_channel = $p->{'devinfo_channel'} || '';

    my $emergency_cid = $p->{'emergency_cid'};

    my $devicetype = 'fixed';
    my ($deviceid, $deviceuser);
    $deviceid = $deviceuser = $extension;

    my $description = DB::db_quote($name);

    if ($tech eq '' || trim($tech) eq 'virtual') {
        return retOk("_OK_ADD_EXTENSION_","Device added.");
    }

    #ensure this id is not already in use
	my $devices = $self->core_devices_list();
    foreach my $item ( @$devices) {
        if($item->{'id'} == $deviceid) {
            return retErr("_ERR_ADD_EXTENSION_","Device already exists.");
        }
    }


    #unless defined, $dial is TECH/id
	if ( $dial eq '' ) {
		#zap is an exception
		if ( lc($tech) eq "zap" ) {
            my $zapchan = $devinfo_channel;
			$dial = 'ZAP/'.$zapchan;
		} else {
			$dial = uc($tech)."/".$deviceid;
		}
	}

    #insert into devices table
	my $sql="INSERT INTO devices (id,tech,dial,devicetype,user,description,emergency_cid) values (\"$deviceid\",\"$tech\",\"$dial\",\"$devicetype\",\"$deviceuser\",$description,".DB::db_quote($emergency_cid).")";

    my ($sth, $result) = DB::db_sql($sql);

    if(!$result) { return retErr("_ERR_ADD_EXTENSION_","Could not insert device. DB problem.");}


    #add details to astdb
	if ($astman) {		

        # if adding or editting a fixed device, user property should always be set
		if ($devicetype eq 'fixed' || !$editmode) {
			$astman->db_put("DEVICE",$deviceid."/user",$deviceuser);
		}
		# If changing from a fixed to an adhoc, the user property should be intialized
		# to the new default, not remain as the previous fixed user

        plog("EDITMODE (VALUE = $editmode)");

		if ($editmode) {

            plog("ENTER HERE ON EDIT (editmode=$editmode)");

			my $previous_type = $astman->db_get("DEVICE",$deviceid."/type");
			if ($previous_type eq 'fixed' && $devicetype eq 'adhoc') {
				$astman->db_put("DEVICE",$deviceid."/user",$deviceuser);
			}
		}

		$astman->db_put("DEVICE",$deviceid."/dial",$dial);
		$astman->db_put("DEVICE",$deviceid."/type",$devicetype);
		$astman->db_put("DEVICE",$deviceid."/default_user",$deviceuser);

		if($emergency_cid) {
			$astman->db_put("DEVICE",$deviceid."/emergency_cid",$emergency_cid);
		}

		if ($deviceuser ne "none") {
			my $existingdevices = $astman->db_get("AMPUSER",$deviceuser."/device");

			if (!$existingdevices) {
				$astman->db_put("AMPUSER",$deviceuser."/device",$deviceid);
			} else {
				my @existingdevices_array = split(/&/,$existingdevices);

                if(!(grep $_ eq $deviceid, @existingdevices_array)) {
					push(@existingdevices_array, $deviceid);
					$existingdevices = join('&',@existingdevices_array);
					$astman->db_put("AMPUSER",$deviceuser."/device",$existingdevices);
				}
			}
		}

    }else {
		plog("Cannot connect to Asterisk Manager with ".$self->{'amp_conf'}{"AMPMGRUSER"}."/".$self->{'amp_conf'}{"AMPMGRPASS"});
        return retErr("_ERR_ADD_EXTENSION_","Cannot connect to Asterisk Manager");
	}


    # create a voicemail symlink if needed
    my $thisUser = $self->core_users_get($deviceuser);
    my $vmcontext;
    
	if(defined($thisUser->{'voicemail'}) && ($thisUser->{'voicemail'} ne "novm")) {
		if(!$thisUser->{'voicemail'}) {
			$vmcontext = "default";
		} else {
			$vmcontext = $thisUser->{'voicemail'};
		}

		#voicemail symlink
        cmd_exec("rm -f /var/spool/asterisk/voicemail/device/".$deviceid);
        cmd_exec("/bin/ln -s /var/spool/asterisk/voicemail/".$vmcontext."/".$deviceuser."/ /var/spool/asterisk/voicemail/device/".$deviceid);
	}

    
	#take care of sip/iax/zap config    

	my $funct = "core_devices_add_".lc($tech);
	my $func_res = $self->$funct($p);

    return wantarray() ? %$func_res : $func_res;

}


=item core_devices_add_sip

    ARGS: extension - extension number (required)
          description -
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
sub core_devices_add_sip {
    my ($self, $p) = @_;
    my @sipfields;
    my $account = $p->{'extension'};    

    my $description = $p->{'description'} ? "$p->{'description'} <$account>" : "device <$account>" ;
    my $record_in = $p->{'record_in'} ? $p->{'record_in'} : 'On-Demand' ;
    my $record_out = $p->{'record_out'} ? $p->{'record_out'} : 'On-Demand' ;
    my $flag = 2;

    for my $k (keys %$p){

        if($k =~ m/^devinfo_(\w+)/){

            my $keyword = $1;
            my $data = $p->{"$k"};

            if ( $keyword eq 'dial' && $data eq '' ) {
                push(@sipfields, [$account, $keyword, 'SIP/'.$account, $flag++]);
            } elsif ($keyword eq 'mailbox' && $data eq '') {
                push(@sipfields, [$account, 'mailbox', $account.'@device', $flag++]);
            } else {
                push(@sipfields, [$account, $keyword, $data, $flag++]);
            }

        }

    }

	push(@sipfields, [$account,'account',$account,$flag++]);
    push(@sipfields, [$account,'callerid',$description,$flag++]);


    push(@sipfields, [$account,'record_in',$record_in,$flag++]);
    push(@sipfields, [$account,'record_out',$record_out,$flag++]);

    

    my ($sth, $result) = DB::db_sql_multi('INSERT INTO sip (id, keyword, data, flags) values (?,?,?,?)',@sipfields);

    if(!$result) { return retErr("_ERR_ADD_EXTENSION_","Could not insert sip info. DB problem.");}

    return retOk("_OK_ADD_EXTENSION_","Device sip info ok.");


}


sub core_devices_add_iax2 {
    my ($self, $p) = @_;
    my @iaxfields;
    my $account = $p->{'extension'};

    my $description = $p->{'description'} ? "$p->{'description'} <$account>" : "device <$account>" ;
    my $record_in = $p->{'record_in'} ? $p->{'record_in'} : 'On-Demand' ;
    my $record_out = $p->{'record_out'} ? $p->{'record_out'} : 'On-Demand' ;
    
    my $flag = 2;

    for my $k (keys %$p){

        if($k =~ m/^devinfo_(\w+)/){

            my $keyword = $1;
            my $data = $p->{"$k"};

            if ( $keyword eq 'dial' && $data eq '' ) {
                push(@iaxfields, [$account, $keyword, 'IAX2/'.$account, $flag++]);
            } elsif ($keyword eq 'mailbox' && $data eq '') {
                push(@iaxfields, [$account, 'mailbox', $account.'@device', $flag++]);
            } else {
                push(@iaxfields, [$account, $keyword, $data, $flag++]);
            }

        }

    }

    push(@iaxfields, [$account,'account',$account,$flag++]);
    push(@iaxfields, [$account,'callerid',$description,$flag++]);    
    
	# Asterisk treats no caller ID from an IAX device as 'hide callerid', and ignores the caller ID
	# set in iax.conf. As we rely on this for pretty much everything, we need to specify the
	# callerid as a variable which gets picked up in macro-callerid.
	# Ref - http://bugs.digium.com/view.php?id=456
    push(@iaxfields, [$account,'setvar',DB::db_quote("REALCALLERIDNUM=$account"),$flag++]);
	
    push(@iaxfields, [$account,'record_in',$record_in,$flag++]);
    push(@iaxfields, [$account,'record_out',$record_out,$flag++]);

    my ($sth, $result) = DB::db_sql_multi('INSERT INTO iax (id, keyword, data, flags) values (?,?,?,?)',@iaxfields);

    if(!$result) { return retErr("_ERR_ADD_EXTENSION_","Could not insert iax info. DB problem.");}

    return retOk("_OK_ADD_EXTENSION_","Device iax info ok.");
}



sub core_devices_add_zap {
    my ($self, $p) = @_;
    my @zapfields;
    my $account = $p->{'extension'};

    my $zapchan = $p->{'devinfo_channel'} || $p->{'channel'};

    my $description = $p->{'description'} ? "$p->{'description'} <$account>" : "device <$account>" ;
    my $record_in = $p->{'record_in'} ? $p->{'record_in'} : 'On-Demand' ;
    my $record_out = $p->{'record_out'} ? $p->{'record_out'} : 'On-Demand' ;

    for my $k (keys %$p){

        if($k =~ m/^devinfo_(\w+)/){

            my $keyword = $1;
            my $data = $p->{"$k"};

            if ( $keyword eq 'dial' && $data eq '' ) {
                push(@zapfields, [$account, $keyword, 'ZAP/'.$zapchan]);
            } elsif ($keyword eq 'mailbox' && $data eq '') {
                push(@zapfields, [$account, 'mailbox', $account.'@device']);
            } else {
                push(@zapfields, [$account, $keyword, $data]);
            }

        }

    }

    push(@zapfields, [$account,'account',$account]);
    push(@zapfields, [$account,'callerid',$description]);

    push(@zapfields, [$account,'record_in',$record_in]);
    push(@zapfields, [$account,'record_out',$record_out]);

    my ($sth, $result) = DB::db_sql_multi('INSERT INTO zap (id, keyword, data) values (?,?,?)',@zapfields);

    if(!$result) { return retErr("_ERR_ADD_EXTENSION_","Could not insert zap info. DB problem.");}

    return retOk("_OK_ADD_EXTENSION_","Device zap info ok.");
}

sub core_devices_del{
    my ($self, $extension, $editmode) = @_;
    my $astman = $self->{'astman'};
    $editmode ||= 0;
    print STDERR "CORE DEVICES DEL ($extension)";
    #get all info about device
    my $devinfo = $self->core_devices_get($extension);
    if (!$devinfo) {
        return 1;
    }


    #delete details to astdb
	if ($astman) {
        # If a user was selected, remove this device from the user
		my $deviceuser = $astman->db_get("DEVICE",$extension."/user");
        print STDERR "\n\nDEVICE USER ($deviceuser) \n\n";
        if ($deviceuser ne "none") {
			# Remove the device record from the user's device list
			my $userdevices = $astman->db_get("AMPUSER",$deviceuser."/device");
            print STDERR "\n\nUSER DEVICES ($userdevices) \n\n";

			# We need to remove just this user and leave the rest alone
            plog("userdevices",Dumper($userdevices));
            $userdevices =~ s/&?\b$extension\b&?//gi;
            plog("userdevices",Dumper($userdevices));

            if(!$userdevices){
                $astman->db_del("AMPUSER",$deviceuser."/device");
			} else {
                $astman->db_put("AMPUSER",$deviceuser."/device",$userdevices);
			}
		}

        plog("EDITMODE core_devices_del (VALUE = $editmode)");
		
        if (! $editmode) {

            plog("ENTER HERE ON NOT EDIT (editmode=$editmode)");

			$astman->db_del("DEVICE",$extension."/dial");
            $astman->db_del("DEVICE",$extension."/type");
            $astman->db_del("DEVICE",$extension."/user");
            $astman->db_del("DEVICE",$extension."/default_user");
            $astman->db_del("DEVICE",$extension."/emergency_cid");
		}

        #delete from devices table
		my $sql="DELETE FROM devices WHERE id = \"$extension\"";
        my ($sth, $result) = DB::db_sql($sql);

        #voicemail symlink
        my ($e,$output) = cmd_exec("rm -f /var/spool/asterisk/voicemail/device/".$extension);

    }
    else {
		plog("Cannot connect to Asterisk Manager with ".$self->{'amp_conf'}{"AMPMGRUSER"}."/".$self->{'amp_conf'}{"AMPMGRPASS"});
        return retErr("_ERR_DEL_EXTENSION_","Cannot connect to Asterisk Manager with ".$self->{'amp_conf'}{"AMPMGRUSER"}."/".$self->{'amp_conf'}{"AMPMGRPASS"});
	}

    #take care of sip/iax/zap config
    my $funct = "core_devices_del_".lc($devinfo->{'tech'});
    my $sub_exists = $self->can($funct);

    if(!$sub_exists){ plog("Method $funct does not exist");}
    else{
        $self->$funct($extension);
    }

    return 1;       
}



sub core_devices_get {
    my ($self,$extension) = @_;
    my $results = {};
    print STDERR "CORE DEVICES GET ($extension)";
    
	my $sql = "SELECT * FROM devices WHERE id = '$extension'";
    my ($sth, $result) = DB::db_sql($sql);
    $results = $sth->fetchrow_hashref;

    #take care of sip/iax/zap config
    my $funct = "core_devices_get_".lc($results->{'tech'});
    my $sub_exists = $self->can($funct);

    if(!$sub_exists){ plog("Method $funct does not exist");}
    else{

        if($results->{'tech'}){
            my $devtech = $self->$funct($extension);
            @$results{keys %$devtech} = values %$devtech;
        }
    }
    return wantarray() ? @$results : $results;
}


sub core_devices_get_sip {
    my ($self,$extension) = @_;
    my $results = ();
    my $sql = "SELECT keyword,data FROM sip WHERE id = '$extension'";
    my ($sth, $result) = DB::db_sql($sql);
    while ( (my ($keword,$data)) = $sth->fetchrow_array() )
    {
        $results->{$keword} = $data;
    }
    return wantarray() ? @$results : $results;
}

sub core_devices_del_sip {
    my ($self,$extension) = @_;
    my $results = ();
    my $sql = "DELETE FROM sip WHERE id = '$extension'";
    my ($sth, $result) = DB::db_sql($sql);
}


sub core_devices_get_iax2 {
    my ($self,$extension) = @_;
    my $results = ();
    my $sql = "SELECT keyword,data FROM iax WHERE id = '$extension'";
    my ($sth, $result) = DB::db_sql($sql);
    while ( (my ($keword,$data)) = $sth->fetchrow_array() )
    {
        $results->{$keword} = $data;
    }
    return wantarray() ? @$results : $results;
}

sub core_devices_del_iax2 {
    my ($self,$extension) = @_;
    my $results = ();
    my $sql = "DELETE FROM iax WHERE id = '$extension'";
    my ($sth, $result) = DB::db_sql($sql);
}



sub core_devices_get_zap {
    my ($self,$extension) = @_;
    my $results = ();
    my $sql = "SELECT keyword,data FROM zap WHERE id = '$extension'";
    my ($sth, $result) = DB::db_sql($sql);
    while ( (my ($keword,$data)) = $sth->fetchrow_array() )
    {
        $results->{$keword} = $data;
    }
    return wantarray() ? @$results : $results;
}

sub core_devices_del_zap {
    my ($self,$extension) = @_;
    my $results = ();
    my $sql = "DELETE FROM zap WHERE id = '$extension'";
    my ($sth, $result) = DB::db_sql($sql);
}


sub core_users_edit {
    my ($self, $p) = @_;
    my $astman = $self->{'astman'};    
    my $extension = $p->{'extension'};
    my $vmcontext = $p->{'vmcontext'};
    my $sipname = $p->{'sipname'};

    my $newdid = trim($p->{'newdid'});   
    my $newdidcid = $p->{'newdidcid'};   


    my $current_vmcontext;
    my $new_vmcontext;
    my $ud;
    my $vars = $p;


    #If we are editing, we need to remember existing user<->device mapping, so we can delete and re-add
	if ($astman) {
		$ud = $astman->db_get("AMPUSER",$extension."/device");
		$current_vmcontext = $astman->db_get("AMPUSER",$extension."/voicemail");
		$new_vmcontext = $vmcontext || 'novm';
		$vars->{'device'} = $ud;
	} else {

        plog("Cannot connect to Asterisk Manager with ".$self->{'amp_conf'}{"AMPMGRUSER"}."/".$self->{'amp_conf'}{"AMPMGRPASS"});
        return retErr("_ERR_EDIT_EXTENSION_","Cannot connect to Asterisk Manager");
	}


    $newdid =~ s/[^0-9._XxNnZz\[\]\-\+]//;

    if($newdidcid !~ m/^priv|^block|^unknown|^restrict|^unavail|^anonym/i){
        $newdidcid =~ s/[^0-9._XxNnZz\[\]\-\+]//;
    }

	if ($newdid || $newdidcid) {
		my $existing = $self->core_did_get($newdid, $newdidcid);

		if($existing) {            
			return retErr("_ERR_ADD_EXTENSION_","A route with this DID/CID (".$existing->{'extension'}."/".$existing->{'cidnum'}.") already exists.");
		}
	}   

    #delete and re-add
	if ($self->core_sipname_check($sipname,$extension)) {
        my $del_user = $self->core_users_del($extension, 1);
        if(!$del_user || isError($del_user)) {
            return retErr("_ERR_EDIT_EXTENSION_","Could not edit extension.");
        }        

        my $added_user = $self->core_users_add($vars, 1);
        if(!$added_user || isError($added_user)) {
            #print STDERR "\n\n\n\nMOSTRA ERRORORORORO\n\n\n";
            #return retErr("_ERR_EDIT_EXTENSION_","Could not edit extension.");
            return wantarray() ? %$added_user : $added_user;
        }       

		# If the vmcontext has changed, we need to change all the links. In extension mode, the link
		# to the current fixed device will get changed, but none others will
		#
		if ($current_vmcontext ne $new_vmcontext) {

            my @user_devices = split(/&/,$ud);
            foreach my $user_device ( @user_devices) {                
                cmd_exec("rm -f /var/spool/asterisk/voicemail/device/".$user_device);
                if ($new_vmcontext ne 'novm') {
					cmd_exec("/bin/ln -s /var/spool/asterisk/voicemail/".$new_vmcontext."/".$extension."/ /var/spool/asterisk/voicemail/device/".$user_device);
				}
            }           			
		}
	}

    return 1;


}


sub core_users_cleanastdb{
    my ($self, $extension) = @_;
    my $astman = $self->{'astman'};
    # This is called to remove any ASTDB traces of the user after a deletion. Otherwise,
	# call forwarding, call waiting settings could hang around and bite someone if they
	# recycle an extension.

    if ($astman) {
		$astman->db_del("CW",$extension);
		$astman->db_del("CF",$extension);
		$astman->db_del("CFB",$extension);
		$astman->db_del("CFU",$extension);
    }
}

sub core_users_del{
    my ($self,$extension, $editmode) = @_;
    my $astman = $self->{'astman'};
    $editmode ||= 0;

	#delete from users extension table
	my $sql="DELETE FROM users WHERE extension = $extension";
    my ($sth, $result) = DB::db_sql($sql);

    if(!$result){ return retErr("_ERR_DEL_EXTENSION_","Can't delete extension. DB problem. $DBI::errstr ."); }    

	#delete details to astdb
	if($astman){
        $astman->db_del("AMPUSER",$extension."/screen");
        if(!$editmode) {$astman->db_deltree("AMPUSER/".$extension);}
	}

    return 1;
}


sub core_sipname_check{
    my ($self,$sipname,$extension) = @_;
    
    if(!trim($sipname)) {return 1;}
    
    my $sql = "SELECT sipname FROM users WHERE sipname = '$sipname' AND extension != '$extension'";    
    my ($sth, $result) = DB::db_sql($sql);

    my $res = $sth->fetchrow_hashref;
    
    if($res && (trim($res->{'sipname'}) eq $sipname)) {return 0;}
    else {return 1;}

}

sub core_check_extension {
    my ($self, $extension) = @_;
    my $results = {};

    my $sql = "SELECT extension, name FROM users ORDER BY CAST(extension AS UNSIGNED)";
    my ($sth, $result) = DB::db_sql($sql);
    
    my $get_res = $sth->fetchall_arrayref({});
    foreach my $item (@$get_res){        
        $results->{$item->{'extension'}} = {'description' => $item->{'name'}};        
    }
    
    return wantarray() ? %$results : $results;
    
}


sub core_devices_list {
    my $self = shift;
	my $sql = 'SELECT id,description FROM devices ORDER BY id';

    my ($sth, $result) = DB::db_sql($sql);
    my $get_res = $sth->fetchall_arrayref({});

    return wantarray() ? @$get_res : $get_res;
}



#
#   TRUNKS
#
#


sub core_trunks_addtrunk {
    my ($self,$p) = @_;
    $self->core_trunks_process('add', $p);
}

sub core_trunks_edittrunk {
    my ($self,$p) = @_;
    $self->core_trunks_process('edit', $p);
}

sub core_trunks_deltrunk {
    my ($self, $trunknum) = @_;
    
    $self->core_trunks_del($trunknum);
    $self->core_trunks_deleteDialRules($trunknum);
    $self->core_routing_trunk_del($trunknum);
    $self->need_reload();

    return retOk("_OK_DEL_TRUNK_","Deleted successfully.");
}

sub core_trunks_deleteDialRules {
    my ($self, $trunknum) = @_;
    DB::db_sql("DELETE FROM `trunks_dialpatterns` WHERE `trunkid` = $trunknum");
}

sub core_routing_trunk_del {	
    my ($self, $trunknum) = @_;
    my $sql = "DELETE FROM `extensions` WHERE `application` = 'Macro' AND `context` LIKE 'outrt-%' AND `args` LIKE 'dialout-%,$trunknum,%'";
    DB::db_sql($sql);
}


sub core_trunks_get {
    my ($self,$trunknum) = @_;
    my $trunkdetails = $self->core_trunks_getDetails($trunknum);


    $trunkdetails->{'peerdetails'} = $self->core_trunks_getTrunkPeerDetails($trunknum);
    $trunkdetails->{'userconfig'} = $self->core_trunks_getTrunkUserConfig($trunknum);
    $trunkdetails->{'register'} = $self->core_trunks_getTrunkRegister($trunknum);


    my $dialrules = $self->core_trunks_getDialRules($trunknum);
    if($dialrules) {$trunkdetails->{'dialrules'} = join("\n",@$dialrules);}

    return wantarray() ? %$trunkdetails : $trunkdetails;

}


sub core_trunks_process {
    my ($self, $action, $p) = @_;
    my $dialrules = $p->{'dialrules'};
    
    $p->{'disabletrunk'} ||= 'off';

    my @dialrules_arr = split(/\n/,$dialrules);
    my $line;
    my $index = 0;
    foreach $line (@dialrules_arr) {
        $line = trim($line);
        if($line eq "") {
            delete $dialrules_arr[$index];
        }
        $index++;
    }

    # check for duplicates, and re-sequence
    my %seen = ();
    my @uniq_dialrules;
    foreach my $item (@dialrules_arr){
        push(@uniq_dialrules, $item) unless $seen{$item}++;
    }

    for($action){
        if(/add/){
            my $trunknum = $self->core_trunks_add($p);
            $self->core_trunks_addDialRules($trunknum, \@uniq_dialrules);
            $self->need_reload();
        }elsif(/edit/) {
                my $trunknum = $p->{'trunknum'};
                $self->core_trunks_edit($p);
                $self->core_trunks_addDialRules($trunknum, \@uniq_dialrules);
                $self->need_reload();

            }

    }

}


sub core_trunks_add{
    my ($self,$p) = @_;

    # find the next available ID
	my $trunknum = 1;

    my @trunks_list = $self->core_trunks_list();
    my @trunk_hash;
    foreach my $trunk (@trunks_list) {
        $trunknum = @$trunk[0];
        $trunknum =~ s/^OUT_//;
        push(@trunk_hash,$trunknum);
    }

	sort(@trunk_hash);

	$trunknum = 1;
	foreach my $trunk_id (@trunk_hash) {
		if ($trunk_id != $trunknum) {
			last;
		}
		$trunknum++;
	}

    $p->{'trunknum'} = $trunknum;

    $self->core_trunks_backend_add($p);

    return $trunknum;

}

sub core_trunks_edit{
    my ($self,$p) = @_;
    my $trunknum = $p->{'trunknum'};

    plog("TRUNK EDIT($trunknum)");

    my $tech = $self->core_trunks_getTrunkTech($trunknum);

    if (!$tech) {
        return 0;
    }

    $self->core_trunks_del($trunknum, $tech);
	$self->core_trunks_backend_add($p);
}


sub core_trunks_backend_add{
    my ($self,$p) = @_;
    my $tech = $p->{'tech'};
    my $trunknum = $p->{'trunknum'};
    my $peerdetails = $p->{'peerdetails'};
    my $channelid = $p->{'channelid'};
    my $disabletrunk = $p->{'disabletrunk'};
    my $usercontext = $p->{'usercontext'};
    my $userconfig = $p->{'userconfig'};
    my $register = $p->{'register'};
    my $name = $p->{'trunk_name'};
    my $outcid = $p->{'outcid'};
    my $keepcid = $p->{'keepcid'};
    my $maxchans = $p->{'maxchans'};
    my $failtrunk = $p->{'failtrunk'};
    my $dialoutprefix = $p->{'dialoutprefix'};
    my $provider = $p->{'provider'};


    my $disable_flag = ($disabletrunk eq "on") ? 1 : 0;

    for(lc($tech)) {
        if(/iax/ || /iax2/) {
                print STDERR "\n\nCORETRUNKSADDIAX ".$_;
                $self->core_trunks_addSipOrIax($peerdetails,'iax',$channelid,$trunknum,$disable_flag,'peer');
                if ($usercontext ne ""){
                    $self->core_trunks_addSipOrIax($userconfig,'iax',$usercontext,$trunknum,$disable_flag,'user');
                }
                if ($register ne ""){
                    $self->core_trunks_addRegister($trunknum,'iax',$register,$disable_flag);
                }
            }
            elsif(/sip/) {
                    print STDERR "\n\nCORE TRUNKS ADD SIP ".$_;
                    $self->core_trunks_addSipOrIax($peerdetails,'sip',$channelid,$trunknum,$disable_flag,'peer');
                    if ($usercontext ne ""){
                        $self->core_trunks_addSipOrIax($userconfig,'sip',$usercontext,$trunknum,$disable_flag,'user');
                    }
                    if ($register ne ""){
                        $self->core_trunks_addRegister($trunknum,'sip',$register,$disable_flag);
                    }

                }
    }


    my $sql = "
		INSERT INTO `trunks`
		(`trunkid`, `name`, `tech`, `outcid`, `keepcid`, `maxchans`, `failscript`, `dialoutprefix`, `channelid`, `usercontext`, `provider`, `disabled`)
		VALUES (
			'$trunknum',
			".DB::db_quote($name).",
			".DB::db_quote($tech).",
			".DB::db_quote($outcid).",
			".DB::db_quote($keepcid).",
			".DB::db_quote($maxchans).",
			".DB::db_quote($failtrunk).",
			".DB::db_quote($dialoutprefix).",
			".DB::db_quote($channelid).",
			".DB::db_quote($usercontext).",
			".DB::db_quote($provider).",
			".DB::db_quote($disabletrunk)."
		)";
    DB::db_sql($sql);

}



sub core_trunks_addDialRules {
    my ($self, $trunknum, $dialrules) = @_;
    my @rules_arr;
    my $i = 1;
    foreach my $rule (@$dialrules) {
        push(@rules_arr, [$rule,$i]);
        $i++;
    }

    my $sql = "DELETE FROM trunks_dialpatterns WHERE trunkid = $trunknum";
    DB::db_sql($sql);

    my ($sth, $result) = DB::db_sql_multi("INSERT INTO `trunks_dialpatterns` (trunkid, rule, seq) VALUES ($trunknum,?,?)",@rules_arr);
    if(!$result) { return retErr("_ERR_ADD_DIAL_RULES_","Could not insert trunks_dialpatterns info. DB problem.");}
}


sub core_trunks_getDialRules {
    my ($self, $trunknum) = @_;
    my $conf = core_trunks_readDialRulesFile();

    if ($conf->{"trunk-".$trunknum}) {
		return $conf->{"trunk-".$trunknum};
	}
    
    return 0;

}

sub core_trunks_readDialRulesFile {
    my $self = shift;
    
    my ($sth, $result) = DB::db_sql("SELECT trunkid, rule FROM trunks_dialpatterns ORDER BY trunkid, seq");
    my $patterns = $sth->fetchall_arrayref({});
	my $trunk_num;
    my $rule_num = 0;
    my $rule_hash = {};

	foreach my $pattern ( @$patterns) {
        if($pattern->{'trunkid'} != $trunk_num) {
            $rule_num = 1;
            $trunk_num = $pattern->{'trunkid'};
        }
        
        #$rule_hash->{'trunk-'.$pattern->{'trunkid'}}{'rule'.$rule_num++} = $pattern->{'rule'};
        push(@{$rule_hash->{'trunk-'.$pattern->{'trunkid'}}},$pattern->{'rule'});
        


    }

    return $rule_hash;

}

sub core_trunks_addRegister {
    my ($self, $trunknum, $tech, $reg, $disable_flag) = @_;
    $disable_flag ||=  0;

    my $sql = "INSERT INTO $tech (id, keyword, data, flags) values ('tr-reg-$trunknum','register','$reg','$disable_flag')";
    DB::db_sql($sql);

}

=item core_trunks_addSipOrIax

    add trunk info to sip or iax table
=cut
sub core_trunks_addSipOrIax {
    my ($self, $config, $table, $channelid, $trunknum, $disable_flag, $type) = @_;
    $disable_flag ||=  0;
    $type ||=  'peer';

    my %confitem;
    my @dbconfitem;

    for($type) {
        if(/peer/) { $trunknum = 'tr-peer-'.$trunknum; }
        elsif(/user/) { $trunknum = 'tr-user-'.$trunknum; }
	}

    $confitem{'account'} = $channelid;

    plog("CONIFG ",Dumper($config));

    my @lines = split(/\n/,$config);
    my @keyval;
    my $line;
    foreach $line (@lines) {
        #$line = trim($line);
        @keyval = split(/\=/,$line);
        if(scalar(@keyval) > 1) {
            if($confitem{$keyval[0]}) { $confitem{$keyval[0]} .= "&".$keyval[1]; }
            else { $confitem{$keyval[0]} = $keyval[1] }
        }
    }

    # rember 1=disabled so we start at 2 (1 + the first 1)
    my $seq = 1;
    for my $k (keys %confitem){
        $seq = ($disable_flag == 1) ? 1 : $seq+1;
        push(@dbconfitem, [$k,$confitem{$k},$seq]);
    }

    plog("peerdetails ",Dumper(@dbconfitem));

    my ($sth, $result) = DB::db_sql_multi("INSERT INTO $table (id, keyword, data, flags) values ('$trunknum',?,?,?)",@dbconfitem);
    if(!$result) { return retErr("_ERR_ADD_TRUNK_","Could not insert $table info. DB problem.");}
}



sub core_trunks_del {
    my($self, $trunknum, $tech) = @_;

    if(!$tech) {
        $tech = $self->core_trunks_getTrunkTech($trunknum);
    }

    my $tech_lc = lc($tech);

    if($tech_lc eq 'iax2' || $tech_lc eq 'iax' || $tech_lc eq 'sip') {

        if($tech_lc eq 'iax2') {$tech = 'iax';}

        DB::db_sql("DELETE FROM $tech WHERE id IN ('tr-peer-$trunknum', 'tr-user-$trunknum', 'tr-reg-$trunknum')");
    }

    DB::db_sql("DELETE FROM trunks WHERE trunkid = '$trunknum'");
}


=item
    get unique trunks
=cut
sub core_trunks_getDetails {
    my ($self, $trunkid) = @_;
    my ($sql, $sth, $result, $trunk);

    if($trunkid) {
		$sql = "SELECT * FROM trunks WHERE trunkid = '$trunkid'";
        ($sth, $result) = DB::db_sql($sql);
        $trunk = $sth->fetchrow_hashref;
        my $tech = lc($trunk->{'tech'});

        if($tech eq "iax2") { $trunk->{'tech'} = 'iax'; }

	} else {
		$sql = "SELECT * FROM trunks ORDER BY tech, name";
        ($sth, $result) = DB::db_sql($sql);
        $trunk = $sth->fetchall_arrayref({});
	}

    return $trunk;
}


sub core_trunks_getTrunkPeerDetails {
    my ($self, $trunknum) = @_;

    my $tech = $self->core_trunks_getTrunkTech($trunknum);
    if ($tech eq "zap" || $tech eq "") {return "";} # zap has no details

    my ($sth, $result) = DB::db_sql("SELECT keyword,data FROM $tech WHERE `id` = 'tr-peer-$trunknum' ORDER BY flags, keyword DESC");
    my $results = $sth->fetchall_arrayref({});

	my $confdetail;

	foreach my $item ( @$results) {
        if($item->{'keyword'} ne 'account') {
            $confdetail .= $item->{'keyword'} .'='. $item->{'data'} . "\n";
        }
    }

    return $confdetail;
}


sub core_trunks_getTrunkUserConfig {
    my ($self, $trunknum) = @_;

    my $tech = $self->core_trunks_getTrunkTech($trunknum);
    if ($tech eq "zap" || $tech eq "") {return "";} # zap has no details

    my ($sth, $result) = DB::db_sql("SELECT keyword,data FROM $tech WHERE `id` = 'tr-user-$trunknum' ORDER BY flags, keyword DESC");
    my $results = $sth->fetchall_arrayref({});

	my $confdetail;

	foreach my $item ( @$results) {
        if($item->{'keyword'} ne 'account') {
            $confdetail .= $item->{'keyword'} .'='. $item->{'data'} . "\n";
        }
    }

    return $confdetail;
}


sub core_trunks_getTrunkRegister {
    my ($self, $trunknum) = @_;

    my $tech = $self->core_trunks_getTrunkTech($trunknum);
    if ($tech eq "zap" || $tech eq "") {return "";} # zap has no details

    my ($sth, $result) = DB::db_sql("SELECT keyword,data FROM $tech WHERE `id` = 'tr-reg-$trunknum'");
    my $results = $sth->fetchall_arrayref({});

	my $register;

	foreach my $item ( @$results) {
        $register = $item->{'data'};
    }

    return $register;

    

}


=item core_trunks_list
    ARGS: assoc - 0|1 returns hash or array

=cut
sub core_trunks_list {
    my ($self, $assoc) = @_;
    $assoc ||= 0;
    my $dialstring;

	my $sql = "SELECT trunkid, tech, channelid, disabled FROM trunks ORDER BY trunkid";
    my ($sth, $result) = DB::db_sql($sql);
    my $trunks = $sth->fetchall_arrayref({});

	my @unique_trunks = ();

	foreach my $trunk ( @$trunks) {
		my $trunk_id = "OUT_".$trunk->{'trunkid'};
        my $disabled = $trunk->{'disabled'};
		my $tech = uc($trunk->{'tech'});

        for($tech) {
            if(/IAX/) { $dialstring = 'IAX2/'.$trunk->{'channelid'}; }
            elsif(/CUSTOM/) {$dialstring = 'AMP:'.$trunk->{'channelid'};}
                else {$dialstring = $tech.'/'.$trunk->{'channelid'};}
        }

        push(@unique_trunks,[$trunk_id, $dialstring, $disabled]);
	}

	if ($assoc) {        
		my @trunkinfo = ();
		foreach my $trunk (@unique_trunks) {
            my ($tech,$name) = split(/\//,$trunk->[1]);
            push(@trunkinfo,{name => $name,tech=>$tech, disabled => $trunk->[2], globalvar => $trunk->[0]});
		}

        return wantarray() ? @trunkinfo : \@trunkinfo;
	} else {        
        return wantarray() ? @unique_trunks : \@unique_trunks;
	}
}


sub core_trunks_getTrunkTech {
    my ($self, $trunknum) = @_;

    my $sql = "SELECT tech FROM trunks WHERE trunkid = $trunknum";
    my ($sth, $result) = DB::db_sql($sql);
    my $result = $sth->fetchrow_hashref;

    if (!$result) {
		return 0;
	}
    my $tech = $result->{'tech'};
    $tech = lc($tech);

    if($tech eq "iax2") {
        $tech = "iax";

    }

    return $tech;

}


#
#   OUTBOUND ROUTES
#
#

sub core_routing_addroute {
    my ($self,$p) = @_;
    $self->core_routing_process('add', $p);
}

sub core_routing_editroute {
    my ($self, $p) = @_;
    $self->core_routing_process('edit', $p);
    return retOk("_OK_EDIT_OUTBOUNDROUTE_","Edited successfully.");
}

sub core_routing_delroute {
    my ($self, $route) = @_;
    $self->core_routing_del($route);
    
    # re-order the routes to make sure that there are no skipped numbers.
    # example if we have 001-test1, 002-test2, and 003-test3 then delete 002-test2
    # we do not want to have our routes as 001-test1, 003-test3 we need to reorder them
    # so we are left with 001-test1, 002-test3
    my $routepriority = $self->core_routing_getroutenames();
	$routepriority = $self->core_routing_setroutepriority($routepriority);
	$self->need_reload();
    return retOk("_OK_DEL_OUTBOUNDROUTE_","Deleted successfully.");
    
}

sub core_routing_get {
    my ($self,$name) = @_;
    my $routingdetails;

    $routingdetails->{'trunkpriority'} = $self->core_routing_getroutetrunks($name);
    $routingdetails->{'routepass'} = $self->core_routing_getroutepassword($name);    
    
    my $dialpattern = $self->core_routing_getroutepatterns($name);
    if($dialpattern) { $routingdetails->{'dialpattern'} = join("\n",@$dialpattern); }

    $routingdetails->{'emergency'} = $self->core_routing_getrouteemergency($name);
    $routingdetails->{'intracompany'} = $self->core_routing_getrouteintracompany($name);
    $routingdetails->{'mohsilence'} = $self->core_routing_getroutemohsilence($name);


    my $routecid_array = $self->core_routing_getroutecid($name);
    $routingdetails->{'routecid'} = $routecid_array->{'routecid'};
    $routingdetails->{'routecid_mode'} = $routecid_array->{'routecid_mode'};

    
    

    return wantarray() ? %$routingdetails : $routingdetails;

}


sub core_routing_process {
    my ($self, $action, $p) = @_;
    my $dialpattern = $p->{'dialpattern'};
    my $trunkpriority = $p->{'trunkpriority'};

    if($trunkpriority && !ref($trunkpriority)) {
        my @arr_aux = ($trunkpriority);
        $trunkpriority = \@arr_aux;
    }    

    my @dialpattern_arr = split(/\n/,$dialpattern);
    my $line;
    my $index = 0;
    foreach $line (@dialpattern_arr) {
        $line = trim($line);
        if($line eq "") {
            delete $dialpattern_arr[$index];
        }
        $index++;
    }

    # check for duplicates, and re-sequence
    my %seen = ();
    my @uniq_dialpattern;
    foreach my $item (@dialpattern_arr){
        push(@uniq_dialpattern, $item) unless $seen{$item}++;
    }


    plog("DIAL PATTERN AS ARRAY ",Dumper(@uniq_dialpattern));

    $p->{'dialpattern'} = \@uniq_dialpattern;

    plog("DIAL PATTERN AS DIALPATTERN ARRAY ",Dumper($p->{'dialpattern'}));


    plog("PRIORITY ANTES ",Dumper($trunkpriority));
    
    my @trunkpriority_trim = grep { $_ } @$trunkpriority;
    $p->{'trunkpriority'} = \@trunkpriority_trim;
    
    plog("PRIORITY ",Dumper(@trunkpriority_trim));

    for($action){
        if(/add/){
            $self->core_routing_add('new', $p);
            #core_routing_add($routename, $dialpattern, $trunkpriority,"new", $routepass, $emergency, $intracompany, $mohsilence, $routecid, $routecid_mode);                      
            $self->need_reload();
        }elsif(/edit/) {
#                core_routing_edit($routename, $dialpattern, $trunkpriority, $routepass, $emergency, $intracompany, $mohsilence, $routecid, $routecid_mode);
                $self->core_routing_edit($p);
                $self->need_reload();

            }

    }

}


sub core_routing_add{
    my ($self, $method, $p) = @_;
    my $routename = $p->{'routename'};
    my $dialpattern = $p->{'dialpattern'};
    my $trunkpriority = $p->{'trunkpriority'};
    my $emergency = $p->{'emergency'};
    my $intracompany = $p->{'intracompany'};
    my $mohsilence = $p->{'mohsilence'};
    my $routecid = $p->{'routecid'};
    my $routecid_mode = $p->{'routecid_mode'};
    my $priority;
    my $routepass = $p->{'routepass'};
    my $exten;
    my $sql;
    my ($sth, $result);

    # Make sure only valid characters are there
	#
    $routename =~ s/[^a-zA-Z0-9_\-]//;

    #Retrieve each trunk tech for later lookup
    my %trunktech;
	my $result = $self->core_trunks_list(1);

    plog("TRUNK LISTS ",Dumper($result));

    #foreach my $tr (keys %$result) {

    #    $trunktech{$tr->{'globalvar'}} = $tr->{'tech'};
	#}

    foreach my $tr (@$result) {

        $trunktech{$tr->{'globalvar'}} = $tr->{'tech'};
	}
    
    #my %arr = map { $i++ => $_ } @$trunkpriority;


    if($method eq 'new') {
        ($sth, $result) = DB::db_sql("select DISTINCT context FROM extensions WHERE context LIKE 'outrt-%' ORDER BY context");
        my $routepriority = $sth->fetchall_arrayref({});
        print STDERR "TAMANHO (".scalar(@$routepriority).")";
        
        my $order = $self->core_routing_setroutepriorityvalue(scalar @$routepriority);
        $routename = sprintf ("%s-%s",$order,$routename);
        print STDERR "\n\nNAME ($routename)\n";
    }

    plog("DIAL PATTERN AS ARRAY ",Dumper($dialpattern));

    foreach my $pattern (@$dialpattern) {

        print STDERR "\nPattern ($pattern) \n";
        if($pattern =~ m/\|/){
            print STDERR "\nPattern encontrou | \n";
            

            
            $pattern =~ s/(\[[^\]]*\])/X/;
            my $pos = index($pattern,'|');
            
            print STDERR "PATTERN POS ($pos)";                        

			#  we have a | meaning to not pass the digits on
			#  (ie, 9|NXXXXXX should use the pattern _9NXXXXXX but only pass NXXXXXX, not the leading 9)			

            $pattern =~ s/\|//; # remove all |'s
			$exten = "EXTEN:".$pos; 
        }
        else{
            # we pass the full dialed number as-is
			$exten = "EXTEN";
        }


        if ($pattern !~ m/^[0-9*]+$/) {
			# note # is not here, as asterisk doesn't recoginize it as a normal digit, thus it requires _ pattern matching
			# it's not strictly digits, so it must have patterns, so prepend a _
			$pattern = "_".$pattern;
		}

        my $startpriority;
        # 1st priority is emergency dialing variable (if set)
		if($emergency) {
			$startpriority = 1;
			$sql = "INSERT INTO extensions (context, extension, priority, application, args, descr) VALUES ";
			$sql .= "('outrt-".$routename."', ";
			$sql .= "'".$pattern."', ";
			$sql .= "'".$startpriority."', ";
			$sql .= "'SetVar', ";
			$sql .= "'EMERGENCYROUTE=YES', ";
			$sql .= "'Use Emergency CID for device')";
            DB::db_sql($sql);
		} else {
			$startpriority = 0;
		}


        # Next Priority (either first or second depending on above)
		if($intracompany) {
           $startpriority += 1;
           $sql = "INSERT INTO extensions (context, extension, priority, application, args, descr) VALUES ";
           $sql .= "('outrt-".$routename."', ";
           $sql .= "'".$pattern."', ";
           $sql .= "'".$startpriority."', ";
           $sql .= "'SetVar', ";
           $sql .= "'INTRACOMPANYROUTE=YES', ";
           $sql .= "'Preserve Intenal CID Info')";
           DB::db_sql($sql);
		}

        # Next Priority (either first, second or third depending on above)
		if($mohsilence && trim($mohsilence) ne 'default') {
           $startpriority += 1;
           $sql = "INSERT INTO extensions (context, extension, priority, application, args, descr) VALUES ";
           $sql .= "('outrt-".$routename."', ";
           $sql .= "'".$pattern."', ";
           $sql .= "'".$startpriority."', ";
           $sql .= "'SetVar', ";
           $sql .= "'MOHCLASS=".$mohsilence."', ";
           $sql .= "'Do not play moh on this route')";
           DB::db_sql($sql);
		}

        # Next Priority (either first, second or third depending on above)
		if($routecid) {
           my $mode = ($routecid_mode eq 'override_extension' ? 'ROUTECID':'EXTEN_ROUTE_CID');
           $startpriority += 1;
           $sql = "INSERT INTO extensions (context, extension, priority, application, args, descr) VALUES ";
           $sql .= "('outrt-".$routename."', ";
           $sql .= "'".$pattern."', ";
           $sql .= "'".$startpriority."', ";
           $sql .= "'SetVar', ";
           $sql .= "'$mode=".$routecid."', ";
           $sql .= "'Force this CID for this Route')";
           DB::db_sql($sql);
		}

        my $first_trunk = 1;
        
        
        #my $i = 0;
        #my %arr = map { $i++ => $_ } @$trunkpriority;

        $priority = 0;
        my $trunk;
        my $pass_str;
        for(my $i = 0; $i < scalar(@$trunkpriority); $i++ ) {
            print STDERR "\npriority index (".$trunkpriority->[$i].")\n";
            $trunk = $trunkpriority->[$i];
            $priority = $i;
            
            $priority += $startpriority;
            $priority += 1; # since arrays are 0-based, but we want priorities to start at 1

            $sql = "INSERT INTO extensions (context, extension, priority, application, args) VALUES ";
			$sql .= "('outrt-".$routename."', ";
			$sql .= "'".$pattern."', ";
			$sql .= "'".$priority."', ";
			$sql .= "'Macro', ";
            
			if ($first_trunk) {
				$pass_str = $routepass;
			}else {
				$pass_str = "";
            }

print STDERR "TRUNK TECH (".$trunktech{$trunk}.")\n";


            if ($trunktech{$trunk} eq "ENUM") {
				$sql .= "'dialout-enum,".substr($trunk,4).",\${".$exten."},".$pass_str."'"; # cut off OUT_ from $trunk
			} elsif ($trunktech{$trunk} eq "DUNDI") {
				$sql .= "'dialout-dundi,".substr($trunk,4).",\${".$exten."},".$pass_str."'"; # cut off OUT_ from $trunk
			} else {
				$sql .= "'dialout-trunk,".substr($trunk,4).",\${".$exten."},".$pass_str."'"; # cut off OUT_ from $trunk
			}
			$sql .= ")";
            

            ($sth, $result) = DB::db_sql($sql);            
            if(!$sth){ return retErr("_ERR_ADD_ROUTING_","Error processing add routing. DB problem."); }
            
            
			#To identify the first trunk in a pattern
			#so that passwords are in the first trunk in
			#each pattern
			$first_trunk = 0;

        }



        $priority += 1;
		$sql = "INSERT INTO extensions (context, extension, priority, application, args, descr) VALUES ";
		$sql .= "('outrt-".$routename."', ";
		$sql .= "'".$pattern."', ";
		$sql .= "'".$priority."', ";
		$sql .= "'Macro', ";
		$sql .= "'outisbusy', ";
		$sql .= "'No available circuits')";

		($sth, $result) = DB::db_sql($sql);
        if(!$sth){ return retErr("_ERR_ADD_ROUTING_","Error processing add routing. DB problem."); }		
    }


    # add an include=>outrt-$name  to [outbound-allroutes]:

	# we have to find the first available priority.. priority doesn't really matter for the include, but
	# there is a unique index on (context,extension,priority) so if we don't do this we can't put more than
	# one route in the outbound-allroutes context.
	$sql = "SELECT priority FROM extensions WHERE context = 'outbound-allroutes' AND extension = 'include'";
    ($sth, $result) = DB::db_sql($sql);

    my $results = $sth->fetchall_arrayref({});
    my @priorities;
    foreach my $row (@$results){
        push(@priorities,$row->{'priority'});        
    }
    

    @priorities = sort(@priorities);
    $priority = @priorities[-1]+1;    

    # $priority should now be the lowest available number

    $sql = "INSERT INTO extensions (context, extension, priority, application, args, descr, flags) VALUES ";
	$sql .= "('outbound-allroutes', ";
	$sql .= "'include', ";
	$sql .= "'".$priority."', ";
	$sql .= "'outrt-".$routename."', ";
	$sql .= "'', ";
	$sql .= "'', ";
	$sql .= "'2')";

print STDERR "SQL ( $sql )";

    ($sth, $result) = DB::db_sql($sql);
    if(!$sth){ return retErr("_ERR_ADD_ROUTING_","Error processing add routing. DB problem."); }    

}



sub core_routing_edit{    
    my ($self, $p) = @_;
    my $name = $p->{'routename'};    
    $self->core_routing_del($name);
    $self->core_routing_add('edit',$p);
}


sub core_routing_del {
    my ($self, $name) = @_;
    my $sql = "DELETE FROM extensions WHERE context = 'outrt-".$name."'";

    my ($sth, $result) = DB::db_sql($sql);
    if(!$sth){ return retErr("_ERR_DEL_ROUTING_","Error deleting routing. DB problem."); }

    $sql = "DELETE FROM extensions WHERE context = 'outbound-allroutes' AND application = 'outrt-".$name."' ";
    ($sth, $result) = DB::db_sql($sql);
	if(!$sth){ return retErr("_ERR_DEL_ROUTING_","Error deleting routing. DB problem."); }

	return $result;
}




sub core_routing_setroutepriority {
    my ($self, $routepriority) = @_;

    plog("VALUES PROIRITY BEFORE ",Dumper($routepriority));

    #$routepriority = values(%{$routepriority}); # resequence our numbers

    plog("VALUES PROIRITY AFTER ",Dumper($routepriority));

	my $counter = 0;
    my $order;
    my $sql;
    my ($sth, $result);
    
	foreach my $tresult (@$routepriority) {
		$order=$self->core_routing_setroutepriorityvalue($counter++);

        plog("DODO ",Dumper($tresult));

		$sql = sprintf("Update extensions set context='outrt-%s-%s' WHERE context='outrt-%s'",$order,substr($tresult->{'context'},4), $tresult->{'context'});
        
        ($sth, $result) = DB::db_sql($sql);

        print STDERR "SQLL $sql";
		#$result = $db->query($sql);
		#if(DB::IsError($result)) {
		#	die_freepbx($result->getMessage());
		#}
	}

    # Delete and readd the outbound-allroutes entries
	$sql = "delete from  extensions WHERE context='outbound-allroutes'";
	($sth, $result) = DB::db_sql($sql);

    $sql = "SELECT DISTINCT context FROM extensions WHERE context like 'outrt-%' ORDER BY context";
	($sth, $result) = DB::db_sql($sql);
    my $results = $sth->fetchall_arrayref({});
    #my @priorities;
    #foreach my $row (@$results){
    #    push(@priorities,$row->{'priority'});
    #}


    my $priority_loops = 1;
	foreach my $row (@$results) {
		$sql = "INSERT INTO extensions (context, extension, priority, application, args, descr, flags) VALUES ";
		$sql .= "('outbound-allroutes', ";
		$sql .= "'include', ";
		$sql .= "'".$priority_loops++."', ";
		$sql .= "'".$row->{'context'}."', ";
		$sql .= "'', ";
		$sql .= "'', ";
		$sql .= "'2')";

        ($sth, $result) = DB::db_sql($sql);
		
	}    
}


sub core_routing_setroutepriorityvalue {
    my ($self, $key) = @_;
    my $prefix;
    
    $key++;

    if ($key<10){
		$prefix = sprintf("00%d",$key);
	}elsif ((9<$key)&&($key<100)){
		$prefix = sprintf("0%d",$key);
	}elsif ($key>100){
		$prefix = sprintf("%d",$key);
    }
    
	return $prefix;
}


#get unique outbound route patterns for a given context
sub core_routing_getroutepatterns {
    my ($self, $route) = @_;
    my $pattern;
    my @patterns;
    my $args;
    my $sql = "SELECT extension, args FROM extensions WHERE context = 'outrt-".$route."' AND (args LIKE 'dialout-trunk%' OR args LIKE 'dialout-enum%' OR args LIKE 'dialout-dundi%') ORDER BY extension ";
    my ($sth, $result) = DB::db_sql($sql);
    if(!$sth){ return retErr("_ERR_GET_ROUTE_PATTERNS","Error get patterns. DB problem."); }

    my $results = $sth->fetchall_arrayref({});
    
    foreach my $row (@$results){
        #$results->{$item->{'extension'}} = {'description' => $item->{'name'}};
        print STDERR "ROW PATTERN (".$row->{'extension'}." )";
        $pattern = $row->{'extension'};
        $args = $row->{'args'};

        # remove leading _
        $pattern =~ s/^_//;

        if($args =~ m/{EXTEN:(\d+)}/){
            # this has a digit offset, we need to insert a |
            $pattern = substr($pattern,0,$1).'|'.substr($pattern,$1);
        }

            
        push(@patterns,$pattern);
        

#        if (preg_match("/{EXTEN:(\d+)}/", $row[1], $matches)) {
			#// this has a digit offset, we need to insert a |
			#$pattern = substr($pattern,0,$matches[1])."|".substr($pattern,$matches[1]);
		#}

print STDERR "ROW PATTERN (".$pattern." )";

    }
    
    my %seen = ();
    my @uniq_patterns;
    foreach my $item (@patterns){
        push(@uniq_patterns, $item) unless $seen{$item}++;
    }

    return wantarray() ? @uniq_patterns : \@uniq_patterns;
}






#get unique outbound route trunks for a given context
sub core_routing_getroutetrunks {
    my ($self, $route) = @_;
    my $args;
    my @trunks;
    my $sql = "SELECT DISTINCT args FROM extensions WHERE context = 'outrt-".$route."' AND (args LIKE 'dialout-trunk,%' OR args LIKE 'dialout-enum,%' OR args LIKE 'dialout-dundi,%') ORDER BY CAST(priority as UNSIGNED) ";
    my ($sth, $result) = DB::db_sql($sql);
    if(!$sth){ return retErr("_ERR_GET_ROUTE_TRUNKS","Error get patterns. DB problem."); }

    my $results = $sth->fetchall_arrayref({});

    foreach my $row (@$results){
        $args = $row->{'args'};        
        if($args =~ m/^dialout-(trunk|enum|dundi),(\d+)/){
            
            if(!@trunks || grep {$_ ne 'OUT_'.$2} @trunks) {
                print STDERR "ADICIONU ".'OUT_'.$2;
                push(@trunks, 'OUT_'.$2);
            }
        }
    }

    return wantarray() ? @trunks : \@trunks;
}



#get password for this route
sub core_routing_getroutepassword {
    my ($self, $route) = @_;
    my $password = "";
    my $args;    

    my $sql = "SELECT DISTINCT args FROM extensions WHERE context = 'outrt-".$route."' AND (args LIKE 'dialout-trunk,%' OR args LIKE 'dialout-enum,%' OR args LIKE 'dialout-dundi,%') ORDER BY CAST(priority as UNSIGNED) ";

    my ($sth, $result) = DB::db_sql($sql);
    if(!$sth){ return retErr("_ERR_GET_ROUTE_PASSWORD","Error get password. DB problem."); }

    my $results = $sth->fetchall_arrayref({});

    foreach my $row (@$results){      
        
        $args = $row->{'args'};
        if($args =~ m/^.*,.*,.*,(\d+|\/\S+)/){
            $password = $1;            
        }        
    }    
    return $password;
}


#get emergency state for this route
sub core_routing_getrouteemergency {
    my ($self, $route) = @_;
    my $emergency = "";
    my $sql = "SELECT DISTINCT args FROM extensions WHERE context = 'outrt-".$route."' AND (args LIKE 'EMERGENCYROUTE%') ";

    my ($sth, $result) = DB::db_sql($sql);
    if(!$sth){ return retErr("_ERR_GET_ROUTE_TRUNKS","Error get patterns. DB problem."); }
    
    my $results = $sth->fetchrow_hashref;
    my $args = $results->{'args'};

    if($args =~ m/^.*=(.*)/){
        $emergency = $1;
    }
    
    return $emergency;
}


#get intracompany routing status for this route
sub core_routing_getrouteintracompany {
    my ($self, $route) = @_;
    my $intracompany = "";
    my $sql = "SELECT DISTINCT args FROM extensions WHERE context = 'outrt-".$route."' AND (args LIKE 'INTRACOMPANYROUTE%') ";

    my ($sth, $result) = DB::db_sql($sql);
    if(!$sth){ return retErr("_ERR_GET_ROUTE_INTRACOMPANY","Error get intracompany. DB problem."); }

    my $results = $sth->fetchrow_hashref;
    my $args = $results->{'args'};

    if($args =~ m/^.*=(.*)/){
        $intracompany = $1;
    }

    return $intracompany;
}


#get mohsilence routing status for this route
sub core_routing_getroutemohsilence {
    my ($self, $route) = @_;
    my $mohsilence = "";
    my $sql = "SELECT DISTINCT args FROM extensions WHERE context = 'outrt-".$route."' AND (args LIKE 'MOHCLASS%') ";

    my ($sth, $result) = DB::db_sql($sql);
    if(!$sth){ return retErr("_ERR_GET_ROUTE_MOHSILENCE","Error get moh silence. DB problem."); }

    my $results = $sth->fetchrow_hashref;
    my $args = $results->{'args'};

    if($args =~ m/^.*=(.*)/){
        $mohsilence = $1;
    }

    return $mohsilence;
}


#get routecid routing status for this route
sub core_routing_getroutecid {
    my ($self, $route) = @_;
    my $routecid = "";
    my $routecid_mode = "";
    my $sql = "SELECT DISTINCT args FROM extensions WHERE context = 'outrt-".$route."' AND (args LIKE 'ROUTECID%' OR args LIKE 'EXTEN_ROUTE_CID%') ";

    my ($sth, $result) = DB::db_sql($sql);
    if(!$sth){ return retErr("_ERR_GET_ROUTE_MOHSILENCE","Error get moh silence. DB problem."); }

    my $results = $sth->fetchrow_hashref;
    my $args = $results->{'args'};

    if($args =~ m/^(.*)=(.*)/){
        
        $routecid = $2;
        $routecid_mode = $1 eq 'ROUTECID' ? 'override_extension':'';


    }

    my $arr = {'routecid' => $routecid, 'routecid_mode' => $routecid_mode};

    return wantarray() ? %$arr : $arr;    
}



sub core_routing_getroutenames {
    my $self = shift;    

    # DOES IT WORK WITH SQLITE# ????
    # we SUBSTRING() to remove "outrt-"
    my($sth, $result) = DB::db_sql("SELECT DISTINCT SUBSTRING(context,7) AS context FROM extensions WHERE context LIKE 'outrt-%' ORDER BY context");
    
    my $data = $sth->fetchall_arrayref({});
    my $count_data = scalar(@$data);

    print STDERR "COUNT DATA $count_data";

    return wantarray() ? %$data : $data;

}



#
#   INBOUND ROUTES
#
#


sub core_did_process_add {
    my ($self,$p) = @_;
    my $result = $self->core_did_add($p);

    if($result && !isError($result)) {
        $self->need_reload();
        return retOk("_OK_ADD_INBOUNDROUTE_","Added successfully.");
    }
    else{
        return wantarray() ? %$result : $result;
    }
}


sub core_did_process_edit {
    my ($self,$p) = @_;
    my $extdisplay = $p->{'extdisplay'};
    my @extarray = split(/\//,$extdisplay,2);

    plog("SPLITTED ",Dumper(@extarray));

    my $result = $self->core_did_edit($extarray[0],$extarray[1], $p);    

    if($result && !isError($result)) {
        $self->need_reload();
        return retOk("_OK_EDIT_INBOUNDROUTE_","Added successfully.");
    }
    else{
        return wantarray() ? %$result : $result;
    }
}


sub core_did_process_del {
    my ($self,$p) = @_;
    my $extdisplay = trim($p->{'extdisplay'});

    my @extarray = split(/\//,$extdisplay,2);
    
    my $result = $self->core_did_del($extarray[0],$extarray[1]);

    if($result && !isError($result)) {
        $self->need_reload();
            return retOk("_OK_DEL_INBOUNDROUTE_","Deleted successfully.");
    }
    else{
        return wantarray() ? %$result : $result;
    }
}


sub core_did_get {
    my ($self, $extension, $cidnum) = @_;
    my $sql = "SELECT * FROM incoming WHERE cidnum = ".DB::db_quote($cidnum)." AND extension = \"$extension\"";

    my ($sth, $result) = DB::db_sql($sql);
    my $res = $sth->fetchrow_hashref;

    return wantarray() ? %$res : $res;	    
}


sub core_did_add {
    my ($self, $p, $target) = @_;
    my $extension = $p->{'extension'};
    my $cidnum = $p->{'cidnum'};
    my $goto0 = $p->{'goto0'};
    my $privacyman = $p->{'privacyman'};    
    my $pmmaxretries = $p->{'pmmaxretries'};
    my $pmminlength = $p->{'pmminlength'};
    my $alertinfo = $p->{'alertinfo'};
    my $ringing = $p->{'ringing'};
    my $mohclass = $p->{'mohclass'};
    my $description = $p->{'description'};
    my $grppre = $p->{'grppre'};
    my $delay_answer = $p->{'delay_answer'};
    my $pricid = $p->{'pricid'};


    my $existing = $self->core_did_get($extension,$cidnum);
    
    if($existing){
        return retErr("_ERR_ADD_DID_","A route for this DID/CID (".$existing->{'extension'}."/".$existing->{'cidnum'}.")already exists.");
    }else{

        #my $destination = $p->{$goto0};
        my $destination = ($target) ? $target : $p->{$goto0};

		my $sql="INSERT INTO incoming (cidnum,extension,destination,privacyman,pmmaxretries,pmminlength,alertinfo, ringing, mohclass, description, grppre, delay_answer, pricid)
              values ('$cidnum','$extension','$destination','$privacyman','$pmmaxretries','$pmminlength','$alertinfo', '$ringing', '$mohclass', '$description', '$grppre', '$delay_answer', '$pricid')";

        DB::db_sql($sql);

        return 1;
    }


}


sub core_did_edit {
    my ($self, $ext_old, $cid_old, $p) = @_;
    my $extension = $p->{'extension'};
    my $cidnum = $p->{'cidnum'};
    my $existing;
    
    # if did or cid changed, then check to make sure that this pair is not already being used.
	
	if (($extension != $ext_old) || ($cidnum != $cid_old)) {
        plog("\n\nCORE DID GET\n\n");
		$existing = $self->core_did_get($extension,$cidnum);
	}

    if($existing){
        return retErr("_ERR_ADD_DID_","A route for this DID/CID (".$existing->{'extension'}."/".$existing->{'cidnum'}.")already exists.");
    }else{
        $self->core_did_del($ext_old,$cid_old);
		$self->core_did_add($p);
        return 1;
        
    }    
}


sub core_did_del{
    my ($self, $extension, $cidnum) = @_;
    my $sql = "DELETE FROM incoming WHERE cidnum = \"$cidnum\" AND extension = \"$extension\"";
    my($sth, $result) = DB::db_sql($sql);    
}


sub core_did_list {
    my ($self, $order) = @_;
    my $sql;
    $order ||= 'extension';

    for($order){
        if(/description/) {
            $sql = "SELECT * FROM incoming ORDER BY description,extension,cidnum";
        }else{
            $sql = "SELECT * FROM incoming ORDER BY extension,cidnum";
        }
    }

    my($sth, $result) = DB::db_sql($sql);
    my $data = $sth->fetchall_arrayref({});    
   
    return wantarray() ? %$data : $data;
}



# The destinations this module provides
# returns a associative arrays with keys 'destination' and 'description'

sub core_destinations {
    my $self = shift;
    my $category = 'Terminate Call';
    my @extens = ({'destination'=>'app-blackhole,hangup,1', 'description'=>'Hangup', 'category'=>$category},
                  {'destination'=>'app-blackhole,congestion,1', 'description'=>'Congestion', 'category'=>$category},
                  {'destination'=>'app-blackhole,busy,1', 'description'=>'Busy', 'category'=>$category},
                  {'destination'=>'app-blackhole,zapateller,1', 'description'=>'Play SIT Tone (Zapateller)', 'category'=>$category},
                  {'destination'=>'app-blackhole,musiconhold,1', 'description'=>'Put caller on hold forever', 'category'=>$category},
                  {'destination'=>'app-blackhole,ring,1', 'description'=>'Play ringtones to caller until they hangup', 'category'=>$category}
                );             

    #get the list of meetmes
	my $results = $self->core_users_list();

	if ($results) {
		#get voicemail
        my $vm_module = ETVOIP::PBX::Voicemail::->new(amp_conf=>$self->{'amp_conf'});
        my $uservm = $vm_module->voicemail_getVoicemail();
		
		my @vmcontexts = keys(%$uservm);
        my %vmboxes;
        my $extnum;
        my $name;
        
		foreach my $thisext (@$results) {
			$extnum = $thisext->{'extension'};
            $name = $thisext->{'name'};            

            # search vm contexts for this extensions mailbox
            foreach my $vmcontext (@vmcontexts){                
                if($uservm->{$vmcontext}->{$extnum}){
                    $vmboxes{$extnum} = 1;
                }
            }

            push(@extens, {'destination' => 'from-did-direct,'.$extnum.',1', 'description' => ' <'.$extnum.'> '.$name, 'category' => 'Extensions'});
            if($vmboxes{$extnum}) {
                # core provides both users and voicemail boxes as destinations
                push(@extens, {'destination' => 'ext-local,vmb'.$extnum.',1', 'description' => '<'.$extnum.'> '.$name.' (busy)', 'category' => 'Voicemail'});
				push(@extens, {'destination' => 'ext-local,vmu'.$extnum.',1', 'description' => '<'.$extnum.'> '.$name.' (unavail)', 'category' => 'Voicemail'});
				push(@extens, {'destination' => 'ext-local,vms'.$extnum.',1', 'description' => '<'.$extnum.'> '.$name.' (no-msg)', 'category' => 'Voicemail'});
            }           
		}
	}    

    return wantarray() ? @extens : \@extens;

}

sub core_users_list {
    my ($sth,$result) = DB::db_sql('SELECT extension,name,voicemail FROM users ORDER BY extension');
    my $get_res = $sth->fetchall_arrayref({});
    return wantarray() ? @$get_res : $get_res;
}
1;