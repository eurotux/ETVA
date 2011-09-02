#!/usr/bin/perl

=pod

=head1 NAME

ETFW::DHCP

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package ETFW::DHCP;

use strict;

use ETVA::Utils;
use FileFuncs;

use Data::Dumper;
use Time::Local;

BEGIN {
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF $AUTOLOAD);
};

my %CONF = ( "dhcpd_conf"=>"/etc/dhcpd.conf", 'lease_file'=>'/var/lib/dhcpd/dhcpd.leases' );

my %EXCLUEPARAMS = ( 'args'=>1, 'parameters'=>1, 'declarations'=>1, 'comments'=>1, 'index'=>1, 'parent'=>1, 'type'=>1, 'lastcomment'=>1, 'uuid'=>1, 'conf_file'=>1, 'lastindex'=>1, 'line'=>1, 'dispatcher'=>1 );
my %QuotedParams = ( 'filename'=>1, 'server-name'=>1, 'dynamic-bootp-lease-cutoff'=>1, 'ddns-domainname'=>1, 'ddns-rev-domainname'=>1, 'ddns-hostname'=>1 );
my %ValidDeclaration = ( "subnet"=>1, "pool"=>1, "shared-network"=>1, "group"=>1, "host"=>1, "zone"=>1, "key"=>1, "allow-update"=>1, "logging"=>1, "channel"=>1, "category"=>1, "failover"=>1, "class"=>1, "subclass"=>1 );
my %NotValidParameters = ( "subnets"=>1, "pools"=>1, "sharednetworks"=>1, "groups"=>1, "hosts"=>1, "zones"=>1 );

=item load_config

=cut

=pod

=begin comment

conf-file :== parameters declarations END_OF_FILE
   parameters :== <nil> | parameter | parameters parameter
   declarations :== <nil> | declaration | declarations declaration

=end comment

=cut

sub load_config {
    my $self = shift;
    my (%p) = @_;

    my $c_file = $p{"conf_file"} || $CONF{"conf_file"} || $CONF{"dhcpd_conf"};
    $CONF{"conf_file"} = $c_file if( !$CONF{"conf_file"} );
    my ($c_dir) = ( $c_file =~ m/^(\S+)\// );

    my %conf = ( "conf_file"=>$c_file );
    my $L = \%conf;

    my $index=0;
    my $line=1;
    my $fh;
    open($fh,$c_file);
    while(<$fh>){
        chomp;

        next if( ! $_ );

        $index = 0;
        $index += scalar(@{$L->{'declarations'}}) if( $L->{'declarations'} );
        $index += scalar(@{$L->{'parameters'}}) if( $L->{'parameters'} );

        $L->{"innercomments"} = [] if( !$L->{"innercomments"} );
        # inner comments
        # TODO ignore other types of comments
        if( s/#\s*(.*)// ){
            my $com = $1 || "";
            my $conly = 0;
            $conly = 1 if( !$_ || /^\s*$/ );
            push(@{$L->{"innercomments"}},{ comment=>$com, line=>$line, "conf_file"=>$c_file, "index"=>$index, alone=>$conly });
        }

        $L = $self->parse_declarations($L,$_,$c_file,$line,$index);

        if( !$L->{'end'} ){
            $L = $self->parse_parameters($L,$_,$c_file,$line,$index,$c_dir);
        }

        if( /}/ || $L->{'end'} ){
            # count lastindex
            my $lastindex = 0;
            $lastindex += scalar(@{$L->{'declarations'}}) if( $L->{'declarations'} );
            $lastindex += scalar(@{$L->{'parameters'}}) if( $L->{'parameters'} );
            $lastindex --;
            $L->{"lastindex"} = $lastindex;
            $L = $L->{"parent"} ? $L->{"parent"} : \%conf;
        }

        $line++;
    }
    # count lastindex
    my $lastindex = 0;
    $lastindex += scalar(@{$L->{'declarations'}}) if( $L->{'declarations'} );
    $lastindex += scalar(@{$L->{'parameters'}}) if( $L->{'parameters'} );
    $lastindex --;
    $L->{"lastindex"} = $lastindex;

    close($fh);

    return wantarray() ? %conf : \%conf;
}

# mkuuid_declaration: generate uuid for declaration
sub mkuuid_declaration {
    my $self = shift;
    my ($L) = @_;
    my $uid = "$L->{'index'}-$L->{'type'}";
    if( my $P = $L->{'parent'} ){
        if( $P->{'uuid'} ){
            $uid .= "-$P->{'uuid'}";
        }
    }
    return $uid;
}

=pod

=begin comment

statement :== parameter | declaration

   parameter :== DEFAULT_LEASE_TIME lease_time
           | MAX_LEASE_TIME lease_time
           | DYNAMIC_BOOTP_LEASE_CUTOFF date
           | DYNAMIC_BOOTP_LEASE_LENGTH lease_time
           | BOOT_UNKNOWN_CLIENTS boolean
           | ONE_LEASE_PER_CLIENT boolean
           | GET_LEASE_HOSTNAMES boolean
           | USE_HOST_DECL_NAME boolean
           | NEXT_SERVER ip-addr-or-hostname SEMI
           | option_parameter
           | SERVER-IDENTIFIER ip-addr-or-hostname SEMI
           | FILENAME string-parameter
           | SERVER_NAME string-parameter
           | hardware-parameter
           | fixed-address-parameter
           | ALLOW allow-deny-keyword
           | DENY allow-deny-keyword
           | USE_LEASE_ADDR_FOR_DEFAULT_ROUTE boolean
           | AUTHORITATIVE
           | NOT AUTHORITATIVE

   declaration :== host-declaration
         | group-declaration
         | shared-network-declaration
         | subnet-declaration
         | VENDOR_CLASS class-declaration
         | USER_CLASS class-declaration
         | RANGE address-range-declaration

=end comment

=cut

sub parse_declarations {
    my $self = shift;
    my ($L,$s,$f,$l,$i) = @_;
    
    while( my ($d,$def) = ( $s =~ /(\S+)\s+(.*)/gc ) ){
        if( my $D = $self->parse_declaration($d,$def) ){
            $D->{"conf_file"} = $f;
            $D->{'line'} = $l;
            $D->{'index'} = $i;
            $D->{"type"} = $d;
            $D->{"parent"} = $L;
            $D->{'uuid'} = $self->mkuuid_declaration($D);
            $D->{"comments"} = $L->{'innercomments'} ? delete $L->{'innercomments'} : [];
            $L->{"declarations"} = [] if( !$L->{"declarations"} );
            push(@{$L->{"declarations"}},$D);
            $L = $D;
        }
    }

    return $L;
}

=pod

=begin comment

   declaration :== host-declaration
         | group-declaration
         | shared-network-declaration
         | subnet-declaration
         | VENDOR_CLASS class-declaration
         | USER_CLASS class-declaration
         | RANGE address-range-declaration

=end comment

=cut

