#!/bin/sh
pushd . >/dev/null
#cd /share/Web/geo

php=`which php`
[ $? -ne 0 ] && echo 'PHP runtime not found, aborting' && exit $?

#locations='b bw nrw'
refresh_only=0
locations='bw nrw'
if [ $# -ne 0 ]; then
 locations=''
 for location in "$@"
 do
  if [ "$location" -eq "$location" ] 2>/dev/null; then
   refresh_only=$location
   continue
  fi
  locations=$locations" $location"
 done
fi

location_file='data/warehouse.json'
sites_filename='Sites.dbf'
working_days=6
# step0: warehouse locations --> JSON
echo 'processing warehouse locations (JSON)...'
$php -f ./warehouse_2_json.php ./data/warehouse.csv >$location_file 2>./data/error.log
[ $? -ne 0 ] && echo 'failed to warehouse locations (JSON), aborting' && exit $?
echo 'processing warehouse locations (JSON)...DONE'

for location in $locations
do
 echo "processing \"$location\"..."

 # pre-step0: set: db dir/file, (default) tourset(s), working days/week, ...
 db_dir='/share/Coffee'
 toursets_file=$db_dir'/toursets.dbf'
 toursets='New Standard'
 case "$location" in
 "b")
  db_dir='.'
  toursets_file=$db_dir'/toursets.dbf'
  toursets=('2010' '2011' '063112' '2008 Jan' 'JAN 05' 'Kombi' 'Import' 'Okt07' 'SEP07' 'Standard' 'TourAlt')
  ;;
 "bw")
  db_dir='/share/Coffee/Coffee BW'
  toursets_file=$db_dir'/Toursets.dbf'
  toursets='2006Q2 NEW'
  ;;
 "d")
  db_dir='/share/Coffee/Coffee D'
  toursets_file=$db_dir'/toursets.dbf'
  sites_filename='SITES.dbf'
  ;;
 "nrw")
  ;;
 "test")
  db_dir='data/'$location
  toursets_file=$db_dir'/toursets.dbf'
  ;;
 *)
  echo "invalid location (was: \"$location\"), aborting"
  exit 1
  ;;
 esac
 sites_file=$db_dir"/$sites_filename"
 tourlist_file='data/'$location'/tourlist.csv'
 [ ! -r "$toursets_file" ] && echo "invalid file (was: \"$toursets_file\"), aborting" && exit 1
 [ ! -r "$sites_file" ] && echo "invalid file (was: \"$sites_file\"), aborting" && exit 1
 [ ! -r "$tourlist_file" ] && echo "invalid file (was: \"$tourlist_file\"), aborting" && exit 1
 [ ! -r "$location_file" ] && echo "invalid file (was: \"$location_file\"), aborting" && exit 1
 [ ! -d "./data/$location" ] && echo "invalid directory (was: \"./data/$location\"), aborting" && exit 1
 [ ! -d "./data/$location/kml" ] && echo "invalid directory (was: \"./data/$location/kml\"), aborting" && exit 1
#echo "location \"$location\" --> database directory: " $db_dir
#echo "location \"$location\" --> tourset(s)        : " $toursets
#echo "location \"$location\" --> #workingdays/week : " $working_days

 # step0: tourset IDs --> JSON
 echo 'processing tourset IDs (JSON)...'
 $php -f ./tourlist_ids_2_json.php "$tourlist_file" >./data/$location/tourset_ids.json 2>./data/$location/error.log
 [ $? -ne 0 ] && echo 'failed to process tourset IDs (JSON), aborting' && exit $?
 echo 'processing tourset IDs (JSON)...DONE'
 # step1: ALL toursets --> JSON
 echo 'processing toursets (JSON)...'
 $php -f ./tours_2_json.php "$toursets_file" ./data/$location/tourset_ids.json "" $working_days >./data/$location/toursets.json 2>./data/$location/error.log
 [ $? -ne 0 ] && echo 'failed to process toursets (JSON), aborting' && exit $?
 echo 'processing toursets (JSON)...DONE'
 if [ $refresh_only -eq 0 ]; then
  # step2: toursets --> TXT
  echo 'processing toursets (TXT)...'
  $php -f ./tours_2_lists.php "$sites_file" ./data/$location/toursets.json "" 0 >./data/$location/toursets.txt 2>>./data/$location/error.log
  [ $? -ne 0 ] && echo 'failed to process toursets (TXT), aborting' && exit $?
  echo 'processing toursets (TXT)...DONE'
  # step3: toursets --> KML
  echo 'processing toursets (KML)...'
  $php -f ./tours_2_kml.php "$sites_file" $location_file $location ./data/$location/toursets.json "" ./data/style.kml >./data/$location/kml/toursets.kml 2>>./data/$location/error.log
  [ $? -ne 0 ] && echo 'failed to process toursets (KML), aborting' && exit $?
  echo 'processing toursets (KML)...DONE'
  for tourset in ${toursets}
  do
   # step3a: each tourset --> JSON
   echo "processing tourset \"$tourset\" (JSON)..."
   $php -f ./tours_2_json.php "$toursets_file" ./data/$location/tourset_ids.json "$tourset" $working_days >./data/$location/tourset_$tourset.json 2>>./data/$location/error.log
   [ $? -ne 0 ] && echo "failed to process tourset \"$tourset\" (JSON), aborting" && exit $?
   echo "processing tourset \"$tourset\" (JSON)...DONE"
   # step3b: each tourset --> TXT
   echo "processing tourset \"$tourset\" (TXT)..."
   $php -f ./tours_2_lists.php "$sites_file" "./data/$location/tourset_$tourset.json" "$tourset" 1 >./data/$location/tourset_$tourset.txt 2>>./data/$location/error.log
   [ $? -ne 0 ] && echo "failed to process tourset \"$tourset\" (TXT), aborting" && exit $?
   echo "processing tourset \"$tourset\" (TXT)...DONE"
   # step3c: each tourset --> KML
   echo "processing tourset \"$tourset\" (KML)..."
   $php -f ./tours_2_kml.php  "$sites_file" $location_file $location ./data/$location/tourset_$tourset.json "$tourset" ./data/style.kml >./data/$location/kml/tourset_$tourset.kml 2>>./data/$location/error.log
   [ $? -ne 0 ] && echo "failed to process tourset \"$tourset\" (KML), aborting" && exit $?
   echo "processing tourset \"$tourset\" (KML)...DONE"
  done
 fi
 echo "processing \"$location\"...DONE"
done
#echo "performing maintenance..."
#rm -f $temp_file 2>>./data/$location/error.log
#[ $? -ne 0 ] && echo "failed to perform maintenance, aborting" && exit $?
#echo "performing maintenance...DONE"

popd >/dev/null
