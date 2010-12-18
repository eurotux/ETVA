#!/usr/bin/perl

=pod

=head1 NAME

ETFW::Qmail

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package ETFW::Qmail;

use strict;

use Utils;
use FileFuncs;

BEGIN {
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF $AUTOLOAD);
};

my %CONF = ( 'conf_dir'=>"/etc/qmail", 'alias_dir'=>"/srv/qmail/alias", 'bin_dir'=>"/srv/qmail/bin",
                'mess_dir'=>"/srv/qmail/queue/mess",
                'info_dir'=>"/srv/qmail/queue/info",
                'local_dir'=>"/srv/qmail/queue/local",
                'remote_dir'=>"/srv/qmail/queue/remote",
                'service_cmd'=>"/etc/init.d/qmail",
                'start_cmd'=>"/srv/qmail/rc"
                 );

sub AUTOLOAD {
    my ($package,$method) = ( $AUTOLOAD =~ m/(.+)::([^:]+)$/ );

    if( my ($ft,$f) = ($method =~ m/(add|set|del|get)_(\S+)/) ){
        my $file = $CONF{"conf_dir"} . "/$f";
        my $func = "${ft}_param";
        $AUTOLOAD = sub {
                        my $self = shift;
                        my (%p) = @_;
                        return $self->$func( file=>$file, %p );
                    };
    }
    if( $AUTOLOAD ){
        &$AUTOLOAD;
    }
}

=item  add_param / set_param / del_param / get_param

    set params for each case

    e.g : maxerrors, maxrecipients, outgoingip, mfcheck

    ARGS: file - file for each param
          value - param value
    
=cut

sub add_param {
    my $self = shift;
    my (%p) = @_;
    my $file = $p{"file"};
    
    my $cfref = read_file_lines($file);
    push(@$cfref,$p{"value"});
    flush_file_lines($file);
}
sub set_param {
    my $self = shift;
    my (%p) = @_;
    my $file = $p{"file"};

    my $cfref = read_file_lines($file);
    $cfref = [ $p{"value"} ];
    flush_file_lines($file);
}
sub del_param {
    my $self = shift;
    my (%p) = @_;
    my $file = $p{"file"};

    if( -f "$file" ){
        if( my $value = $p{"value"} ){
            my $lnum;
            my $cfref = read_file_lines($file);
            for my $l (@$cfref){
                if( $l eq $value ){ 
                    $lnum ||= 0;
                    last;
                }
                $lnum++;
            }
            if( defined $lnum ){    # value found
                # delete them
                splice(@$cfref,$lnum,1);
            }
            flush_file_lines($file);
        } else {
            unlink $file;
        }
    }
}
sub get_param {
    my $self = shift;
    my (%p) = @_;
    my $file = $p{"file"};
    
    my $cfref = read_file_lines($file);

    my %r = ( value=>$cfref );
    return wantarray() ? %r : \%r;
}

=item list_alias

=cut

sub list_alias {
    my $self = shift;

    my %alias = ();
    opendir(D,$CONF{"alias_dir"});
    for my $f (readdir(D)){
        if( my ($user) = ( $f =~ m/\.qmail-(\w+)/ ) ){
            my $file = $CONF{"alias_dir"} . "/$f";
            open(F,$file);
            my $a=<F>; chomp($a);
            close(F);
            $alias{"$user"} = $a;
        }
    }
    closedir(D);

    return wantarray() ? %alias : \%alias;
}

=item set_alias

    ARGS: user
          alias

=cut

sub set_alias {
    my $self = shift;
    my (%p) = @_;
    my $user = $p{"user"};
    my $alias = $p{"alias"};

    if( $user && $alias ){
        my $file = $CONF{"alias_dir"} . "/.qmail-$user";

        $self->set_param( file=>$file, value=>$alias );
    }
}

=item del_alias

    ARGS: user
          alias

=cut

sub del_alias {
    my $self = shift;
    my (%p) = @_;
    my $user = $p{"user"};
    my $alias = $p{"alias"};

    if( $user ){
        my $file = $CONF{"alias_dir"} . "/.qmail-$user";

        $self->del_param( file=>$file, value=>$alias );
    }
}

=item get_smtproutes

=cut

sub get_smtproutes {
    my $self = shift;

    my $file = $CONF{"conf_dir"} . "/smtproutes";

    my @l = ();
    open(F,$file);
    while(<F>){
        chomp;
        s/#.*//;
        if( /([^:\s]+):(\S+)$/ ){
            push(@l,{ mail=>$1, server=>$2 });
        }
    }
    close(F);

    return wantarray() ? @l : \@l;
}

=item add_smtproutes

    ARGS: mail
          server

=cut

sub add_smtproutes {
    my $self = shift;
    my (%p) = @_;

    my $file = $CONF{"conf_dir"} . "/smtproutes";
    
    my $cfref = read_file_lines($file);
    push(@$cfref,$p{"mail"} . ":" . $p{"server"});
    flush_file_lines($file);
}

