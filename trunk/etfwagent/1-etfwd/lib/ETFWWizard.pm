#!/usr/bin/perl

package ETFWWizard;

use strict;

use ETVA::Utils;

require ETFW::Network;
require ETFW::DHCP;
require ETFW::Squid;
require ETFW::Samba;

BEGIN {
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF $AUTOLOAD);
};

my %conf = ( 'base_dir'=>'.' );

sub submit {
    my $self = shift;
    my (%p) = @_;

    # interfaces is mandatory
    %p = $self->config_interfaces( %p ) if( !isError(%p) );

    # config other stuff
    %p = $self->config_other( %p ) if( !isError(%p) );

    return wantarray() ? %p : \%p;
}

sub config_interfaces {
    my $self = shift;
    my (%p) = @_;

    if( $p{'interfaces'} ){
        my $ifs = $p{'interfaces'};

        my %routes = ( 'DefaultRoutes'=>[], 'StaticRoutes'=>[], 'LocalRoutes'=>[] );
        my %del_routes = ( 'DefaultRoutes'=>[], 'StaticRoutes'=>[], 'LocalRoutes'=>[] );

        for my $If (@$ifs){
            my $type = $If->{'type'};

            my %if = ( 'name'=>$If->{'name'}, 'up'=>1 );

            $if{'dhcp'} = $If->{'dhcp'} if( $If->{'dhcp'} );
            $if{'address'} = $If->{'address'} if( $If->{'address'} );
            $if{'netmask'} = $If->{'netmask'} if( $If->{'netmask'} );
            $if{'broadcast'} = $If->{'broadcast'} if( $If->{'broadcast'} );
            $if{'gateway'} = $If->{'gateway'} if( $If->{'gateway'} );

            if( $if{'address'} && $if{'netmask'} ){
                $If->{'network'} = $if{'network'} = ETFW::Network::make_netaddr($if{'address'}, $if{'netmask'});
            }

            my %t = ();

            %t = ETFW::Network->save_boot_interface( %if ) if( !isError(%t) );

            %t = ETFW::Network->activate_interface( %if ) if( !isError(%t) );

            if( $if{'network'} ){
                my %r = ();
                $r{'device'} = $if{'name'};
                $r{'address'} = $if{'network'};
                $r{'netmask'} = $if{'netmask'};

                push(@{$del_routes{'StaticRoutes'}}, { 'device'=>$r{'device'} } );
                push(@{$del_routes{'LocalRoutes'}}, { 'device'=>$r{'device'} } );

                if( $if{'gateway'} ){
                    $r{'gateway'} = $if{'gateway'};
                    push(@{$routes{'StaticRoutes'}}, \%r);
                } else {
                    push(@{$routes{'LocalRoutes'}}, \%r);
                }
            } elsif( $if{'gateway'} ){
                my %r = ();
                $r{'device'} = $if{'name'};
                $r{'gateway'} = $if{'gateway'};
                push(@{$routes{'DefaultRoutes'}}, \%r);
            }

            if( isError(%t) ){
                # show iface with problems
                $t{'_if_'} = $if{'name'};
                return wantarray() ? %t : \%t;
            }
        }

        ETFW::Network->del_boot_routing(%del_routes);
        ETFW::Network->add_boot_routing(%routes);

        my %e = ETFW::Network->apply_config();
        if( isError(%e) ){
            return wantarray() ? %e : \%e;
        }
    } else {
        my %IFS = ETFW::Network->active_interfaces();
        my @ifs = values %IFS;

        for my $If (@ifs){
            if( !$If->{'network'} ){
                if( $If->{'address'} && $If->{'netmask'} ){
                    $If->{'network'} = ETFW::Network::make_netaddr($If->{'address'}, $If->{'netmask'});
                }
            }
        }
        $p{'interfaces'} = \@ifs;
    }
    return wantarray() ? %p : \%p;
}

sub config_other {
    my $self = shift;
    my (%p) = @_;

    if( $p{'dhcp'} ){
        %p = $self->config_dhcp( %p ) if( !isError(%p) );
    }

    if( $p{'squid'} ){
        %p = $self->config_squid( %p ) if( !isError(%p) );
    }

    return wantarray() ? %p : \%p;
}

