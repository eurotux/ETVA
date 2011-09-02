use 5.006;
use strict;

use ExtUtils::MakeMaker;

ExtUtils::MakeMaker::WriteMakefile(
    'NAME'         => 'ETVA',
    'VERSION_FROM' => 'lib/ETVA/Agent.pm',
    'PMLIBDIRS'    => [ 'lib/ETVA' ]
);

1;
