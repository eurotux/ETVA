#!/usr/bin/perl

=pod

=head1 NAME

ETFW::Squid

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package ETFW::Squid;

use strict;

#require ETFW::Squid::Webmin;

use ETVA::Utils;

use Data::Dumper;

BEGIN {
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF $AUTOLOAD);
};

my %PROTECTKEYS = ( 'force'=>1 );

sub AUTOLOAD {
    my ($package,$method) = ( $AUTOLOAD =~ m/(.+)::([^:]+)$/ );

    if( my ($f,$a) = ($method =~ m/^(add|set|del)_(\S+)/) ){
        my $f_config = "${f}_config_option";
        my $f_args = "mkargs_${a}";
        $AUTOLOAD = sub {
                        my $self = shift;
                        my %p = @_;

                        %p = $self->$f_args( %p );

                        if( %p && ! isError(%p) ){
                            return $self->$f_config( %p );
                        }
                        return wantarray() ? %p : \%p;
                    };
    } elsif( my ($a) = ($method =~ m/^mkargs_(\S+)/) ){
        $AUTOLOAD = sub {
                        my $self = shift;
                        my %p = @_;
                        $p{'name'} = $a;
                        if( defined $p{"$a"} ){
                            # set value of value key
                            $p{'value'} = $p{"$a"};
                        }
                        return wantarray() ? %p : \%p;
                    };
    } elsif( my ($a) = ($method =~ m/^get_(\S+)/) ){
        $AUTOLOAD = sub {
                        my $self = shift;
                        my %p = @_;

                        my $G = {};
                        if( my $C = $self->get_config_option( %p, 'name'=>$a ) ){
                            my $fmkget = "mkget_$a";
                            $G = $self->$fmkget( $C );
                        }
                        return wantarray() ? %$G : $G;
                    };
    } elsif( my ($a) = ($method =~ m/^mkget_(\S+)/) ){
        $AUTOLOAD = sub {
                        my $self = shift;
                        my ($C) = @_;

                        my %res = ();
                        if( ref($C) eq 'ARRAY' ){
                          $res{"$a"} = @$C && $C->[0] ? [ @$C ] : [];
                        } else {
                          $res{"$a"} = $C || "";
                        } 
                        
                        return wantarray() ? %res : \%res;
                    };
    } elsif( my ($a) = ($method =~ m/^move_(\S+)/) ){
        $AUTOLOAD = sub {
                        my $self = shift;
                        my %p = @_;

                        return $self->move_config_option( %p, 'name'=>$a );
                    };
    }
    if( $AUTOLOAD ){
        &$AUTOLOAD;
    }
}

sub startup_config {
    my $self = shift;
    return ETFW::Squid::Webmin->startup_config(@_);
}

sub load_config {
    my $self = shift;

    return ETFW::Squid::Webmin->load_config(@_);
}

=item get_enabled_config
    get all enabled config

    ARGS: force - force load

=cut

