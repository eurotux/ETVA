#!/bin/bash

SYMFONY=`dirname $0`"/../../../symfony"
#SYMFONY=`dirname $0`"/../../../symfony -t"

SEP="==========================================================================="
echo "Rebuilding full database from DB4 schema and fixtures"

echo $SEP
echo "Converting DB4 schema.xml to symfony propel schema"
$SYMFONY propel:db4-to-propel frontend --env=cli --debug=1 --file_dir=/doc/database --file=db4.xml --output_dir=/config --package=lib.model --external_tables=sf_guard_user

echo $SEP
echo "Rebuilding db, all tables & ORM classes"
$SYMFONY propel:build-all-load --no-confirmation

echo $SEP
echo "Clearing the cache..."
$SYMFONY cc

echo $SEP
cd ..
echo "JOB's DONE ! ;) "