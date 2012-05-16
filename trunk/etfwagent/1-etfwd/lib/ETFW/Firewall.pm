#!/usr/bin/perl

=pod

=head1 NAME

ETFW::Firewall

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package ETFW::Firewall;

use strict;

use ETVA::Utils;

my %TABLES = ( "filter"=>1, "mangle"=>1, "nat"=>1 );
my @ARGS = ('-p', '-m', '-s', '-d', '-i', '-o', '-f',
             '--dport', '--sport', '--tcp-flags', '--tcp-option',
             '--icmp-type', '--mac-source', '--limit', '--limit-burst',
             '--ports', '--uid-owner', '--gid-owner',
             '--pid-owner', '--sid-owner', '--state', '--tos', '-j',
             '--to-ports', '--to-destination', '--to-source',
             '--reject-with', '--dports', '--sports',
             '--comment' );

my %CONF = ( "config_iptables_file"=>"/etc/sysconfig/iptables" );

=item set_config_file

=cut

sub set_config_file {
    my $self = shift;
    my (%p) = @_;
    
    if( defined $p{"direct"} ){
        $CONF{"direct"} = $p{"direct"};
        $CONF{"cfg_file"} = "/sbin/iptables-save 2>/dev/null |";
    } else {
        $CONF{"cfg_file"} = $p{'cfg_file'} || $CONF{"config_iptables_file"};
    }
}

=item load_config

=cut

sub load_config {
    my $self = shift;
    my (%p) = @_;
    my $cfg_file;
    my $direct = $p{"direct"} || $CONF{"direct"} || 0;
    if( $direct ){
        $cfg_file = "/sbin/iptables-save 2>/dev/null |";
    } else {
        $cfg_file = $p{'cfg_file'} || $CONF{"cfg_file"} || $CONF{"config_iptables_file"};
    }
    
    my $cmt = "";
    my $lnum = 0;
    my $t;
    my %Conf = ();
    open(C,$cfg_file);
    while(<C>){
        chomp;
        my $read_comment;
        # parse and clean comments
        if( s/#\s*(.*)// ){
            $cmt .= " " if( $cmt );
            $cmt .= "$1";
            $read_comment = 1;
        }

        if( /^\*(\w+)/ ){
            # table
            $t = $1;
            # init
            $Conf{"$t"} = { name=>$t, defaults=>{}, rules=>[], line=>$lnum, eline=>$lnum };
        } elsif( /^:(\S+)\s+(\S+)/ ){
            # default policy
            $Conf{"$t"}{"defaults"}{"$1"} = $2;
        } elsif( /^(\[[^\]]*\]\s+)?-N\s+(\w+)/ ){
            # new chain
            $Conf{"$t"}{"defaults"}{"$2"} = "-";
        } elsif( /^(\[[^\]]*\]\s+)?-A\s+(\w+)(.*)/ ) {
            push @{$Conf{"$t"}{"rules"}},
                    { chain=>$2, 'index'=>scalar(@{$Conf{"$t"}{"rules"}}), cmt=>$cmt, line=>$lnum, eline=>$lnum, convertargs($3) };
        } elsif( /^COMMIT/ ){
            $Conf{"$t"}{"commit"} = 1;
            # Marks end of a table
            $Conf{"$t"}{"eline"} = $lnum;
        }

        if( !defined($read_comment) ){
            $cmt = "";
        }

        $lnum++;
    }
    close(C);

    return wantarray() ? %Conf : \%Conf;
}

=item get_config_rules

=cut

sub get_config_rules {
    my $self = shift;
    my (%p) = @_;

    my %ab = $self->get_boot_status();
    
    my %rules = $self->list_rules();
    if( !$CONF{"direct"} ){

        # Verify that all known tables exist, and if not add them to the
        # save file
        my $need_reload=0;
        for my $t (keys %TABLES){
            if( !$rules{"$t"} ){
                my %C = $self->get_iptables_save( 'cfg_file'=>"iptables-save --table $t 2>/dev/null |" );
                if( %C && !isError(%C) ){
                    if( my $M = $C{"$t"} ){
                        delete $M->{'line'};    # delete line reference
                        $self->save_table( %$M, 'table'=>$t );
                        $need_reload++;
                    }
                }
            }
        }
        if( $need_reload ){
            %rules = $self->list_rules();
        }
    }

    my %r = ( boot=>\%ab, rules=>\%rules);

    return wantarray() ? %r : \%r;
}

