<?php
/*

.oPYo.               o  .oPYo. .oPYo.  ooooo                    o  
8    8               8  8    8     `8  8                     8  
8      .oPYo. .oPYo. 8 o8YooP'    oP' o8oo   .oPYo. .oPYo.  o8P 
8   oo 8oooo8 8    8 8  8      .oP'    8     .oooo8 Yb..     8  
8    8 8.     8    8 8  8      8'      8     8    8   'Yb.   8  
`YooP8 `Yooo' `YooP' 8  8      8ooooo  8     `YooP8 `YooP'   8  
:....8 :.....::.....:..:..:::::.......:..:::::.....::.....:::..:
:::::8 :::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:::::..:::::::::::::::::::::::::::::::::::::::::::::::::::::::::

Author: Ricardo Abuchaim - ricardoabuchaim@gmail.com
        https://github.com/rabuchaim/geoip2fastphp/
        
License: MIT

Based on GeoIP2Fast library for Python - https://pypi.org/project/geoip2fast/

#########################################################################################

What's new in v0.0.2 - 15/Dec/2023
- Tested GeoIP2Fast.class.php with PHP 5.6, 7.4, 8.1 and 8.3
- Removed dependency on mb_string functions
- Fix gethostbyaddr_timeout function that sometimes throws an error
- Fix in percentage_v6 of function get_coverage
- Put some flowers

What's new in v0.0.1 - 14/Dec/2023
- First Public Release
- The dat file size and load time will be improved, this is just a test
- The file used to create the data files will be release soon (geoip2dat.php)
- All geoip information was imported using Maxmind Geolite2 CSV files from 12/Dec/2023

Quick Usage:

$G = new GeoIP2Fast();

$result = $G->lookup("9.9.9.9");
print($result['country_code']);

$result = $G->lookup_raw("9.9.9.9");
print($result[1]);

// Pretty print the result as json
print($G->lookup_pp("9.9.9.9"));

// Pretty print the result as json and resolve the IP hostname
print($G->lookup_pp("9.9.9.9",$resolve_hostname=true));

*/

class GeoIP2Fast {
   private $CLASS_NAME    = "GeoIP2Fast";
   private $CLASS_VERSION = "0.0.2";

   private $dat_version = null;
   private $load_database_text;
   private $total_networks;

   public $has_asn_data = false;
   public $has_city_data = false;
   public $has_ipv6_data = false;

   private $default_database_file = "geoip2fastphp.dat.gz";

   private $default_dns_lookup_timeout = 1000;
   private $default_dns_lookup_server = "8.8.8.8";

   private $default_error_code_not_found_in_database = "--";
   private $default_error_code_invalid_ip = "##";
   private $default_error_code_reserved_networks = "@@";

   public function __construct($geoip2fast_data_file = null, $verbose=false) {
      if ($geoip2fast_data_file == null or empty($geoip2fast_data_file)) {
         $geoip2fast_data_file = $this->default_database_file;
      }
      $geoip2fast_data_file = $this->locate_data_file($geoip2fast_data_file);
      if ($geoip2fast_data_file == null) {
         exit;
      }
      $this->is_loaded = false;
      $this->load_data_file($geoip2fast_data_file,$verbose);
      if ($this->dat_version == null) {
         print("ERROR loading database");
         exit;
      };
      return $this;
   }

   private function locate_data_file($geoip2fast_data_file = null) {
      try {
         $file_basename = basename($geoip2fast_data_file);
         $file_script_path = getcwd()."/".$file_basename."\n";
         $file_class_path = dirname(__FILE__)."/".$file_basename;
         if (!file_exists($geoip2fast_data_file)) {
            if (!file_exists($file_script_path)) {
               if (!file_exists($file_class_path)) {
                  throw new Exception("The data file \"{$geoip2fast_data_file}\" was not found\n");
                  return null;
               } else return $file_class_path;
            } else return $file_script_path;
         } else return realpath($geoip2fast_data_file);
     } catch(Exception $ERR) {
         echo $ERR->getMessage();
     }
   }