=item set_smtproutes

    ARGS: mail
          server

=cut

sub set_smtproutes {
    my $self = shift;
    my (%p) = @_;

    my $file = $CONF{"conf_dir"} . "/smtproutes";

    my $mail = $p{"mail"};
    my $server = $p{"server"};
    my $cfref = read_file_lines($file);
    grep { s/${mail}:(\S+)/${mail}:${server}/ } @$cfref;
    flush_file_lines($file);
}

=item del_smtproutes

    ARGS: mail
          server

=cut

sub del_smtproutes {
    my $self = shift;
    my (%p) = @_;

    my $file = $CONF{"conf_dir"} . "/smtproutes";

    if( -f "$file" ){
        my $lnum;
        my $mail = $p{"mail"};
        my $server = $p{"server"};
        my $cfref = read_file_lines($file);
        for my $l (@$cfref){
            if( ( $mail && $server && $l =~ /^${mail}:${server}$/ ) ||
                 ( $mail && !$server && $l =~ /^${mail}/ ) ){ 
                $lnum ||= 0;
                last;
            }
            $lnum++;
        }
        if( defined $lnum ){    # value found
            # delete them
            splice(@$cfref,$lnum,1);
        }
        flush_file_lines($file);
    }
}

=item list_queue

=cut

sub list_queue {
    my $str = shift;

    my %qm = ();

    my $qdir = $CONF{"mess_dir"};

    opendir(D,$qdir);
    for my $m (readdir(D)){
        if( $m =~ /^\d+$/ ){
            opendir(D2,"$qdir/$m");
            for my $m2 (readdir(D2)){
                if( $m2 =~ /^\d+$/ ){
                    $qm{"$m2"} = "$qdir/$m/$m2";
                }
            }
            closedir(D2);
        }
    }
    closedir(D);

    my @lq = ();

    my $qread = $CONF{"bin_dir"} . "/qmail-qread";
    open(Q,"$qread |"); 
    while(<Q>){
        chomp;
        if( /^(\d+\s+\S+\s+\d+\s+\d+:\d+:\d+)\s+(\S+)\s+#(\d+)\s+(\d+)\s+(.*)/ ){
            my %q = ( 'from' => $5,
                        'id' => $3,
                        'file' => $qm{"$3"},
                        'date' => $1 );
            $_ = <Q>;
            if( /\s*(\S+)\s+(.*)/ ){
                $q{'source'} = $1;
                $q{'to'} = $2;
                push(@lq,\%q);
            }
        }
    }
    close(Q);

    return wantarray() ? @lq : \@lq;
}

=item view_message

    ARGS file|id - message file or message id

=cut

sub view_message {
    my $self = shift;
    my (%p) = @_;

    my %M = ();

    my $mfile = $p{"file"};

    if( !$mfile && $p{"id"} ){
        my $id = $p{"id"};
        my @lq = $self->list_queue();
        if( my ($q) = grep { $_->{'id'} eq $id } @lq ){
            $mfile = $q->{"file"};
        }
    } 

    if( $mfile ){
        open(F,$mfile);
        my $he = 0;
        while(<F>){
            if( !$he && /^([^:]+):\s*(.*)/ ){
                my $plc = lc($1);
                my $v = $2;
                chomp($v);
                $M{"$plc"} = $v;
            } else {
                $he = 1;
                $M{"text"} .= $_;
            }
        }
        close(F);
    }

    return wantarray() ? %M : \%M;
}

=item del_message

    ARGS id - message id

=cut

sub del_message {
    my $self = shift;
    my (%p) = @_;

    if( my $id = $p{"id"} ){
        if( my ($q) = grep { $_->{'id'} eq $id } $self->list_queue() ){

            my ($pid) = find_procname("qmail-send");
            if( $pid ){
                # stop qmail
                $self->stop_qmail();
            }

            my $file = $q->{"file"};
            my ($d,$f) = ( $file =~ /(\d+)\/(\d+)$/ );
            
            # delete from queue
            unlink( $CONF{"mess_dir"} . "/$d/$f" );
            unlink( $CONF{"info_dir"} . "/$d/$f" );
            unlink( $CONF{"remote_dir"} . "/$d/$f" );
            unlink( $CONF{"local_dir"} . "/$d/$f" );

            my ($newpid) = find_procname("qmail-send");
            if( $pid && $newpid ){
                # restart qmail
                $self->start_qmail();
            }
        }
    }
}

=item start_qmail

=cut

sub start_qmail {
    my $self = shift;
    if( -x $CONF{"service_cmd"} ){
        cmd_exec($CONF{"service_cmd"},"start");
    } else {
        cmd_exec($CONF{"start_cmd"});
    }
}

=item stop_qmail

=cut

sub stop_qmail {
    my $self = shift;
    if( -x $CONF{"service_cmd"} ){
        cmd_exec($CONF{"service_cmd"},"stop");
    } else {
        cmd_exec("killall","qmail-send");
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

