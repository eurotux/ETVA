#!/usr/bin/perl

=pod

=head1 NAME

ETFW::Sophos

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package ETFW::Sophos;

use strict;

use Utils;
use FileFuncs;

my %CONF = ( 'conf_file'=>"/etc/sav.conf", 'conf_dir'=>"",
                'intercheck_bin'=>'/usr/bin/icheckd', 'sweep_bin'=>'/usr/bin/sweep',
                'intercheck_conf'=>'/etc/icheckd.conf' );

sub read_conf {
    my ($file) = @_;

    my @lconf = ();
    my $lnum = 0;
    open(F,$file);
    while(<F>){
        chomp;
        s/#.*//;
        if( /^\s*([^=])\s*=\s*(.*)$/ ){
            push( @lconf, { name=>"$1", value=>"$2", line=>$lnum } );
        }
        $lnum++;
    }
    close(F);

    return wantarray() ? @lconf : \@lconf;
}

=item get_config

=cut

sub get_config {
    my $self = shift;

    return read_conf($CONF{"conf_file"});
}

=item get_intercheck_config

=cut

sub get_intercheck_config {
    my $self = shift;

    return read_conf($CONF{"intercheck_conf"});
}

sub set_conf {
    my ($file,%p) = @_;
    my $cfref = read_file_lines($file); 
    for my $k (keys %p){
        my $v = $p{"$k"};
        $v = '"' . $v . '"' if( $v =~ /\s/ );
        my $gc = grep { s/^\s*${k}\s*=\s*(.*)/${k} = $v/ } @$cfref;
    }
    flush_file_lines($file);
}

sub add_conf {
    my ($file,%p) = @_;
    my $cfref = read_file_lines($file); 
    for my $k (keys %p){
        my $v = $p{"$k"};
        $v = '"' . $v . '"' if( $v =~ /\s/ );
        push(@$cfref, "$k = $v");
    }
    flush_file_lines($file);
}

=item set_config

    ARGS: %p - parameters

=cut

sub set_config {
    my $self = shift;

    return set_conf($CONF{"conf_file"},@_);
}

=item add_config

    ARGS: %p - parameters

=cut

sub add_config {
    my $self = shift;

    return add_conf($CONF{"conf_file"},@_);
}

=item set_intercheck_conf

    set intercheck configuration

    ARGS: %p - parameters

=cut

sub set_intercheck_conf {
    my $self = shift;

    return set_conf($CONF{"intercheck_conf"},@_);
}

=item add_intercheck_conf

    add intercheck configuration

    ARGS: %p - parameters

=cut

sub add_intercheck_conf {
    my $self = shift;

    return add_conf($CONF{"intercheck_conf"},@_);
}

=item start_intercheck

    start intercheck

=cut

sub start_intercheck {
    my $self = shift;

    if( -x $CONF{"intercheck_bin"} ){
        cmd_exec($CONF{"intercheck_bin"},"-d");
    }
}

=item stop_intercheck

    stop intercheck

=cut

sub stop_intercheck {
    my $self = shift;

    if( -x $CONF{"intercheck_bin"} ){
        cmd_exec($CONF{"intercheck_bin"},"-stop");
    }
}

=item run_sweep

    run sweep

=cut

sub run_sweep {
    my $self = shift;
    my (%p) = @_;

    my %R = ();
    if( -x $CONF{"sweep_bin"} ){
        
        my @args = map { length($_)>1 ? "--$_" : "-$_" } keys %p;
        ($R{"status"},$R{"message"}) = cmd_exec($CONF{"sweep_bin"},@args);
    }
    return wantarray() ? %R : \%R;
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

