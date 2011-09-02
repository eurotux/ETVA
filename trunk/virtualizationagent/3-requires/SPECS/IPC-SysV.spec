#
#   - IPC::SysV -
#   This spec file was automatically generated by cpan2rpm [ver: 2.027]
#   The following arguments were used:
#       IPC::SysV
#   For more information on cpan2rpm please visit: http://perl.arix.com/
#

%define pkgname IPC-SysV
%define filelist %{pkgname}-%{version}-filelist
%define NVR %{pkgname}-%{version}-%{release}
%define maketest 1

name:      perl-IPC-SysV
summary:   IPC-SysV - System V IPC constants and system calls
version:   2.03
release:   1
vendor:    Graham Barr <gbarr@pobox.com,>
packager:  Arix International <cpan2rpm@arix.com>
license:   Artistic
group:     Applications/CPAN
url:       http://www.cpan.org
buildroot: %{_tmppath}/%{name}-%{version}-%(id -u -n)
prefix:    %(echo %{_prefix})
source:    http://search.cpan.org//CPAN/authors/id/M/MH/MHX/IPC-SysV-2.03.tar.gz

%description
"IPC::SysV" defines and conditionally exports all the constants
defined in your system include files which are needed by the SysV
IPC calls.  Common ones include

  IPC_CREATE IPC_EXCL IPC_NOWAIT IPC_PRIVATE IPC_RMID IPC_SET IPC_STAT
  GETVAL SETVAL GETPID GETNCNT GETZCNT GETALL SETALL
  SEM_A SEM_R SEM_UNDO
  SHM_RDONLY SHM_RND SHMLBA

and auxiliary ones

  S_IRUSR S_IWUSR S_IRWXU
  S_IRGRP S_IWGRP S_IRWXG
  S_IROTH S_IWOTH S_IRWXO

but your system might have more.

=over 4

=item ftok( PATH )

=item ftok( PATH, ID )

Return a key based on PATH and ID, which can be used as a key for
"msgget", "semget" and "shmget". See ftok.

If ID is omitted, it defaults to 1. If a single character is
given for ID, the numeric value of that character is used.

=item shmat( ID, ADDR, FLAG )

Attach the shared memory segment identified by ID to the address
space of the calling process. See shmat.

ADDR should be "undef" unless you really know what you're doing.

=item shmdt( ADDR )

Detach the shared memory segment located at the address specified
by ADDR from the address space of the calling process. See shmdt.

=item memread( ADDR, VAR, POS, SIZE )

Reads SIZE bytes from a memory segment at ADDR starting at position POS.
VAR must be a variable that will hold the data read. Returns true if
successful, or false if there is an error. memread() taints the variable.

=item memwrite( ADDR, STRING, POS, SIZE )

Writes SIZE bytes from STRING to a memory segment at ADDR starting at
position POS. If STRING is too long, only SIZE bytes are used; if STRING
is too short, nulls are written to fill out SIZE bytes. Returns true if
successful, or false if there is an error.

=back

#
# This package was generated automatically with the cpan2rpm
# utility.  To get this software or for more information
# please visit: http://perl.arix.com/
#

%prep
%setup -q -n %{pkgname}-%{version} 
chmod -R u+w %{_builddir}/%{pkgname}-%{version}

%build
grep -rsl '^#!.*perl' . |
grep -v '.bak$' |xargs --no-run-if-empty \
%__perl -MExtUtils::MakeMaker -e 'MY->fixin(@ARGV)'
CFLAGS="$RPM_OPT_FLAGS"
%{__perl} Makefile.PL INSTALLDIRS="vendor" `%{__perl} -MExtUtils::MakeMaker -e ' print qq|PREFIX=%{buildroot}%{_prefix}| if \$ExtUtils::MakeMaker::VERSION =~ /5\.9[1-6]|6\.0[0-5]/ '`
%{__make} 
%if %maketest
%{__make} test
%endif

%install
[ "%{buildroot}" != "/" ] && rm -rf %{buildroot}

%{makeinstall} `%{__perl} -MExtUtils::MakeMaker -e ' print \$ExtUtils::MakeMaker::VERSION <= 6.05 ? qq|PREFIX=%{buildroot}%{_prefix}| : qq|DESTDIR=%{buildroot}| '`

cmd=/usr/share/spec-helper/compress_files
[ -x $cmd ] || cmd=/usr/lib/rpm/brp-compress
[ -x $cmd ] && $cmd

# SuSE Linux
if [ -e /etc/SuSE-release -o -e /etc/UnitedLinux-release ]
then
    %{__mkdir_p} %{buildroot}/var/adm/perl-modules
    %{__cat} `find %{buildroot} -name "perllocal.pod"`  \
        | %{__sed} -e s+%{buildroot}++g                 \
        > %{buildroot}/var/adm/perl-modules/%{name}
fi

# remove special files
find %{buildroot} -name "perllocal.pod" \
    -o -name ".packlist"                \
    -o -name "*.bs"                     \
    -o -name "*.3pm.gz"                 \
    |xargs -i rm -f {}

# no empty directories
find %{buildroot}%{_prefix}             \
    -type d -depth                      \
    -exec rmdir {} \; 2>/dev/null

%{__perl} -MFile::Find -le '
    find({ wanted => \&wanted, no_chdir => 1}, "%{buildroot}");
    print "%doc  TODO hints Changes README";
    for my $x (sort @dirs, @files) {
        push @ret, $x unless indirs($x);
        }
    print join "\n", sort @ret;

    sub wanted {
        return if /auto$/;

        local $_ = $File::Find::name;
        my $f = $_; s|^\Q%{buildroot}\E||;
        return unless length;
        return $files[@files] = $_ if -f $f;

        $d = $_;
        /\Q$d\E/ && return for reverse sort @INC;
        $d =~ /\Q$_\E/ && return
            for qw|/etc %_prefix/man %_prefix/bin %_prefix/share|;

        $dirs[@dirs] = $_;
        }

    sub indirs {
        my $x = shift;
        $x =~ /^\Q$_\E\// && $x ne $_ && return 1 for @dirs;
        }
    ' > %filelist

[ -z %filelist ] && {
    echo "ERROR: empty %files listing"
    exit -1
    }

%clean
[ "%{buildroot}" != "/" ] && rm -rf %{buildroot}

%files -f %filelist
%defattr(-,root,root)

%changelog
* Thu Nov 25 2010 root@cmar
- Initial build.
