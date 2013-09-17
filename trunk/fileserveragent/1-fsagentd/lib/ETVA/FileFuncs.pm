#!/usr/bin/perl

package ETVA::FileFuncs;

use strict;

BEGIN {
    require Exporter;
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF $AUTOLOAD);
    $VERSION = '0.0.1';
    @ISA = qw( Exporter );
    @EXPORT = qw( read_file_lines flush_file_lines unflush_file_lines
                    open_tempfile close_tempfile read_env_file write_env_file open_readfile
                    is_under_directory resolve_links
                    read_conf_file read_conf_option
                    splice_file_lines push_file_lines
                    save_file_lines );
};

use ETVA::Utils;

my %FILE_CACHE = ();
my %FILE_CACHE_NOFLUSH = ();

# read_file_lines(file, [readonly])
# Returns a reference to an array containing the lines from some file. This
# array can be modified, and will be written out when flush_file_lines()
# is called.
sub read_file_lines {
    my ($file,$ro) = @_;
    if( !$file ){
        my ($package, $filename, $line) = caller;
        print STDERR "Missing file to read at ${package}::${filename} line $line\n";
    } 
    if( !$FILE_CACHE{$file} ){
        my @lines = ();
        open(READFILE, $file);
        while(<READFILE>){
            chomp;
            push(@lines, $_);
        }
        close(READFILE);
        $FILE_CACHE{$file} = \@lines;
        $FILE_CACHE_NOFLUSH{$file} = $ro;
    } else {
        # Make read-write if currently readonly
        if (!$ro) {
            $FILE_CACHE_NOFLUSH{$file} = 0;
        }
    }
    return $FILE_CACHE{$file};
}

# flush_file_lines([file], [eol])
# Write out to a file previously read by read_file_lines to disk (except
# for those marked readonly).
sub flush_file_lines {
    my ($file,$eol) = @_;
    my @files = ();
    if( $file ){
        if( !$FILE_CACHE{$file} ){
            plog("flush_file_lines called on non-loaded file $file");
        }
        push(@files, $file);
    } else {
        @files = ( keys %FILE_CACHE );
    }
    $eol ||= "\n";
    foreach my $f (@files) {
        if (!$FILE_CACHE_NOFLUSH{$f}) {
            open_tempfile(*FLUSHFILE, ">$f");
            foreach my $line (@{$FILE_CACHE{$f}}) {
                (print FLUSHFILE $line,$eol) ||
                    plog("Error file write $f: $!");
            }
            close_tempfile(*FLUSHFILE);
        }
        delete($FILE_CACHE{$f});
        delete($FILE_CACHE_NOFLUSH{$f});
    }
}

# unflush_file_lines(file)
# Clear the internal cache of some file
sub unflush_file_lines {
    my ($file) = @_;
    delete($FILE_CACHE{$file});
    delete($FILE_CACHE_NOFLUSH{$file});
}

=head2 open_tempfile([handle], file)

Opens a file handle for writing to a temporary file, which will only be
renamed over the real file when the handle is closed. This allows critical
files like /etc/shadow to be updated safely, even if writing fails part way
through due to lack of disk space. The parameters are :

=item handle - File handle to open, as you would use in Perl's open function.

=item file - Full path to the file to write, prefixed by > or >> to indicate over-writing or appending. In append mode, no temp file is used.

=cut

my %OPEN_TEMPFILES = ();
my @TEMPORARY_FILES = ();
my %OPEN_TEMPHANDLES = ();

sub open_tempfile {

    if( scalar(@_) == 1 ) {
        my ($file) = @_;
        # Just getting a temp file
        if( !defined($OPEN_TEMPFILES{$_[0]}) ){
            $file =~ /^(.*)\/(.*)$/ || return $file;
            my $dir = $1 || "/";
            my $tmp = "$dir/$2.tmp.$$";
            $OPEN_TEMPFILES{$file} = $tmp;
            push(@TEMPORARY_FILES, $tmp);
		}
        return $OPEN_TEMPFILES{$_[0]};
	} else {
        # Actually opening
        my ($fh, $file) = @_;
        my $tmp = "$file.tmp.$$";

        open($fh, $tmp);
        $file =~ s/^>//g;
        $tmp  =~ s/^>//g;
        $OPEN_TEMPFILES{$file} = $tmp;
        return $OPEN_TEMPHANDLES{$fh} = $file;
	}
}

=head2 close_tempfile(file|handle)

Copies a temp file to the actual file, assuming that all writes were
successful. The handle must have been one passed to open_tempfile.

=cut
sub close_tempfile {
    my ($f) = @_;

    if( defined(my $file = $OPEN_TEMPHANDLES{$f}) ){
        # Closing a handle
        close($f) || plog("Error close file", $file, $!);
        delete($OPEN_TEMPHANDLES{$f});
        return close_tempfile($file);
	} elsif( defined($OPEN_TEMPFILES{$f}) ){
        # Closing a file
        rename($OPEN_TEMPFILES{$f}, $f) || plog("Failed to replace $f with $OPEN_TEMPFILES{$f} : $!");
        delete($OPEN_TEMPFILES{$f});
        @TEMPORARY_FILES = grep { $_ ne $OPEN_TEMPFILES{$f} } @TEMPORARY_FILES;
        return 1;
	} else {
        # Must be closing a handle not associated with a file
        close($f);
        return 1;
	}
}

=head2 read_env_file(file, &hash)