sub parse_declaration {
    my $self = shift;
    my ($d,$def,$force) = @_;

    # should end with {
    if( $force || $def =~ /{\s*$/ ){
        if( $d eq "subnet" ){ 
            $def =~ /(\S+)\s+(\S+)\s+(\S+)/;
            my %S = ( address=>"$1", netmask=>"$3", "args"=>"$1 $2 $3" );
            return \%S;
        } elsif( $d eq "pool" ){
            my %P = ( "args"=>"" );
            return \%P;
        } elsif( $d eq "shared-network" ){
            $def =~ /(\S+)/;
            my %S = ( name=>"$1", "args"=>$1 );
            return \%S;
        } elsif( $d eq "group" ){
            my %G = ( "args"=>"" );
            return \%G;
        } elsif( $d eq "host" ){
            $def =~ /(\S+)/;
            my %H = ( host=>$1, "args"=>$& );
            return \%H;
        } elsif( $d eq "zone" ){
            $def =~ /"?([^"\s]+)"?/;
            my %Z = ( name=>$1, "args"=>$& );
            return \%Z;
        } elsif( $d eq "key" &&
                    $def =~ m/([^;{\s]+)/ ){
            my %K = ( key=>$1, "args"=>"$1" );
            return \%K;
        } elsif( $d eq "allow-update" ){
            my %A = ( "args"=>"" );
            return \%A;
        } elsif( $d eq "logging" ){
            my %L = ( "args"=>"" );
            return \%L;
        } elsif( $d eq "channel" ){
            $def =~ /(\S+)/;
            my %N = ( type=>$1, "args"=>$& );
            return \%N;
        } elsif( $d eq "category" ){
            $def =~ /(\S+)/;
            my %G = ( name=>$1, "args"=>$& );
            return \%G;
        } elsif( $d eq "failover" &&
                    $def =~ /((\S+)\s+"?([^"]+)"?(\s+"?([^"]+)"?)?)/ ){
            my %F = ( name=>$3, "args"=>$1 );
            $F{"state"} = $5 if( $5 );
            return \%F;
        } elsif( $d eq "class" ){
            $def =~ /"?([^"]+)"?/;
            my %C = ( name=>$2, "args"=>$& );
            return \%C;
        } elsif( $d eq "subclass" ){
            $def =~ /("?([^"]+)"?\s+(\S+))/;
            my %C = ( name=>$2, subclass=>$3, "args"=>"$1" );
            return \%C;
        } elsif( $def =~ /([^{]*)/ ){
            my %O = ( "args"=> $1 );
            return \%O;
        }
    }
    return;
}

=pod

=begin comment

   parameter :== DEFAULT_LEASE_TIME lease_time
           | MAX_LEASE_TIME lease_time
           | DYNAMIC_BOOTP_LEASE_CUTOFF date
           | DYNAMIC_BOOTP_LEASE_LENGTH lease_time
           | BOOT_UNKNOWN_CLIENTS boolean
           | ONE_LEASE_PER_CLIENT boolean
           | GET_LEASE_HOSTNAMES boolean
           | USE_HOST_DECL_NAME boolean
           | NEXT_SERVER ip-addr-or-hostname SEMI
           | option_parameter
           | SERVER-IDENTIFIER ip-addr-or-hostname SEMI
           | FILENAME string-parameter
           | SERVER_NAME string-parameter
           | hardware-parameter
           | fixed-address-parameter
           | ALLOW allow-deny-keyword
           | DENY allow-deny-keyword
           | USE_LEASE_ADDR_FOR_DEFAULT_ROUTE boolean
           | AUTHORITATIVE
           | NOT AUTHORITATIVE

=end comment

=cut

sub parse_parameters {
    my $self = shift;
    my ($L,$s,$f,$l,$i,$c_dir) = @_;

    my @p = ();
    while( my ($d,$def) = ( $s =~ /(\S+)\s*(.*);/gc ) ){

        my $P = { name=>$d, value=>$def, 'index'=>$i, 'line'=>$l, "conf_file"=>$f, "parent"=>$L, quoted=>0 };
        if( $P->{"name"} eq "include" ){
            my ($i_file) = ( $def =~ /"([^"]+)"/ );
            if( $i_file !~ m/^\// ){
                $i_file = "$c_dir/$i_file";
            }
            $P->{"include"} = $self->load_config( "conf_file"=>$i_file );

            # merge files
            if( $P->{"include"}{"declarations"} ){
                $L->{"declarations"} = [] if( !$L->{"declarations"} );
                push(@{$L->{"declarations"}},@{$P->{"include"}{"declarations"}});
            }
            if( $P->{"include"}{"parameters"} ){
                $L->{"parameters"} = [] if( !$L->{"parameters"} );
                push(@{$L->{"parameters"}},@{$P->{"include"}{"parameters"}});
            }
        } elsif( $P->{"name"} eq "option" ){
            ($P->{"option"},$P->{"value"}) = ( $def =~ /(\S+)\s*(.*)/ );
        }
        if( $P->{"value"} =~ s/^"([^"]+)"$/$1/ ){
            $P->{"quoted"} = 1;
        } else {
            $P->{"value"} =~ s/"//g;
        }
        $L->{"parameters"} = [] if( !$L->{"parameters"} );
        push(@{$L->{"parameters"}},$P);
    }

    return $L;
}

=item save_config

=cut