sub get_enabled_config {
    my $self = shift;

    my $conf = $self->load_config(@_);

    my %conf = ();
    for my $L ( @$conf ){
        if( $L->{"enabled"} ){
            my $n = $L->{"name"};
            next if ( $n =~ /^\s*#/ );
            my $value = $L->{"value"};
            $value =~ s/\s*#.*//;
            if( defined $conf{"$n"} ){
                if( ref($conf{"$n"}) ){
                    push(@{$conf{"$n"}}, $value);
                } else {
                    $conf{"$n"} = [ $conf{"$n"}, $value ]
                }
            } else {
                $conf{"$n"} = $value;
            }
        }
    }
    return wantarray() ? %conf : \%conf;
}

=item get_config_fields

    ARGS: fields - get config for specific fields

=cut

sub get_config_fields {
    my $self = shift;
    my (%p) = @_;

    my @fields = $p{'fields'} ? @{$p{'fields'}} : @_;
    my %ec = $self->get_enabled_config(@_);

    my %sel_conf = ();
    for my $f (@fields){
        next if( $PROTECTKEYS{"$f"} );

        my $fmkget = "mkget_$f";
        my $r = $self->$fmkget( $ec{"$f"} );
        $sel_conf{"$f"} = ( ref($r) eq 'HASH' ) ? $r->{"$f"} : $r;
    }

    return wantarray() ? %sel_conf : \%sel_conf;
}

=item set_config

=cut

sub set_config {
    my $self = shift;
    my (%p) = @_;
    my $conf = $self->load_config(@_);
    for my $o ( keys %p ){
        next if( $PROTECTKEYS{"$o"} );

        my @lpv = ();
        if( $p{"$o"} ){
            @lpv = ref($p{"$o"}) eq "ARRAY" ? @{$p{"$o"}} : ($p{"$o"});
        }
        my @v = map { { name=>$o, 'values'=>[ $_ ] } } @lpv; 
        ETFW::Squid::Webmin::save_directive($conf,$o,\@v);
    }
    ETFW::Squid::Webmin::flush_file_lines();
    $self->load_config(1);
}

=item add_config

=cut

sub add_config {
    my $self = shift;
    my (%p) = @_;
    my $conf = $self->load_config(@_);
    my %ec = $self->get_enabled_config(@_);
    for my $o ( keys %p ){
        next if( $PROTECTKEYS{"$o"} );

        my @oldv = ();
        if( $ec{"$o"} ){
            @oldv = ref($ec{"$o"}) eq "ARRAY" ? @{$ec{"$o"}} : ($ec{"$o"});
        }
        my @newv = ();
        if( $p{"$o"} ){
            @newv = ref($p{"$o"}) eq "ARRAY" ? @{$p{"$o"}} : ($p{"$o"});
        }
        my @v = map { { name=>$o, 'values'=>[ $_ ] } } @newv,@oldv;
        ETFW::Squid::Webmin::save_directive($conf,$o,\@v);
    }
    ETFW::Squid::Webmin::flush_file_lines();
    $self->load_config(1);
}

=item del_config

=cut

sub del_config {
    my $self = shift;
    my (%p) = @_;
    my $conf = $self->load_config(@_);
    my %ec = $self->get_enabled_config(@_);
    for my $o ( keys %p ){
        next if( $PROTECTKEYS{"$o"} );

        my @qv = ref($p{"$o"}) eq "ARRAY" ? @{$p{"$o"}} : ($p{"$o"});
        my $re = join('\s',@qv);

        my @newv = ();
        for my $e (ref($ec{"$o"}) eq "ARRAY" ? @{$ec{"$o"}} : ($ec{"$o"})){
            # if params to delete not match
            if( $e !~ /^$re$/ ){
                push(@newv,$e);
            }
        }
        my @v = map { { name=>$o, 'values'=>[ $_ ] } } @newv;
        ETFW::Squid::Webmin::save_directive($conf,$o,\@v);
    }
    ETFW::Squid::Webmin::flush_file_lines();
    $self->load_config(1);
}

sub add_config_option {
    my $self = shift;
    my (%p) = @_;

    if( my $o = $p{'name'} ){

        my $conf = $self->load_config(@_);
        my %ec = $self->get_enabled_config(@_);
        my $values = $p{'values'} || ( $p{'value'} ? [ $p{'value'} ] : [] );

        # get all values
        my @oldv = ();
        if( defined($ec{"$o"}) ){
            @oldv = ref($ec{"$o"}) eq "ARRAY" ? @{$ec{"$o"}} : ($ec{"$o"});
        }

        # push it
        my @v = map { { name=>$o, 'values'=>[ $_ ] } } @oldv,@$values;

        ETFW::Squid::Webmin::save_directive($conf,$o,\@v);

        ETFW::Squid::Webmin::flush_file_lines();
        $self->load_config(1);
    }
}
sub del_config_option {
    my $self = shift;
    my (%p) = @_;

    if( my $o = $p{'name'} ){

        my $conf = $self->load_config(@_);
        my %ec = $self->get_enabled_config(@_);
        my $values = $p{'values'} || ( $p{'value'} ? [ $p{'value'} ] : [] );

        # get all values
        my @oldv = ();
        if( defined($ec{"$o"}) ){
            @oldv = ref($ec{"$o"}) eq "ARRAY" ? @{$ec{"$o"}} : ($ec{"$o"});
        }

        # drop it
        if( defined $p{'index'} ){          # by one index
            my $i = $p{'index'} || 0;
            splice(@oldv,$i,1);
        } elsif( defined $p{'indexes'} ){   # by one or more index ( multi-index )
            my $li = $p{'indexes'};
            for my $i ( sort { $b <=> $a } @$li ){
                splice(@oldv,$i,1);
            }
        } else {                            # by values
            my @newv = ();
            for my $e (@oldv){
                # if params to delete not match
                if( ! grep { /^$e$/ } @$values ){
                    push(@newv,$e);
                }
            }
            @oldv = @newv;
        }

        my @v = map { { name=>$o, 'values'=>[ $_ ] } } @oldv;

        ETFW::Squid::Webmin::save_directive($conf,$o,\@v);

        ETFW::Squid::Webmin::flush_file_lines();
        $self->load_config(1);
    }
}
sub set_config_option {
    my $self = shift;
    my (%p) = @_;

    if( my $o = $p{'name'} ){
        my $conf = $self->load_config(@_);
        my %ec = $self->get_enabled_config(@_);
        my $values = $p{'values'} || ( $p{'value'} ? [ $p{'value'} ] : [] );

        # get all values
        my @oldv = ref($ec{"$o"}) eq "ARRAY" ? @{$ec{"$o"}} : ($ec{"$o"});

        # replace it
        if( defined $p{'index'} ){
            # relpace only n-index value
            my $i = $p{'index'} || 0;
            splice(@oldv,$i,1,@$values);
        } else {
            @oldv = @$values;
        }

        my @v = map { { name=>$o, 'values'=>[ $_ ] } } @oldv;

        ETFW::Squid::Webmin::save_directive($conf,$o,\@v);

        ETFW::Squid::Webmin::flush_file_lines();
        $self->load_config(1);
    }
}
sub move_config_option {
    my $self = shift;
    my (%p) = @_;

    if( my $o = $p{'name'} ){
        my $conf = $self->load_config(@_);
        my %ec = $self->get_enabled_config(@_);

        # get all values
        my @oldv = ref($ec{"$o"}) eq "ARRAY" ? @{$ec{"$o"}} : ($ec{"$o"});

        if( defined $p{'index'} ){

            my $i = $p{'index'} || 0;
            my $nl = scalar(@oldv);

            if( $i >= 0 && $i < $nl ){
                my $to = $p{'to'};
                if( ! defined($to) ){
                    if( $p{'up'} ){
                        $to = $i > 0 ? $i - 1 : 0;
                    } elsif( $p{'down'} ){
                        $to = $i < int($nl - 1) ? $i + 1 : $nl - 1;
                    }
                }

                if( defined $to &&
                    ( $to >= 0 && $to < $nl ) ){
                    my @lr = splice(@oldv,$i,1);
                    splice(@oldv,$to,0,@lr);
                }
            }
        }

        my @v = map { { name=>$o, 'values'=>[ $_ ] } } @oldv;

        ETFW::Squid::Webmin::save_directive($conf,$o,\@v);

        ETFW::Squid::Webmin::flush_file_lines();
        $self->load_config(1);
    }
}
sub get_config_option {
    my $self = shift;
    my (%p) = @_;

    if( my $o = $p{'name'} ){

        my %ec = $self->get_enabled_config(@_);

        # get all values
        my @oldv = ref($ec{"$o"}) eq "ARRAY" ? @{$ec{"$o"}} : ($ec{"$o"});

        if( defined $p{'index'} ){
            # return only n-index value
            my $i = $p{'index'} || 0;
            @oldv = ( $oldv[$i] );
        }

        return wantarray() ? @oldv : \@oldv;
    }
}

=item add_http_port / del_http_port / set_http_port / get_http_port

=cut

sub mkargs_http_port {
    my $self = shift;
    my (%p) = @_;

    my @v = ();
    if( my $port = $p{"port"} ){
        my $line = "$port";
        if( my $addr = $p{"addr"} ){
            $line = "$addr:$port";
        }
        if( my $opts = $p{"opts"} ){
            $line .= " $opts";
        }

        @v = ( $line );
    } elsif( my $value = $p{'value'} || $p{'values'} || $p{'http_port'} ){
        @v = ( ref($value) eq 'ARRAY' ? @$value : $value );
    }

    my %r = ( %p, 'name'=>"http_port", 'values'=>[ @v ] );

    return wantarray() ? %r : \%r;
}

sub mkget_http_port {
    my $self = shift;
    my ($C) = @_;

    my $a = "http_port";

    my %res = ();

    my @l = ( );
    for my $l ( ( ref($C) eq 'ARRAY' ? @$C : $C ) ){
        next if( !$l );

        my (undef,$addr,$port,$opts) = ( $l =~ m/^((\S+):)?(\d+)\s*(.*)$/ );
        my %c = ( 'value'=>$l );
        $c{"addr"} = $addr if( defined $addr );
        $c{"port"} = $port if( defined $port );
        $c{"opts"} = $opts if( defined $opts );

        $c{'index'} = scalar(@l);
        push( @l, \%c );
    }

    $res{"$a"} = \@l;
    return wantarray() ? %res : \%res;
}

=item add_https_port / del_https_port / set_https_port / get_https_port

=cut

sub mkargs_https_port {
    my $self = shift;
    my (%p) = @_;

    my @v = ();
    if( my $port = $p{"port"} ){
        my $line = "$port";
        if( my $addr = $p{"addr"} ){
            $line = "$addr:$port";
        }
        if( my $opts = $p{"opts"} ){
            $line .= " $opts";
        }

        @v = ( $line );
    } elsif( my $value = $p{'value'} || $p{'values'} || $p{'https_port'} ){
        @v = ( ref($value) eq 'ARRAY' ? @$value : $value );
    }

    my %r = ( %p, 'name'=>"https_port", 'values'=>[ @v ] );

    return wantarray() ? %r : \%r;
}

sub mkget_https_port {
    my $self = shift;
    my ($C) = @_;

    my $a = "https_port";

    my %res = ();

    my @l = ( );
    for my $l ( ( ref($C) eq 'ARRAY' ? @$C : $C ) ){
        next if( !$l );

        my (undef,$addr,$port,$opts) = ( $l =~ m/^((\S+):)?(\d+)\s*(.*)$/ );
        my %c = ( 'value'=>$l );
        $c{"addr"} = $addr if( defined $addr );
        $c{"port"} = $port if( defined $port );
        $c{"opts"} = $opts if( defined $opts );

        $c{'index'} = scalar(@l);
        push( @l, \%c );
    }

    $res{"$a"} = \@l;
    return wantarray() ? %res : \%res;
}

=item add_acl / del_acl / set_acl 

       Defining an Access List

    ARGS: name type string

=cut

sub mkargs_acl {
    my $self = shift;
    my (%p) = @_;

    my @v = ();
    if( $p{'name'} ){
        my $vals = $p{"vals"};

        if( $p{'file'} ){
            if( !$vals ){
                $vals = "\"$p{'file'}\"";
            }

            if( $p{'filecontent'} ){
                open(F,">$p{'file'}");
                print F $p{'filecontent'};
                close(F);
            }
        }

        @v = ( "$p{'name'} $p{'type'} $vals" );
    } elsif( my $value = $p{'value'} || $p{'values'} || $p{'acl'} ){
        @v = ( ref($value) eq 'ARRAY' ? @$value : $value );
    }

    my %r = ( %p, 'name'=>"acl", 'values'=>[ @v ] );

    return wantarray() ? %r : \%r;
}

sub mkget_acl {
    my $self = shift;
    my ($C) = @_;

    my $a = "acl";

    my %res = ();

    my %D = $self->get_deny_info();
    my @denyl = $D{'deny_info'} ? @{$D{'deny_info'}} : ();

    my @l = ( );
    for my $l ( ( ref($C) eq 'ARRAY' ? @$C : $C ) ){
        next if( !$l );

        my @acl = split(/\s/,$l,3);
        my %c = ( 'value'=>$l, 'name'=>$acl[0], 'type'=>$acl[1], 'vals'=>$acl[2] );

        if( $c{'vals'} =~ m/"([^"]+)"/ ){
            $c{'file'} = $1;
            open(F,"$c{'file'}");
            while(<F>){
                $c{'filecontent'} .= $_;
            }
            close(F);
        }

        my @fdeny = grep { $_->{'acl'} eq $c{'name'} } @denyl;
        if( @fdeny ){
            $c{'deny_info'} = \@fdeny;
        }

        $c{'index'} = scalar(@l);
        push( @l, \%c );
    }

    $res{"$a"} = \@l;
    return wantarray() ? %res : \%res;
}