sub convertargs {
    my ($str) = @_;
    my %args = ();
    for my $a ( @ARGS ){
        my @v = ();
        while( $str =~ s/\s+(!?)\s*($a)\s+(!?)\s*("[^"]*")(\s+|$)/ / ||
                $str =~ s/\s+(!?)\s*($a)\s+(!?)\s*(([^ \-!]\S*(\s+|$))+)/ / ||
                $str =~ s/\s+(!?)\s*($a)()(\s+|$)/ / ){
            if( my $f = $1 || $3 ){
                push(@v,trim($f));
            }
            if( $4 ){
                push(@v,trim($4));
            }
        }
        if( @v ){
            my ($ca) = ($a =~ /^-+(.+)/);
            $args{"$ca"} = join(" ",@v);
        }
    }
    $args{"args"} = trim($str) if( $str );
    return wantarray() ? %args : \%args;
}

=item apply_config

=cut

sub apply_config {
    my $self = shift;
    my (%p) = @_;

    if( -x "/etc/init.d/iptables" ){
        cmd_exec("/etc/init.d/iptables restart");
    } else {
        my $cfg_file = $CONF{"config_iptables_file"};
        cmd_exec("/sbin/iptables-restore <$cfg_file"); 
    } 

    return retOk("_OK_APPLYCONF_","Ok config applied.");
}

=item revert_config

=cut

sub revert_config {
    my $self = shift;
    my (%p) = @_;

    if( -x "/etc/init.d/iptables" ){
        cmd_exec("/etc/init.d/iptables save");
    } else {
        my $cfg_file = $CONF{"config_iptables_file"};
        cmd_exec("/sbin/iptables-save >$cfg_file");
    } 

    return retOk("_OK_REVERTCONF_","Ok config reverted.");
}

=item reset_config

    clear out all rules (WARNING)

    ARGS: policies - list of initial policies to set
          rules - list of initial rules to add

=cut

sub reset_config {
    my $self = shift;
    my (%p) = @_;

    # Clear out all rules
    foreach my $t (keys %TABLES) {
        cmd_exec("iptables -t $t -P INPUT ACCEPT >/dev/null 2>&1");
        cmd_exec("iptables -t $t -P OUTPUT ACCEPT >/dev/null 2>&1");
        cmd_exec("iptables -t $t -P FORWARD ACCEPT >/dev/null 2>&1");
        cmd_exec("iptables -t $t -P PREROUTING ACCEPT >/dev/null 2>&1");
        cmd_exec("iptables -t $t -P POSTROUTING ACCEPT >/dev/null 2>&1");
        cmd_exec("iptables -t $t -F >/dev/null 2>&1");
        cmd_exec("iptables -t $t -X >/dev/null 2>&1");
    }

    # save all existing active rules
    $self->revert_config();

    # set initial policies
    if( my $policies = $p{"policies"} ){
        for my $P ( @$policies ){
            $self->set_policy( %$P );
        }
    }

    # add initial rules
    if( my $rules = $p{"rules"} ){
        for my $R ( @$rules ){
            $self->add_rule( %$R );
        }
    }

    # TODO: apply template rules

    # apply rules
    $self->apply_config();

    return retOk("_OK_RESETCONF_","Ok reset config.");
}

=item get_iptables_save

    get iptables rules

    ARGS: table - table rule ( filter, mangle, nat )

=cut

