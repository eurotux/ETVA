#!/bin/sh

. check_functions.sh

echo poweroff $@;

poweroffnode $@

exit $?;