sub add_acl {
    my $self = shift;
    my %acl = my %p = @_;

    %p = $self->mkargs_acl( %p );

    if( %p && ! isError(%p) ){
        my $R = $self->add_config_option( %p );
        if( my $deny = $acl{'deny_info'} ){
            my @ld = ref($deny) eq 'ARRAY' ? @$deny : ($deny);
            for my $D (@ld){
                # garantee no repeated
                $self->del_deny_info( 'acl'=>$acl{'name'}, %$D );
                $self->add_deny_info( 'acl'=>$acl{'name'}, %$D );
            }
        }

        return $R;
    }
    return wantarray() ? %p : \%p;
}
sub del_acl {
    my $self = shift;
    my %acl = my %p = @_;

    %p = $self->mkargs_acl( %p );

    if( %p && ! isError(%p) ){
        if( my $deny = $acl{'deny_info'} ){
            my @ld = ref($deny) eq 'ARRAY' ? @$deny : ($deny);
            for my $D (@ld){
                $self->del_deny_info( 'acl'=>$acl{'name'}, %$D );
            }
        }
        return $self->del_config_option( %p );
    }
    return wantarray() ? %p : \%p;
}
sub set_acl {
    my $self = shift;
    my %acl = my %p = @_;

    %p = $self->mkargs_acl( %p );

    if( %p && ! isError(%p) ){
        my $R = $self->set_config_option( %p );
        if( my $deny = $acl{'deny_info'} ){
            my @ld = ref($deny) eq 'ARRAY' ? @$deny : ($deny);
            for my $D (@ld){
                # garantee no repeated
                $self->del_deny_info( 'acl'=>$acl{'name'}, %$D );
                $self->add_deny_info( 'acl'=>$acl{'name'}, %$D );
            }
        }

        return $R;
    }
    return wantarray() ? %p : \%p;
}

=item get_uniq_acl
    
    return uniq acl rules names

=cut

sub get_uniq_acl {
    my $self = shift;

    my %r = ();
    my %G = $self->get_acl();

    if( my $acl = $G{'acl'} ){
        my %dupa = ();  # duplicates
        my @u_acl = ();
        for my $A (@$acl){
            my $name = $A->{'name'};
            if( !$dupa{"$name"}++ ){
                push( @u_acl, { 'name'=> $name } );
            }
        }
        %r = ( 'uniq_acl'=> \@u_acl );
    }
    return wantarray() ? %r : \%r;
}

=item add_deny_info / del_deny_info / set_deny_info / get_deny_info

=cut

sub mkargs_deny_info {
    my $self = shift;
    my (%p) = @_;

    my @v = ();
    if( $p{'acl'} && $p{'val'} ){
        @v = ( "$p{'val'} $p{'acl'}" );
    } elsif( my $value = $p{'value'} || $p{'values'} || $p{'deny_info'} ){
        @v = ( ref($value) eq 'ARRAY' ? @$value : $value );
    }

    my %r = ( %p, 'name'=>"deny_info", 'values'=>[ @v ] );

    return wantarray() ? %r : \%r;
}

