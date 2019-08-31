#!/bin/sh
pushd /share/Web/geo >/dev/null

php=`which php`
[ $? -ne 0 ] && echo 'PHP runtime not found, aborting' && exit $?

#locations='b bw nrw'
refresh_only=0
statistics=1
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
if [ $refresh_only -ne 0]; then
 statistics=0
fi
for location in $locations
do
 echo "processing \"$location\"..."

 # step0: set: db dir, db codepage, ...
 db_dir='/share/Coffee'
 db_codepage='ansi'
 weeks_filename='weeks.dbf'
 sites_filename='Sites.dbf'
 case "$location" in
 "b")
  db_dir='.'
  ;;
 "bw")
  db_dir="$db_dir/Coffee BW"
  weeks_filename='Weeks.dbf'
  ;;
 "d")
  db_dir="$db_dir/Coffee D"
  sites_filename='SITES.dbf'
  ;;
 "nrw")
  ;;
 "test")
  db_dir='./data/test'
  ;;
 *)
  echo 'invalid location (was: "' $location '"), aborting'
  exit 1
  ;;
 esac
 sites_file="$db_dir/$sites_filename"
 weeks_file="$db_dir/$weeks_filename"
 [ ! -r "$sites_file" ] && echo "invalid file (was: \"$sites_file\"), aborting" && exit 1
 [ ! -r "$weeks_file" ] && echo "invalid file (was: \"$weeks_file\"), aborting" && exit 1
 [ ! -d ./data/$location ] && echo "invalid directory (was: \"./data/$location\"), aborting" && exit 1
 [ ! -d ./data/$location/kml ] && echo "invalid directory (was: \"./data/$location/kml\"), aborting" && exit 1
 #echo "location \"$location\" --> database directory: " $db_dir
 #echo "location \"$location\" --> database codepage : " $db_codepage

 echo 'processing new sites (coordinates)...'
 # step1: addresses --> coordinates
 $php -f ./SID_2_LatLong.php "$sites_file" $db_codepage >./data/$location/output.txt 2>./data/$location/error.log
 [ $? -ne 0 ] && echo 'failed processing new sites (coordinates), aborting' && exit $?
 echo 'processing new sites (coordinates)...DONE'
 echo 'processing sites (JSON)...'
 # step2: sites --> JSON
 $php -f ./sites_2_json.php "$sites_file" "$weeks_file" $statistics >./data/$location/sites.json 2>>./data/$location/error.log
 [ $? -ne 0 ] && echo 'failed processing sites (JSON), aborting' && exit $?
 echo 'processing sites (JSON)...DONE'

 if [ $refresh_only -eq 0 ]; then
  echo 'processing sites (KML)...'
  # step3: sites --> KML
  $php -f ./SID_2_KML.php "$sites_file" $location ./data/style.kml >./data/$location/kml/sites.kml 2>>./data/$location/error.log
  [ $? -ne 0 ] && echo 'failed processing sites (KML), aborting' && exit $?
  echo 'processing sites (KML)...DONE'
 fi
 echo "processing \"$location\"...DONE"
done

popd >/dev/null
