#!/usr/bin/perl

=pod

=head1 NAME

ETFW::NTP

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package NTP;

use strict;

use FileFuncs;

my %CONF = ( 'conf_file'=>'/etc/ntp.conf',
                'conf_path'=>'/etc/ntp' );

my @get_config_cache = ();
sub read_config {
    my ($file) = @_;
    if (!@get_config_cache) {
        open(CONF, $file);
        my $lnum = 0;
        while(<CONF>){
            s/\r|\n//g;	# strip newlines and comments
            if( /^\s*(\#?\s*)(\S+)\s*(.*)$/ ){
                my %dir = ();
                $dir{'name'} = $2;
                $dir{'value'} = $3;
                $dir{'enabled'} = !$1;
                $dir{'comment'} = $1;
                my $str = $3;
                while( $str =~ /^\s*("[^"]*")(.*)$/ ||
                        $str =~ /^\s*(\S+)(.*)$/ ){
                    my $v = $1;
                    $str = $2;
                    if( $v !~ /^"/ && $v =~ /^(.*)#/ &&
                        !$dir{'comment'} ){
                        # A comment .. end of values
                        $v = $1;
                        $dir{'postcomment'} = $str;
                        $str = undef;
                        last if ($v eq '');
                    }
                    push(@{$dir{'values'}}, $v);
                }
                $dir{'line'} = $lnum;
                $dir{'index'} = scalar(@get_config_cache);
                push(@get_config_cache, \%dir);
            }
            $lnum++;
        }
        close(CONF);
	}
    return \@get_config_cache;
}

=item get_config

    get NTP configuration

=cut

sub get_config {
    my $self = shift;

    my %conf = ();
    my $lconf = read_config( $CONF{"conf_file"} );

    for my $L (@$lconf){
        if( $L->{"enabled"} ){
            my $p = $L->{"name"};
            if( $conf{"$p"} ){
                push(@{$conf{"$p"}}, @{$L->{"values"}});
            } else {
                $conf{"$p"} = $L->{"values"};
            }
        }
    }

    return wantarray() ? %conf : \%conf ;
}

sub save_config {
    my ($file,%p) = @_;

    my $cfref = read_file_lines($file);
    foreach my $opt (keys %p) {
        my $val = $p{"$opt"};
        my @lval = ref($val) ? @$val : ($val);
        my $v = shift(@lval);
        my $l = 0;
        for (@$cfref){
            if( $v && s/^(\s*$opt\s+)([^#]*)(.*)$/$1$v$3/ ){
                $v = shift(@lval);
            } elsif( /^(\s*$opt\s+)([^#]*)(.*)$/ ){
                splice(@$cfref,$l,1);   # delete line
            }
            $l++;
        }
        while($v){
            push @$cfref, "$opt $v";
            $v = shift(@lval);
        }
    }
    flush_file_lines($file);
}

=item set_config

    set NTP configuration

    ARGS: %p - parameteres

=cut

sub set_config {
    my $self = shift;
    my (%p) = @_;

    save_config($CONF{"conf_file"},%p);
}

sub add_config {
    my ($file,%p) = @_;

    my $cfref = read_file_lines($file);
    foreach my $opt (keys %p) {
        my $val = $p{"$opt"};
        my @lval = ref($val) ? @$val : ($val);
        my $grep_count = 0;
        my ($i,$s);
        for($i=0;$i<scalar(@$cfref);$i++){
            my $l = $cfref->[$i];
            if( $l =~ /^(\s*$opt\s+)([^#]*)(.*)$/ ){
                $s=$i+1;
            } else {
                last if( $s );
            }
        }
        $s = scalar(@$cfref) if( !$s );
        splice(@$cfref,$s,0,map { "$opt $_" } @lval);
    }
    flush_file_lines($file);
}

=item add_server

    add NTP server

    ARGS: server - the server

=cut

sub add_server {
    my $self = shift;
    my (%p) = @_; 
    if( my $server = $p{"server"} ){
        add_config($CONF{"conf_file"}, "server"=>$server );
    }
}

=item listserver

    list NTP server

=cut

sub list_server {
    my $self = shift;

    my @list = ();

    my $lconf = read_config( $CONF{"conf_file"} );
    for my $L (@$lconf){
        if( $L->{"enabled"} && ($L->{'name'} eq 'server') ){
            push(@list, $L->{"values"}[0]);
        }
    }

    return wantarray() ? @list : \@list;
}

=item apply_config

=cut

sub apply_config {
    my $self = shift;

    if( -x "/etc/init.d/ntpd" ){
        system("/etc/init.d/ntpd stop");
        system("/etc/init.d/ntpd start");
    }
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

