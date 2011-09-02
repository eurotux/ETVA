#!/usr/bin/perl

=pod

=head1 NAME

ETFW::DDClient

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package ETFW::DDClient;

use strict;

use ETVA::Utils;
use FileFuncs;

my %CONF = ( "conf_file"=>"/etc/ddclient/ddclient.conf",
                "conf_path"=>"/etc/ddclient",
                "service_path"=>"/etc/init.d/ddclient",
                "binary_file" =>"/usr/sbin/ddclient" );

sub read_config {
    my ($file) = @_;

    my $line = "";
    my %conf = ();
    open(C,$file);
    while(<C>){
#print STDERR;
        chomp;
        s/#\s*(.*)//;
        if( $_ ){
            if( s/\s*\\$/ / ){
                $line .= $_;
                next;
            } else {
                $line .= $_;
print STDERR "line=",$line,"\n";
                if( $line =~ m/^\s*((\S+\s*=\s*('[^']+'|\S+)\s*,?\s*)*)\s*((\S+\s*,?\s*)*)\s*((\S+\s*)*)\s*$/ ){
                    my ($ini,$hosts,$end) = ($1,$4,$6);
                    if( !$ini && !$end ){
                        push(@{$conf{"hosts"}},split(/,/,$hosts));
                    } else {
                        my $S = \%conf;
                        push(@{$conf{"servers"}}, $S = {}) if($line =~ ",");
                        if( $ini || $end ){
                            for my $a ((split(/,/,$ini),split(/,/,$end))){
                                my ($k,$v) = split(/=/,$a);
                                my ($ck,$cv) = (trim($k),trim($v));
                                $cv =~ s/^'//;$cv =~ s/'$//;
                                $S->{"$ck"} = $cv;
                            }
                        }
                        if( $hosts ){
                            push(@{$S->{"hosts"}},split(/,/,$hosts));
                        }
                    }
                }
                        
                $line = "";
            }
        }
    }
    close(C);

    return wantarray() ? %conf : \%conf;
}

=item get_config

=cut

sub get_config {
    my $self = shift;
    
    return read_config($CONF{"conf_file"});
}

sub save_config {
    my ($file,%p) = @_;

    my $cfref = read_file_lines($file);

    for my $opt (keys %p){
        my $val = $p{"$opt"};
        if( !ref($val) ){
            my $sval = ( $val =~ /\s/ )? "'$val'":"$val";
            my $grep_count = grep { s/^(\s*$opt\s*=\s*)([^#]*)(.*)$/$1$sval$3/ } @$cfref;
            if( !$grep_count ){
                push @$cfref, "$opt = $sval";
            }
        } else {
            my %s = %$val;
            my ($k,$v);
            if( $v = delete $s{"protocol"} ){
                $k = "protocol";
            } elsif( $v = delete $s{"use"} ){
                $k = "use";
            } else {
                $k = "";
                $v = delete $s{"host"};
            }
            my $expr = ($k)?("$k\s*=\s*$v"):("$v");
            my $line = "";
            for my $a (keys %s){
                my $b = $s{"$a"};
                $b = join(",",@$b) if( ref($b) );
                my $sb = ($b =~ /\s/)? "'$b'":"$b";
                $line .= ", " if( $line );
                $line .= ( $a =~ "host" )? "$sb":"$a = $sb"
            }
            my $grep_count = grep { s/^([^#]*)(\s*$expr\s*)([^#]*)(.*)$/$2$line$4/ } @$cfref;
            if( !$grep_count ){
                my $head = ($k)?("$k=$v"):("$v");
                push @$cfref, ($head && $line)? ("$head, $line"):($head||$line);
            }
        }
    }
    flush_file_lines($file)
}

=item set_config

=cut

sub set_config {
    my $self = shift;
    my (%p) = @_;

    save_config($CONF{"conf_file"},%p);
}

=item start

=cut

sub start {
    my $self = shift;
    if( -x $CONF{"service_path"} ){
        cmd_exec($CONF{"service_path"},"start");
    } else {
        my %C = $self->get_config() || %CONF;
        my @a = ();

        my $file = $CONF{"conf_file"};

        if( my $daemon = $C{"daemon"} || $CONF{"delay"} ){ push(@a,"-daemon",$daemon); }
        if( my $pid = $C{"pid"} || $CONF{"pid_file"} ){ push(@a,"-pid",$pid); }
        if( my $cache = $C{"cache"} || $CONF{"cache_file"} ){ push(@a,"-cache",$cache); }
        if( -f "$file" ){
            push(@a,"-file",$file);
        } else {
            if( my $proxy = $C{"proxy"} || $CONF{"proxy"} ){ push(@a,"-proxy",$proxy); }
            if( my $server = $C{"server"} || $CONF{"server"} ){ push(@a,"-server",$server); }
            if( my $protocol = $C{"protocol"} || $CONF{"protocol"} ){ push(@a,"-protocol",$protocol); }
            if( my $use = $C{"use"} || $CONF{"use"} ){ push(@a,"-use",$use); }
            if( my $ip = $C{"ip"} || $CONF{"ip"} ){ push(@a,"-ip",$ip); }
            if( my $if = $C{"if"} || $CONF{"if"} ){ push(@a,"-if",$if); }
            if( my $ifskip = $C{"if-skip"} || $CONF{"if-skip"} ){ push(@a,"-if-skip",$ifskip); }
            if( my $web = $C{"web"} || $CONF{"web"} ){ push(@a,"-web",$web); }
            if( my $webskip = $C{"web-skip"} || $CONF{"web-skip"} ){ push(@a,"-web-skip",$webskip); }
            if( my $fw = $C{"fw"} || $CONF{"fw"} ){ push(@a,"-fw",$fw); }
            if( my $fwskip = $C{"fw-skip"} || $CONF{"fw-skip"} ){ push(@a,"-fw-skip",$fwskip); }
            if( my $fwlogin = $C{"fw-login"} || $CONF{"fw-login"} ){ push(@a,"-fw-login",$fwlogin); }
            if( my $fwpassword = $C{"fw-password"} || $CONF{"fw-password"} ){ push(@a,"-fw-password",$fwpassword); }
            if( my $cmd = $C{"cmd"} || $CONF{"cmd"} ){ push(@a,"-cmd",$cmd); }
            if( my $cmdskip = $C{"cmd-skip"} || $CONF{"cmd-skip"} ){ push(@a,"-cmd-skip",$cmdskip); }
            if( my $login = $C{"login"} || $CONF{"login"} ){ push(@a,"-login",$login); }
            if( my $password = $C{"password"} || $CONF{"password"} ){ push(@a,"-password",$password); }
            if( my $host = $C{"host"} || $CONF{"host"} ){ push(@a,"-host",$host); }
        }
        
        cmd_exec($CONF{"binary_file"},@a);
    }
}

=item stop

=cut

sub stop {
    my $self = shift;
    if( -x $CONF{"service_path"} ){
        cmd_exec($CONF{"service_path"},"stop");
    } else {
        my %C = $self->get_config() || %CONF;
        my $pidf = $C{"pid"} || $CONF{"pid_file"};
        if( -f "$pidf" ){
            open(F,"$pidf");
            my ($pid) = <F>; chomp($pid);
            close(F);
            cmd_exec("/bin/kill",$pid);
        }
    } 
}

=item restart

=cut

sub restart {
    my $self = shift;

    if( -x $CONF{"service_path"} ){
        cmd_exec($CONF{"service_path"},"restart");
    } else {
        $self->stop();
        $self->start();
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