sub get_iptables_save {
    my $self = shift;
    my (%p) = @_;

    my %C = $self->load_config( %p );

    # table (optional)
    if( my $table = $p{'table'} ){
        if( $TABLES{"$table"} ){
            if( my $R = $C{"$table"} ){
                return wantarray() ? %$R : $R;
            }
        }
    } else {
        return wantarray() ? %C : \%C;
    }
    return retErr( "_ERR_NOTVALIDTABLE_","Error: not valid table" );
}

=item list_rules

    ARGS: table, chain

=cut

sub list_rules {
    my $self = shift;
    my (%p) = @_;

    my %C = $self->load_config( %p );

    my %R = ();

    for my $tbl ( keys %C ){
        # default policy for chain
        if( my $hdefs = $C{"$tbl"}{"defaults"} ){
            for my $ch (keys %$hdefs){
                $R{"$tbl"}{"$ch"}{"default"} = $C{"$tbl"}{"defaults"}{"$ch"};
            }
        }
        # rules for each chain
        if( my $crules = $C{"$tbl"}{"rules"} ){
            for my $CR (@$crules){
                my $ch = $CR->{"chain"};
                push(@{$R{"$tbl"}{"$ch"}{"rules"}}, $CR);
            }
        }
    }
    
    # table (optional)
    if( my $table = $p{'table'} ){
        if( $TABLES{"$table"} ){
            if( my $RT = $R{"$table"} ){
                # chain (optional)
                if( my $chain = $p{'chain'} ){
                    if( my $RC = $RT->{"$chain"} ){
                        return wantarray() ? %$RC : $RC;
                    }
                    return retErr( "_ERR_NOTVALIDCHAIN_","Error: not valid chain" );
                } else {
                    return wantarray() ? %$RT : $RT;
                }
            }
        }
        return retErr( "_ERR_NOTVALIDTABLE_","Error: not valid table" );
    }
    return wantarray() ? %R : \%R;
}

=item get_rule

    ARGS: table - table
          index - rule at index

=cut

sub get_rule {
    my $self = shift;
    my (%p) = @_;

    if( my $t = delete $p{"table"} ){
        my %conf = $self->load_config( %p );
        if( $TABLES{"$t"} ){
            my $table = $conf{"$t"} || { name=>"$t" };
            my $i = delete $p{"index"};
            if( defined $i  ){
                if( my $rules = $table->{"rules"} ){
                    if( $i >= 0 && $i < scalar(@$rules) ){
                        if( my $R = $rules->[$i] ){
                            return wantarray() ? %$R : $R;
                        }
                    }
                }
            }
        }
    }

    return retErr("_ERR_GETRULE_","Error get rule.");
}

=item set_rule

    ARGS: table - table
          index - rule at index
          other params like: chain, source, protocol, target, options, destination, ...

=cut

sub set_rule {
    my $self = shift;
    my (%p) = @_;

    if( my $t = delete $p{"table"} ){
        my %conf = $self->load_config( %p );
        if( $TABLES{"$t"} ){
            my $table = $conf{"$t"} || { name=>"$t" };
            my $i = delete $p{"index"};
            if( defined $i  ){
                if( my $rules = $table->{"rules"} ){
                    if( $i >= 0 && $i < scalar(@$rules) ){
                        if( my $chain = delete $p{"chain"} ){
                            $rules->[$i] = { chain=>$chain, %p };

                            $table->{"rules"} = $rules;
                            $self->save_table( %$table ); 
                            return retOk("_OK_SETRULE_","Rule changed successfully.");
                        }
                    }
                }
            }
        }
    }

    return retErr("_ERR_SETRULE_","Rule not found.");
}

=item add_rule

    ARGS: table, chain, ...
          index - add at index

=cut