   private function load_data_file($geoip2fast_data_file = null, $verbose=false) {
      if ($this->is_loaded) {
         return $this;
      }
      $startTimeLoad = microtime(true);

      if ($geoip2fast_data_file == null or empty($geoip2fast_data_file)) {
         $geoip2fast_data_file = $this->default_database_file;
      }
      $this->current_data_file = $geoip2fast_data_file;

      $gzjson = file_get_contents($geoip2fast_data_file);
      $json_data = gzdecode($gzjson);
      
      $memStart = memory_get_usage();

      $database = json_decode($json_data, true);
      
      $endTimeLoad = microtime(true)-$startTimeLoad;
      
      $this->dat_version = $database['__DAT_VERSION__'];
      $this->has_ipv6_data = $database['total_networks_country_v6'] > 0;
      $this->has_asn_data = $database['total_networks_asn'] > 0;
      $this->total_networks_ipv4 = $database['total_networks_country'];
      $this->total_networks_ipv6 = $database['total_networks_country_v6'];
      $this->total_asn_networks_ipv4 = $database['total_networks_asn'];
      $this->total_asn_networks_ipv6 = $database['total_networks_asn_v6'];

      $this->country_names = $database['country_names'];
      $this->mainIndex = $database['mainIndex'];
      $this->country_first_ip = $database['country_first_ip'];
      $this->country_net_length = $database['country_net_length'];
      $this->country_id = $database['country_id'];

      if ($this->has_asn_data) {
         $this->asn_names = $database['asn_names'];
         $this->mainIndexASN = $database['mainIndexASN'];
         $this->asn_first_ip = $database['asn_first_ip'];
         $this->asn_net_length = $database['asn_net_length'];
         $this->asn_id = $database['asn_id'];
      }

      if ($this->has_ipv6_data) {
         $this->mainIndex_v6 = $database['mainIndex_v6'];
         $this->country_first_ip_v6 = $database['country_first_ip_v6'];
         $this->country_net_length_v6 = $database['country_net_length_v6'];
         $this->country_id_v6 = $database['country_id_v6'];   
         if ($this->has_asn_data) {
            $this->mainIndexASN_v6 = $database['mainIndexASN_v6'];
            $this->asn_first_ip_v6 = $database['asn_first_ip_v6'];
            $this->asn_net_length_v6 = $database['asn_net_length_v6'];
            $this->asn_id_v6 = $database['asn_id_v6'];   
         }
      }

      unset($json_data);
      unset($gzjson);
      unset($database);
      
      $memEnd = memory_get_usage();
      $memUsed = (($memEnd - $memStart) / 1024) / 1024;
      $this->load_database_text = $this->CLASS_NAME." v".$this->CLASS_VERSION." is ready! ".basename($geoip2fast_data_file)." loaded with ".number_format(($this->total_networks_ipv4+$this->total_networks_ipv6),0,".",".")." networks in ".number_format($endTimeLoad,5)." seconds and using ".number_format($memUsed,2)." MiB";
      if ($verbose) {
         print($this->load_database_text."\n");
      }      
      $this->is_loaded = true;
      return $this;
   }

   public function get_load_database_text() {
      return $this->load_database_text;
   }

   public function get_database_info() {
      $result = array(
         "data_file"=>realpath($this->current_data_file),
         "ipv4_networks"=>$this->total_networks_ipv4,
         "ipv6_networks"=>$this->total_networks_ipv6,
         "asn_ipv4_networks"=>$this->total_asn_networks_ipv4,
         "asn_ipv6_networks"=>$this->total_asn_networks_ipv6,
      );
      return $result;
   }

   public static function cWhite($text) {
      return "\033[1;37m".$text."\033[0m";
   }
   public static function cYellow($text) {
      return "\033[1;33m".$text."\033[0m";
   }

   private function getNumIPsv4($net_length=0) {
      return 2 ** (32-$net_length);
   }

   private function getNumIPsv6($net_length=0) {
      // return gmp_pow('2', (128-$net_length)); // Usando a função gmp_pow para manipulação de números grandes.
      return gmp_pow('2', (128-$net_length)); // Usando a função gmp_pow para manipulação de números grandes.
   }
  
   private function bisect($array, $value, $lo = 0, $hi = null) {
      if ($hi === null) {
         $hi = count($array);
       }
       while ($lo < $hi) {
          $mid = (int)(($lo + $hi) / 2);
          if ($array[$mid] > $value) {
             $hi = $mid;
            } else {
               $lo = $mid + 1;
            }
         }
         return $lo;
   }

   private function join_list($list_of_lists) {
      $joined_list = [];
      foreach ($list_of_lists as $sublist) {
          $joined_list = array_merge($joined_list, $sublist);
      }
      return $joined_list;
   }
  
   public static function ipv6_to_int($ipv6_address) {
       $binary = inet_pton($ipv6_address);
       $numeric = gmp_import($binary);
       return gmp_strval($numeric);
   }
   
   public static function int_to_ipv6($num) {
       $binary = gmp_export(gmp_init(strval($num)));
       return inet_ntop($binary);
   }

