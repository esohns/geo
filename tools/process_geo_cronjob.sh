#!/bin/sh
pushd . >/dev/null
cd /share/Web/geo

sites_file='./process_sites.sh'
toursets_file='./process_toursets.sh'
[ ! -x $sites_file ] && echo "invalid file (was: \"$sites_file\"), aborting" && exit 1
[ ! -x $toursets_file ] && echo "invalid file (was: \"$toursets_file\"), aborting" && exit 1

logger "started processing Humana Geo database..."
$($sites_file)
[ $? -ne 0 ] && echo "failed to process sites, aborting" && exit $?
$($toursets_file)
[ $? -ne 0 ] && echo "failed to process toursets, aborting" && exit $?
logger "started processing Humana Geo database...DONE"

popd >/dev/null