sub add_rule {
    my $self = shift;
    my (%p) = @_;

    if( my $t = delete $p{"table"} ){
        my %conf = $self->load_config( %p );
        if( $TABLES{"$t"} ){
            my $table = $conf{"$t"} || { name=>"$t" };
            if( my $chain = delete $p{"chain"} ){
                if( %p ){
                    my $rules = $table->{"rules"} || [];

                    my $i = delete $p{"index"};
                    if( defined $i && 
                            ( $i >= 0 && $i < scalar(@$rules) ) ){
                        splice(@$rules,$i,0,{ chain=>"$chain", %p });
                    } else {
                        push(@$rules,{ chain=>"$chain", %p });
                    }

                    $table->{"rules"} = $rules;
                    $self->save_table( %$table ); 

                    return retOk("_OK_ADDRULE_","Rule added successfully.");
                }
            }
        }
    }

    return retErr("_ERR_ADDRULE_","Cant add rule.");
}

=item del_rule

    ARGS: table, chain, ...
          index - delete from index of rule

=cut

sub del_rule {
    my $self = shift;
    my (%p) = @_;

    if( my $t = delete $p{"table"} ){
        my %conf = $self->load_config( %p );
        if( $TABLES{"$t"} ){
            my $table = $conf{"$t"} || { name=>"$t" };
            my $i = delete $p{"index"};
            if( defined $i  ){
                if( my $rules = $table->{"rules"} ){
                    if( $i >= 0 && $i < scalar(@$rules) ){
                        splice(@$rules,$i,1);
                        $table->{"rules"} = $rules;
                        $self->save_table( %$table ); 

                        return retOk("_OK_DELRULE_","Rule deleted.");
                    }
                }
            } elsif( my $chain = delete $p{"chain"} ){
                if( %p ){
                    my @rules = ();
                    for my $R ( @{$table->{"rules"}} ){
                        my $b = 0;
                        if( $R->{"chain"} eq $chain ){
                            $b ||= 1;
                            for my $e (keys %p){
                                if( $R->{"$e"} ne $p{"$e"} ){
                                    $b = 0;
                                    last;
                                } else{ $b ||= 1; }
                            }
                        }
                        if( not $b ){
                            push(@rules,$R);
                        }
                    }
                    $table->{"rules"} = \@rules;
                    $self->save_table( %$table ); 

                    return retOk("_OK_DELRULE_","Rule deleted.");
                }
            }
        }
    }

    return retErr("_ERR_DELRULE_","Cant delete rule.");
}

=item del_rules

    ARGS: table
          rules - list of rules

=cut

sub del_rules {
    my $self = shift;
    my (%p) = @_;

    my $tbl = $p{"table"};
    if( my $rules = $p{"rules"} ){
        for my $R ( sort { $b->{"index"} <=> $a->{"index"} } @$rules ){
            $self->del_rule( table=>$tbl, %$R );
        }
        return retOk("_OK_DELRULES_","Rules deleted.");
    }

    return retErr("_ERR_DELRULES_","Cant delete rules.");
}

=item move_rule

    move rule up/down or to position

    ARGS: table - table rule ( filter, mangle, nat )
          index - indice
          to - position to move
          up - move up
          down - move down

=cut

sub move_rule {
    my $self = shift;
    my (%p) = @_;

    if( my $t = delete $p{"table"} ){
        my %conf = $self->load_config( %p );
        if( $TABLES{"$t"} ){
            if( my $table = $conf{"$t"} ){
                if( my $rules = $table->{"rules"} ){
                    my $i = delete $p{"index"};
                    my $nl = scalar(@$rules);
                    if( defined $i && 
                            ( $i >= 0 && $i < $nl ) ){
                        my $to = delete $p{"to"};
                        if( ! defined $to ){
                            if( $p{"up"} ){
                                $to = $i > 0 ? $i - 1 : 0;
                            } elsif( $p{"down"} ){
                                $to = $i < int($nl - 1) ? $i + 1 : $nl - 1;
                            }
                        }

plog "move_rule i=",$i," to t=",$to,"\n";   # debug

                        if( defined $to &&
                            ( $to >= 0 && $to < $nl ) ){
                            my @lr = splice(@$rules,$i,1);
                            splice(@$rules,$to,0,@lr);

                            $table->{"rules"} = $rules;
                            $self->save_table( %$table ); 
                            return retOk("_OK_MOVERULE_","Rule moved successfully.");
                        }
                    }
                }
            }
        }
    }

    return retErr("_ERR_MOVERULE_","Cant move rule.");
}