   public static function ipv4_to_int($ipv4_address) {
      return ip2long($ipv4_address);
   }

   public static function int_to_ipv4($ipv4_address) {
      return ip2long($ipv4_address);
   }

   public function set_error_code_for_not_found_in_database($error_code=null) {
      $this->default_error_code_not_found_in_database = $error_code;
      return $this->default_error_code_not_found_in_database;
   }   
   public function get_error_code_for_not_found_in_database() {
      return $this->default_error_code_not_found_in_database;
   }   

   public function set_error_code_for_invalid_ip($error_code=null) {
      $this->default_error_code_invalid_ip = $error_code;
      return $this->default_error_code_invalid_ip;
   }   
   public function get_error_code_for_invalid_ip() {
      return $this->default_error_code_error_code_invalid_ip;
   }   

   public function set_error_code_for_reserved_networks($error_code=null) {
      $this->default_error_code_reserved_networks = $error_code;
      return $this->default_error_code_reserved_networks;
   }   
   public function get_error_code_for_reserved_networks() {
      return $this->default_error_code_reserved_networks;
   }   

   public function set_dns_lookup_timeout($timeout_milliseconds=null) {
      if ($timeout_milliseconds != null) {
         $this->default_dns_lookup_timeout = $timeout_milliseconds;
      }
      return $this->default_dns_lookup_timeout;
   }   
   public function get_dns_lookup_timeout() {
      return $this->default_dns_lookup_timeout;
   }   

   public function set_dns_lookup_server($dns_resolver_ip=null) {
      if ($dns_resolver_ip != null) {
         $this->default_dns_lookup_server = $dns_resolver_ip;
      }
      return $this->default_dns_lookup_server;
   }   
   public function get_dns_lookup_server() {
      return $this->default_dns_lookup_server;
   }   
      
   private function resolve_hostname_by_ipaddress($ipaddr=null,$resolver_ip=null,$timeout=null) {
      if ($timeout == null) {
         $timeout = $this->default_dns_lookup_timeout;
      }
      if ($resolver_ip == null) {
         $resolver_ip = $this->default_dns_lookup_server;
      }
      return $this->gethostbyaddr_timeout($ipaddr,$resolver_ip,$timeout);
   }

   private function asn_lookup_ipv4($iplong) {
      if (!$this->has_asn_data) {
         return ["",""];
      }
      $matchRootASN = $this->bisect($this->mainIndexASN, $iplong)-1;
      $matchChunkASN = $this->bisect($this->asn_first_ip[$matchRootASN], $iplong)-1;
      $asn_first_ip2int = $this->asn_first_ip[$matchRootASN][$matchChunkASN];
      $asn_sufix = $this->asn_net_length[$matchRootASN][$matchChunkASN];
      $asn_last_ip = ($asn_first_ip2int + $this->getNumIPsv4($asn_sufix))-1;
      if ($iplong > $asn_last_ip) {
         return ["", ""];
      }
      $asn_id = $this->asn_id[$matchRootASN][$matchChunkASN]-1;
      $asn_name = $this->asn_names[$asn_id];
      $asn_cidr = long2ip($asn_first_ip2int)."/".$asn_sufix;
      return [$asn_cidr, $asn_name];
   }

   private function asn_lookup_ipv6($iplong) {
      if (!$this->has_asn_data) {
         return ["",""];
      }
      $matchRootASN = $this->bisect($this->mainIndexASN_v6, $iplong)-1;
      $matchChunkASN = $this->bisect($this->asn_first_ip_v6[$matchRootASN], $iplong)-1;
      $asn_first_ip2int = $this->asn_first_ip_v6[$matchRootASN][$matchChunkASN];
      $asn_sufix = $this->asn_net_length_v6[$matchRootASN][$matchChunkASN];
      $asn_last_ip = ($asn_first_ip2int + $this->getNumIPsv6($asn_sufix))-1;
      if ($iplong > $asn_last_ip) {
         return ["", ""];
      }
      $asn_id = $this->asn_id_v6[$matchRootASN][$matchChunkASN]-1;
      $asn_name = $this->asn_names[$asn_id];
      $asn_cidr = $this->int_to_ipv6($asn_first_ip2int)."/".$asn_sufix;
      return [$asn_cidr, $asn_name];
      return ["",""];
   }

