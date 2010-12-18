#!/usr/bin/perl

sub main {
    use Data::Dumper;

    require ETFW;

    my $M = ETFW->get_activemodules();

    print Dumper($M),"\n";
}
main();
1;
