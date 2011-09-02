#!/usr/bin/perl

package ETVA::ArchiveTar;

use strict;

require Exporter;

use Package::Constants;
use MIME::Base64 qw(encode_base64);

use vars qw[$DEBUG $error $VERSION $WARN $FOLLOW_SYMLINK $CHOWN $CHMOD
            $DO_NOT_USE_PREFIX $HAS_PERLIO $HAS_IO_STRING $SAME_PERMISSIONS
            $INSECURE_EXTRACT_MODE $ZERO_PAD_NUMBERS @ISA @EXPORT
         ];

require Archive::Tar;
@ISA = qw[Archive::Tar];

$ZERO_PAD_NUMBERS       = 0;

use constant FILE           => 0;
use constant HARDLINK       => 1;
use constant SYMLINK        => 2;
use constant CHARDEV        => 3;
use constant BLOCKDEV       => 4;
use constant DIR            => 5;
use constant FIFO           => 6;
use constant SOCKET         => 8;
use constant UNKNOWN        => 9;
use constant LONGLINK       => 'L';
use constant LABEL          => 'V';

use constant BUFFER         => 4096;
use constant HEAD           => 512;
use constant BLOCK          => 512;

use constant COMPRESS_GZIP  => 9;
use constant COMPRESS_BZIP  => 'bzip2';

use constant BLOCK_SIZE     => sub { my $n = int($_[0]/BLOCK); $n++ if $_[0] % BLOCK; $n * BLOCK };
use constant TAR_PAD        => sub { my $x = shift || return; return "\0" x (BLOCK - ($x % BLOCK) ) };
use constant TAR_END        => "\0" x BLOCK;

use constant READ_ONLY      => sub { shift() ? 'rb' : 'r' };
use constant WRITE_ONLY     => sub { $_[0] ? 'wb' . shift : 'w' };
use constant MODE_READ      => sub { $_[0] =~ /^r/ ? 1 : 0 };

# Pointless assignment to make -w shut up
my $getpwuid; $getpwuid = 'unknown' unless eval { my $f = getpwuid (0); };
my $getgrgid; $getgrgid = 'unknown' unless eval { my $f = getgrgid (0); };
use constant UNAME          => sub { $getpwuid || scalar getpwuid( shift() ) || '' };
use constant GNAME          => sub { $getgrgid || scalar getgrgid( shift() ) || '' };
use constant UID            => $>;
use constant GID            => (split ' ', $) )[0];

use constant MODE           => do { 0666 & (0777 & ~umask) };
use constant STRIP_MODE     => sub { shift() & 0777 };
use constant CHECK_SUM      => "      ";

use constant UNPACK         => 'A100 A8 A8 A8 A12 A12 A8 A1 A100 A6 A2 A32 A32 A8 A8 A155 x12';
use constant PACK           => 'a100 a8 a8 a8 a12 a12 A8 a1 a100 a6 a2 a32 a32 a8 a8 a155 x12';
use constant NAME_LENGTH    => 100;
use constant PREFIX_LENGTH  => 155;

use constant TIME_OFFSET    => ($^O eq "MacOS") ? Time::Local::timelocal(0,0,0,1,0,70) : 0;    
use constant MAGIC          => "ustar";
use constant TAR_VERSION    => "00";
use constant LONGLINK_NAME  => '././@LongLink';
use constant PAX_HEADER     => 'pax_global_header';

                            ### allow ZLIB to be turned off using ENV: DEBUG only
use constant ZLIB           => do { !$ENV{'PERL5_AT_NO_ZLIB'} and
                                        eval { require IO::Zlib };
                                    $ENV{'PERL5_AT_NO_ZLIB'} || $@ ? 0 : 1 
                                };

                            ### allow BZIP to be turned off using ENV: DEBUG only                                
use constant BZIP           => do { !$ENV{'PERL5_AT_NO_BZIP'} and
                                        eval { require IO::Uncompress::Bunzip2;
                                               require IO::Compress::Bzip2; };
                                    $ENV{'PERL5_AT_NO_BZIP'} || $@ ? 0 : 1 
                                };

use constant GZIP_MAGIC_NUM => qr/^(?:\037\213|\037\235)/;
use constant BZIP_MAGIC_NUM => qr/^BZh\d/;