sub config_dhcp {
    my $self = shift;
    my (%p) = @_;

    if( $p{'dhcp'} ){

        # reset all configuration
        ETFW::DHCP->reset_config();

        my $ld = $p{'dhcp'};
        my @ifs = ();
        for my $D (@$ld){
            my $if = $D->{'if'};

            if( my $lifs = $p{'interfaces'} ){
                my ($If) = grep { ( $_->{'fullname'} eq $if ) || ( !defined($_->{'fullname'}) && ( $_->{'name'} eq $if ) ) } @$lifs;
                if( $If && $If->{'network'} ){
                    my $network = $D->{'network'} = $If->{'network'};
                    my $netmask = $D->{'netmask'} = $If->{'netmask'};

                    my %subnet = ( 'address'=>$network, 'netmask'=>$netmask );
                    if( $D->{'ranges'} ){
                        my $ranges = $D->{'ranges'};
                        my @lr = ();
                        for my $R (@$ranges){
                            my $sl = "";
                            $sl .= "dynamic-bootp " if( $R->{'dyn'} );
                            $sl .= "$R->{'low'} $R->{'hi'}";
                            push(@lr, $sl ) if( $sl );
                        } 
                        $subnet{'range'} = \@lr if( @lr );
                    }
                    my %e = ETFW::DHCP->add_subnet( %subnet );

                    if( isError(%e) ){
                        $e{'_if_'} = $if;
                        return wantarray() ? %e : \%e;
                    }
                } else {
                    my %e = retErr('_NO_NETWORK_FOUND_',"No network found for interface");
                    $e{'_if_'} = $if;
                    return wantarray() ? %e : \%e;
                }
            }
            push(@ifs,$if);
        }

        my %r = ETFW::DHCP->set_interface( 'ifaces'=>\@ifs );
        if( isError(%r) ){
            return wantarray() ? %r : \%r;
        }

        my %e = ETFW::DHCP->apply_config();
        if( isError(%e) ){
            return wantarray() ? %e : \%e;
        }
    }

    return wantarray() ? %p : \%p;
}

sub config_squid {
    my $self = shift;
    my (%p) = @_;

    if( $p{'squid'} ){
        my $S = $p{'squid'};

        # install template config file
        if( defined $S->{'ini_template'} ){
            $S->{'template'} ||= $S->{'ini_template'};
            &install_template( $S->{'template'} || 'transparent' );
        }
        $S->{'template'} ||= 'transparent';

        # TODO set network by interface...

        my $if = $S->{'if'};

        my $lifs = $p{'interfaces'}||[];
        my ($If) = grep { ( $_->{'fullname'} eq $if ) || ( !defined($_->{'fullname'}) && ( $_->{'name'} eq $if ) ) } @$lifs;

        if( !$If ){
            my %e = retErr('_NO_NETWORK_FOUND_',"No network found for interface");
            $e{'_if_'} = $if;
            return wantarray() ? %e : \%e;
        }

        my $O;
        my %A = ETFW::Squid->get_acl();
        my $acl_name = "rede";
        if( my $ld = $A{'acl'} ){
            ($O) = grep { $_->{'name'} eq $acl_name } @$ld;
        }
        if( $O ){
            ETFW::Squid->set_acl( 'index'=>$O->{'index'},
                                            'name'=>$acl_name,
                                            'type'=>'src',
                                            'vals'=>"$If->{'network'}" );
        } else {
            ETFW::Squid->add_acl( 'name'=>$acl_name,
                                            'type'=>'src',
                                            'vals'=>"$If->{'network'}" );
        }

        if( $S->{'template'} eq 'proxy_ad' ){

            # change config
            ETFW::Samba->set_dom_conf( 'DOMINIO'=>{ 
                                                    'workgroup'=>$S->{'workgroup'},
                                                    'dcipaddr'=>$S->{'dcipaddr'},
                                                    'dchostname'=>$S->{'dchostname'},
                                                    'domainadmin'=>$S->{'domainadmin'},
                                                    'domainpasswd'=>$S->{'domainpasswd'},
                                                    'realm'=>$S->{'realm'},
                                                    } );
            # join to domain
            my %e = ETFW::Samba->joindomain();
            if( isError(%e) ){
                return wantarray() ? %e : \%e;
            }
            
        } elsif( $S->{'template'} eq 'proxy_ldap' ){
            my $basedn = $S->{'base_dn'} || '"ou=Groups,dc=eurotux,dc=local"';
            my $ldapserver = $S->{'ip_admin'} || 'pdc';

            # TODO test ldap config

            my $O;
            my %E = ETFW::Squid->get_external_acl_type();
            my $external_acl_name = 'ldapgroup';
            if( my $ld = $E{'external_acl_type'} ){
                ($O) = grep { $_->{'name'} eq $external_acl_name } @$ld;
            }
            if( $O ){
                ETFW::Squid->set_external_acl_type( 'index'=>$O->{'index'},
                                                    'name'=>$external_acl_name,
                                                    'format'=>'%LOGIN',
                                                    'helper'=>'/usr/lib/squid/squid_ldap_group',
                                                    'args'=>[ '-b',$basedn, '-f "(&(cn=%g)(memberUid=%u))"', '-h',$ldapserver, '-S' ] );
            } else {
                ETFW::Squid->add_external_acl_type( 'name'=>$external_acl_name,
                                                    'format'=>'%LOGIN',
                                                    'helper'=>'/usr/lib/squid/squid_ldap_group',
                                                    'args'=>[ '-b',$basedn, '-f "(&(cn=%g)(memberUid=%u))"', '-h',$ldapserver, '-S' ] );
            }
            
#        } else {    # transparent
        }

        my %e = ETFW::Squid->apply_config();
        if( isError(%e) ){
            return wantarray() ? %e : \%e;
        }
    }
    return wantarray() ? %p : \%p;
}

sub install_template {
    my ($tmpl) = @_;

    cmd_exec("cd $conf{'base_dir'}/config; ./install-template.sh $tmpl");
}

1;
