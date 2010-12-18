#!/usr/bin/perl

=pod

=head1 NAME

ETFW::IPSec

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package ETFW::IPSec;

use strict;

use Utils;
use FileFuncs;

my %CONF = ( "conf_file"=>"/etc/ipsec.conf", "conf_dir"=>"/etc/ipsec.d",
                "secrets_file"=>"/etc/ipsec.secrets", "policies_dir"=>"/etc/ipsec.d/policies",
                "service_cmd"=>"/etc/init.d/ipsec","binary_file"=>"/usr/sbin/ipsec" );

my @rsa_attribs = ( "Modulus", "PublicExponent", "PrivateExponent",
                     "Prime1", "Prime2", "Exponent1", "Exponent2", "Coefficient" );

# read_config([file])
# Returns an array of configured connections
sub read_config {
    my ($file) = @_;

    my (@rv, $sect);
    my $lnum = 0;

    open(CONF, $file);
    while( <CONF> ){
        s/\r|\n//g;
        s/#.*$//;
        if (/^\s*([^= ]+)\s*=\s*"([^"]*)"/ ||
            /^\s*([^= ]+)\s*=\s*'([^"]*)'/ ||
            /^\s*([^= ]+)\s*=\s*(\S+)/) {
            # Directive within a section
            if ($sect) {
                if ($sect->{'values'}->{lc($1)}) {
                        $sect->{'values'}->{lc($1)} .= "\0".$2;
                } else {
                        $sect->{'values'}->{lc($1)} = $2;
                }
                $sect->{'eline'} = $lnum;
            }
        } elsif (/^\s*include\s+(\S+)/) {
            # Including possibly multiple files
            my $inc = $1;
            if ($inc !~ /^\//) {
                $file =~ /^(.*)\//;
                $inc = "$1/$inc";
            }
            foreach my $g (glob($inc)) {
                my @inc = get_config($g);
                map { $_->{'index'} += scalar(@rv) } @inc;
                push(@rv, @inc);
            }
        } elsif (/^\s*(\S+)\s+(\S+)/) {
            # Start of a section
            $sect = { 'name' => $1,
                      'value' => $2,
                      'line' => $lnum,
                      'eline' => $lnum,
                      'file' => $file,
                      'index' => scalar(@rv),
                      'values' => { } };
            push(@rv, $sect);
        }
        $lnum++;
    }
    close(CONF);
    return @rv;
}

=item get_config

    get server configuration

=cut

sub get_config {
    my $self = shift;

    my %conf = ();

    my ($config) = grep { $_->{"name"} eq "config" } read_config( $CONF{"conf_file"} );
    if( $config ){
        %conf = %{$config->{"values"}};
    }

    return wantarray() ? %conf : \%conf;
}

# save configuration
sub save_config {
    my ($file,%config) = @_;

    my @lines = ($config{"name"}." ".$config{"value"});
    for my $o ( sort { $a cmp $b } keys %{$config{'values'}} ){
        my $v = $config{'values'}{"$o"};

        foreach my $vv ( split(/\0/, $v) ){
            if ($vv =~ /\s|=/) {
                push(@lines, "\t".$o."=\"".$vv."\"");
            } else {
                push(@lines, "\t".$o."=".$vv);
            }
        }
    }
        
    if( $config{"file"} ){
        splice_file_lines($file, $config{"line"},$config{"eline"} - $config{"line"} + 1, @lines);
    } else {
        push_file_lines($file,@lines);
    }
}

=item set_config

    set server configuration

=cut

sub set_config {
    my $self = shift;
    my (%p) = @_;

    my ($config) = grep { $_->{"name"} eq "config" } read_config( $CONF{"conf_file"} );
    if( !$config ){
        $config = { name=>"config", value=>"setup", 'values'=>{} };
    }
    
    for my $k (keys %p){
        $config->{"values"}{"$k"} = $p{"$k"};
    }
    return save_config( $config->{"file"} || $CONF{"conf_file"}, %$config );
}

=item get_public_key

    Returns this system's public key

=cut

sub get_public_key {
    my $self = shift;

    my %key = ( );
    my ($e,$out) = cmd_exec($CONF{"binary_file"},"showhostkey","--file ",quotemeta($CONF{"secrets_file"}),"--left");
    if( $out =~ /leftrsasigkey=(\S+)/ ){
        %key = ( key=>$1 );
    }
    return wantarray() ? %key : \%key;
}

=item get_public_key_dns

    Returns the flags, protocol, algorithm and key data for the public key,
    suitable for creating a DNS KEY record