use constant CAN_CHOWN      => sub { ($> == 0 and $^O ne "MacOS" and $^O ne "MSWin32") };
use constant CAN_READLINK   => ($^O ne 'MSWin32' and $^O !~ /RISC(?:[ _])?OS/i and $^O ne 'VMS');
use constant ON_UNIX        => ($^O ne 'MSWin32' and $^O ne 'MacOS' and $^O ne 'VMS');
use constant ON_VMS         => $^O eq 'VMS'; 

use constant MINOR_MASK     => 037774000377;
use constant MINOR_SHIFT    => 0000000;
use constant MAJOR_MASK     => 03777400;
use constant MAJOR_SHIFT    => 0000010;

sub new {
    my $self = shift;
    my %arg = @_;

    unless( ref($self) ){
        $self = bless { %arg }, $self;
    }
    return $self;
}

sub header {
    my $entry       = shift or return;
    my $no_prefix   = shift || 0;

    my $file    = $entry->{'name'};
    my $prefix  = $entry->{'prefix'}; $prefix = '' unless defined $prefix;

    ### remove the prefix from the file name
    ### not sure if this is still neeeded --kane
    ### no it's not -- Archive::Tar::File->_new_from_file will take care of
    ### this for us. Even worse, this would break if we tried to add a file
    ### like x/x.
    #if( length $prefix ) {
    #    $file =~ s/^$match//;
    #}

    ### not sure why this is... ###
    my $l = PREFIX_LENGTH; # is ambiguous otherwise...
    substr ($prefix, 0, -$l) = "" if length $prefix >= PREFIX_LENGTH;

    my $f1 = "%06o"; my $f2  = $ZERO_PAD_NUMBERS ? "%011o" : "%11o";

    ### this might be optimizable with a 'changed' flag in the file objects ###
    my $tar = pack (
                PACK,
                $file,

                (map { sprintf( $f1, $entry->{"$_"} ) } qw[mode uid gid]),
                (( $entry->{'size'} <  ( 4 * 1024 * 1024 * 1024 ) ) ? sprintf( $f2, $entry->{'size'} ) : "\200\0\0\0\0\0\0\002\200\0\0\0"),
                (map { sprintf( $f2, $entry->{"$_"} ) } qw[mtime]),

                "",  # checksum field - space padded a bit down

                (map { $entry->{"$_"} }                 qw[type linkname magic]),

                $entry->{"version"} || TAR_VERSION,

                (map { $entry->{"$_"} }                 qw[uname gname]),
                (map { sprintf( $f1, $entry->{"$_"} ) } qw[devmajor devminor]),

                ($no_prefix ? '' : $prefix)
    );

    ### add the checksum ###
    my $checksum_fmt = $ZERO_PAD_NUMBERS ? "%06o\0" : "%06o\0";
    substr($tar,148,7) = sprintf("%6o\0", unpack("%16C*",$tar));

    return $tar;
}

# calc package size at priory
# Note: only works with no-compressed files
sub package_size {
    my $self = shift;
    my $lfiles = $self->{'_files'};

    die "no files to add to tar!" if( !$lfiles );

    my $pkgsize = 0;
    for my $F (@$lfiles){
        $pkgsize += length( &header( $F ) );
        $pkgsize += $F->{'size'};
        $pkgsize += length( TAR_PAD->( $F->{'size'} ) ) if( $F->{'size'} % BLOCK );
    }
    $pkgsize += length( TAR_END x 2 );
    return $pkgsize;
}

sub write {
    my $self = shift;
    my $file = shift;

    $file ||= $self->{'file'};

    my $lfiles = $self->{'_files'};
    die "no files to add to tar!" if( !$lfiles );

    my $handle;
    $handle = $self->{'handle'} if( $self->{'handle'} );
    if( !$handle && $file ){
        open($handle,">$file");
    } elsif( !$handle && !$file ){
        die "cant write file!";
    }

    for my $F (@$lfiles){
        my $fpath = $F->{'path'};
        my $size = 0;
        my $toklen = 512;
        my $fh;
        if( -b "$fpath" ){
            open($fh,"/bin/dd if=$fpath 2>/dev/null|");
        } elsif( -e "$fpath" ){
            open($fh,"$fpath");
            binmode($fh);
        }

        print $handle &header( $F );

        if( -e "$fpath" ){
            my $buf;
            while (read($fh, $buf, 60*57)) {
                $size += length($buf);
                print $handle $buf;
            }
        } elsif( $F->{'data'} ){
            $size += length($F->{'data'});
            print $handle $F->{'data'};
        }
        close($fh) if( -e "$fpath" );

        ### pad the end of the clone if required ###
        print $handle TAR_PAD->( $size ) if $size % BLOCK;
    }

    print $handle TAR_END x 2;

    close($handle) if( $file );
}

