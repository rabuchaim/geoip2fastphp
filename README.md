# GeoIP2FastPHP v0.0.2 Preview Release
GeoIP2FastPHP is the fastest GeoIP2 country/city/asn lookup class for PHP. A search takes less than 0.00003 seconds. It has its own data file updated twice a week with Maxmind-Geolite2-CSV!

## Requirements:
- PHP versions 5.6, 7.4, 8.1 and 8.3 (very fast and very low memory usage)
- PHP GMP library (necessary for manipulation of huge integer numbers of IPv6)
  
### To do list:
- Include city names support
- Serialization of dat files to get a smaller file size (today it is just a json file gzipped)
- Tests under MacOS and Windows

<br>

```
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
```

## - How does it work?

We converted all network ranges of Maxmind Geolite2 databases into a few lists of integers (Yes! an IP address has an integer representation, try to ping this number: ```ping 134744072``` or this ```ping 2130706433``` ). The bisect() function of Python was converted to PHP to find the closest number in a lista of all first IP address of each network converted to integer. And we sliced this lists in a hundred of chunks and created a main index of each chunk. With this method, search a integer number in a list of 100.000 items has the same speed of search a list with 4.000.000 items. The logic is a little bit complicated but I promise to describe everything here soon. 

## - A speed test with IPv4 country + asn 

```php
<?php
    require_once("GeoIP2Fast.class.php");
    $G = new GeoIP2Fast($geoip2fast_data_file="geoip2fastphp-asn.dat.gz",$verbose=true);
    $G->speed_test($verbose=true);
?>
```
```
GeoIP2Fast v0.0.1 is ready! geoip2fastphp-asn.dat.gz loaded with 455.985 networks in 0.23607 seconds and using 144.43 MiB

Calculating current speed... wait a few seconds please...

Current speed: 206012.48 lookups per second (1.000.000 IPs with an average of 0.000004034 seconds per lookup) [4.85407 sec]
```

## - A self-test 

```php
<?php
    require_once("GeoIP2Fast.class.php");
    $G = new GeoIP2Fast($geoip2fast_data_file="geoip2fastphp-asn-ipv6.dat.gz",$verbose=false);
    $G->self_test($max_ips=30);
?>
```
```
Starting a self-test...

- IP: x35,146,187,103                         ## <invalid ip address>                [0.000000954 sec]
- IP: 121.18.83.208/32                        ## <invalid ip address>                [0.000000954 sec]
- IP: 23.172.168.14                           ?? <not found in database>             [0.000008106 sec]
- IP: 10.205.172.46                           -- Private Network Class A             [0.000015974 sec] IANA.ORG
- IP: 58.188.243.14                           JP Japan                               [0.000009060 sec] OPTAGE Inc.
- IP: 218.48.84.204                           KR South Korea                         [0.000008106 sec] SK Broadband Co Ltd
- IP: 46.198.85.251                           CY Cyprus                              [0.000008106 sec] Cyprus Telecommunications Authority
- IP: 100.0.196.201                           US United States                       [0.000008106 sec] UUNET
- IP: 71.46.217.104                           US United States                       [0.000013113 sec] BHN-33363
- IP: 128.170.135.183                         US United States                       [0.000012875 sec] HARRIS-ATD-AS
- IP: 162.170.215.168                         US United States                       [0.000010967 sec] T-MOBILE-AS21928
- IP: 68.94.130.82                            US United States                       [0.000009060 sec] ATT-INTERNET4
- IP: 33.150.193.48                           US United States                       [0.000007868 sec] DNIC-AS-00749
- IP: 20.110.190.69                           US United States                       [0.000006914 sec] MICROSOFT-CORP-MSN-AS-BLOCK
- IP: 126.44.6.45                             JP Japan                               [0.000006199 sec] SoftBank Corp.
- IP: 2.70.148.157                            SE Sweden                              [0.000010014 sec] Hi3G Access AB
- IP: 84.167.107.191                          DE Germany                             [0.000015020 sec] Deutsche Telekom AG
- IP: 206.116.202.82                          CA Canada                              [0.000010014 sec] TELUS Communications
- IP: 138.228.111.167                         CH Switzerland                         [0.000006914 sec]
- IP: 149.111.182.77                          US United States                       [0.000005960 sec]
- IP: 141.181.160.46                          US United States                       [0.000005960 sec]
- IP: 59.36.149.22                            CN China                               [0.000005960 sec] Chinanet
- IP: 69.6.136.218                            US United States                       [0.000005960 sec] AS-6620
- IP: 93.6.0.88                               FR France                              [0.000008106 sec] Societe Francaise Du Radiotelephone - SFR SA
- IP: 19.142.36.39                            US United States                       [0.000007153 sec]
- IP: 2a09:bac1:62c0:1b3:9af0:62e3:f37a:dbbc  US United States                       [0.000065088 sec] CLOUDFLARENET
- IP: 2a0d:2406:1de5:4dbd:cc7c:da97:34aa:bd1b NL The Netherlands                     [0.000046015 sec]
- IP: 2a07:c4c3:850d:2210:a8f2:164d:165d:77d6 US United States                       [0.000045776 sec]
- IP: 2602:fd50:fb6b:e9e8:849f:eb6a:e149:7716 CA Canada                              [0.000055075 sec]
- IP: 2a09:bac1:14e0:245:752d:3fcb:8653:1acc  US United States                       [0.000051022 sec] CLOUDFLARENET

Self-test with 30 randomic IP addresses with an average of 0.000015680 seconds per lookup.
```