sub save_config {
    my $self = shift;
    my (%p) = @_;

    my $c_file = $p{"conf_file"} || $CONF{"conf_file"} || $CONF{"dhcpd_conf"};
    my ($c_dir) = ( $c_file =~ m/^(\S+)\// );

    my $fh;
    open($fh,">${c_file}");
    $self->save_declaration($fh,\%p,$c_file,0);
    # unflush file
    unflush_file_lines($c_file); 
    close($fh);
}

sub save_parameter {
    my $self = shift;
    my ($fh,$P,$file,$tab) = @_;

    return if( $P->{"conf_file"} && $P->{"conf_file"} ne $file );

    if( $P->{"name"} eq "include" ){
        if( my $inc = $P->{"include"} ){
            $self->save_config( %$inc );
        }
    }
    $self->save_comments($fh,$tab,$P->{"comments"},$P->{"index"},$P->{"line"});

    # check if quoted
    if( $P->{"name"} eq "option" ){
        $P->{"quoted"} ||= $QuotedParams{"$P->{'option'}"} ? 1 : 0;
        my $value = $P->{"quoted"} ? '"'.$P->{"value"}.'"' : $P->{"value"};
        print $fh $tab,$P->{"name"}," ",$P->{"option"}," ",$value,";","\n"; 
    } else {
        $P->{"quoted"} ||= $QuotedParams{"$P->{'name'}"} ? 1 : 0;
        my $value = $P->{"quoted"} ? '"'.$P->{"value"}.'"' : $P->{"value"};
        print $fh $tab,$P->{"name"}," ",$value,";","\n"; 
    }
}

sub save_comments {
    my $self = shift;
    my ($fh,$tab,$l,$i,$k,$file) = @_;

    if( defined $l && defined $i ){
        my $s = $i-1;
        my @lc = ();
        for my $C ( reverse @$l ){

            # ignore after comments
            next if( $C->{"index"} > $i );

            if( ( $C->{"index"} == $i ) ||
                ( $C->{"index"} == $s &&  $C->{"alone"} ) ){
                my $cmt = $C->{"comment"}||"";
                unshift(@lc,"$tab# $cmt\n");

                if( $C->{"index"} == $s ){
                    $s--;
                }
            } else {
                last;
            }
        }

        print $fh @lc if( @lc );
    }
}

sub save_declaration {
    my $self = shift;
    my ($fh,$D,$file,$t) = @_;

    my $tab = "\t";
    my $ptab = $tab x ($t>0? $t-1 : 0);
    my $stab = $tab x $t;
    $t++;

    return if( $D->{"conf_file"} && $D->{"conf_file"} ne $file );

    $self->save_comments($fh,$tab,$D->{"comments"},$D->{"index"},$D->{"line"});

    if( $D->{'end'} && !$D->{"parameters"} && !$D->{"declarations"} ){
        print $fh $ptab,$D->{"type"}," ",$D->{"args"},";","\n" if( $D->{"type"} );
    } else {
        print $fh $ptab,$D->{"type"}," ",$D->{"args"}," ","{","\n" if( $D->{"type"} );

        my @lp = ();
        if( $D->{"parameters"} ){
            @lp = @{$D->{"parameters"}};
        }
        my @ld = ();
        if( $D->{"declarations"} ){
            @ld = @{$D->{"declarations"}};
        }
        while( scalar(@lp) || scalar(@ld) ){
            if( !scalar(@ld) || scalar(@lp) && defined($lp[0]->{'index'}) && ( $lp[0]->{'index'} <= $ld[0]->{'index'} ) ){
                # parameters first
                $self->save_parameter( $fh,shift(@lp),$file,$stab );
            } else {
                $self->save_declaration( $fh,shift(@ld),$file,$t );
            }

        }

        # print comments at end
        $self->save_comments($fh,$stab,$D->{"comments"},$D->{"lastindex"},$D->{"line"});

        print $fh $ptab,"}","\n" if( $D->{"type"} );
    }
}

sub find_parameters {
    my $self = shift;
    my ($C,$name,%p) = @_;
    
    my @r = ();
    if( my $lp = $C->{"parameters"} ){
        for my $P ( @$lp ){
            if( $P->{"name"} eq $name ){
                my $b = 1;
                for my $k ( keys %p ){
                    if( $P->{"$k"} ne $p{"$k"} ){
                        $b = 0;
                        last;
                    }
                }
                if( $b ){
                    push(@r,$P);
                }
            }
        }
    }
    if( my $ld = $C->{"declarations"} ){
        for my $D ( @$ld ){
            push( @r, $self->find_parameters($D,$name,%p) );
        }
    }
    return wantarray() ? @r : \@r;
}

sub find_declarations {
    my $self = shift;
    my ($C,$type,%p) = @_;
    my @r = ();
    if( my $l = $C->{"declarations"} ){
        for my $D ( @$l ){
            if( $D->{"type"} eq $type ){
                my $b = 1;
                for my $k ( keys %p ){
                    next if( ref($p{"$k"}) );
                    if( $D->{"$k"} ne $p{"$k"} ){
                        $b = 0;
                        last;
                    }
                }
                if( $b ){
                    push(@r,$D);
                }
            } else {
                my @rx = $self->find_declarations($D,$type,%p);
                push( @r, @rx);
            }
        }
    }
    return wantarray() ? @r : \@r;
}

sub find_declarations_byuuid {
    my $self = shift;
    my ($C,$uuid) = @_;

    if( my $l = $C->{"declarations"} ){
        for my $D ( @$l ){
            if( $D->{"uuid"} eq $uuid ){
                return $D;
            } else {
                if( my $S = $self->find_declarations_byuuid($D,$uuid) ){
                    return $S;
                }
            }
        }
    }
    return;
}

# find_parent: find parent declaration. if no declaration found return main config declaration
sub find_parent {
    my $self = shift;
    my ($C, $P) = @_;

    my $E;
    
    if( $P ){           # check for parent
        if( $P->{'uuid'} ){
            $E = $self->find_declarations_byuuid( $C, $P->{'uuid'} );
        } elsif( $P->{'type'} ){
            ($E) = $self->find_declarations( $C, $P->{'type'}, %$P );
        } elsif( !$P->{'parent'} ){ # is top level
            $E = $C;
        }
    } else {        # no parent set to top config
        $E = $C;    # have parent but not uuid nor type
    }

    return $E;
}

=item set_interface

=cut

sub set_interface {
    my $self = shift;
    my (%p) = @_;

    my %dhcpd = ();
    %dhcpd = loadconfigfile("/etc/sysconfig/dhcpd",\%dhcpd);
    my $ifaces = $p{'ifaces'};
    $dhcpd{'DHCPDARGS'} = ref $ifaces ? '"'.join(" ",@$ifaces).'"' : $ifaces;
    saveconfigfile("/etc/sysconfig/dhcpd",\%dhcpd,0,1);
}

sub get_interface {
    my $self = shift;
    my (%p) = @_;

    my %dhcpd = ();
    %dhcpd = loadconfigfile("/etc/sysconfig/dhcpd",\%dhcpd);
    my $ifaces = $dhcpd{'DHCPDARGS'};
    $ifaces =~ s/"//g;

    my @lifaces = split(/ /,$ifaces);
    my %r = ( 'ifaces'=>\@lifaces );

    return wantarray() ? %r : \%r; 
}

=item apply_config

=cut

sub apply_config {
    my $self = shift;

    if( -x "/etc/init.d/dhcpd" ){
        cmd_exec("/etc/init.d/dhcpd restart");
    }
}

=item start_service

=cut

sub start_service {
    my $self = shift;

    if( -x "/etc/init.d/dhcpd" ){
        cmd_exec("/etc/init.d/dhcpd start");
    }
}

=item stop_service

=cut

sub stop_service {
    my $self = shift;

    if( -x "/etc/init.d/dhcpd" ){
        cmd_exec("/etc/init.d/dhcpd stop");
    }
}

=item add/set_option

=cut

sub set_parameter {
    my $self = shift;
    my ($P,%p) = @_;

    %$P = (%$P,%p);

    return $P;
}
sub add_parameter {
    my $self = shift;
    my ($D,%p) = @_;

    $D->{"parameters"} = [] if( !$D->{"parameters"} );
    push(@{$D->{"parameters"}}, \%p );

    return $D;
}
sub set_option {
    my $self = shift;
    my (%p) = @_;

    my $name = delete $p{"name"};
    my %old = ();
    if( my $o = delete $p{"old"} ){
        %old = %$o;
    }
    my $conf = $self->load_config( %p );
    if( my $dec = delete $p{'parent'} ){
        if( my $l = $self->find_declarations( $conf, delete $dec->{"type"}, %$dec ) ){
            for my $D ( @$l ){
                if( my $lp = $self->find_parameters( $D, $name, %old ) ){
                    for my $P ( @$lp ){
                        $P = $self->set_parameter( $P, %p );
                    }
                }
            }
        }
    } else {
        if( my $lp = $self->find_parameters( $conf, $name, %old ) ){
            for my $P ( @$lp ){
                $P = $self->set_parameter( $P, %p );
            }
        }
    }
    $self->save_config( %$conf );
}

sub add_option {
    my $self = shift;
    my (%p) = @_;

    my $name = delete $p{"name"};
    my $conf = $self->load_config( %p );
    if( my $dec = delete $p{'parent'} ){
        if( my $l = $self->find_declarations( $conf, delete $dec->{"type"}, %$dec ) ){
            for my $D ( @$l ){
                $D = $self->add_parameter( $D, name=>$name, %p );
            }
        }
    } else {
        $conf = $self->add_parameter( $conf, name=>$name, %p );
    }
    $self->save_config( %$conf );
}

# get_declarations: get declaration from uuid or parent and index
sub get_declaration {
    my $self = shift;
    my (%p) = @_;

    my $D;

    # looking for uuid and type ( for fast find )
    if( $p{'uuid'} && $p{'type'} ){
        my $C = $self->load_config(%p);
        ($D) = $self->find_declarations( $C, $p{'type'}, 'uuid'=>$p{'uuid'} );
    } elsif( $p{'uuid'} ){
    # looking for uuid only ( slow find )
        my $C = $self->load_config(%p);
        ($D) = $self->find_declarations_byuuid( $C, $p{'uuid'} );
    } else {
        # else need follow parents
        my $P;
        if( $p{'parent'} && ( $p{'parent'}{'uuid'} || $p{'parent'}{'type'} ) ){
            $P = $self->get_declaration( %{$p{'parent'}} );
        } else {
            $P = $self->load_config(%p);
        }

        if( my $ld = $P->{'declarations'} ){
            ($D) = grep { ( $_->{'uuid'} && $p{'uuid'} && ( $_->{'uuid'} eq $p{'uuid'} ) ) ||
                            ( ( $_->{'type'} eq $p{'type'} ) && ( $_->{'index'} eq $p{'index'} ) ) } @$ld;
        }
    }

    return $D;
}

# set_declaration: set declaration values. specify old for identify old declaration or uuid for 
sub set_declaration {
    my $self = shift;
    my (%p) = @_;

    my $L;
    if( my $old = delete $p{'old'} ){
        $L = $self->get_declaration( %$old, 'type'=>$old->{'type'} );
    } elsif( $p{'uuid'} ){
        # prevent to be restrictive to uuid or else use previous old clause
        $L = $self->get_declaration( %p, 'type'=>$p{'type'} );
    }

    if( $L ){
        my $C = $self->load_config( %p );

        # look for parent
        my $E = $self->find_parent( $C, $L->{'parent'} );
        
        if( $E ){
            if( my $ld = $E->{"declarations"} ){
                my $N;
                my $c = 0;
                for my $D (@$ld){
                    if( $self->eqDeclarations( $D, $L ) ){
                        $N = $D;
                        last;
                    }
                    $c++;
                }
                if( $N ){
                    $C = $self->move_declarations( $C, $L, $p{'declarations'} );

                    # old declarations: move to parent
                    if( my $old = $N->{'declarations'} ){
                        if( my $nld = $p{'declarations'} ){

                            $E->{'declarations'} = [] if( !$E->{'declarations'} );

                            my $n = scalar(@$old);
                            for( my $c=0; $c<$n; $c++ ){
                                my $k = $c + scalar(@$old) - $n;
                                my $O = $old->[$k];
                                if( !grep { $self->eqDeclarations( $O, $_ ) } @$nld ){
                                    # delete one
                                    my $X = splice(@$old,$k,1);

                                    # move to parent declarations not present on set hash (%p)
                                    push(@{$E->{'declarations'}}, $X);
                                }
                            }
                        }
                    }

                    if( !$self->eqParent($N, \%p) ){ 
                        splice(@$ld,$c,1);

                        # add to new parent
                        my $P = $self->find_parent( $C, $p{'parent'} );
                        $P = $C if( !$P );  # add to global config

                        $P->{'declarations'} = [] if( !$P->{'declarations'} );
                        push(@{$P->{'declarations'}}, $N );
                    }
                    %$N = (%$N,%p);
                }
            }
            $self->save_config( %$C );
        }
    }
}

sub eqParent {
    my $self = shift;
    my ($a,$b) = @_;

    return (!$a->{'parent'} && !$b->{'parent'} ) ||
                        $self->eqDeclarations( $a->{'parent'}, $b->{'parent'} );
}
sub eqDeclarations {
    my $self = shift;
    my ($a,$b) = @_;

    if( $a->{'uuid'} || $b->{'uuid'} ){
        return ( $a->{'uuid'} eq $b->{'uuid'} ) ? 1 : 0;
    }

    if( $self->eqParent($a,$b) ){ 

        return 1 if( $a->{'type'} && $b->{'type'} && (defined $a->{'index'}) && (defined $b->{'index'}) && ( $a->{'type'} eq $b->{'type'} ) && ( $a->{'index'} eq $b->{'index'} ) );


        my $eq = 0;

        if( $a->{"type"} eq $b->{"type"} ){
            $eq = 1 if( keys %$b );
            for my $k ( keys %$b ){
                next if( ref($b->{"$k"}) );
                if( $a->{"$k"} ne $b->{"$k"} ){
                    $eq = 0;
                    last;
                }
            }
        }
        return $eq;
    }
    return 0;
}

sub move_declarations {
    my $self = shift;
    my ($C,$L,$ld) = @_;

    if( $ld ){
        for my $A (@$ld){
            my $D;
            my $P = $A->{'parent'};
            if( !$P && $A->{'uuid'} ){
                if( $D = $self->get_declaration( 'uuid'=>$A->{'uuid'}, 'type'=>$A->{'type'} ) ){
                    $P = $D->{'parent'};
                }
            }
            if( $P && ( !$self->eqDeclarations( $P, $L ) ) ){

                # look for parent
                my $E = $self->find_parent( $C, $P );
 
                if( my $le = $E->{'declarations'} ){
                    my $c=0;
                    for my $T (@$le){
                        if( $self->eqDeclarations( $T, $A ) ){
                            $D = splice( @$le, $c, 1);
                            last;
                        }
                        $c++;
                    }
                }
            }
            if( $D ){
                # using previous config rewrite them
                %$A = (%$D, %$A);
            }
        }
    }
    return $C;
}
sub add_declaration {
    my $self = shift;
    my (%p) = @_;

    my $C = $self->load_config( %p );

    # look parent
    my $E = $self->find_parent( $C, $p{'parent'} );
    
    if( $E ){
        $E->{"declarations"} = [] if( !$E->{"declarations"} );
        $C = $self->move_declarations( $C, \%p, $p{'declarations'} );

        if( ! defined($p{'index'}) ){
            $p{'index'} = 0;
        }
        if( ! defined($p{'lastindex'}) ){
            my $i = 0;
            $i += scalar(@{$p{'parameters'}}) if( $p{'parameters'} );
            $i += scalar(@{$p{'declarations'}}) if( $p{'declarations'} );
            $i --;
            $p{'lastindex'} = $i;
        }

        push(@{$E->{"declarations"}}, \%p );

        $self->save_config( %$C );
    }
}

sub del_declaration {
    my $self = shift;
    my (%p) = @_;

    if( my $L = $self->get_declaration( %p, 'type'=>$p{'type'} ) ){

        my $C = $self->load_config( %p );

        # look parent
        my $E = $self->find_parent( $C, $L->{'parent'} );
        
        if( $E ){
            if( my $ld = $E->{"declarations"} ){
                my $c = 0;
                for my $D (@$ld){
                    if( $self->eqDeclarations( $D, $L ) ){
                        splice( @$ld, $c, 1);
                        last;
                    }
                    $c++;
                }
            }
            $self->save_config( %$C );
        }
    }
}

=item del_declarations

delete one or more declarations by uuid

=cut

sub del_declarations {

    sub del_declarations_rec {
        my ($C,$HD) = @_;
        if( my $ld = $C->{'declarations'} ){
            my $n = scalar(@$ld);
            for( my $c=0; $c<$n; $c++ ){
                my $k = $c + scalar(@$ld) - $n;
                my $D = $ld->[$k];
                my $uuid = $D->{'uuid'};
                if( $HD->{"$uuid"} ){
                    # delete one
                    splice(@$ld,$k,1);
                } else {
                    # go ahead
                    $D = del_declarations_rec($D,$HD);
                }
            }
        }
        return $C;
    }

    my $self = shift;
    my (%p) = @_;

    # put uuid into hash for delete
    my %HD = ();
    for my $k (keys %p){
        my $l = $p{"$k"};
        if( ref($l) eq 'ARRAY' ){
            for my $E ( @$l ){
                my $uuid = ref($E) ? $E->{'uuid'} : $E;
                if( $uuid ){
                    $HD{"$uuid"} = 1;
                }
            }
        }
    }

    # delete them
    if( %HD ){
        my $C = $self->load_config( %p );
        $C = del_declarations_rec($C,\%HD);
        $self->save_config( %$C );
    }
}

sub mkargs_declarations {
    my $self = shift;
    my (%p) = @_;

    my %EX = %EXCLUEPARAMS;
    if( my $E = delete($p{'exclude'}) ){
        %EX = (%EX, ref($E) eq 'ARRAY' ? map { $_ => 1 } @$E : %$E);
    }

    for my $k (keys %p){
        if( !$EX{"$k"} ){
            my $v = $p{"$k"};
            if( ref($v) eq 'ARRAY' ){
                for my $D (@$v){
                    # if HASH or other object
                    if( ref($D) && ( ref($D) ne 'ARRAY' ) && 
                        ( $D->{'uuid'} || $D->{'type'} ) ){
                        my $type = $D->{'type'};

                        if( !$type ){
                            if( my $A = $self->get_declaration( 'uuid'=>$D->{'uuid'} ) ){
                                $type = $A->{'type'};
                            }
                        }
                        
                        if( $type ){
                            my $fmka = "makeargs_$type";
                            my %L = $self->$fmka( %$D, 'type'=>$type );

                            $p{'declarations'} = [] if( !$p{'declarations'} ); 
                            push(@{$p{'declarations'}}, \%L );
                        }
                    }
                }
            }
        }
    }
    return %p;
}
sub mkargs_parameters {
    my $self = shift;
    my (%p) = @_;

    my %EX = (%EXCLUEPARAMS,%NotValidParameters);
    if( my $E = delete($p{'exclude'}) ){
        %EX = (%EX, ref($E) eq 'ARRAY' ? map { $_ => 1 } @$E : %$E);
    }

    # make parameters
    for my $k (keys %p){
        if( !$EX{"$k"} ){
            $p{'parameters'} = [] if( !$p{'parameters'} ); 
            if( $k eq 'option' ){
                my $opts = $p{'option'};
                for my $ko (keys %$opts){
                    my $vo = $opts->{"$ko"};
                    if( ref($vo) eq 'ARRAY' ){
                        for my $ve (@$vo){
                            push(@{$p{'parameters'}}, { 'name'=>'option', 'option'=>$ko, 'value'=>$ve } );
                        }
                    } else {
                        push(@{$p{'parameters'}}, { 'name'=>'option', 'option'=>$ko, 'value'=>$vo } );
                    }
                }
            } else {
                my $vp = $p{"$k"};
                if( ref($vp) eq 'ARRAY' ){
                    # only ARRAY is valid
                    for my $ve (@$vp){
                        # only scalar is valid (ignore possible declarations)
                        if( !ref($ve) ){
                            push(@{$p{'parameters'}}, { 'name'=>$k, 'value'=>$ve } );
                        }
                    }
                } elsif( !ref($vp) ){
                    # only scalar is valid (ignore possible declarations)
                    push(@{$p{'parameters'}}, { 'name'=>$k, 'value'=>$vp } );
                }
            }
        }
    }

    return %p;
}

sub mkargs_comments {
    my $self = shift;
    my (%p) = @_;
    if( $p{'comments'} || defined $p{'lastcomment'} ){
        my $index = 0;
        my $lc = [];
        if( $p{'uuid'} ){
            if( my $D = $self->get_declaration( %p ) ){
                $index = $D->{'index'};
                $lc = $D->{'comments'} || [];
            }
        }
        if( my $cmts = $p{'comments'} ){
            for( my $i=0; $i<scalar(@$cmts); $i++ ){
                my $C = ref($cmts->[$i]) ? $cmts->[$i] : { 'comment'=>$cmts->[$i], 'index'=>$index };
                if( ref($cmts->[$i]) && !defined($cmts->[$i]{'index'}) ){
                    my $cmt = $C->{'comment'};
                    if( my ($OC) = grep { $cmt eq $_->{'comment'} } @$lc ){
                        %$C = (%$OC);
                    }
                }
                $cmts->[$i] = $C;
            }
        }
        my $lcmt = $p{'lastcomment'};
        if( defined $lcmt ){
            if( my $cmts = $p{'comments'} ){
                if( $cmts->[-1]{'comment'} ne $lcmt ){
                    $cmts->[-1]{'comment'} = $lcmt;
                }
            } elsif( scalar(@$lc) ){
                $lc->[-1]{'comment'} = $lcmt;
                $p{'comments'} = $lc;
            } else {
                $p{'comments'} = [ { 'comment'=>$lcmt, 'index'=>$index, 'alone'=>1 } ];
            }
        }
    }
    return %p;
}

sub AUTOLOAD {
    my ($package,$method) = ( $AUTOLOAD =~ m/(.+)::([^:]+)$/ );

    my $a = "none";
    if( ($a) = ($method =~ m/^get_(\S+)/) ){
        $AUTOLOAD = sub {
                        my $self = shift;
                        my (%p) = @_;

                        my $D = {};

                        if( my $L = $self->get_declaration( %p, 'type'=>$a ) ){
                            my $fmkget = "mkget_$a";
                            $D = $self->$fmkget( $L );
                        }

                        return wantarray() ? %$D : $D;
                    };
    } elsif( ($a) = ($method =~ m/^mkget_(\S+)/) ){
        $AUTOLOAD = sub {
                        my $self = shift;
                        my ($L) = @_;

                        return $self->mkget_declaration( $L );
                    };
    } elsif( ($a) = ($method =~ m/^set_(\S+)/) ){
        $AUTOLOAD = sub {
                        my $self = shift;
                        my (%p) = @_;

                        my $fset = "mkset_$a";
                        %p = $self->$fset( %p, 'type'=>$a );

                        my $fargs = "makeargs_$a";
                        %p = $self->$fargs( %p, 'type'=>$a );
                        return $self->set_declaration( %p, 'type'=>$a );
                    };
    } elsif( ($a) = ($method =~ m/^mkset_(\S+)/) ){
        $AUTOLOAD = sub {
                        my $self = shift;
                        my (%p) = @_;

                        if( my $D = $self->get_declaration( %p ) ){
                            if( ! defined($p{'parent'}) ){
                                $p{'parent'} = $D->{'parent'} if( $D->{'parent'} );
                            } elsif( !$p{'parent'} ){
                                $p{'parent'} = {};
                            }
                        }

                        return %p;
                    };
    } elsif( ($a) = ($method =~ m/^add_(\S+)/) ){
        $AUTOLOAD = sub {
                        my $self = shift;
                        my (%p) = @_;
                        my $fargs = "makeargs_$a";
                        %p = $self->$fargs( %p, 'type'=>$a );
                        return $self->add_declaration( %p, 'type'=>$a );
                    };
    } elsif( ($a) = ($method =~ m/^del_(\S+)/) ){
        $AUTOLOAD = sub {
                        my $self = shift;
                        my (%p) = @_;

                        return $self->del_declaration( %p, 'type'=>$a );
                    };
    } elsif( ($a) = ($method =~ m/^makeargs_(\S+)/) ){
        $AUTOLOAD = sub {
                        my $self = shift;
                        my (%p) = @_;
                        %p = $self->mkargs_comments( %p, 'type'=>$a );
                        %p = $self->mkargs_declarations( %p, 'type'=>$a );
                        %p = $self->mkargs_parameters( %p, 'type'=>$a );
                        return %p;
                    };
    } elsif( ($a) = ($method =~ m/^list_(\S+)/) ){
        $AUTOLOAD = sub {
                        my $self = shift;
                        my (%p) = @_;

                        my $C = $self->load_config(%p);

                        my $fmlist = "mklist_$a";
                        my $l = $self->$fmlist( $C, %p, 'type'=>$a );
                        my %r = ( list=>$l );

                        return wantarray() ? %r : \%r;
                    };
    } elsif( ($a) = ($method =~ m/^mklist_(\S+)/) ){
        $AUTOLOAD = sub {
                        my $self = shift;
                        my ($C,%p) = @_;

                        my @l = $self->find_declarations($C,$a,%p, 'type'=>$a);
                        my @nl = ();
                        for my $L (@l){
                            my $fmkget = "mkget_$a";
                            my $D = $self->$fmkget( $L );
                            push(@nl, $D);
                        }
                        return wantarray() ? @nl : \@nl;
                    };
    }
    # AUTOLOAD not valid method
    if( ( ref($AUTOLOAD) ne "CODE" ) || ($a ne "none" && !$ValidDeclaration{"$a"} ) ){
        die "method $method not found\n";
    }

    if( $AUTOLOAD ){
        &$AUTOLOAD;
    }
}

=item add/set_subnet 

=cut

sub makeargs_subnet {
    my $self = shift;
    my (%p) = @_;

    if( !$p{"args"} ){
        if( $p{"address"} && $p{"netmask"} ){
            $p{"args"} = $p{"address"}." netmask ".$p{"netmask"};
        }
    }

    my $E = [ qw( address netmask ) ];
    %p = $self->mkargs_comments( %p );
    %p = $self->mkargs_declarations( 'exclude'=>$E, %p );
    %p = $self->mkargs_parameters( 'exclude'=>$E, %p );

    return %p;
}

sub mkset_subnet {
    my $self = shift;
    my (%p) = @_;
 
    if( my $D = $self->get_declaration( %p ) ){
        my $ld = $D->{'declarations'} || [];

        if( ! defined($p{'parent'}) ){
            $p{'parent'} = $D->{'parent'} if( $D->{'parent'} );
        } elsif( !$p{'parent'} ){
            $p{'parent'} = {};
        }

        if( ! defined($p{'hosts'}) ){
            $p{'hosts'} = [ grep { $_->{'type'} eq 'host' } @$ld ];
        } elsif( ref($p{'hosts'}) ne 'ARRAY' ){
            $p{'hosts'} = [];
        }
        if( ! defined($p{'groups'}) ){
            $p{'groups'} = [ grep { $_->{'type'} eq 'group' } @$ld ];
        } elsif( ref($p{'groups'}) ne 'ARRAY' ){
            $p{'groups'} = [];
        }
        if( ! defined($p{'pools'}) ){
            $p{'pools'} = [ grep { $_->{'type'} eq 'pool' } @$ld ];
        } elsif( ref($p{'pools'}) ne 'ARRAY' ){
            $p{'pools'} = [];
        }

        $p{'declarations'} = [] if( !$p{'declarations'} &&
                                        ( $p{'hosts'} || $p{'groups'} || $p{'pools'} ) );
    }
    return %p;
}

=item add/set_sharednetwork

=cut

sub add_sharednetwork {
    my $self = shift;
    my (%p) = @_;

    $p{'type'} = "shared-network";
    %p = $self->makeargs_sharednetwork( %p );
    return $self->add_declaration( %p );
}

sub mkset_sharednetwork {
    my $self = shift;
    my (%p) = @_;
 
    if( my $D = $self->get_declaration( %p ) ){
        my $ld = $D->{'declarations'} || [];

        if( ! defined($p{'parent'}) ){
            $p{'parent'} = $D->{'parent'} if( $D->{'parent'} );
        } elsif( !$p{'parent'} ){
            $p{'parent'} = {};
        }

        if( ! defined($p{'subnets'}) ){
            $p{'subnets'} = [ grep { $_->{'type'} eq 'subnet' } @$ld ];
        } elsif( ref($p{'subnets'}) ne 'ARRAY' ){
            $p{'subnets'} = [];
        }
        if( ! defined($p{'hosts'}) ){
            $p{'hosts'} = [ grep { $_->{'type'} eq 'host' } @$ld ];
        } elsif( ref($p{'hosts'}) ne 'ARRAY' ){
            $p{'hosts'} = [];
        }
        if( ! defined($p{'groups'}) ){
            $p{'groups'} = [ grep { $_->{'type'} eq 'group' } @$ld ];
        } elsif( ref($p{'groups'}) ne 'ARRAY' ){
            $p{'groups'} = [];
        }
        if( ! defined($p{'pools'}) ){
            $p{'pools'} = [ grep { $_->{'type'} eq 'pool' } @$ld ];
        } elsif( ref($p{'pools'}) ne 'ARRAY' ){
            $p{'pools'} = [];
        }

        $p{'declarations'} = [] if( !$p{'declarations'} &&
                                        ( $p{'subnets'} || $p{'hosts'} || $p{'groups'} || $p{'pools'} ) );
    }
    return %p;
}

sub set_sharednetwork {
    my $self = shift;
    my (%p) = @_;
    
    $p{'type'} = 'shared-network';

    %p = $self->mkset_sharednetwork( %p );

    %p = $self->makeargs_sharednetwork( %p );
    return $self->set_declaration( %p );
}

=item del_sharednetwork

=cut

sub del_sharednetwork {
    my $self = shift;
    my (%p) = @_;

    return $self->del_declaration( %p, 'type'=>'shared-network' );
}

sub makeargs_sharednetwork {
    my $self = shift;
    my (%p) = @_;

    if( !$p{'args'} ){
        $p{'args'} = $p{'name'} if( $p{'name'} );
    }
    my $E = [ qw( name ) ];
    %p = $self->mkargs_comments( %p );
    %p = $self->mkargs_declarations( 'exclude'=>$E, %p );
    %p = $self->mkargs_parameters( 'exclude'=>$E, %p );

    return %p;
}

=item add/set_host

=cut

sub makeargs_host {
    my $self = shift;
    my (%p) = @_;

    if( !$p{'args'} ){
        $p{'args'} = $p{'host'} if( $p{'host'} );;
    }
    my $E = [ qw( host ) ];
    %p = $self->mkargs_comments( %p );
    %p = $self->mkargs_declarations( 'exclude'=>$E, %p );
    %p = $self->mkargs_parameters( 'exclude'=>$E, %p );

    return %p;
}

=item add/set_zone

=cut

sub makeargs_zone {
    my $self = shift;
    my (%p) = @_;

    if( !$p{'args'} ){
        if( $p{'name'} ){
            $p{'args'} = '"'.$p{'name'}.'"';
        }
    }
    my $E = [ qw( name ) ];
    %p = $self->mkargs_comments( %p );
    %p = $self->mkargs_declarations( 'exclude'=>$E, %p );
    %p = $self->mkargs_parameters( 'exclude'=>$E, %p );

    return %p;
}

=item add/set_group

=cut

sub mkset_group {
    my $self = shift;
    my (%p) = @_;
 
    if( my $D = $self->get_declaration( %p ) ){
        my $ld = $D->{'declarations'} || [];

        if( ! defined($p{'parent'}) ){
            $p{'parent'} = $D->{'parent'} if( $D->{'parent'} );
        } elsif( !$p{'parent'} ){
            $p{'parent'} = {};
        }

        if( ! defined($p{'hosts'}) ){
            $p{'hosts'} = [ grep { $_->{'type'} eq 'host' } @$ld ];
        } elsif( ref($p{'hosts'}) ne 'ARRAY' ){
            $p{'hosts'} = [];
        }

        $p{'declarations'} = [] if( !$p{'declarations'} && $p{'hosts'} );
    }
    return %p;
}

=item add/set_pool

=cut

=item add/set_key

=cut

sub makeargs_key {
    my $self = shift;
    my (%p) = @_;

    if( !$p{'args'} ){
        if( $p{'key'} ){
            $p{'args'} = $p{'key'};
        }
    }
    my $E = [ qw( key ) ];
    %p = $self->mkargs_comments( %p );
    %p = $self->mkargs_declarations( 'exclude'=>$E, %p );
    %p = $self->mkargs_parameters( 'exclude'=>$E, %p );

    return %p;
}

=item add/set_allowupdate

=cut

sub add_allowupdate {
    my $self = shift;
    my (%p) = @_;

    $p{'type'} = 'allow-update';

    %p = $self->makeargs_allowupdate( %p );
    return $self->add_declaration( %p );
}

sub set_allowupdate {
    my $self = shift;
    my (%p) = @_;

    $p{'type'} = 'allow-update';

    %p = $self->makeargs_allowupdate( %p );
    return $self->set_declaration( %p );
}

=item del_allowupdate

=cut

sub del_allowupdate {
    my $self = shift;
    my (%p) = @_;

    return $self->del_declaration( %p, 'type'=>'allow-update' );
}

=item add/set_logging

=cut

=item add/set_channel

=cut

=item add/set_category

=cut

=item add/set_failover

=cut

=item add/set_class

=cut

=item add/set_subclass

=cut

=item set_clientoptions

=cut

sub set_clientoptions {
    my $self = shift;
    my (%p) = @_;

    my $C = $self->load_config( %p );

    $C->{'parameters'} = [] if( !$C->{'parameters'} );
    my $lp = $C->{'parameters'};
    my $n = scalar(@$lp);
    for( my $c=0; $c<$n; $c++ ){
        my $k = $c + scalar(@$lp) - $n;
        my $P = $lp->[$k];
        if( $P->{'name'} eq 'option' ){
            my $o = $P->{'option'};
            if( my $vo = $p{'option'}{"$o"} ){
                if( ref($vo) eq 'ARRAY' ){
                    $P->{'value'} = shift(@$vo);
                    my $nl = scalar(@$vo);
                    if( $nl == 1 ){
                        $p{'option'}{"$o"} = shift(@$vo);
                    } elsif( !scalar(@$vo) ){
                        delete $p{'option'}{"$o"};
                    }
                } else {
                    $P->{'value'} = delete $p{'option'}{"$o"};
                }
            } else {
                splice(@$lp,$k,1);
            }
        } else {
            my $n = $P->{'name'};
            if( my $vp = $p{"$n"} ){
                if( ref($vp) eq 'ARRAY' ){
                    $P->{'value'} = shift(@$vp);
                    my $nl = scalar(@$vp);
                    if( $nl == 1 ){
                        $p{"$n"} = shift(@$vp);
                    } elsif( !scalar(@$vp) ){
                        delete $p{"$n"};
                    }
                } else {
                    $P->{'value'} = delete $p{"$n"};
                }
            } else {
                splice(@$lp,$k,1);
            }
        }
    }

    # the rest of options
    for my $kp (keys %p){
        my $vp = $p{"$kp"};
        if( $kp eq 'option' ){
            for my $ko (keys %$vp){
                my $vo = $vp->{"$ko"};
                if( ref($vo) eq 'ARRAY' ){
                    for my $ve (@$vo){
                        push(@$lp, { 'name'=>'option', 'option'=>$ko, 'value'=>$ve } );
                    }
                } else {
                    push(@$lp, { 'name'=>'option', 'option'=>$ko, 'value'=>$vo } );
                }
            }
        } else {
            if( ref($vp) eq 'ARRAY' ){
                for my $ve (@$vp){
                    push(@$lp, { 'name'=>$kp, 'value'=>$ve } );
                }
            } else {
                push(@$lp, { 'name'=>$kp, 'value'=>$vp } );
            }
        }
    }
    $self->save_config( %$C );
}

=item list_clientoptions

=cut

sub list_clientoptions {
    my $self = shift;
    my (%p) = @_;

    my $C = $self->load_config(%p);

    my $D = $self->mkparameters_declaration($C);

    return wantarray() ? %$D : $D;
}

=item list_declaration

=cut

sub list_declaration {
    my $self = shift;
    my (%p) = @_;

    my %conf = $self->load_config(%p);
    my $l = $self->find_declarations(\%conf,$p{'type'},%p);
    my %res = ( list=>$l );

    return wantarray() ? %res : \%res;
}

sub mkparent_declaration {
    my $self = shift;
    my ($parent) = @_;

    # set parent

    my $ret_parent;

    if( $parent && ( $parent->{'type'} || $parent->{'uuid'} ) ){
        # copy parent info
        for my $k (keys %$parent){
            my $v = $parent->{"$k"};
            if( $k eq 'parent' ){
                # set recursive parent
                if( my $rP = $self->mkparent_declaration($v) ){
                    $ret_parent->{'parent'} = $rP;
                }
            } elsif( !ref($v) ){    # ignore recursive declarations
                $ret_parent->{"$k"} = $v;
            }
        }
    }

    return $ret_parent;
}
sub mkparameters_declaration {
    my $self = shift;
    my ($L) = @_;

    my $D = $self->parse_declaration( $L->{'type'},$L->{'args'}, 1 );
    if( my $parms = $L->{'parameters'} ){
        for my $P (@$parms){
            if( $P->{'name'} eq 'option' ){
                my $k = $P->{'option'};
                if( $D->{"option"}{"$k"} ){
                    if( ref($D->{"option"}{"$k"}) eq 'ARRAY' ){
                        push(@{$D->{"option"}{"$k"}}, $P->{'value'} );
                    } else {
                        $D->{"option"}{"$k"} = [ $D->{"$k"}, $P->{'value'} ];
                    }
                } else {
                    $D->{"option"}{"$k"} = $P->{'value'};
                }
            } else {
                my $k = $P->{'name'};
                if( $D->{"$k"} ){
                    if( ref($D->{"$k"}) eq 'ARRAY' ){
                        push(@{$D->{"$k"}}, $P->{'value'} );
                    } else {
                        $D->{"$k"} = [ $D->{"$k"}, $P->{'value'} ];
                    }
                } else {
                    $D->{"$k"} = $P->{'value'};
                }
            }
        }
    }

    # show me this fields
    for my $f (qw( uuid type index )){
        if( defined($L->{"$f"}) ){
            $D->{"$f"} = $L->{"$f"};
        }
    }

    # get comments
    if( my $cmts = $L->{"comments"} ){
        $D->{"comments"} = $cmts;
        if( @$cmts ){
            my @prevcmts = grep { $_->{'index'} <= $L->{'index'} } @$cmts;
            my $prevcmt = pop(@prevcmts);
            # get last comment
            $D->{"lastcomment"} = $prevcmt->{"comment"};
        }
    }

    # set parent
    if( $L->{'parent'} ){
        $D->{'parent'} = $self->mkparent_declaration( $L->{'parent'} );
    }

    return wantarray() ? %$D : $D;
}
sub mklist_declarations {
    my $self = shift;
    my ($C,%p) = @_;

    my @l = $self->find_declarations($C,$p{'type'},%p);
    my @nl = ();
    for my $L (@l){
        my $D = $self->mkget_declaration( $L );
        push(@nl, $D);
    }
    return wantarray() ? @nl : \@nl;
}

=item list_host

    list hosts

=cut

=item list_group

    list group of hosts

=cut

sub mkget_group {
    my $self = shift;
    my ($L) = @_;

    my $D = {};
    if( $D = $self->mkget_declaration( $L ) ){
        $D->{"hosts"} = $self->mklist_host( $L );
    }

    return wantarray() ? %$D : $D;
}

=item list_subnet

    list sub networks

=cut

sub mkget_subnet {
    my $self = shift;
    my ($L) = @_;

    my $D = {};
    if( $D = $self->mkget_declaration( $L ) ){
        $D->{"hosts"} = $self->mklist_host( $L );
        $D->{"groups"} = $self->mklist_group( $L);
        $D->{"pools"} = $self->mklist_pool( $L );
    }

    return wantarray() ? %$D : $D;
}

sub mkget_declaration {
    my $self = shift;
    my ($L) = @_;

    return $self->mkparameters_declaration( $L );
}

=item list_sharednetwork

    list shared networks

=cut

sub mkget_sharednetwork {
    my $self = shift;
    my ($L) = @_;

    my $D = {};
    if( $D = $self->mkget_declaration( $L ) ){
        $D->{"hosts"} = $self->mklist_host( $L );
        $D->{"groups"} = $self->mklist_group( $L );
        $D->{"subnets"} = $self->mklist_subnet( $L );
        $D->{"pools"} = $self->mklist_pool( $L );
    }

    return wantarray() ? %$D : $D;
}

sub mklist_sharednetwork {
    my $self = shift;
    my ($C,%p) = @_;

    my @l = $self->find_declarations($C,"shared-network",%p);
    my @nl = ();
    for my $L (@l){
        my $D = $self->mkget_sharednetwork( $L );
        push(@nl, $D);
    }
    return wantarray() ? @nl : \@nl;
}

sub list_sharednetwork {
    my $self = shift;
    my (%p) = @_;

    my $C = $self->load_config(%p);

    my $l = $self->mklist_sharednetwork( $C, %p );
    my %r = ( list=>$l );

    return wantarray() ? %r : \%r;
}

=item get_sharednetwork

=cut

sub get_sharednetwork {
    my $self = shift;
    my (%p) = @_;

    my $L = $self->get_declaration( %p, 'type'=>'shared-network' );
    my $D = $self->mkget_sharednetwork( $L );

    return wantarray() ? %$D : $D;
}

=item list_zone 

=cut

=item list_pool

=cut

=item list_key

=cut

=item list_allowupdate

=cut

sub list_allowupdate {
    my $self = shift;
    my (%p) = @_;

    my $C = $self->load_config(%p);
    my $l = $self->mklist_declarations( $C, %p, type=>"allow-update" );
    my %r = ( list=>$l );

    return wantarray() ? %r : \%r;
}

=item get_allowupdate

=cut

sub get_allowupdate {
    my $self = shift;
    my (%p) = @_;

    my $L = $self->get_declaration( %p, 'type'=>'allow-update' );
    my $D = $self->mkget_declaration( $L );
    $D ||= {};

    return wantarray() ? %$D : $D;
}

=item list_logging

=cut

=item list_channel

=cut

=item list_category

=cut

=item list_failover

=cut

=item list_all

=cut

sub list_all {
    my $self = shift;
    my (%p) = @_;

    my $C = $self->load_config(%p);

    my %r = ();
    $r{"hosts"} = $self->mklist_host( $C );
    $r{"groups"} = $self->mklist_group( $C );
    $r{"subnets"} = $self->mklist_subnet( $C );
    $r{"sharednetworks"} = $self->mklist_sharednetwork( $C );

    return wantarray() ? %r : \%r;
}

=item list_leases

=cut

# read_leases: aux function to read and parsing leases from file
sub read_leases {
    my $self = shift;

    my $lease_file = $CONF{'lease_file'};

    my @leases = ();
    if( -r "$lease_file" ){
        my $lref = read_file_lines($lease_file); 
        my $k = 0;
        for( $k=0; $k<scalar(@$lref); $k++){
            my $l = $lref->[$k];
            chomp($l);
            $l =~ s/#\s*(.*)//;
            if( $l =~ m/^\s*lease\s+(\d+\.\d+\.\d+\.\d+)\s*{/ ){
                my %L = ( 'ipaddr'=>$1, 'line'=>$k, 'file'=>$lease_file );
                for( $k++; $k<scalar(@$lref); $k++){
                    $l = $lref->[$k];
                    chomp($l);
                    $l =~ s/#\s*(.*)//;
                    if( $l =~ m/(\S+)\s+"?([^"]+)"?;/ ){
                        $L{"$1"} = $2;
                    }
                    last if( $l =~ m/\s*}/ );
                }
                $L{'eline'} = $k;
                $L{'index'} = scalar(@leases);
                push(@leases,\%L);
            }
        }
    }

    return wantarray() ? @leases : \@leases;
}

