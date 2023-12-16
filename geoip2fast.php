#!/usr/bin/php
<?php
/*
Examples:

- The default file to load is geoip2fastphp.dat.gz with only country names support.

- Lookup country names
./geoip2fast.php 9.9.9.9 1.1.1.1 

- Lookup country names with IPv6 support
./geoip2fast.php 9.9.9.9 1.1.1.1 2a10:8b40:: geoip2fastphp-ipv6.dat.gz 

- Lookup country names and asn names with IPv6 support
./geoip2fast.php 9.9.9.9 1.1.1.1 2a10:8b40:: geoip2fastphp-asn-ipv6.dat.gz 

- Lookup country names and resolve the hostnames WITHOUT IPv6 SUPPORT BECAUSE THE FILE WITH IPV6 DATA WAS NOT SUPPLIED
./geoip2fast.php 9.9.9.9,1.1.1.1,2a10:8b40::,8.8.8.8 -d 

- Lookup country names with IPv6 support and used with jq application
./geoip2fast.php geoip2fastphp-ipv6.dat.gz 9.9.9.9,1.1.1.1,2a10:8b40::,8.8.8.8 | jq -r '.country_code'

*/

require_once("GeoIP2Fast.class.php");

$APPNAME = "GeoIP2FastPHP Lookup CLI";
$verbose = false;
$resolve_hostname = false;
$geoip2fast_data_file = null;

function print_menu() {
   global $APPNAME, $argv;
   print($APPNAME." Usage: ".$argv[0]." [-h] [-v] [-d] [data_file_name] <ip_address_1> <ip_address_2> <ip_address_N> ...\n");
}   

if (in_array("-v",$argv)) {
   $verbose = true;
   $index = array_search("-v",$argv);
   array_splice($argv,$index,1);
}

if (in_array("-d",$argv)) {
   $resolve_hostname = true;
   $index = array_search("-d",$argv);
   array_splice($argv,$index,1);
}
 
if ((count($argv) > 1) and ($argv[1] != "")) {
   $argfilename = preg_grep('/geoip2fast.*.dat.gz/i', $argv);
   if ($argfilename != null) {
      list($geoip2fast_array_key) = array_keys($argfilename);
      if ($geoip2fast_array_key != null) {
         $geoip2fast_data_file = $argfilename[$geoip2fast_array_key];
         array_splice($argv,$geoip2fast_array_key,1);
      }
   }

   $args = preg_grep ('/(\d+).(\d+).(\d+).(\d+)/i', $argv);
   $matches = [];
   foreach ($args as $arg) {
      $matches = array_merge($matches,explode(",",$arg));
   }

   if (count($matches) > 0) {
      $G = new GeoIP2Fast($geoip2fast_data_file,$verbose);
      for ($I = 0; $I < count($matches); $I++) {
         $ip = array_values($matches)[$I];
         print($G->lookup_pp($ip,$resolve_hostname));
      }

   } else {
      print_menu();
   }

} else {
   print_menu();   
}

?>