   private function lookup_ipv4($ipaddr = null, $resolve_hostname = false) { 
      $startTimeGeoIPLookup = microtime(true);
      $iplong = ip2long($ipaddr);
      if ($iplong == null) {
         return [$ipaddr,$this->default_error_code_invalid_ip,"<invalid ip address>","","","","","",number_format(microtime(true)-$startTimeGeoIPLookup,9),""];
      }
      $is_private = false;
      $matchRoot = $this->bisect($this->mainIndex, $iplong)-1;
      $matchChunk = $this->bisect($this->country_first_ip[$matchRoot], $iplong)-1;
      $sufix = $this->country_net_length[$matchRoot][$matchChunk];
      $first_ip2int = $this->country_first_ip[$matchRoot][$matchChunk];
      $last_ip2int = ($first_ip2int + $this->getNumIPsv4($sufix))-1;
      if ($iplong > $last_ip2int) {
         return [$ipaddr,$this->default_error_code_not_found_in_database,"<not found in database>","","","","","",number_format(microtime(true)-$startTimeGeoIPLookup,9),""]; 
      } else {
         if ($sufix == 32) {
            $cidr = $ipaddr."/32";
         } else {
            $cidr = long2ip($first_ip2int)."/".$sufix;
         }   
         $country_code_id = $this->country_id[$matchRoot][$matchChunk];
         list($res_country_code,$res_country_name) = explode(":",$this->country_names[$country_code_id]);
         if ($country_code_id < 15) {
             $res_country_code = $this->default_error_code_reserved_networks;
             $is_private = true;
         }
         list($asn_cidr, $asn_name) = $this->asn_lookup_ipv4($iplong);
      }
      $endTimeGeoIPLookup = microtime(true)-$startTimeGeoIPLookup;
      if ($resolve_hostname) {
         $startHostnameTime = microtime(true);
         $hostname = $this->resolve_hostname_by_ipaddress($ipaddr);
         $endHostnameTime = (microtime(true)-$startHostnameTime);
         $elapsed_hostname = number_format($endHostnameTime,9);
      } else {
         $hostname = "";
         $elapsed_hostname = "";
      }
      return [$ipaddr,$res_country_code,$res_country_name,$cidr,$hostname,$asn_name,$asn_cidr,(boolval($is_private) ? 'true' : 'false'),number_format($endTimeGeoIPLookup,9),$elapsed_hostname];
   }

   private function lookup_ipv6($ipaddr = null, $resolve_hostname = false) { 
      $startTimeGeoIPLookup = microtime(true);
      if (!$this->has_ipv6_data) {
         return [$ipaddr,$this->default_error_code_not_found_in_database,"<not found in database>","","","","","",number_format(microtime(true)-$startTimeGeoIPLookup,9),""]; 
      }
      $iplong = self::ipv6_to_int($ipaddr);
      if ($iplong == null) {
         return [$ipaddr,$this->default_error_code_invalid_ip,"<invalid ip address>","","","","","",number_format(microtime(true)-$startTimeGeoIPLookup,9),""];
      }
      $is_private = false;
      $matchRoot = $this->bisect($this->mainIndex_v6, $iplong)-1;
      $matchChunk = $this->bisect($this->country_first_ip_v6[$matchRoot], $iplong)-1;
      $sufix = $this->country_net_length_v6[$matchRoot][$matchChunk];
      $first_ip2int = $this->country_first_ip_v6[$matchRoot][$matchChunk];
      $last_ip2int = gmp_add($first_ip2int,$this->getNumIPsv6($sufix))-1;
      if ($iplong > $last_ip2int) {
         return [$ipaddr,$this->default_error_code_not_found_in_database,"<not found in database>","","","","","",number_format(microtime(true)-$startTimeGeoIPLookup,9),""]; 
      } else {
         if ($sufix == 128) {
            $cidr = $ipaddr."/128";
         } else {
            $cidr = self::int_to_ipv6($first_ip2int)."/".$sufix;
         }   
         $country_code_id = $this->country_id_v6[$matchRoot][$matchChunk];
         list($res_country_code,$res_country_name) = explode(":",$this->country_names[$country_code_id]);
         if ($country_code_id < 15) {
             $res_country_code = $this->default_error_code_reserved_networks;
             $is_private = true;
         }
         list($asn_cidr, $asn_name) = $this->asn_lookup_ipv6($iplong);
      }
      $endTimeGeoIPLookup = microtime(true)-$startTimeGeoIPLookup;
      if ($resolve_hostname) {
         $startHostnameTime = microtime(true);
         $hostname = $this->resolve_hostname_by_ipaddress($ipaddr);
         $endHostnameTime = (microtime(true)-$startHostnameTime);
         $elapsed_hostname = number_format($endHostnameTime,9);
      } else {
         $hostname = "";
         $elapsed_hostname = "";
      }
      return [$ipaddr,$res_country_code,$res_country_name,$cidr,$hostname,$asn_name,$asn_cidr,(boolval($is_private) ? 'true' : 'false'),number_format($endTimeGeoIPLookup,9),$elapsed_hostname];
   }
   