sub mkget_deny_info {
    my $self = shift;
    my ($C) = @_;

    my $a = "deny_info";

    my %res = ();

    my @l = ( );
    for my $l ( ( ref($C) eq 'ARRAY' ? @$C : $C ) ){
        next if( !$l );

        my @denyl = split(/\s/,$l);
        my %c = ( 'value'=>$l, 'acl'=>pop(@denyl), 'val'=>join(" ",@denyl) );

        $c{'index'} = scalar(@l);
        push( @l, \%c );
    }

    $res{"$a"} = \@l;
    return wantarray() ? %res : \%res;
}

=item add_http_access / del_http_access / set_http_access

       Allowing or Denying access based on defined access lists

    ARGS: allow|deny acl

=cut

sub mkargs_http_access {
    my $self = shift;
    my (%p) = @_;

    my @v = ();

    if( $p{'acl'} || $p{'match'} || $p{'dontmatch'} ){
        my $action = $p{"action"} || ( $p{"allow"} ? "allow" : "deny" );
        
        my @acl = ();

        if( $p{"acl"} ){
            push( @acl, ref($p{"acl"}) eq "ARRAY" ? @{$p{"acl"}} : ($p{"acl"}) );
        }

        if( $p{"match"} ){
            push( @acl, ref($p{"match"}) eq "ARRAY" ? @{$p{"match"}} : ($p{"match"}) );
        }

        if( $p{"dontmatch"} ){
            push( @acl, map { !/^!/ ? "!$_" : $_ } ref($p{"dontmatch"}) eq "ARRAY" ? @{$p{"dontmatch"}} : ($p{"dontmatch"}) );
        }

        my $lacl = join(" ",@acl);
        
        @v = ( "$action $lacl" );
    } elsif( my $value = $p{'value'} || $p{'values'} || $p{'http_access'} ){
        @v = ( ref($value) eq 'ARRAY' ? @$value : $value );
    }

    my %r = ( %p, 'name'=>"http_access", 'values'=>[ @v ] );

    return wantarray() ? %r : \%r;
}

sub mkget_http_access {
    my $self = shift;
    my ($C) = @_;

    my $a = "http_access";

    my %res = ();

    my @l = ( );
    for my $l ( ( ref($C) eq 'ARRAY' ? @$C : $C ) ){
        next if( !$l );

        my @acl = split(/\s/,$l);
        my %c = ( 'value'=>$l, 'action'=>shift(@acl), 'acl'=>\@acl );
        if( $c{'action'} eq 'allow' ){
            $c{'allow'} = 1;
        } else {
            $c{'deny'} = 1;
        }

        my @match = ();
        for my $a (@acl){
            if( $a !~ m/^!/ ){
                push(@match,$a);
            }
        }
        $c{'match'} = \@match;

        my @dontmatch = ();
        for my $a (@acl){
            if( $a =~ m/^!(.*)/ ){
                push(@dontmatch,$1);
            }
        }
        $c{'dontmatch'} = \@dontmatch;

        $c{'index'} = scalar(@l);
        push( @l, \%c );
    }

    $res{"$a"} = \@l;
    return wantarray() ? %res : \%res;
}

=item add_icp_access / del_icp_access / set_icp_access

       Allowing or Denying access to the ICP port based on defined
       access lists

    ARGS: allow|deny acl

=cut

sub mkargs_icp_access {
    my $self = shift;
    my (%p) = @_;

    my @v = ();

    if( $p{'acl'} || $p{'match'} || $p{'dontmatch'} ){
        my $action = $p{"allow"} ? "allow" : "deny";

        my @acl = ();

        if( $p{"acl"} ){
            push( @acl, ref($p{"acl"}) eq "ARRAY" ? @{$p{"acl"}} : ($p{"acl"}) );
        }

        if( $p{"match"} ){
            push( @acl, ref($p{"match"}) eq "ARRAY" ? @{$p{"match"}} : ($p{"match"}) );
        }

        if( $p{"dontmatch"} ){
            push( @acl, map { !/^!/ ? "!$_" : $_ } ref($p{"dontmatch"}) eq "ARRAY" ? @{$p{"dontmatch"}} : ($p{"dontmatch"}) );
        }

        my $lacl = join(" ",@acl);

        @v = ( "$action $lacl" );
    } elsif( my $value = $p{'value'} || $p{'values'} || $p{'http_access'} ){
        @v = ( ref($value) eq 'ARRAY' ? @$value : $value );
    }

    my %r = ( %p, 'name'=>"icp_access", 'values'=>[ @v ] );

    return wantarray() ? %r : \%r;
}

sub mkget_icp_access {
    my $self = shift;
    my ($C) = @_;

    my $a = "icp_access";

    my %res = ();

    my @l = ( );
    for my $l ( ( ref($C) eq 'ARRAY' ? @$C : $C ) ){
        next if( !$l );

        my @acl = split(/\s/,$l);
        my %c = ( 'value'=>$l, 'action'=>shift(@acl), 'acl'=>\@acl );
        if( $c{'action'} eq 'allow' ){
            $c{'allow'} = 1;
        } else {
            $c{'deny'} = 1;
        }

        my @match = ();
        for my $a (@acl){
            if( $a !~ m/^!/ ){
                push(@match,$a);
            }
        }
        $c{'match'} = \@match;

        my @dontmatch = ();
        for my $a (@acl){
            if( $a =~ m/^!(.*)/ ){
                push(@dontmatch,$1);
            }
        }
        $c{'dontmatch'} = \@dontmatch;

        $c{'index'} = scalar(@l);
        push( @l, \%c );
    }

    $res{"$a"} = \@l;
    return wantarray() ? %res : \%res;
}

=item add_http_reply_access / del_http_reply_access / set_http_reply_access / get_http_reply_access

       Allow replies to client requests. This is complementary to http_access.


    ARGS: allow|deny acl

=cut

sub mkargs_http_reply_access {
    my $self = shift;
    my (%p) = @_;

    my @v = ();

    if( $p{'acl'} || $p{'match'} || $p{'dontmatch'} ){
        my $action = $p{"action"} || ( $p{"allow"} ? "allow" : "deny" );
        
        my @acl = ();

        if( $p{"acl"} ){
            push( @acl, ref($p{"acl"}) eq "ARRAY" ? @{$p{"acl"}} : ($p{"acl"}) );
        }

        if( $p{"match"} ){
            push( @acl, ref($p{"match"}) eq "ARRAY" ? @{$p{"match"}} : ($p{"match"}) );
        }

        if( $p{"dontmatch"} ){
            push( @acl, map { !/^!/ ? "!$_" : $_ } ref($p{"dontmatch"}) eq "ARRAY" ? @{$p{"dontmatch"}} : ($p{"dontmatch"}) );
        }

        my $lacl = join(" ",@acl);
        
        @v = ( "$action $lacl" );
    } elsif( my $value = $p{'value'} || $p{'values'} || $p{'http_reply_access'} ){
        @v = ( ref($value) eq 'ARRAY' ? @$value : $value );
    }

    my %r = ( %p, 'name'=>"http_reply_access", 'values'=>[ @v ] );

    return wantarray() ? %r : \%r;
}