=item add_chain

    ARGS: table, chain

=cut

sub add_chain {
    my $self = shift;
    my (%p) = @_;

    if( my $t = $p{"table"} ){
        my %conf = $self->load_config( %p );
        if( $TABLES{"$t"} ){
            my $table = $conf{"$t"} || { name=>"$t" };
            if( my $chain = $p{"chain"} ){
                $table->{"defaults"}{"$chain"} = "-";
                $self->save_table( %$table ); 
                return retOk("_OK_ADDCHAIN_","Chain added successfully.");
            }
        }
    }
    return retErr("_ERR_ADDCHAIN_","Error add chain.");
}

=item del_chain

    ARGS: table, chain

=cut

sub del_chain {
    my $self = shift;
    my (%p) = @_;

    if( my $t = $p{"table"} ){
        my %conf = $self->load_config( %p );
        if( $TABLES{"$t"} ){
            if( my $table = $conf{"$t"} ){
                if( my $chain = $p{"chain"} ){
                    # Delete this entire chain and all rules in it
                    $table->{"rules"} = [ grep { $_->{'chain'} ne $chain } @{$table->{"rules"}} ];
                    delete $table->{"defaults"}{"$chain"};

                    $self->save_table( %$table ); 
                    return retOk("_OK_DELCHAIN_","Chain deleted successfully.");
                }
            }
        }
    }
    return retErr("_ERR_DELCHAIN_","Error delete chain.");
}

=item set_policy

    ARGS: table, chain, policy

=cut

sub set_policy {
    my $self = shift;
    my (%p) = @_;
    if( my $t = $p{"table"} ){
        my %conf = $self->load_config( %p );
        if( $TABLES{"$t"} ){
            my $table = $conf{"$t"} || { name=>"$t" };
            if( my $chain = $p{"chain"} ){
                if( my $policy = $p{"policy"} ){
                    $table->{"defaults"}{"$chain"} = "$policy";
                    $self->save_table( %$table ); 

                    return retOk("_OK_SETPOLICY_","Policy change successfully."); 
                }
            }
        }
    }
    return retErr("_ERR_SETPOLICY_","Error set policy.");
}

