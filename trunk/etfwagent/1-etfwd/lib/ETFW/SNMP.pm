#!/usr/bin/perl

=pod

=head1 NAME

ETFW::SNMP

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package ETFW::SNMP;

use strict;

use FileFuncs;

my %CONF = ( 'conf_file'=>"/etc/snmp/snmpd.conf", 'conf_dir'=>"/etc/snmp", 'snmpd_restart'=>'/etc/init.d/squid restart', 'snmpd_reload'=>'/etc/init.d/squid reload' );

=item get_config

=cut

sub get_config {
    my $self = shift;

    my %Security = ();
    my @LGroups = ();
    my @LView = ();
    my @LAccess = ();
    my @LConf = ();

    my $lnum = 0;
    open(F,$CONF{"conf_file"});
    while(<F>){
        chomp;
        s/#.*//g;
        if( /com2sec\s+(\S+)\s+(\S+)\s+(\S+)/ ){
            $Security{"$1"} = { 'secname'=>$1,
                                'source'=>$2,
                                'community'=>$3,
                                'groups'=>[],
                                line=>$lnum
                                };
        } elsif( /group\s+(\S+)\s+(\S+)\s+(\S+)/ ){
            my $Group = { 'groupname'=>$1,
                                'securitymodel'=>$2,
                                'securityname'=>$3,
                                line=>$lnum
                                 };
            push(@LGroups,$Group);
            if( my $Sec = $Security{"$3"} ){
                push(@{$Sec->{"groups"}}, $Group );
            }
        } elsif( /view\s+(\S+)\s+(\S+)\s+(\S+)\s*(\S*)/ ){
            push @LView, { 'name'=>$1, 
                            'inc_exc'=>$2,
                            'subtree'=>$3,
                            'mask'=>$4||'',
                            line=>$lnum
                            };
        } elsif( /access\s+(\S+)\s+"([^"]*)"\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)/ ){
            push @LAccess, { 'group'=>$1,
                                'context'=>$2,
                                'secmodel'=>$3,
                                'seclevel'=>$4,
                                'prefix'=>$5,
                                'read'=>$6,
                                'write'=>$7,
                                'notif'=>$8,
                                line=>$lnum
                                };
        } elsif( $_ && /^\s*(\S+)\s+(.*)$/ ){
            push @LConf, { directive=>$1,
                            value=>$2,
                            content=>$_,
                            line=>$lnum };
        }
        $lnum++;
    }
    close(F);

    my %conf = ( Security=>\%Security, Groups=>\@LGroups, View=>\@LView, Access=>\@LAccess, Directives=>\@LConf );

    return wantarray() ? %conf : \%conf;
}

=item set_config 

=cut

sub set_config {
    my $self = shift;
    my (%conf) = @_;

    open(F,">$CONF{'conf_file'}");
    if( my $ld = $conf{'Directives'} ){
        for my $D ( @$ld ){
            print F "$D->{'directive'} $D->{'value'}","\n";
        }
    }
    if( my $ls = $conf{'Security'} ){
        my @arr_ls = ref($ls) eq 'HASH' ? values %$ls : @$ls;
        for my $S ( @arr_ls ){
            print F "com2sec $S->{'secname'} $S->{'source'} $S->{'community'}","\n";
        }
    }
    if( my $lg = $conf{'Groups'} ){
        for my $G ( @$lg ){
            print F "group $G->{'groupname'} $G->{'securitymodel'} $G->{'securityname'}","\n";
        }
    }
    if( my $lv = $conf{'View'} ){
        for my $V ( @$lv ){
            print F "view $V->{'name'} $V->{'inc_exc'} $V->{'subtree'} $V->{'mask'}","\n";
        }
    }
    if( my $la = $conf{'Access'} ){
        for my $A ( @$la ){
            print F "access $A->{'group'} \"$A->{'context'}\" $A->{'secmodel'} $A->{'seclevel'} $A->{'prefix'} $A->{'read'} $A->{'write'} $A->{'notif'}","\n"; 
        }
    }
    close(F);

    return wantarray() ? %conf : \%conf;
}

=item set_config_n_apply

    set and apply configuration

=cut

sub set_config_n_apply {
    my $self = shift;
    $self->set_config(@_);
    return $self->apply_config();
}

my @Security_args = qw(secname source community);
my @Group_args = qw(groupname securitymodel securityname);
my @View_args = qw(name inc_exc subtree mask);
my @Access_args = qw(group context secmodel seclevel prefix read write notif);

=item set_security

    ARGS: name|secname - security name
          source
          community

=cut

sub set_security {
    my $self = shift;
    my (%p) = @_;

    if( my $name = $p{"name"} || $p{"secname"} ){
        my %C = $self->get_config();
        if( $C{"Security"}{"$name"} ){
            my $line = $C{"Security"}{"$name"}{"line"};
            my $value = "com2sec";
            for my $k (@Security_args){
                $value .= " " . $p{"$k"};
            }
            splice_file_lines($CONF{"conf_file"},$line,1,$value);
        }
    }
}

=item add_security

    ARGS: secname - security name
          source
          community

=cut

sub add_security {
    my $self = shift;
    my (%p) = @_;

    my $value = "com2sec";
    for my $k (@Security_args){
        $value .= " " . $p{"$k"};
    }
    push_file_lines($CONF{"conf_file"},$value);
}

=item del_security

    ARGS: name|secname - security name

=cut

sub del_security {
    my $self = shift;
    my (%p) = @_;

    if( my $name = $p{"name"} || $p{"secname"} ){
        my %C = $self->get_config();
        if( $C{"Security"}{"$name"} ){
            my $line = $C{"Security"}{"$name"}{"line"};
            splice_file_lines($CONF{"conf_file"},$line,1);
        }
    }
}