sub mkget_http_reply_access {
    my $self = shift;
    my ($C) = @_;

    my $a = "http_reply_access";

    my %res = ();

    my @l = ( );
    for my $l ( ( ref($C) eq 'ARRAY' ? @$C : $C ) ){
        next if( !$l );

        my @acl = split(/\s/,$l);
        my %c = ( 'value'=>$l, 'action'=>shift(@acl), 'acl'=>\@acl );
        if( $c{'action'} eq 'allow' ){
            $c{'allow'} = 1;
        } else {
            $c{'deny'} = 1;
        }

        my @match = ();
        for my $a (@acl){
            if( $a !~ m/^!/ ){
                push(@match,$a);
            }
        }
        $c{'match'} = \@match;

        my @dontmatch = ();
        for my $a (@acl){
            if( $a =~ m/^!(.*)/ ){
                push(@dontmatch,$1);
            }
        }
        $c{'dontmatch'} = \@dontmatch;

        $c{'index'} = scalar(@l);
        push( @l, \%c );
    }

    $res{"$a"} = \@l;
    return wantarray() ? %res : \%res;
}

=item add_always_direct / del_always_direct / set_always_direct / get_always_direct

    ARGS: allow|deny aclname

=cut

sub mkargs_always_direct {
    my $self = shift;
    my (%p) = @_;

    my @v = ();

    if( $p{'acl'} || $p{'match'} || $p{'dontmatch'} ){
        my $action = $p{"action"} || ( $p{"allow"} ? "allow" : "deny" );
        
        my @acl = ();

        if( $p{"acl"} ){
            push( @acl, ref($p{"acl"}) eq "ARRAY" ? @{$p{"acl"}} : ($p{"acl"}) );
        }

        if( $p{"match"} ){
            push( @acl, ref($p{"match"}) eq "ARRAY" ? @{$p{"match"}} : ($p{"match"}) );
        }

        if( $p{"dontmatch"} ){
            push( @acl, map { !/^!/ ? "!$_" : $_ } ref($p{"dontmatch"}) eq "ARRAY" ? @{$p{"dontmatch"}} : ($p{"dontmatch"}) );
        }

        my $lacl = join(" ",@acl);
        
        @v = ( "$action $lacl" );
    } elsif( my $value = $p{'value'} || $p{'values'} || $p{'always_direct'} ){
        @v = ( ref($value) eq 'ARRAY' ? @$value : $value );
    }

    my %r = ( %p, 'name'=>"always_direct", 'values'=>[ @v ] );

    return wantarray() ? %r : \%r;
}

sub mkget_always_direct {
    my $self = shift;
    my ($C) = @_;

    my $a = "always_direct";

    my %res = ();

    my @l = ( );
    for my $l ( ( ref($C) eq 'ARRAY' ? @$C : $C ) ){
        next if( !$l );

        my @acl = split(/\s/,$l);
        my %c = ( 'value'=>$l, 'action'=>shift(@acl), 'acl'=>\@acl );
        if( $c{'action'} eq 'allow' ){
            $c{'allow'} = 1;
        } else {
            $c{'deny'} = 1;
        }

        my @match = ();
        for my $a (@acl){
            if( $a !~ m/^!/ ){
                push(@match,$a);
            }
        }
        $c{'match'} = \@match;

        my @dontmatch = ();
        for my $a (@acl){
            if( $a =~ m/^!(.*)/ ){
                push(@dontmatch,$1);
            }
        }
        $c{'dontmatch'} = \@dontmatch;

        $c{'index'} = scalar(@l);
        push( @l, \%c );
    }

    $res{"$a"} = \@l;
    return wantarray() ? %res : \%res;
}

=item add_never_direct / del_never_direct / set_never_direct / get_never_direct

    ARGS: allow|deny aclname

=cut

sub mkargs_never_direct {
    my $self = shift;
    my (%p) = @_;

    my @v = ();

    if( $p{'acl'} || $p{'match'} || $p{'dontmatch'} ){
        my $action = $p{"action"} || ( $p{"allow"} ? "allow" : "deny" );
        
        my @acl = ();

        if( $p{"acl"} ){
            push( @acl, ref($p{"acl"}) eq "ARRAY" ? @{$p{"acl"}} : ($p{"acl"}) );
        }

        if( $p{"match"} ){
            push( @acl, ref($p{"match"}) eq "ARRAY" ? @{$p{"match"}} : ($p{"match"}) );
        }

        if( $p{"dontmatch"} ){
            push( @acl, map { !/^!/ ? "!$_" : $_ } ref($p{"dontmatch"}) eq "ARRAY" ? @{$p{"dontmatch"}} : ($p{"dontmatch"}) );
        }

        my $lacl = join(" ",@acl);
        
        @v = ( "$action $lacl" );
    } elsif( my $value = $p{'value'} || $p{'values'} || $p{'never_direct'} ){
        @v = ( ref($value) eq 'ARRAY' ? @$value : $value );
    }

    my %r = ( %p, 'name'=>"never_direct", 'values'=>[ @v ] );

    return wantarray() ? %r : \%r;
}

sub mkget_never_direct {
    my $self = shift;
    my ($C) = @_;

    my $a = "never_direct";

    my %res = ();

    my @l = ( );
    for my $l ( ( ref($C) eq 'ARRAY' ? @$C : $C ) ){
        next if( !$l );

        my @acl = split(/\s/,$l);
        my %c = ( 'value'=>$l, 'action'=>shift(@acl), 'acl'=>\@acl );
        if( $c{'action'} eq 'allow' ){
            $c{'allow'} = 1;
        } else {
            $c{'deny'} = 1;
        }

        my @match = ();
        for my $a (@acl){
            if( $a !~ m/^!/ ){
                push(@match,$a);
            }
        }
        $c{'match'} = \@match;

        my @dontmatch = ();
        for my $a (@acl){
            if( $a =~ m/^!(.*)/ ){
                push(@dontmatch,$1);
            }
        }
        $c{'dontmatch'} = \@dontmatch;

        $c{'index'} = scalar(@l);
        push( @l, \%c );
    }

    $res{"$a"} = \@l;
    return wantarray() ? %res : \%res;
}

