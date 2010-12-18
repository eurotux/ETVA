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

use Utils;

use Data::Dumper;

BEGIN {
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF $AUTOLOAD);
};

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
    }
    if( $AUTOLOAD ){
        &$AUTOLOAD;
    }
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
    my %ec = $self->get_enabled_config();

    my %sel_conf = ();
    for my $f (@fields){
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
    my $conf = $self->load_config();
    for my $o ( keys %p ){
        my @v = map { { name=>$o, 'values'=>[ $_ ] } } ref($p{"$o"}) eq "ARRAY" ? @{$p{"$o"}} : ($p{"$o"});
        save_directive($conf,$o,\@v);
    }
    flush_file_lines();
    $self->load_config(1);
}

=item add_config

=cut

sub add_config {
    my $self = shift;
    my (%p) = @_;
    my $conf = $self->load_config();
    my %ec = $self->get_enabled_config();
    for my $o ( keys %p ){
        my @oldv = ref($ec{"$o"}) eq "ARRAY" ? @{$ec{"$o"}} : ($ec{"$o"});
        my @newv = ref($p{"$o"}) eq "ARRAY" ? @{$p{"$o"}} : ($p{"$o"});
        my @v = map { { name=>$o, 'values'=>[ $_ ] } } @newv,@oldv;
        save_directive($conf,$o,\@v);
    }
    flush_file_lines();
    $self->load_config(1);
}

=item del_config

=cut

sub del_config {
    my $self = shift;
    my (%p) = @_;
    my $conf = $self->load_config();
    my %ec = $self->get_enabled_config();
    for my $o ( keys %p ){
        my @qv = ref($p{"$o"}) eq "ARRAY" ? @{$p{"$o"}} : ($p{"$o"});
        my $re = join('\s+',@qv);

        my @newv = ();
        for my $e (ref($ec{"$o"}) eq "ARRAY" ? @{$ec{"$o"}} : ($ec{"$o"})){
            # if params to delete not match
            if( $e !~ /^$re$/ ){
                push(@newv,$e);
            }
        }
        my @v = map { { name=>$o, 'values'=>[ $_ ] } } @newv;
        save_directive($conf,$o,\@v);
    }
    flush_file_lines();
    $self->load_config(1);
}

sub add_config_option {
    my $self = shift;
    my (%p) = @_;

    if( my $o = $p{'name'} ){

        my $conf = $self->load_config();
        my %ec = $self->get_enabled_config();
        my $values = $p{'values'} || [ $p{'value'} ];

        # get all values
        my @oldv = ref($ec{"$o"}) eq "ARRAY" ? @{$ec{"$o"}} : ($ec{"$o"});

        # push it
        my @v = map { { name=>$o, 'values'=>[ $_ ] } } @oldv,@$values;

        save_directive($conf,$o,\@v);

        flush_file_lines();
        $self->load_config(1);
    }
}
sub del_config_option {
    my $self = shift;
    my (%p) = @_;

    if( my $o = $p{'name'} ){

        my $conf = $self->load_config();
        my %ec = $self->get_enabled_config();
        my $values = $p{'values'} || [ $p{'value'} ];

        # get all values
        my @oldv = ref($ec{"$o"}) eq "ARRAY" ? @{$ec{"$o"}} : ($ec{"$o"});

        # drop it
        if( defined $p{'index'} ){          # by one index
            my $i = $p{'index'} || 0;
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

        save_directive($conf,$o,\@v);

        flush_file_lines();
        $self->load_config(1);
    }
}
sub set_config_option {
    my $self = shift;
    my (%p) = @_;

    if( my $o = $p{'name'} ){

        my $conf = $self->load_config();
        my %ec = $self->get_enabled_config();
        my $values = $p{'values'} || [ $p{'value'} ];

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

        save_directive($conf,$o,\@v);

        flush_file_lines();
        $self->load_config(1);
    }
}
sub get_config_option {
    my $self = shift;
    my (%p) = @_;

    if( my $o = $p{'name'} ){

        my %ec = $self->get_enabled_config();

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

=item add_http_port / del_http_port / set_http_port

=cut

sub mkargs_http_port {
    my $self = shift;
    my (%p) = @_;

    my %r = ();
    if( my $port = $p{"port"} ){
        my $line = "$port";
        if( my $addr = $p{"addr"} ){
            $line = "$addr:$port";
        }
        my @v = ( $line );
        if( my $opts = $p{"opts"} ){
            push(@v,$opts);
        }

        %r = ( %p, 'name'=>"http_port", 'values'=>[ @v ] );
    }
    return wantarray() ? %r : \%r;
}

=item add_acl / del_acl / set_acl 

       Defining an Access List

    ARGS: name type string

=cut

sub mkargs_acl {
    my $self = shift;
    my (%p) = @_;

    my $arg = $p{"arg"} =~ /\s/ ? '"'.$p{"arg"}.'"' : $p{"arg"};

    my %r = ( %p, 'name'=>"acl", 'values'=>[ "$p{'name'} $p{'type'} $arg" ] );

    return wantarray() ? %r : \%r;
}

=item add_http_access / del_http_access / set_http_access

       Allowing or Denying access based on defined access lists

    ARGS: allow|deny acl

=cut

sub mkargs_http_access {
    my $self = shift;
    my (%p) = @_;

    my $action = $p{"allow"} ? "allow" : "deny";
    my @acl = ref($p{"acl"}) eq "ARRAY" ? @{$p{"acl"}} : ($p{"acl"});
    my $lacl = join(" ",@acl);
    
    my %r = ( %p, 'name'=>"http_access", 'values'=>[ "$action $lacl" ] );

    return wantarray() ? %r : \%r;
}

=item add_icp_access / del_icp_access / set_icp_access

       Allowing or Denying access to the ICP port based on defined
       access lists

    ARGS: allow|deny acl

=cut

sub mkargs_icp_access {
    my $self = shift;
    my (%p) = @_;

    my $action = $p{"allow"} ? "allow" : "deny";
    my @acl = ref($p{"acl"}) eq "ARRAY" ? @{$p{"acl"}} : ($p{"acl"});
    my $lacl = join(" ",@acl);
    
    my %r = ( %p, 'name'=>"icp_access", 'values'=>[ "$action $lacl" ] );

    return wantarray() ? %r : \%r;
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

    my @v = ( $p{"name"} );
    my @options = ref($p{"options"}) eq "ARRAY" ? @{$p{"options"}} : ($p{"options"});
    push(@v,@options) if( @options );
    push(@v,$p{"format"});
    push(@v,$p{"helper"});
    my @args = ref($p{"args"}) eq "ARRAY" ? @{$p{"args"}} : ($p{"args"});
    push(@v,@args) if( @args );

    my $sv = join(" ",@v);
    my %r = ( %p, 'name'=>"external_acl_type", 'values'=>[ $sv ] );

    return wantarray() ? %r : \%r;
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

no strict;

use ETFW::Webmin;

my %WebminConf = ETFW::Webmin->get_config();

require "$WebminConf{root}/squid/parser-lib.pl";
require "$WebminConf{root}/web-lib-funcs.pl";

%config = ( squid_conf=>'/etc/squid/squid.conf' );

use strict;

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

1;