## - Quick start (the test-geoip.php file)

<br>

```php
#!/usr/bin/php
<?php
require_once("GeoIP2Fast.class.php");

$geoip = new GeoIP2Fast("geoip2fastphp-asn-ipv6.dat.gz",false);
// $geoip = new GeoIP2Fast("geoip2fastphp.dat.gz",false);
# If you didn't create the object with the "$verbose=true" parameter, you can access the database loading information using this method:
print($geoip->get_load_database_text()."\n");

// Setup some useful properties to resolve hostnames (usually is not needed to change anything).
$geoip->set_dns_lookup_timeout(1000);
$geoip->set_dns_lookup_server("8.8.8.8");

print("\n>>> Get the current database information: print_r(\$geoip->get_database_info());\n\n");
print_r($geoip->get_database_info());

print("\n\n>>> Returning an array with indexes: print_r(\$geoip->lookup_raw('200.147.0.10'));\n\n");
$result = $geoip->lookup_raw("200.147.0.10");
print_r($result);

print("\n\n>>> Returning an array with names: print_r(\$geoip->lookup('1.1.1.1'));\n\n");
$result = $geoip->lookup("1.1.1.1");
print_r($result);

if ($geoip->has_ipv6_data) {
    print("\n\n>>> Searching for an IPv6 address: print_r(\$geoip->lookup('2a10:8b40:0070:6969:1200:0120:0120:1976'));\n\n");
    $result = $geoip->lookup("2a10:8b40:0070:6969:1200:0120:0120:1976");
    print_r($result);    
}

print("\n\n>>> Returning only the country_code: print(\$geoip->lookup('1.1.1.1')['country_code']);\n\n");
$result = $geoip->lookup("1.1.1.1")['country_code'];
print($result);

print("\n\n>>> Returning a pretty print json: print(\$geoip->lookup_pp('200.204.0.10'));\n\n");
$result = $geoip->lookup_pp("200.204.0.10");
print_r($result);

print("\n\n>>> Returning a pretty print json with hostname lookup: print(\$geoip->lookup_pp('9.9.9.9',\$resolve_hostname=true));\n\n");
$result = $geoip->lookup_pp("9.9.9.9",true);
print_r($result);

print("\n\n>>> Returning a pretty print json with hostname lookup setting 5 miliseconds as dns lookup timeout (to force an error)\n\n");
print("    \$geoip->set_dns_lookup_timeout(5);\n");
print("    print(\$geoip->lookup_pp('9.9.9.9',\$resolve_hostname=true));\n\n");
$geoip->set_dns_lookup_timeout(5);
$result = $geoip->lookup_pp("9.9.9.9",true);
print_r($result);

print("\n\n>>> You can also define which recursive DNS server you want to use\n\n");
print("    print(\$geoip->get_dns_lookup_server());\n");
print($geoip->get_dns_lookup_server()."\n");
print("    \$geoip->set_dns_lookup_server('1.1.1.1');\n");
print($geoip->set_dns_lookup_server('1.1.1.1')."\n");
print("    print(\$geoip->get_dns_lookup_server());\n");
print($geoip->get_dns_lookup_server()."\n");

print("\n\n>>> Returning a 'reserved network' response: print(\$geoip->lookup_pp('10.20.30.40'));\n\n");
$result = $geoip->lookup_pp("10.20.30.40");
print_r($result);
print("\n>>> Changing the returned country_code for 'reserved network' responses\n\n");
print("    \$geoip->set_error_code_for_reserved_networks('==');\n");
print("    print(\$geoip->lookup_pp('10.20.30.40'));\n\n");
$geoip->set_error_code_for_reserved_networks("==");
$result = $geoip->lookup_pp("10.20.30.40");
print_r($result);

print("\n\n>>> Returning an 'invalid ip address' error: print(\$geoip->lookup_pp('300.400.500.600'));\n\n");
$result = $geoip->lookup_pp("300.400.500.600");
print_r($result);
print("\n>>> Changing the returned country_code for 'invalid ip address' errors\n\n");
print("    \$geoip->set_error_code_for_invalid_ip('++');\n");
print("    print(\$geoip->lookup_pp('300.400.500.600'));\n\n");
$geoip->set_error_code_for_invalid_ip("++");
$result = $geoip->lookup_pp("300.400.500.600");
print_r($result);

print("\n\n>>> Returning a 'not found in database' error: print(\$geoip->lookup_pp('23.132.233.10'));\n\n");
$result = $geoip->lookup_pp("23.132.233.10");
print_r($result);
print("\n>>> Changing the returned country_code for 'not found in database' errors\n\n");
print("    \$geoip->set_error_code_for_not_found_in_database('//');\n");
print("    print(\$geoip->lookup_pp('23.132.233.10'));\n\n");
$geoip->set_error_code_for_not_found_in_database("//");
$result = $geoip->lookup_pp("23.132.233.10");
print_r($result);


print("\n\n>>> Returning the IPv4 and IPv6 coverage information non verbose and non detailed: print_r(\$geoip->get_coverage(false,false));\n\n");
print("    Value 0: IPv4 percentage of coverage\n");
print("    Value 1: IPv6 percentage of coverage (if using a datafile with ipv6\n\n");
print_r($geoip->get_coverage());
print("\n");

print("\n\n>>> Returning the missing IPv4 information non verbose and non detailed: print_r(\$geoip->get_missing_ips(false,false));\n\n");
print("    Value 0: Number of missing IPv4\n");
print("    Value 1: Percentage of missing IPv4\n\n");
print_r($geoip->get_missing_ips());
print("\n");

print("\n\n>>> Returning a speed test non verbose: print_r(\$geoip->speed_test(false));\n\n");
print("    Value 0: Lookups per second\n");
print("    Value 1: Seconds per loookup\n\n");
print_r($geoip->speed_test());
print("\n");

?>
```

## - The file used to create the dat.gz files (will be available soon)
<br>
<img src="https://raw.githubusercontent.com/rabuchaim/geoip2fastphp/main/images/geoip2fast01.jpg">


## - A simple and fast geoip lookup cli
<img src="https://raw.githubusercontent.com/rabuchaim/geoip2fastphp/main/images/geoip2fast02.jpg">
<br><br>


## Sugestions, feedbacks, bugs, wrong locations...
E-mail me: ricardoabuchaim at gmail.com
