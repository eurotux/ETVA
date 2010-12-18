#!/usr/bin/perl

sub main {
    use Data::Dumper;

    require ETFW::NRPE;

    my $C = ETFW::NRPE->get_config();

    print STDERR Dumper($C),"\n";

    my $bkp_port = $C->{"server_port"};

    ETFW::NRPE->set_config( "server_port"=>5667 );

    ETFW::NRPE->add_command( command=>"teste", line=>'echo "teste"' );

    my $C = ETFW::NRPE->get_config();

    print STDERR Dumper($C),"\n";

    ETFW::NRPE->del_command( command=>"teste" );

    ETFW::NRPE->set_config( "server_port"=>$bkp_port );

    my $C = ETFW::NRPE->get_config();

    print STDERR Dumper($C),"\n";

}
main();

1;