=item add_external_acl_type / del_external_acl_type / set_external_acl_type

       This option defines external acl classes using a helper program to
       look up the status

    ARGS: name [options] format helper [args]

       Options:

         ttl=n         TTL in seconds for cached results (defaults to 3600
                       for 1 hour)
         negative_ttl=n
                       TTL for cached negative lookups (default same
                       as ttl)
         children=n    number of processes spawn to service external acl
                       lookups of this type. (default 5).
         concurrency=n concurrency level per process. Only used with helpers
                       capable of processing more than one query at a time.
                       Note: see compatibility note below
         cache=n       result cache size, 0 is unbounded (default)
         grace=        Percentage remaining of TTL where a refresh of a
                       cached entry should be initiated without needing to
                       wait for a new reply. (default 0 for no grace period)
         protocol=2.5  Compatibility mode for Squid-2.5 external acl helpers

       FORMAT specifications

         %LOGIN        Authenticated user login name
         %EXT_USER     Username from external acl
         %IDENT        Ident user name
         %SRC          Client IP
         %SRCPORT      Client source port
         %DST          Requested host
         %PROTO        Requested protocol
         %PORT         Requested port
         %METHOD       Request method
         %MYADDR       Squid interface address
         %MYPORT       Squid http_port number
         %PATH         Requested URL-path (including query-string if any)
         %USER_CERT    SSL User certificate in PEM format
         %USER_CERTCHAIN SSL User certificate chain in PEM format
         %USER_CERT_xx SSL User certificate subject attribute xx
         %USER_CA_xx   SSL User certificate issuer attribute xx
         %{Header}     HTTP request header
         %{Hdr:member} HTTP request header list member
         %{Hdr:;member}
                       HTTP request header list member using ; as
                       list separator. ; can be any non-alphanumeric
                       character.
        %ACL           The ACL name
        %DATA          The ACL arguments. If not used then any arguments
                       is automatically added at the end

=cut

sub mkargs_external_acl_type {
    my $self = shift;
    my (%p) = @_;

    my @v = ();

    if( $p{'name'} ){
        my @acl = ( $p{"name"} );
        
        if( my $options = $p{'options'} ){
            if( !ref($options) ){
                push(@acl, $options);
            } elsif( ref($options) eq 'ARRAY' ){
                push(@acl, @$options);
            } elsif( ref($options) ){
                for my $o (keys %$options){
                    if( my $v = $options->{"$o"} ){
                        push(@acl, "$o=$v" );
                    }
                }
            }
        }

        push(@acl,$p{"format"});
        push(@acl,$p{"helper"});
        my @args = ref($p{"args"}) eq "ARRAY" ? @{$p{"args"}} : ($p{"args"});
        push(@acl,@args) if( @args );

        my $sv = join(" ",@acl);

        @v = ( $sv );
    } elsif( my $value = $p{'value'} || $p{'values'} || $p{'external_acl_type'} ){
        @v = ( ref($value) eq 'ARRAY' ? @$value : $value );
    }

    my %r = ( %p, 'name'=>"external_acl_type", 'values'=>[ @v ] );

    return wantarray() ? %r : \%r;
}

sub mkget_external_acl_type {
    my $self = shift;
    my ($C) = @_;

    my $a = "external_acl_type";

    my %res = ();

    my @l = ( );
    for my $l ( ( ref($C) eq 'ARRAY' ? @$C : $C ) ){
        next if( !$l );

        my @acl = split(/\s/,$l);

        my %c = ( 'value'=>$l, 'name'=>shift(@acl) );
        my @options = ();
        my $i=0;
        for(; $i<scalar(@acl); $i++){
            if( $acl[$i] =~ m/([^=]+)=(\S+)/ ){
                $c{'options'}{"$1"} = "$2";
            } else {
                last;
            }
        }
        $c{'format'} = $acl[$i++];
        $c{'helper'} = $acl[$i++];
        for(; $i<scalar(@acl); $i++){
            push(@{$c{'args'}}, $acl[$i] );
        }

        $c{'index'} = scalar(@l);
        push( @l, \%c );
    }

    $res{"$a"} = \@l;
    return wantarray() ? %res : \%res;
}

=item add_cache_peer / del_cache_peer / set_cache_peer

    cache_peer hostname type http-port icp-port [options]

=cut

sub mkargs_cache_peer {
    my $self = shift;
    my (%p) = @_;

    my @v = ();

    if( $p{'hostname'} &&
        $p{'type'} &&
        defined($p{'http-port'}) &&
        defined($p{'icp-port'}) ){

        my @cache_peer = ( $p{'hostname'}, $p{'type'}, $p{'http-port'}, $p{'icp-port'} );
        
        if( my $options = $p{'options'} ){
            if( !ref($options) ){
                push(@cache_peer, $options);
            } elsif( ref($options) eq 'ARRAY' ){
                push(@cache_peer, @$options);
            } elsif( ref($options) ){
                for my $o (keys %$options){
                    my $v = $options->{"$o"};
                    push(@cache_peer, ( $v ? "$o=$v" : "$o" ) );
                }
            }
        }

        my $sv = join(" ",@cache_peer);

        @v = ( $sv );
    } elsif( my $value = $p{'value'} || $p{'values'} || $p{'cache_peer'} ){
        @v = ( ref($value) eq 'ARRAY' ? @$value : $value );
    }

    my %r = ( %p, 'name'=>"cache_peer", 'values'=>[ @v ] );

    return wantarray() ? %r : \%r;
}

sub mkget_cache_peer {
    my $self = shift;
    my ($C) = @_;

    my $a = "cache_peer";

    my %res = ();

    my %D = $self->get_cache_peer_domain();
    my @cache_peer_domain = $D{'cache_peer_domain'} ? @{$D{'cache_peer_domain'}} : ();

    my @l = ( );
    for my $l ( ( ref($C) eq 'ARRAY' ? @$C : $C ) ){
        next if( !$l );

        my @cache_peer = split(/\s/,$l);

        my %c = ( 'value'=>$l, 'hostname'=>shift(@cache_peer), 'type'=>shift(@cache_peer), 'http-port'=>shift(@cache_peer), 'icp-port'=>shift(@cache_peer) );
        my @options = ();
        my $i=0;
        for(; $i<scalar(@cache_peer); $i++){
            my ($o,$v) = split(/=/,$cache_peer[$i]);
            $c{'options'}{"$o"} = $v ? "$v" : "";
        }

        $c{'index'} = scalar(@l);

        my @fcache_peer_domain = grep { $_->{'host'} eq $c{'hostname'} } @cache_peer_domain;
        if( @fcache_peer_domain ){
            $c{'cache_peer_domain'} = \@fcache_peer_domain;
        }

        push( @l, \%c );
    }

    $res{"$a"} = \@l;
    return wantarray() ? %res : \%res;
}