sub _filetype {
    my $file = shift;
    
    return unless defined $file;

    return SYMLINK  if (-l $file);	# Symlink

    return FILE     if (-f _);		# Plain file

    return DIR      if (-d _);		# Directory

    return FIFO     if (-p _);		# Named pipe

    return SOCKET   if (-S _);		# Socket

    return BLOCKDEV if (-b _);		# Block special

    return CHARDEV  if (-c _);		# Character special

    ### shouldn't happen, this is when making archives, not reading ###
    return LONGLINK if ( $file eq LONGLINK_NAME );

    return UNKNOWN;		            # Something else (like what?)

}

sub size_blockdev {
    my ($dev) = @_;
    if( -l "$dev" ){
        $dev = readlink $dev;
    }
    if( -b "$dev" ){
        my ($rdev) = (lstat $dev)[6];
        my $major = ( $rdev & MAJOR_MASK ) >> MAJOR_SHIFT;
        my $minor = ( $rdev & MINOR_MASK ) >> MINOR_SHIFT;
        if( -r "/proc/partitions" ){
            open(F,"/proc/partitions");
            while(<F>){
                if( /^\s+$major\s+$minor\s+(\d+)\s+.+$/ ){
                    my $blockn = $1;
                    return ($blockn * 1024); # in bytes
                }
            }
            close(F);
        }
    }
    return 0;
}

sub add_file {
    my $self = shift;
    my (%a) = @_;

    $self->{'_files'} = [] if( !$self->{'_files'} );

    my $path = $a{'path'};

    my $type        = _filetype($path);
    my $data        = $a{'data'} || '';

    my @items       = qw[mode uid gid size mtime];
    my %hash        = map { shift(@items), $_ } (lstat $path)[2,4,5,7,9];

    if (ON_VMS) {
        ### VMS has two UID modes, traditional and POSIX.  Normally POSIX is
        ### not used.  We currently do not have an easy way to see if we are in
        ### POSIX mode.  In traditional mode, the UID is actually the VMS UIC.
        ### The VMS UIC has the upper 16 bits is the GID, which in many cases
        ### the VMS UIC will be larger than 209715, the largest that TAR can
        ### handle.  So for now, assume it is traditional if the UID is larger
        ### than 0x10000.

        if ($hash{uid} > 0x10000) {
            $hash{uid} = $hash{uid} & 0xFFFF;
        }

        ### The file length from stat() is the physical length of the file
        ### However the amount of data read in may be more for some file types.
        ### Fixed length files are read past the logical EOF to end of the block
        ### containing.  Other file types get expanded on read because record
        ### delimiters are added.

    }
    my $data_len = length $data;
    $hash{size} = $data_len if $hash{size} < $data_len;

    ### you *must* set size == 0 on symlinks, or the next entry will be
    ### though of as the contents of the symlink, which is wrong.
    ### this fixes bug #7937
    $hash{size}     = 0 if ($type == DIR or $type == SYMLINK);
    $hash{mtime}    -= TIME_OFFSET;

    if( $type == BLOCKDEV ){
        $hash{'size'}   = &size_blockdev($path);
    }

    ### strip the high bits off the mode, which we don't need to store
    $hash{mode}     = STRIP_MODE->( $hash{mode} );


    ### probably requires some file path munging here ... ###
    ### name and prefix are set later
    my $obj = {
        %hash,
        name        => '',
        chksum      => CHECK_SUM,
        type        => $type,
        linkname    => ($type == SYMLINK and CAN_READLINK)
                            ? readlink $path
                            : '',
        magic       => MAGIC,
        version     => TAR_VERSION,
        uname       => UNAME->( $hash{uid} ),
        gname       => GNAME->( $hash{gid} ),
        devmajor    => 0,   # not handled
        devminor    => 0,   # not handled
        prefix      => '',
        data        => $data,
        %a,
    };
    push(@{$self->{'_files'}}, $obj);
}

1;

