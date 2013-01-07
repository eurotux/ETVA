#!/bin/sh

. check_functions.sh

echo restart $@;

restartnode $@

exit $?;

