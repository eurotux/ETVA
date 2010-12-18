#!/usr/bin/perl

use strict;

sub main {
    use Data::Dumper;

    require ETFW::Qmail;

    my $La = ETFW::Qmail->list_alias();

    print STDERR Dumper($La),"\n";

    my $Lr = ETFW::Qmail->get_smtproutes();

    print STDERR Dumper($Lr),"\n";

    ETFW::Qmail->add_smtproutes( mail=>'zbr.pt', server=>'os.zbr.pt:25' );

    my $Lr = ETFW::Qmail->get_smtproutes();

    print STDERR Dumper($Lr),"\n";

    ETFW::Qmail->del_smtproutes( mail=>'zbr.pt', server=>'os.zbr.pt:25' );

    my $Lr = ETFW::Qmail->get_smtproutes();

    print STDERR Dumper($Lr),"\n";

    my $Lq = ETFW::Qmail->list_queue();

    print STDERR Dumper($Lq),"\n";

    my $M = ETFW::Qmail->view_message( id=>1406 );

    print STDERR Dumper($M),"\n";

    my $M = ETFW::Qmail->del_message( id=>1406 );

    my $Lq = ETFW::Qmail->list_queue();

    print STDERR Dumper($Lq),"\n";

}

main();
1;