sub within_network {
    my ($na,$nk,$ip) = @_;
    if( $na ){
        # Is lease within network/netmask?
        my @ad = split(/\./, $ip);
        my @nw = split(/\./, $na);
        my @nm = split(/\./, $nk);

        for( my $k=0; $k<4; $k++ ){
            if( (int($ad[$k]) & int($nm[$k])) != int($nw[$k]) ){
                return 0;
            }
        }
    }
    return 1;
}

sub lease_time {
    my ($stime) = @_;
    my ($w,$dd,$tt) = split(/ /, $stime);
    my @d = split(/\//, $dd);
    my @t = split(/:/, $tt);

    return timegm($t[2], $t[1], $t[0], $d[2], $d[1]-1, $d[0]-1900);
}

sub list_leases {
    my $self = shift;
    my (%p) = @_;

    my @leases = $self->read_leases();

    my $timenow = time();

    my %r = ( 'list'=>[] );
    foreach my $L (@leases){
        $L->{'stime'} = lease_time($L->{'starts'});
        $L->{'etime'} = lease_time($L->{'ends'});

        if( $L->{'stime'} < $timenow ||
            $L->{'etime'} > $timenow ){
            $L->{'expired'}++;
        }
        if( ( $p{'all'} || !$L->{'expired'} ) && 
            ( !$p{'netaddr'} || !$p{'netmask'} || 
                within_network($p{'netaddr'},$p{'netmask'},$L->{'ipaddr'}) ) ){
            push(@{$r{'list'}}, $L);
        }
    }

    return wantarray() ? %r : \%r;
}

=item del_leases

=cut

sub del_leases {
    my $self = shift;
    my (%p) = @_;

    my $lease_file = $CONF{'lease_file'};

    my @indexes = $p{'indexes'} ? @{$p{'indexes'}} : ( $p{'index'} );

    my @leases = $self->read_leases();
    my $lref = read_file_lines($lease_file); 
    for my $i ( sort { $b <=> $a } @indexes ){
        my $L = $leases[$i];
        if( $L ){
            splice(@$lref,$L->{'line'},$L->{'eline'} - $L->{'line'} + 1);
        }
    }
    flush_file_lines($lease_file); 

    return;
}

=item set_options

=cut

sub set_options {
    my $self = shift;
    my (%p) = @_;

    my $L;
    if( my $old = delete $p{'old'} ){
        $L = $self->get_declaration( %$old, 'type'=>$old->{'type'} );
    } elsif( $p{'uuid'} ){
        # prevent to be restrictive to uuid or else use previous old clause
        $L = $self->get_declaration( %p, 'type'=>$p{'type'} );
    }

    if( $L ){
        my $C = $self->load_config( %p );

        # look for parent
        my $E = $self->find_parent( $C, $L->{'parent'} );
        
        if( $E ){
            if( my $ld = $E->{"declarations"} ){
                my $N;
                my $c = 0;
                for my $D (@$ld){
                    if( $self->eqDeclarations( $D, $L ) ){
                        $N = $D;
                        last;
                    }
                    $c++;
                }
                if( $N ){

                    if( my $HO = $p{'option'} ){
                        $N->{'parameters'} = [] if( !$N->{'parameters'} );
                        my $lp = $N->{'parameters'};
                        my $n = scalar(@$lp);
                        for( my $c=0; $c<$n; $c++ ){
                            my $k = $c + scalar(@$lp) - $n;
                            my $P = $lp->[$k];
                            if( $P->{'name'} eq 'option' ){
                                my $o = $P->{'option'};
                                if( my $vo = $HO->{"$o"} ){
                                    if( ref($vo) eq 'ARRAY' ){
                                        $P->{'value'} = shift(@$vo);
                                        my $nl = scalar(@$vo);
                                        if( $nl == 1 ){
                                            $HO->{"$o"} = shift(@$vo);
                                        } elsif( !scalar(@$vo) ){
                                            delete $HO->{"$o"};
                                        }
                                    } else {
                                        $P->{'value'} = delete $HO->{"$o"};
                                    }
                                } else {
                                    splice(@$lp,$k,1);
                                }
                            }
                        }

                        # the rest of options
                        for my $ko (keys %$HO){
                            my $vo = $HO->{"$ko"};
                            if( ref($vo) eq 'ARRAY' ){
                                for my $ve (@$vo){
                                    push(@$lp, { 'name'=>'option', 'option'=>$ko, 'value'=>$ve } );
                                }
                            } else {
                                push(@$lp, { 'name'=>'option', 'option'=>$ko, 'value'=>$vo } );
                            }
                        }
                    }
                }
            }
            $self->save_config( %$C );
        }
    }
}

=item get_configfile_content

=cut

sub get_configfile_content {
    my $self = shift;
    my (%p) = @_;

    my $file = $CONF{'conf_file'} || $CONF{'dhcpd_conf'} ;

    if( -f "$file" ){
        unflush_file_lines($file) if( $p{'force'} ); 
        my $cfref = read_file_lines($file);
        my %r = ( content=>join("\n",@$cfref) );
        return wantarray() ? %r : \%r;
    }
}

=item save_configfile_content

=cut

sub save_configfile_content {
    my $self = shift;
    my (%p) = @_;

    my $file = $CONF{'conf_file'} || $CONF{'dhcpd_conf'} ;

    open(OUTFILE,">$file");
    # TODO lock file
    print OUTFILE $p{"content"};
    close(OUTFILE);

    unflush_file_lines($file); 
}

# reset configuration
sub reset_config {
    my $self = shift;

    $self->save_configfile_content();
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