sub save_table {
    my $self = shift;
    my (%p) = @_;

    # load previous lines
    my $cfg_file = $CONF{'cfg_file'} || $CONF{"config_iptables_file"};

    open(C,"$cfg_file");
    my @oldlines=<C>;
    close(C);

    my $table = $p{"table"}||$p{"name"};
    # new lines go here
    my @lines = ( "*$table" );
    # default chains
    if( my $chains = $p{"defaults"} ){
        for my $d (keys %$chains){
            my $policy = $chains->{"$d"};
            push(@lines,":$d $policy [0:0]");
        }
    }
    # rules
    if( my $rl = $p{"rules"} ){
        for my $R (@$rl){
            my $args = "";
            for my $a ( @ARGS ){
                my ($ca) = ($a =~ /^-+(.+)/);
                my $v = $R->{"$ca"};
                if( defined $v ){
                    $args .= " " if( $args );
                    my @la = ();
                    if( $ca eq 'm' ){
                        push(@la,(map { "$a $_" } grep { $_ } split(/\s+/,$v))) if( $v );
                    } else {
                        @la = ( $v =~ s/^\s*!\s*// )? ("!",$a) : ($a);
                        push(@la,$v) if( $v );
                    }
                    $args .= join(" ",@la);
                }
            }
            $args .= " $R->{args}" if( $R->{"args"} );
            my $line = "";
            $line .= "# $R->{cmt}\n" if( $R->{'cmt'} );
            $line .= "-A $R->{chain} $args";
            push(@lines,$line);
        }
    }
    push(@lines,"COMMIT");

    if( defined($p{"line"}) ){
        # Update in file
        splice(@oldlines, $p{'line'}, $p{'eline'} - $p{'line'} + 1,@lines);
    } else {
        # Append new table to file
        push(@oldlines, "# Generated by ETFW", @lines, "# Completed");
    }

    # save iptables
    my $wfile = $CONF{"direct"} ? "| /sbin/iptables-restore" : ">$CONF{config_iptables_file}";
    open(S,$wfile);
    for my $l (@oldlines){
        chomp $l;
        print S $l,"\n"; 
    }
    close(S);
}

=item open_port

    open port to internal address

    ARGS: dport  - destination port ( required )
          p      - protocol ( default: tcp )
          dest   - destination address ( optional )

=cut

sub open_port {
    my $self = shift;
    my (%p) = @_;

    if( my $dport = delete $p{"dport"} ){
        my $p = delete $p{"p"} || "tcp";
        $p{"d"} = delete $p{"dest"} if( $p{"dest"} );
        $self->add_rule( table=>"filter", chain=>"FORWARD", dport=>$dport, p=>$p, j=>"ACCEPT", %p );

        return retOk("_OK_OPENPORT_","Ok open port.");
    }
    return retErr("_ERR_OPENPORT_","Error open port.");
}

=item forward_port

    forward port to address

    ARGS: dport          - port to forward ( required )
          dest           - destination address ( required )
          p              - protocol ( default: tcp )
          sport          - source port ( optional )
          source         - source address ( optional )

=cut

sub forward_port {
    my $self = shift;
    my (%p) = @_;

    if( my $dport = delete $p{"dport"} ){
        if( my $dest = delete $p{"dest"} ){
            my $p = delete $p{"p"} || "tcp";

            my %on = ();my %of = ();

            $of{"d"} = $on{"to-destination"} = $dest;

            $on{"d"} = delete $p{"source"} if( $p{"source"} );
            $on{"to-destination"} .= ":" . delete $p{"sport"} if( $p{"sport"} );
            
            $self->add_rule( table=>"nat", chain=>"PREROUTING", p=>$p, dport=>$dport, j=>"DNAT", %p, %on );
            $self->add_rule( table=>"filter", chain=>"FORWARD", p=>$p, dport=>$dport, j=>"ACCEPT", %p, %of );
            return retOk("_OK_FORWARDPORT_","Ok forward port.");
        }
    }
    return retErr("_ERR_FORWARDPORT_","Error forward port.");
}

=item get_inittab_runlevel

Returns the runlevels entered at boot time. If more than one is returned,
actions from all of them are used.

=cut

sub get_inittab_runlevel {
    my %iconfig = ( "inittab_file"=>"/etc/inittab","inittab_id"=>"id" );
    my $rl;
    my $id = $iconfig{'inittab_id'};
    if( open(TAB, $iconfig{'inittab_file'}) ){
        # Read the inittab file
        while(<TAB>) {
            if (/^$id:(\d+):/) { $rl = $1; last; }
        }
        close(TAB);
    } elsif( -x "/sbin/runlevel" ){
        # Use runlevel command to get current level
        my ($e,$out) = cmd_exec("/sbin/runlevel");
        if( $out =~ /^(\S+)\s+(\S+)/ ){
            $rl = $2;
        }
    }

    return $rl;
}

=item get_boot_status 

=cut

sub get_boot_status {
    my $self = shift;

    my $rl = get_inittab_runlevel();

    my ($e,$out) = cmd_exec("/sbin/chkconfig --list iptables");

    my %r = ();
    if( $out =~ /$rl:on/ ){
        $r{"active"} = 1;
    } else {
        $r{"active"} = 0;
    }

    return wantarray() ? %r : \%r;
}

=item activate_onboot

=cut

sub activate_onboot {
    my $self = shift;

    my ($e,$out) = cmd_exec("/sbin/chkconfig iptables on");
}

=item deactivate_onboot

=cut

sub deactivate_onboot {
    my $self = shift;

    my ($e,$out) = cmd_exec("/sbin/chkconfig iptables off");
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