   public function lookup_raw($ipaddr = null, $resolve_hostname = false) {
      if (!strpos($ipaddr,":")) {
         return $this->lookup_ipv4($ipaddr,$resolve_hostname);
      } else {
         return $this->lookup_ipv6($ipaddr,$resolve_hostname);
      }
   }
   
   public function lookup($ipaddr = null, $resolve_hostname = false) {
      if (!strpos($ipaddr,":")) {
         list($res_ipaddr,$res_country_code,$res_country_name,$res_cidr,$res_hostname,$res_asn_name,$res_asn_cidr,$res_is_private,$res_elapsed_time,$res_elapsed_hostname) = $this->lookup_ipv4($ipaddr,$resolve_hostname);
      } else {
         list($res_ipaddr,$res_country_code,$res_country_name,$res_cidr,$res_hostname,$res_asn_name,$res_asn_cidr,$res_is_private,$res_elapsed_time,$res_elapsed_hostname) = $this->lookup_ipv6($ipaddr,$resolve_hostname);
      }
      $result = array(
         "ip"=>$ipaddr,
         "country_code"=>$res_country_code,
         "country_name"=>$res_country_name,
         "country_cidr"=>$res_cidr,
         "hostname"=>$res_hostname,
         "asn_name"=>$res_asn_name,
         "asn_cidr"=>$res_asn_cidr,
         "is_private"=>$res_is_private,
         "elapsed_time"=>$res_elapsed_time,
      );
      if (!empty($res_hostname)) {
         $result = array_merge($result,array("elapsed_hostname"=>$res_elapsed_hostname));
      }
      return $result;
   }
   
