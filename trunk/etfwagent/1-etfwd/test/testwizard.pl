#!/usr/bin/perl

use strict;

use ETFWWizard;

sub main {
    ETFWWizard->submit(
                        'interfaces'=>[
                                        { 'type'=>'wan', 'name'=>'eth0', 'dhcp'=>1 },
                                        { 'type'=>'lan', 'name'=>'eth1', 'address'=>'1.1.1.254', 'netmask'=>'255.255.255.0', 'broadcast'=>'1.1.1.255' },
                                        ],
                        'dhcp'=>[
                                    { 'if'=>'eth1' },
                                ],
                        'squid'=>{
                                    'if'=>'eth1',
                                    'ini_template'=>1,
                                    'template'=>'transparent',
                                },
                        );
}

main();

1;
