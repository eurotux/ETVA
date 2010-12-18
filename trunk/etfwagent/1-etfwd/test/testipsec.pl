#!/usr/bin/perl

sub main {
    use Data::Dumper;

    require ETFW::IPSec;

    my $C = ETFW::IPSec->get_config();

    print STDERR Dumper($C),"\n";

#    ETFW::IPSec->set_config( forwardcontrol=>"yes" );

    my $K = ETFW::IPSec->get_public_key();

    print STDERR Dumper($K),"\n";

    my @Ls = ETFW::IPSec->list_secrets();

    print STDERR Dumper(\@Ls),"\n";
}

main();

1;
