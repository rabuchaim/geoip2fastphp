#!/usr/bin/php
<?php
try {
    if (!@include_once('./GeoIP2Fast.class.php'))
        if (!@include_once('../GeoIP2Fast.class.php'))
            throw new Exception ("GeoIP2Fast.class.php does not exist\n");
        else 
            require_once('../GeoIP2Fast.class.php'); 
    else
        require_once('./GeoIP2Fast.class.php'); 
} catch(Exception $e) {    
    echo "Message : " . $e->getMessage() . "   Code : " . $e->getCode() . "\n";
    exit;
}

$geoip = new GeoIP2Fast("geoip2fastphp-asn-ipv6.dat.gz",false);
// $geoip = new GeoIP2Fast("geoip2fastphp.dat.gz",false);
# If you didn't create the object with the "$verbose=true" parameter, you can access the database loading information using this method:
print($geoip->get_load_database_text()."\n");

// Setup some useful properties to resolve hostnames (usually is not needed to change anything).
$geoip->set_dns_lookup_timeout(1000);
$geoip->set_dns_lookup_server("1.1.1.1");

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
print("    Value 1: IPv6 percentage of coverage (if using a datafile with ipv6)\n\n");
print_r($geoip->get_coverage());
print("\n");

print("\n\n>>> Returning the missing IPv4 information non verbose and non detailed: print_r(\$geoip->get_missing_ips(false,false));\n\n");
print("    Value 0: Number of missing IPv4\n");
print("    Value 1: Percentage of missing IPv4\n\n");
print_r($geoip->get_missing_ips());
print("\n");

$version = explode('.', PHP_VERSION);
if ($version[0] == 5) {
    $geoip = new GeoIP2Fast("geoip2fastphp.dat.gz",false);
}

print("\n\n>>> Returning a speed test non verbose: print_r(\$geoip->speed_test(false));\n\n");
print("    Value 0: Lookups per second\n");
print("    Value 1: Seconds per loookup\n\n");
print_r($geoip->speed_test());
print("\n");

?>