sub add_cache_peer {
    my $self = shift;
    my %cache_peer = my %p = @_;

    %p = $self->mkargs_cache_peer( %p );

    if( %p && ! isError(%p) ){
        my $R = $self->add_config_option( %p );
        if( my $cpd = $cache_peer{'cache_peer_domain'} ){
            my @ld = ref($cpd) eq 'ARRAY' ? @$cpd : ($cpd);
            for my $D (@ld){
                # garantee no repeated
                $self->del_cache_peer_domain( 'host'=>$cache_peer{'hostname'}, %$D );
                $self->add_cache_peer_domain( 'host'=>$cache_peer{'hostname'}, %$D );
            }
        }

        return $R;
    }
    return wantarray() ? %p : \%p;
}
sub del_cache_peer {
    my $self = shift;
    my %cache_peer = my %p = @_;

    %p = $self->mkargs_cache_peer( %p );

    if( %p && ! isError(%p) ){
        if( my $cpd = $cache_peer{'cache_peer_domain'} ){
            my @ld = ref($cpd) eq 'ARRAY' ? @$cpd : ($cpd);
            for my $D (@ld){
                $self->del_cache_peer_domain( 'host'=>$cache_peer{'hostname'}, %$D );
            }
        }
        return $self->del_config_option( %p );
    }
    return wantarray() ? %p : \%p;
}
sub set_cache_peer {
    my $self = shift;
    my %cache_peer = my %p = @_;

    %p = $self->mkargs_cache_peer( %p );

    if( %p && ! isError(%p) ){
        my $R = $self->set_config_option( %p );
        if( my $cpd = $cache_peer{'cache_peer_domain'} ){
            my @ld = ref($cpd) eq 'ARRAY' ? @$cpd : ($cpd);
            for my $D (@ld){
                # garantee no repeated
                $self->del_cache_peer_domain( 'host'=>$cache_peer{'hostname'}, %$D );
                $self->add_cache_peer_domain( 'host'=>$cache_peer{'hostname'}, %$D );
            }
        }

        return $R;
    }
    return wantarray() ? %p : \%p;
}

=item add_cache_peer_domain / del_cache_peer_domain / set_cache_peer_domain

    cache_peer_domain cache-host domain [domain ...]
    cache_peer_domain cache-host !domain

=cut

sub mkargs_cache_peer_domain {
    my $self = shift;
    my (%p) = @_;

    my @v = ();

    if( $p{'host'} &&
        ( $p{'domains'} ||
            $p{'query'} ||
            $p{'dontquery'} ) ){

        my @domains = ();
        if( $p{"domains"} ){
            for my $d ( ref($p{"domains"}) eq "ARRAY" ? @{$p{"domains"}} : ($p{"domains"}) ){
                next if( !$d );
                push( @domains, $d );
            }
        }

        if( $p{"query"} ){
            for my $d ( ref($p{"query"}) eq "ARRAY" ? @{$p{"query"}} : ($p{"query"}) ){
                next if( !$d );
                push( @domains, $d );
            }
        }

        if( $p{"dontquery"} ){
            for my $d ( ref($p{"dontquery"}) eq "ARRAY" ? @{$p{"dontquery"}} : ($p{"dontquery"}) ){
                next if( !$d );
                push( @domains, ( !/^!/ ? "!$d" : $d ) );
            }
        }

        if( @domains ){
            my @cache_peer_domain = ( $p{'host'} );
        
            push( @cache_peer_domain, @domains );

            my $sv = join(" ",@cache_peer_domain);

            @v = ( $sv );
        }
    } elsif( my $value = $p{'value'} || $p{'values'} || $p{'cache_peer_domain'} ){
        @v = ( ref($value) eq 'ARRAY' ? @$value : $value );
    }

    my %r = ( %p, 'name'=>"cache_peer_domain", 'values'=>[ @v ] );

    return wantarray() ? %r : \%r;
}

sub mkget_cache_peer_domain {
    my $self = shift;
    my ($C) = @_;

    my $a = "cache_peer_domain";

    my %res = ();

    my @l = ( );
    for my $l ( ( ref($C) eq 'ARRAY' ? @$C : $C ) ){
        next if( !$l );

        my @cache_peer_domain = split(/\s/,$l);

        my %c = ( 'value'=>$l, 'host'=>shift(@cache_peer_domain), 'domains'=>\@cache_peer_domain );

        my @query = ();
        for my $a (@cache_peer_domain){
            if( $a !~ m/^!/ ){
                push(@query,$a);
            }
        }
        $c{'query'} = \@query;

        my @dontquery = ();
        for my $a (@cache_peer_domain){
            if( $a =~ m/^!(.*)/ ){
                push(@dontquery,$1);
            }
        }
        $c{'dontquery'} = \@dontquery;

        $c{'index'} = scalar(@l);
        push( @l, \%c );
    }

    $res{"$a"} = \@l;
    return wantarray() ? %res : \%res;
}

=item apply_config

=cut

sub apply_config {
    my $self = shift;

    return ETFW::Squid::Webmin->apply_config(@_);
}