Similar to Webmin's read_file function, but handles files containing shell
environment variables formatted like :

  export FOO=bar
  SMEG="spod"

The file parameter is the full path to the file to read, and hash a Perl hash
ref to read names and values into.

=cut
sub read_env_file {
    my $FILE;
    open($FILE,"<", $_[0]) || return 0;
    while(<$FILE>) {
        s/#.*$//g;
        if (/^\s*(export\s*)?([A-Za-z0-9_\.]+)\s*=\s*"(.*)"/i ||
                /^\s*(export\s*)?([A-Za-z0-9_\.]+)\s*=\s*'(.*)'/i ||
                /^\s*(export\s*)?([A-Za-z0-9_\.]+)\s*=\s*(.*)/i) {
            $_[1]->{$2} = $3;
        }
    }
    close($FILE);
    return 1;
}

=head2 write_env_file(file, &hash, [export])

Writes out a hash to a file in name='value' format, suitable for use in a shell
script. The parameters are :

=item file - Full path for a file to write to

=item hash - Hash reference of names and values to write.

=item export - If set to 1, preceed each variable setting with the word 'export'.

=cut
sub write_env_file {
    my ($file,$hash,$export) = @_;

    my $exp = $export ? "export " : "";
    open_tempfile(*FILE, ">$file");
    foreach my $k (keys %{$hash}) {
        my $v = $_[1]->{$k};
        if ($v =~ /^\S+$/) {
            print FILE "$exp$k=$v\n";
        } else {
            print FILE "$exp$k=\"$v\"\n";
        }
    }
    close_tempfile(*FILE);
}

=head2 open_readfile(handle, file)

Opens some file for reading. Returns 1 on success, 0 on failure. Pretty much
exactly the same as Perl's open function.

=cut
sub open_readfile {
    my ($fh, $file) = @_;
    return open($fh, "<".$file);
}

=head2 is_under_directory(directory, file)

Returns 1 if the given file is under the specified directory, 0 if not.
Symlinks are taken into account in the file to find it's 'real' location.

=cut
sub is_under_directory {
    my ($dir, $file) = @_;
    return 1 if ($dir eq "/");
    return 0 if ($file =~ /\.\./);
    my $ld = resolve_links($dir);
    if ($ld ne $dir) {
        return is_under_directory($ld, $file);
	}
    my $lp = resolve_links($file);
    if ($lp ne $file) {
        return is_under_directory($dir, $lp);
	}
    return 0 if (length($file) < length($dir));
    return 1 if ($dir eq $file);
    $dir =~ s/\/*$/\//;
    return substr($file, 0, length($dir)) eq $dir;
}

=head2 resolve_links(path)

Given a path that may contain symbolic links, returns the real path.

=cut

sub resolve_links {
    my ($path) = @_;
    $path =~ s/\/+/\//g;
    $path =~ s/\/$// if ($path ne "/");
    my @p = split(/\/+/, $path);
    shift(@p);
    for(my $i=0; $i<@p; $i++) {
        my $sofar = "/".join("/", @p[0..$i]);
        my $lnk = readlink($sofar);
        if ($lnk =~ /^\//) {
            # Link is absolute..
            return resolve_links($lnk."/".join("/", @p[$i+1 .. $#p]));
		} elsif ($lnk) {
            # Link is relative
            return resolve_links("/".join("/", @p[0..$i-1])."/".$lnk."/".join("/", @p[$i+1 .. $#p]));
		}
	}
    return $path;
}

sub read_conf_file {
    my ($file) = @_;

    my %conf = ();

    my $cfref = read_file_lines($file);
    for my $l (@$cfref){
        my $cline = $l;
        $cline =~ s/#.*//;

        if( $cline =~ /=/ ){
            my ($name,$value) = split(/=/,$cline);
            $name =~ s/ //g;
            $value =~ s/ //g;
            $value =~ s/^'//g;
            $value =~ s/'.*$//g;
            $conf{"$name"} = $value;
        }
    }

    return wantarray() ? %conf : \%conf;
}

sub read_conf_option {
    my ($file,$opt) = @_;

    my $cfref = read_file_lines($file);
    for my $l (@$cfref){
        my $cline = $l;
        $cline =~ s/#.*//;

        if( $cline =~ /=/ ){
            my ($name,$value) = split(/=/,$cline);
            if( $name eq $opt ){
                $name =~ s/ //g;
                $value =~ s/ //g;
                $value =~ s/^'//g;
                $value =~ s/'.*$//g;
                return $value;
            }
        }
    }
    return;
}

# splice_file_lines( file, i, e, [ @newlines ] )
#  splice file lines between i-line and e-line and
#  replace by newlines
#
sub splice_file_lines {
    my ($file,$i,$e,@newlines) = @_;

    my $cfref = read_file_lines($file);
    
    splice(@$cfref,$i,$e,@newlines);

    flush_file_lines($file);
}
# push_file_lines
#  add newlines at end of file
# 
sub push_file_lines {
    my ($file,@newlines) = @_;

    my $cfref = read_file_lines($file);
    
    push(@$cfref,@newlines);

    flush_file_lines($file);
}

# save_file_lines
#   replace or add at end of file
#   NOTE: this func not flush file, we need do this after
#
sub save_file_lines {
    my ($file,$i,$e,@nl) = @_;
    
    my $cfref = read_file_lines($file);

    if( !$i && !$e ){
        push(@$cfref,@nl);
    } else {
        splice(@$cfref,$i,$e,@nl);
    }
}

1;


