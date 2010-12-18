#!/usr/bin/perl

sub main {
    use Data::Dumper;

    require ETFW::Samba;

    my $C = ETFW::Samba->get_dom_conf();

    print STDERR Dumper($C),"\n";

    ETFW::Samba->set_dom_conf( 'DOMINIO'=>{ 'dcipaddr'=>'10.10.10.101' } );
}
main();

1;