=item add_group

    ARGS: groupname
          securitymodel
          securityname

=cut

sub add_group {
    my $self = shift;
    my (%p) = @_;

    my $value = "group";
    for my $k (@Group_args){
        $value .= " " . $p{"$k"};
    }
    push_file_lines($CONF{"conf_file"},$value);
}

=item del_group

    ARGS: groupname
          securitymodel
          securityname

=cut

sub del_group {
    my $self = shift;
    my (%p) = @_;

    my %C = $self->get_config();
    if( my $lg = $C{"Groups"} ){
        my @dlg = grep { matchhash(\%p,$_) } @$lg;

        my $file = $CONF{"conf_file"};
        my $cfref = read_file_lines($file);
        for my $G (sort { $b->{"line"} <=> $a->{"line"} } @dlg){
            splice(@$cfref,$G->{"line"},1);
        }
        flush_file_lines($file);
    }
}

=item add_view

    ARGS: name
          inc_exc
          subtree
          mask

=cut

sub add_view {
    my $self = shift;
    my (%p) = @_;

    my $value = "view";
    for my $k (@View_args){
        $value .= " " . $p{"$k"};
    }
    push_file_lines($CONF{"conf_file"},$value);
}

=item del_view

    ARGS: name
          inc_exc
          subtree
          mask

=cut

sub del_view {
    my $self = shift;
    my (%p) = @_;

    my %C = $self->get_config();
    if( my $lv = $C{"View"} ){
        my @dlv = grep { matchhash(\%p,$_) } @$lv;

        my $file = $CONF{"conf_file"};
        my $cfref = read_file_lines($file);
        for my $V (sort { $b->{"line"} <=> $a->{"line"} } @dlv){
            splice(@$cfref,$V->{"line"},1);
        }
        flush_file_lines($file);
    }
}

=item add_access

    ARGS: group
          context
          secmodel
          seclevel
          prefix
          read
          write
          notif

=cut

sub add_access {
    my $self = shift;
    my (%p) = @_;

    my $value = "access";
    for my $k (@Access_args){
        $value .= " " . $p{"$k"};
    }
    push_file_lines($CONF{"conf_file"},$value);
}

=item del_access

    ARGS: group
          context
          secmodel
          seclevel
          prefix
          read
          write
          notif

=cut

sub del_access {
    my $self = shift;
    my (%p) = @_;

    my %C = $self->get_config();
    if( my $la = $C{"Access"} ){
        my @dla = grep { matchhash(\%p,$_) } @$la;

        my $file = $CONF{"conf_file"};
        my $cfref = read_file_lines($file);
        for my $A (sort { $b->{"line"} <=> $a->{"line"} } @dla){
            splice(@$cfref,$A->{"line"},1);
        }
        flush_file_lines($file);
    }
}

sub matchhash {
    my ($h1,$h2) = @_;
    my $b;
    for my $k1 (keys %$h1){
        if( not defined $b && 
            defined $h2->{"$k1"} && $h1->{"$k1"} eq $h2->{"$k1"} ) {
            $b = 1;
        }
        if( defined $h2->{"$k1"} && $h1->{"$k1"} ne $h2->{"$k1"} ) { 
            $b = 0;
        }
    }
    $b ||= 0;
    return $b;
}

=item add_directive

    add directive line config

    ARGS: directive - directive type
          value - value of directive

=cut

sub add_directive {
    my $self = shift;
    my (%p) = @_;

    if( my $directive = $p{"directive"} ){
        my %C = $self->get_config();
        my $value = $p{"value"};
        if( my $line = $p{"line"} ){    # add to line
            splice_file_lines($CONF{"conf_file"},$line,0,"$directive $value");
        } else {
            push_file_lines($CONF{"conf_file"},"$directive $value");
        }
    }
}

=item del_directive

    delete directive line config

    ARGS: directive - directive type
          value - value of directive to delete

=cut

sub del_directive {
    my $self = shift;
    my (%p) = @_;

    if( my $directive = $p{"directive"} ){
        my %C = $self->get_config();
        my $value = $p{"value"};
        
        # get all directives
        my @dld = grep { $_->{'directive'} eq $directive && $_->{'value'} eq $value } @{$C{"Directives"}};

        my $file = $CONF{"conf_file"};
        my $cfref = read_file_lines($file);

        # delete them
        for my $D (sort { $b->{"line"} <=> $a->{"line"} } @dld){
            splice(@$cfref,$D->{"line"},1);
        }
        flush_file_lines($file);
    }
}

=item apply_config

    apply configuration

=cut

sub apply_config {
    my $self = shift;

    my ($e,$m);

    if( $CONF{'snmpd_reload'} ){
        ($e,$m) = cmd_exec("$CONF{'snmpd_reload'}");
    }
    unless( defined($e) && ( $e == 0 ) ){
        if( $CONF{'snmpd_restart'} ){
            ($e,$m) = cmd_exec("$CONF{'snmpd_restart'}");
        } elsif( -x "/etc/init.d/snmpd" ){
            ($e,$m) = cmd_exec("/etc/init.d/snmpd reload");
            unless( $e == 0 ){
                ($e,$m) = cmd_exec("/etc/init.d/snmpd stop");
                ($e,$m) = cmd_exec("/etc/init.d/snmpd start");
            }
        }
    }
    
    unless( $e == 0 ){
        return retErr("_ERR_APPLY_CONFIG_","Error apply configuration: $m");
    }
    return retOk("_OK_APPLY_CONFIG_","Apply configuration ok.");
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

