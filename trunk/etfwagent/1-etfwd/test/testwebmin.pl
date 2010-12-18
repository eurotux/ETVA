#!/usr/bin/perl

sub main {

    use Data::Dumper;

    require ETFW::Webmin;

    my $C = ETFW::Webmin->load_config();

    print Dumper($C),"\n";
}
main();

1;
