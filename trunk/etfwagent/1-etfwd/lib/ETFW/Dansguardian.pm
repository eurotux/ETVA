#!/usr/bin/perl

=pod

=head1 NAME

ETFW::Dansguardian

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package ETFW::Dansguardian;

use strict;

BEGIN {
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF $AUTOLOAD);
};

use Utils;
use FileFuncs;

use Fcntl qw(:DEFAULT :flock);

my %CONF = ( "conf_path"=>"/etc/dansguardian",
                "conf_file"=>"/etc/dansguardian/dansguardian.conf",
                "binary_file"=>"/usr/sbin/dansguardian" );

# Group configuration files
my @GROUP_CONFIG_FILES = ("bannedphraselist", "exceptionphraselist", "weightedphraselist", "bannedsitelist", "greysitelist", "exceptionsitelist", "bannedurllist", "greyurllist", "exceptionurllist", "bannedregexpurllist", "bannedextensionlist", "bannedmimetypelist", "picsfile", "contentregexplist");

# Other configuration files
my @CONF_FILES = ("banneduserlist", "exceptionuserlist", "bannediplist", "exceptioniplist",
                    "htmltemplate", "bannedphraselist", "bannedextensionlist", "bannedmimetypelist", "bannedsitelist", "bannedurllist", "bannedregexpurllist", "exceptionphraselist", "exceptionsitelist", "exceptionurllist", "weightedphraselist", "picsfile");

sub AUTOLOAD {
    my ($package,$method) = ( $AUTOLOAD =~ m/(.+)::([^:]+)$/ );

    if( my ($f1,$f2) = ($method =~ m/add_(\S+)_(\S+)/) ){
        my $file = "${f1}${f2}list";
        if( grep(/^$file$/,@CONF_FILES,@GROUP_CONFIG_FILES) ){
            $AUTOLOAD = sub {
                            my $self = shift;
                            my (%p) = @_;
                            my $tofile = $CONF{"conf_path"}."/".$file;
                            return $self->append_content_to_file( file=>$tofile, content=>$p{"value"} );
                        };
        }
    } elsif( my ($f1,$f2) = ($method =~ m/del_(\S+)_(\S+)/) ){
        my $file = "${f1}${f2}list";
        if( grep(/^$file$/,@CONF_FILES,@GROUP_CONFIG_FILES) ){
            $AUTOLOAD = sub {
                            my $self = shift;
                            my (%p) = @_;
                            my $tofile = $CONF{"conf_path"}."/".$file;
                            return $self->remove_content_from_file( file=>$tofile, content=>$p{"value"} );
                        };
        }
    }
    if( $AUTOLOAD ){
        &$AUTOLOAD;
    }
}

=item get_conf

=cut

sub get_conf {
    my $self = shift;

    return read_conf_file($CONF{"conf_file"});
} 

=item set_conf

    ARGS: %p - parameteres

=cut

sub set_conf {
    my $self = shift;
    my (%p) = @_;

    my $cfref = read_file_lines($CONF{"conf_file"});

    foreach my $opt (keys %p) {
        my $val = $p{"$opt"};
        my $grep_count = grep { s/^(\s*$opt\s*=\s*)([^#]*)(.*)$/$1$val$3/ } @$cfref;
        if( !$grep_count ){
            push @$cfref, "$opt = $val";
        }
    }

    flush_file_lines($CONF{"conf_file"});
}

=item get_group_conf
    
    ARGS: group - group name

=cut

sub get_group_conf {
    my $self = shift;
    my (%p) = @_; 

    my $group = $p{"group"} || "f1";

    my $groupnum = read_conf_option( $CONF{"conf_file"},"filtergroups" );

    if( $group =~ /\w(\d+)/ && $groupnum < $1 ){
        my $group_file = $CONF{"conf_path"}."/dansguardian".$group.".conf";
        if( -e "$group_file" ){ 
            return read_conf_file($group_file);
        }
    }
}

=item get_file_content

    ARGS: file - config file 

=cut

sub get_file_content {
    my $self = shift;
    my (%p) = @_;

    my $file = $p{"file"};
    if( is_under_directory( $CONF{"conf_path"}, $file ) ){
        if( -f "$file" ){
            my $cfref = read_file_lines($file);
            my %r = ( content=>join("\n",@$cfref) );
            return wantarray() ? %r : \%r;
        }
    }
}

=item save_file_content

    ARGS: file    - config file
          content - file content

=cut

sub save_file_content {
    my $self = shift;
    my (%p) = @_;

    my $file = $p{"file"};
    if( is_under_directory( $CONF{"conf_path"}, $file ) ){
        
        open(OUTFILE,">$file");
        # TODO lock file
        print OUTFILE $p{"content"};
        close(OUTFILE);

        # remove from cache
        unflush_file_lines($file);
    }
}

sub remove_content_from_file {
    my $self = shift;
    my (%p) = @_;

    my $file = $p{"file"};
    if( is_under_directory( $CONF{"conf_path"}, $file ) ){
        
        open(INFILE,"$file");
        # TODO lock file
        my @lines=<INFILE>;
        close(INFILE);
        open(OUTFILE,">$file");
        for(@lines){
            if( $_ !~ $p{"content"} ){
                print OUTFILE $_;
            }
        }
        close(OUTFILE);

        # remove from cache
        unflush_file_lines($file);
    }
}

sub append_content_to_file {
    my $self = shift;
    my (%p) = @_;

    my $file = $p{"file"};
    if( is_under_directory( $CONF{"conf_path"}, $file ) ){
        
        open(OUTFILE,">>$file");
        # TODO lock file
        print OUTFILE $p{"content"},"\n";
        close(OUTFILE);

        # remove from cache
        unflush_file_lines($file);
    }
}

=item get_state

    get dansguardian state
    
=cut

sub get_state {
    my $self = shift;

    my %r = ( );

    ($r{"e"},$r{"state"}) = cmd_exec($CONF{"binary_file"},'-s');

    return wantarray() ? %r : \%r;
}

=item add_banned_user / del_banned_user

=cut

=item add_exception_user / del_exception_user

=cut

=item add_banned_ip / del_banned_ip

=cut

=item add_exception_ip / del_exception_ip

=cut

=item add_banned_phrase / del_banned_phrase

=cut

=item add_banned_extension / del_banned_extension

=cut

=item add_banned_mimetype / del_banned_mimetype

=cut

=item add_banned_site / del_banned_site

=cut

=item add_banned_url / del_banned_url

=cut

=item add_banned_regexpurl / del_banned_regexpurl

=cut

=item add_exception_phrase / del_exception_phrase

=cut

=item add_exception_site / del_exception_site

=cut

=item add_exception_url / del_exception_url

=cut

=item add_weighted_phrase / del_weighted_phrase

=cut

=item add_grey_site / del_grey_site

=cut

=item add_grey_url / del_grey_url

=cut

=item add_content_regexp / del_content_regexp

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