   public function lookup_pp($ipaddr = null, $resolve_hostname = false) {
      return json_encode($this->lookup($ipaddr,$resolve_hostname),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n";
   }

   private function get_a_random_private_ip() {
      $netA = array(167772160,184549375);
      $netB = array(3232235520,3232301055);
      $netC = array(2886729728,2887778303);
      $private_networks = array($netA,$netB,$netC);
      $randon_network = $private_networks[rand(0,2)];
      $random_private_ip = long2ip(rand($randon_network[0],$randon_network[1]));
      return $random_private_ip;
   }
     
   public function self_test($max_ips=30) {
      $ip_list = [];
      array_push($ip_list,"x".str_replace(".",",",long2ip(rand(16777216,3758096383)))); // generates an invalid IP inserting the 'x' letter and changing dot by comma
      array_push($ip_list,long2ip(rand(16777216,3758096383))."/32"); // generates an invalid IP adding '/32' to the end. Is a valid CIDR but an invalid IP
      array_push($ip_list,long2ip(rand(397189376,397191423))); // generates a random IP between 23.172.161.0 and 23.172.168.255 to force a 'not found' response
      array_push($ip_list,$this->get_a_random_private_ip()); // generates a random IP of 10.0.0.0/8 network
      // array_push($ip_list,long2ip(rand(167772160,184549375))); // generates a random IP of 10.0.0.0/8 network
      while (count($ip_list) < $max_ips) {
         array_push($ip_list,long2ip(rand(16777216,3758096383)));
      }

      if ($this->has_ipv6_data) {

         while (count($ip_list) > ($max_ips-floor(($max_ips * 5)/30))) array_pop($ip_list);
         while (count($ip_list) < $max_ips) {
            $sortRoot = rand(0,count($this->country_first_ip_v6)-1);
            $sortChunk = rand(0,count($this->country_first_ip_v6[$sortRoot])-1);
            $first_ipv6_sorted = $this->country_first_ip_v6[$sortRoot][$sortChunk];
            $netlength = $this->country_net_length_v6[$sortRoot][$sortChunk];
            $last_ipv6_sorted = gmp_add($first_ipv6_sorted,$this->getNumIPsv6($netlength));
            $ipv6_sorted = self::int_to_ipv6(gmp_random_range(strval($first_ipv6_sorted),strval($last_ipv6_sorted)));
            array_push($ip_list,$ipv6_sorted);
         }
      }
      $this->set_error_code_for_invalid_ip("##");
      $this::set_error_code_for_not_found_in_database("??");
      $this->set_error_code_for_reserved_networks("--");

      $startTimeTest = microtime(true);
      print($this->get_load_database_text()."\n");
      print("\nStarting a self-test...\n\n");
      $avg_time = [];
      $ip_pad = $this->has_ipv6_data ? 40 : 19;
      for ($x = 0; $x < $max_ips; $x++) {
         $result = $this->lookup($ip_list[$x]);
         array_push($avg_time,$result['elapsed_time']);
         print("- IP: ".str_pad($result['ip'],$ip_pad," ").substr($result['country_code'],0,2)." ".str_pad($result['country_name'],35+(strlen($result['country_name'])-iconv_strlen($result['country_name'])))." [".$result['elapsed_time']." sec] ".$result['asn_name']."\n");
      }
      print("\n");
      print("Self-test with ".count($ip_list)." randomic IP addresses with an average of ".number_format(array_sum($avg_time)/count($ip_list),9)." seconds per lookup.\n");
      print("\n");
   }

   public function speed_test($verbose=false,$max_ips=1000000) {
      $ip_list = [];
      for ($x = 0; $x < $max_ips; $x++) {
         array_push($ip_list,long2ip(rand(16777216,3758096383)));
      }
      $startTimeTest = microtime(true);
      if ($verbose) {
         // print($this->get_load_database_text()."\n");
         print("\nCalculating current speed... wait a few seconds please...\n");
      }
      $avg_time = [];
      for ($x = 0; $x < $max_ips; $x++) {
         list($res_ipaddress,$res_country_code,$res_country_name,$res_cidr,$res_hostname,$res_asn_cidr,$res_asn_name,$is_private,$res_elapsed_time) = $this->lookup_raw($ip_list[$x]);
         // just simulate the use of returned values to get closer a real situation of use
         array_push($avg_time,$res_elapsed_time);
         $xxx_country_code = $res_country_code;
         $xxx_country_name = $res_country_name;
         unset($xxx_country_code);
         unset($xxx_country_name);
         // print("   \r".$res_country_code);
      }
      $endTimeTest = (microtime(true)-$startTimeTest);
      if ($verbose) {
         print("\nCurrent speed: ".number_format($max_ips/$endTimeTest,2,".","")." lookups per second (".number_format($max_ips,0,".",".")." IPs with an average of ".number_format(array_sum($avg_time)/count($avg_time),9)." seconds per lookup) [".number_format($endTimeTest,5)." sec]\n\n");
      }
      return [number_format($max_ips/$endTimeTest,2,".",""),number_format(array_sum($avg_time)/count($avg_time),9)];
   }

   public function get_coverage($verbose=false,$detailed=false) {
      if ($verbose) {
         print($this->get_load_database_text()."\n");
      }
      $old_has_asn_data = $this->has_asn_data;
      $this->has_asn_data = false;
      $formatted_number = "";
      $num_ips_v4 = 0;
      $num_ipv4_networks = 0;
      $num_ips_v6 = 0;
      $num_ipv6_networks = 0;
      $startTime = microtime(true);
      $avg_time_v4 = [];
      $avg_time_v6 = [];

      $joinedFirstIPList =$this->join_list($this->country_first_ip);
      $joinedNetLengthList =$this->join_list($this->country_net_length);
      if ($this->has_ipv6_data) {
         $joinedFirstIPList_v6 =$this->join_list($this->country_first_ip_v6);
         $joinedNetLengthList_v6 =$this->join_list($this->country_net_length_v6);
      }

      $num_ipv4_networks = count($joinedNetLengthList);
      for ($index = 0; $index < $num_ipv4_networks; $index++) {
         $first_ipstring = long2ip($joinedFirstIPList[$index]);
         $num_ips = $this->getNumIPsv4($joinedNetLengthList[$index]);
         $num_ips_v4 += $num_ips;
         $_ip_string = str_pad($num_ips,10," ");
         if ($verbose and $detailed) {
            $result = $this->lookup($first_ipstring);
            array_push($avg_time_v4,$result['elapsed_time']);
            print("- Network: ".self::cWhite(str_pad($result["country_cidr"],19," "))." IPs: ".self::cWhite($_ip_string)." ".substr($result['country_code'],0,2)." ".self::cWhite(str_pad($result['country_name'],35+(strlen($result['country_name'])-iconv_strlen($result['country_name']))))." ".$result['elapsed_time']." sec\n");
         }
      }
      $percentage_v6 = 0.0;
      $num_ipv6_formatted = '0';
      if ($this->has_ipv6_data) {
         $num_ipv6_networks = count($joinedNetLengthList_v6);
         for ($index = 0; $index < $num_ipv6_networks; $index++) {
            $first_ipstring = self::int_to_ipv6($joinedFirstIPList_v6[$index]);
            $num_ips = $this->getNumIPsv6($joinedNetLengthList_v6[$index]);
            $num_ips_v6 = gmp_add($num_ips_v6,$num_ips);
            $_ip_string = str_pad($num_ips,38," ");
            if ($verbose and $detailed) {
               $result = $this->lookup($first_ipstring);
               array_push($avg_time_v6,$result['elapsed_time']);
               print("- Network: ".self::cWhite(str_pad($result['country_cidr'],25," "))." IPs: ".self::cWhite($_ip_string)." ".substr($result['country_code'],0,2)." ".self::cWhite(str_pad($result['country_name'],35+(strlen($result['country_name'])-iconv_strlen($result['country_name']))))." ".$result['elapsed_time']." sec\n");
            }
         }
         $percentage_v6 = (gmp_strval($num_ips_v6) * 100) / gmp_strval($this->getNumIPsv6(0));
         // formatting the total of IPv6 using GMP because php decrease this number with big difference
         $num_str = gmp_strval(gmp_init(strval($num_ips_v6)));
         $num_ipv6_formatted = implode('.', str_split(strrev($num_str), 3));
         $num_ipv6_formatted = strrev($num_ipv6_formatted);
         if (count($avg_time_v6) > 0) {
            $percentage_v6_string = "IPv6 lookup average time: ".self::cYellow(number_format(floatval(array_sum($avg_time_v6)/count($avg_time_v6)),9))." seconds per lookup\n";
         } 
      } else {
         $percentage_v6_string = "IPv6 lookup average time: ".self::cYellow(number_format(floatval(0.0),9))." seconds per lookup\n";
      }
      $this->has_asn_data = $old_has_asn_data;
      $percentage_v4 = ($num_ips_v4 * 100) / $this->getNumIPsv4(0);
      $endTime = microtime(true) - $startTime;
      if ($verbose) {
         print("\n");
         print("Current IPv4 coverage: ".self::cYellow(str_pad(number_format(floatval($percentage_v4),2),6," ",STR_PAD_LEFT)."% "   )."(".number_format($num_ips_v4,0,',','.')." IPv4 in ".number_format($num_ipv4_networks,0,',','.')." networks) [".number_format($endTime,5)." sec]\n");
         print("Current IPv6 coverage: ".self::cYellow(str_pad(number_format($percentage_v6,2),6," ",STR_PAD_LEFT)."% ")."(".$num_ipv6_formatted." IPv6 in ".number_format($num_ipv6_networks,0,',','.')." networks) [".number_format($endTime,5)." sec]\n");
         print("\n");
         if ($detailed) {
            print("IPv4 lookup average time: ".self::cYellow(number_format(floatval(array_sum($avg_time_v4)/count($avg_time_v4)),9))." seconds per lookup\n");
            print($percentage_v6_string);
            print("\n");
         }
      }
      return [number_format(floatval($percentage_v4),2),number_format(floatval($percentage_v6),2)];
   }

   private function get_missing_subnets($start, $end) {
      $listNumIPsv4 = array_map(function ($num) {
         return 2 ** $num;
      }, range(0, 32));
      $missingSubnets = [];
      $currentStart = $start;
      $currentEnd = $start;
      $diff = 0;
      while ($currentEnd < $end) {
         $diff = ($end - $currentStart);
         $matchNetLen = $this->bisect($listNumIPsv4, $diff)-1;
         $valNetLen = $listNumIPsv4[intval($matchNetLen)];
         $diff -= $valNetLen;
         $indexNetLen = 32 - array_search($valNetLen, $listNumIPsv4);
         $cidr = long2ip($currentStart) . '/' . $indexNetLen;
         $missingSubnets[] = $cidr;
         $currentEnd += $valNetLen;
         $currentStart = $currentEnd;
         if ($diff <= 1) {
               break;
         }
      }
      return $missingSubnets;
   }
   
   public function get_missing_ips($verbose=false,$detailed=false) {
      if ($verbose and $detailed) print("\nSearching for missing IPs...\n\n");
      $total_missing_ips = 0;
      $total_missing_networks = 0;
      $missingRanges = [];
      $old_last_iplong = 0;
      $joinedFirstIPList = $this->join_list($this->country_first_ip);
      $joinedNetLengthList = $this->join_list($this->country_net_length);
      $startTime = microtime(true);
      for ($N = 0; $N < count($joinedFirstIPList); $N++) {
         $first_iplong = $joinedFirstIPList[$N];
         $first_ipstring = long2ip($first_iplong);
         $last_iplong = $first_iplong + $this->getNumIPsv4(intval($joinedNetLengthList[$N]));
         if (($first_iplong - $old_last_iplong) > 0) {
            $miss_first_iplong = $old_last_iplong;
            $miss_last_iplong = $first_iplong;
            $missing_ips = $miss_last_iplong - $miss_first_iplong;
            $total_missing_ips += $missing_ips;
            if ($detailed and $verbose) {
               $missingRanges = $this->get_missing_subnets($miss_first_iplong,$miss_last_iplong);
               foreach ($missingRanges as $cidr) {
                  $total_missing_networks += 1;
                  list($cidr_first_ip, $cidr_netlen) = explode("/",$cidr);
                  $cidr_first_ip2int = ip2long($cidr_first_ip);
                  $cidr_last_ip2int = ($cidr_first_ip2int + $this->getNumIPsv4(intval($cidr_netlen)))-1;
                  $missing_ip = ($cidr_last_ip2int - $cidr_first_ip2int) + 1;
                     print("> From ".self::cWhite(str_pad($cidr_first_ip,16,' '))." to ".self::cWhite(str_pad(long2ip($cidr_last_ip2int),16,' '))." > Network: ".self::cWhite(str_pad($cidr,19))." - Missing IPs: ".self::cWhite($missing_ip)."\n");
               }
            }
         }
         $old_last_iplong = $last_iplong;
      }
      $percentage = ($total_missing_ips * 100) / ($this->getNumIPsv4()); # don´t count the network 0.0.0.0/8
      if ($verbose) {
         print("\n>>> Valid IPv4 addresses without geo information: ".self::cYellow(number_format($total_missing_ips,0,"","."))." (".number_format($percentage,2)."% of all IPv4 in ".number_format($total_missing_networks,0,"",".")." networks) [".number_format((microtime(true)-$startTime),5)." sec]\n\n");
      }
      return [$total_missing_ips,number_format($percentage,2)];
   }
   
   public static function gethostbyaddr_timeout($ip, $dns_server, $timeout=1000) {
      $data = rand(0, 99);
      // trim it to 2 bytes
      $data = substr($data, 0, 2);
      // request header
      $data .= "\1\0\0\1\0\0\0\0\0\0";
      // split IP up
      $bits = explode(".", $ip);
      // error checking
      if (count($bits) != 4) return "ERROR";
      // there is probably a better way to do this bit...
      // loop through each segment
      for ($x=3; $x>=0; $x--) {
         // needs a byte to indicate the length of each segment of the request
         switch (strlen($bits[$x])) {
            case 1: // 1 byte long segment
            $data .= "\1"; break;
            case 2: // 2 byte long segment
            $data .= "\2"; break;
            case 3: // 3 byte long segment
            $data .= "\3"; break;
            default: // segment is too big, invalid IP
            return "INVALID";
         }
         // and the segment itself
         $data .= $bits[$x];
      }
      // and the final bit of the request
      $data .= "\7in-addr\4arpa\0\0\x0C\0\1";
      // create UDP socket
      $handle = @fsockopen("udp://$dns_server", 53);
      // send our request (and store request size so we can cheat later)
      $requestsize=@fwrite($handle, $data);
      @socket_set_timeout($handle, $timeout - $timeout % 1000, $timeout % 1000);
      // hope we get a reply
      $response = @fread($handle, 1000);
      @fclose($handle);
      if ($response == "") return "<dns lookup timeout>";
      // find the response type
      $type = @unpack("s", substr($response, $requestsize+2));
      if ($type != false) {
         if (count($type) > 0) {
            if ($type[1] == 0x0C00) {  // answer
               // set up our variables
               $host="";
               $len = 0;
               // set our pointer at the beginning of the hostname
               // uses the request size from earlier rather than work it out
               $position=$requestsize+12;
               // reconstruct hostname
               do {
                  // get segment size
                  $len = unpack("c", substr($response, $position));
                  // null terminated string, so length 0 = finished
                  if ($len[1] == 0)
                  // return the hostname, without the trailing .
                     return substr($host, 0, strlen($host) -1);
                     // add segment to our host
                  $host .= substr($response, $position+1, $len[1]) . ".";
                  // move pointer on to the next segment
                  $position += $len[1] + 1;
               }
            while ($len != 0);
               // error - return the hostname we constructed (without the . on the end)
               return $ip;
            }
         }
      } 
      return "";
   }   
}

?>