=cut

sub get_public_key_dns {
    my $self = shift;

    my %key = ();
    my ($e,$out) = cmd_exec($CONF{"binary_file"},"showhostkey","--file ",quotemeta($CONF{"secrets_file"}),"--left");
    if( $out =~ /KEY\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)/ ){
        %key = ( flags=>$1, proto=>$2, alg=>$3, key=>$4 );
    } else {
        ($e,$out) = cmd_exec($CONF{"binary_file"},"showhostkey","--key","--file ",quotemeta($CONF{"secrets_file"}),"--left");
        if( $out =~ /KEY\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)/ ){
            %key = ( flags=>$1, proto=>$2, alg=>$3, key=>$4 );
        }
    }
    return wantarray() ? %key : \%key;
}

=item list_secrets

    Returns a list of IPsec secret keys

=cut

sub list_secrets {
    my $self = shift;

    my @lines = ();
    my $lnum = 0;
    open(S,$CONF{"secrets_file"});
    while(<S>){
        s/\r|\n//g;
        s/^\s*#.*$//;
        if (/^(\S.*)$/) {
            push(@lines, { 'value' => $1,
                           'line' => $lnum,
                           'eline' => $lnum });
        } elsif (/^\s+(.*)/ && @lines) {
            $lines[$#lines]->{'value'} .= "\n".$1;
            $lines[$#lines]->{'eline'} = $lnum;
        }
        $lnum++;
    }
    close(S);

    my @list_secrets;

    # Turn joined lines into secrets
    foreach my $l (@lines) {
        $l->{'value'} =~ /^([^:]*)\s*:\s+(\S+)\s+((.|\n)*)$/ || next;
        my $sec = { 'type' => $2,
                       'name' => $1,
                       'value' => $3,
                       'line' => $l->{'line'},
                       'eline' => $l->{'eline'},
                       'idx' => scalar(@list_secrets),
                  };
        $sec->{'name'} =~ s/\n/ /g;
        $sec->{'name'} =~ s/\s+$//;
        push(@list_secrets, $sec);
    }

    return @list_secrets;
}

sub save_secret {
    my ($file,%secret) = @_;

    if( $secret{"type"} eq "rsa" ){
        my ($strsecret) = ( $secret{"value"} =~ m/{\n([.\n]*)\s*}/gs );
        for my $p (@rsa_attribs){
            if( defined($secret{"$p"}) ){
                my $val = $secret{"$p"};
                $val =~ s/\s//g;
                if( not $strsecret =~ s/^($p:\s+)(\S+)(.*)$/$1$val$2/s ){
                    $strsecret .= "\t" . $p . ": " . $val . "\n";
                }
            }
        }
        $secret{"value"} = "{\n";
        $secret{"value"} .= $strsecret;
        $secret{"value"} .= "\t}";
    } elsif( $secret{"type"} eq "psk" ){
        $secret{"value"} .= '"' . $secret{"pass"} . '"';
    }

    my $str = $secret{"name"} ? $secret{"name"} . ": " : ": ";
    $str .= uc($secret{"type"});
    $str .= " " . $secret{"value"};
    my @lines = split(/\n/,$str);

    if( $secret{"secret"} ){
        splice_file_lines($file, $secret{"line"},$secret{"eline"} - $secret{"line"} + 1, @lines);
    } else {
        push_file_lines($file,@lines);
    }
}

=item find_secrect

    lookup for a secret

    ARGS: name - name secret
          index - index of secret in list

=cut

sub find_secret {
    my $self = shift;
    my (%p) = @_;

    my %secret = ();
    
    my @lsecrets = $self->list_secrets();
    if( my $i = delete $p{"index"} ){
        %secret = (%{$lsecrets[$i]},%p);
    } elsif( my ($isecret) = grep { $_->{"name"} eq $p{"name"} } @lsecrets ){
        %secret = (%$isecret,%p);
    }

    return wantarray() ? %secret : \%secret;
}

=item set_secret

    change secret

    ARGS: name - secret name
          index - index list
          type - secret type (e.g. rsa, ...)
          value - secret value
          %p - other parameters

=cut

sub set_secret {
    my $self = shift;
    my (%p) = @_;

    my %secret = $self->find_secret( %p );

    return save_secret($CONF{"secrets_file"},%secret);
}

=item add_secret

    add new secret

    ARGS: name - secret name
          index - index list
          type - secret type (e.g. rsa, ...)
          value - secret value
          %p - other parameters

=cut

sub add_secret {
    my $self = shift;
    my (%p) = @_;

    return save_secret($CONF{"secrets_file"},%p);
}

=item del_secret

    delete secret

    ARGS: name - secret name
          index - index list

=cut

sub del_secret {
    my $self = shift;
    my (%p) = @_;

    if( my %secret = $self->find_secret( %p ) ){
        splice_file_lines($CONF{"secrets_file"}, $secret{"line"},$secret{"eline"} - $secret{"line"} + 1);
    }
}

=item list_policies()

    Returns a list of all policy files

=cut

sub list_policies {
    my $self = shift;

    my @list = ();
    opendir(DIR,$CONF{"policies_dir"});
    for my $f (readdir(DIR)){
        if( $f !~ /^\./ && $f !~ /\.rpmsave$/ ){
            push(@list,$f);
        }
    }
    close(DIR);

    return wantarray() ? @list : \@list;
}

=item read_policy

    show policy

    ARGS: name - name of policy

=cut
sub read_policy {
    my $self = shift;
    my (%p) = @_;

    my @rv = ();
    if( my $name = $p{"name"} ){
        open(FILE, "$CONF{'policies_dir'}/$name");
        while(<FILE>) {
            if( /^\s*([0-9\.]+)\/(\d+)/ ){
                push(@rv, "$1/$2");
            }
        }
        close(FILE);
    }
    return wantarray() ? @rv : \@rv;
}

=item  write_policy

    change policies

    ARGS: name - policy name
          networks - list of networks policies defination
            address - network address
            prefix - network prefix

=cut

sub write_policy {
    my $self = shift;
    my (%p) = @_;

    if( my $name = delete $p{"name"} ){
        my $lref = read_file_lines("$CONF{'policies_dir'}/$name");
        my $l = 0;
        for my $N ( @{$p{"networks"}} ) {
            my $p = "$N->{address}/$N->{prefix}";
            while( $l < @$lref && $lref->[$l] !~ /^\s*([0-9\.]+)\/(\d+)/ ) {
                $l++;
            }
            if ($l < @$lref) {
                # Found line to replace
                $lref->[$l] = $p;
            } else {
                # Add at end 
                push(@$lref, $p);
            }
            $l++;
        }
        while($l < @$lref) {
            if ($lref->[$l] =~ /^\s*([0-9\.]+)\/(\d+)/) {
                splice(@$lref, $l, 1);
            } else { $l++; }
        }
        flush_file_lines();
    }
}

=item add_policy_network

    add network policy

    ARGS: name - policy name
          network - network ( address + prefix ), or
          address - network address
          prefix - network prefix

=cut

sub add_policy_network {
    my $self = shift;
    my (%p) = @_;

    my $name = $p{"name"};
    my $network = $p{"network"} || $p{"address"}."/".$p{"prefix"};
    if( $name && $network ){
        # Add at end 
        push_file_lines("$CONF{'policies_dir'}/$name", $network);
    }
}

=item del_policy_network

    delete network policy

    ARGS: name - policy name
          network - network ( address + prefix ), or
          address - network address 
          prefix - network prefix

=cut

sub del_policy_network {
    my $self = shift;
    my (%p) = @_;

    my $name = $p{"name"};
    my $network = $p{"network"} || $p{"address"}."/".$p{"prefix"};
    if( $name && $network ){
        my $lref = read_file_lines("$CONF{'policies_dir'}/$name");

        my $l = 0;
        while( $l < @$lref && $lref->[$l] !~ $network ) {
            $l++;
        }

        if ($l < @$lref) {
            # Found line
            splice(@$lref,$l,1);
        }
        flush_file_lines();
    }
}

=item start

    start service

=cut

sub start {
    my $self = shift;
    if( -x $CONF{"service_cmd"} ){
        cmd_exec($CONF{"service_cmd"},"start");
    }
}

=item stop

    stop service

=cut

sub stop {
    my $self = shift;
    if( -x $CONF{"service_cmd"} ){
        cmd_exec($CONF{"service_cmd"},"stop");
    }
}

=item restart

    restart service

=cut

sub restart {
    my $self = shift;

    if( -x $CONF{"service_cmd"} ){
        cmd_exec($CONF{"service_cmd"},"restart");
    } else {
        $self->stop();
        $self->start();
    } 
}

=item is_ipsec_running

    check service status

=cut

sub is_ipsec_running {
    my $self = shift;

    my ($e,$out) = cmd_exec($CONF{"binary_file"},"auto","--status");

    return $e || $out =~ /not running/i ? 0 : 1;
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