=item set other attributes/configuration

    ver: http://www.squid-cache.org/Doc/config/

    auth_param
    authenticate_cache_garbage_interval
    authenticate_ttl
    authenticate_ip_ttl
    external_acl_type
    acl
    http_access
    http_access2
    http_reply_access
    icp_access
    htcp_access
    htcp_clr_access
    miss_access
    ident_lookup_access
    reply_body_max_size     bytes allow|deny acl acl...
    follow_x_forwarded_for
    acl_uses_indirect_client        on|off
    delay_pool_uses_indirect_client on|off
    log_uses_indirect_client        on|off
    http_port
    https_port
    tcp_outgoing_tos
    tcp_outgoing_address
    ssl_unclean_shutdown
    ssl_engine
    sslproxy_client_certificate
    sslproxy_client_key
    sslproxy_version
    sslproxy_options
    sslproxy_cipher
    sslproxy_cafile
    sslproxy_capath
    sslproxy_flags
    sslpassword_program
    cache_peer
    cache_peer_domain
    cache_peer_access
    neighbor_type_domain
    dead_peer_timeout       (seconds)
    hierarchy_stoplist
    cache_mem       (bytes)
    maximum_object_size_in_memory   (bytes)
    memory_replacement_policy
    cache_replacement_policy
    cache_dir
    store_dir_select_algorithm
    max_open_disk_fds
    minimum_object_size     (bytes)
    maximum_object_size     (bytes)
    cache_swap_low  (percent, 0-100)
    cache_swap_high (percent, 0-100)
    logformat
    access_log
    log_access      allow|deny acl acl...
    cache_log
    cache_store_log
    cache_swap_state
    logfile_rotate
    emulate_httpd_log       on|off
    log_ip_on_direct        on|off
    mime_table
    log_mime_hdrs   on|off
    useragent_log
    referer_log
    pid_filename
    debug_options
    log_fqdn        on|off
    client_netmask
    forward_log
    strip_query_terms
    buffered_logs   on|off
    ftp_user
    ftp_list_width
    ftp_passive
    ftp_sanitycheck
    ftp_telnet_protocol
    diskd_program
    unlinkd_program
    pinger_program
    url_rewrite_program
    url_rewrite_children
    url_rewrite_concurrency
    url_rewrite_host_header
    url_rewrite_access
    redirector_bypass
    location_rewrite_program
    location_rewrite_children
    location_rewrite_concurrency
    location_rewrite_access
    cache
    refresh_pattern
    quick_abort_min (KB)
    quick_abort_max (KB)
    quick_abort_pct (percent)
    read_ahead_gap  buffer-size
    negative_ttl    time-units
    positive_dns_ttl        time-units
    negative_dns_ttl        time-units
    range_offset_limit      (bytes)
    minimum_expiry_time     (seconds)
    store_avg_object_size   (kbytes)
    store_objects_per_bucket
    request_header_max_size (KB)
    reply_header_max_size   (KB)
    request_body_max_size   (KB)
    broken_posts
    via     on|off
    cache_vary
    broken_vary_encoding
    collapsed_forwarding    (on|off)
    refresh_stale_hit       (time)
    ie_refresh      on|off
    vary_ignore_expire      on|off
    extension_methods
    request_entities
    header_access
    header_replace
    relaxed_header_parser   on|off|warn
    forward_timeout time-units
    connect_timeout time-units
    peer_connect_timeout    time-units
    read_timeout    time-units
    request_timeout
    persistent_request_timeout
    client_lifetime time-units
    half_closed_clients
    pconn_timeout
    ident_timeout
    shutdown_lifetime       time-units
    cache_mgr
    mail_from
    mail_program
    cache_effective_user
    cache_effective_group
    httpd_suppress_version_string   on|off
    visible_hostname
    unique_hostname
    hostname_aliases
    umask
    announce_period
    announce_host
    announce_file
    announce_port
    httpd_accel_no_pmtu_disc        on|off
    delay_pools
    delay_class
    delay_access
    delay_parameters
    delay_initial_bucket_level      (percent, 0-100)
    wccp_router
    wccp2_router
    wccp_version
    wccp2_rebuild_wait
    wccp2_forwarding_method
    wccp2_return_method
    wccp2_assignment_method
    wccp2_service
    wccp2_service_info
    wccp2_weight
    wccp_address
    wccp2_address
    client_persistent_connections
    server_persistent_connections
    persistent_connection_after_error
    detect_broken_pconn
    digest_generation
    digest_bits_per_entry
    digest_rebuild_period   (seconds)
    digest_rewrite_period   (seconds)
    digest_swapout_chunk_size       (bytes)
    digest_rebuild_chunk_percentage (percent, 0-100)
    snmp_port
    snmp_access
    snmp_incoming_address
    snmp_outgoing_address
    icp_port
    htcp_port
    log_icp_queries on|off
    udp_incoming_address
    udp_outgoing_address
    icp_hit_stale   on|off
    minimum_direct_hops
    minimum_direct_rtt
    netdb_low
    netdb_high
    netdb_ping_period
    query_icmp      on|off
    test_reachability       on|off
    icp_query_timeout       (msec)
    maximum_icp_query_timeout       (msec)
    minimum_icp_query_timeout       (msec)
    mcast_groups
    mcast_miss_addr
    mcast_miss_ttl
    mcast_miss_port
    mcast_miss_encode_key
    mcast_icp_query_timeout (msec)
    icon_directory
    global_internal_static
    short_icon_urls
    error_directory
    error_map
    err_html_text
    deny_info
    nonhierarchical_direct
    prefer_direct
    always_direct
    never_direct
    incoming_icp_average
    incoming_http_average
    incoming_dns_average
    min_icp_poll_cnt
    min_dns_poll_cnt
    min_http_poll_cnt
    tcp_recv_bufsize        (bytes)
    check_hostnames
    allow_underscore
    cache_dns_program
    dns_children
    dns_retransmit_interval
    dns_timeout
    dns_defnames    on|off
    dns_nameservers
    hosts_file
    dns_testnames
    append_domain
    ignore_unknown_nameservers
    ipcache_size    (number of entries)
    ipcache_low     (percent)
    ipcache_high    (percent)
    fqdncache_size  (number of entries)
    memory_pools    on|off
    memory_pools_limit      (bytes)
    forwarded_for   on|off
    cachemgr_passwd
    client_db       on|off
    reload_into_ims on|off
    maximum_single_addr_tries
    retry_on_error
    as_whois_server
    offline_mode
    uri_whitespace
    coredump_dir
    chroot
    balance_on_multiple_ip
    pipeline_prefetch
    high_response_time_warning      (msec)
    high_page_fault_warning
    high_memory_warning
    sleep_after_fork        (microseconds)
    max_filedesc

=cut

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

package ETFW::Squid::Webmin;

use ETVA::Utils;

use Data::Dumper;

sub startup_config {
    my $self = shift;
    my (%p) = @_;

    no strict;

    use ETFW::Webmin;

    my %WebminConf = ETFW::Webmin->get_config();

    require "$WebminConf{root}/squid/parser-lib.pl";
    require "$WebminConf{root}/web-lib-funcs.pl";

    %config = $p{'squid_conf'} ? %p : ( squid_conf=>'/etc/squid/squid.conf' );

    use strict;

}

sub load_config {
    my $self = shift;
    my ($force) = @_;
    if( $force ){
        no strict;
        @get_config_cache = ();
        use strict;
    }
    return get_config();
}

sub apply_config {
    my $self = shift;

    my ($e,$m);
    no strict;
    if( $config{'squid_reload'} ){
        ($e,$m) = cmd_exec("$config{'squid_reload'}");
    }
    unless( defined($e) && ( $e == 0 ) ){
        if( $config{'squid_restart'} ){
            ($e,$m) = cmd_exec("$config{'squid_restart'}");
        } elsif( -x "/etc/init.d/squid" ){
            ($e,$m) = cmd_exec("/etc/init.d/squid reload");
            unless( $e == 0 ){
                ($e,$m) = cmd_exec("/etc/init.d/squid stop");
                ($e,$m) = cmd_exec("/etc/init.d/squid start");
            }
        } else {
            ($e,$m) = cmd_exec("$config{'squid_path'} -f $config{'squid_conf'} -k reconfigure");
        }
    }
    use strict;
    
    unless( $e == 0 ){
        return retErr("_ERR_APPLY_CONFIG_","Error apply configuration: $m");
    }
    return retOk("_OK_APPLY_CONFIG_","Apply configuration ok.");
}

&startup_config();

1;
