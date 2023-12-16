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

print("\n>>> Speed test with country IPv4 database: \$geoip = new GeoIP2Fast(\"geoip2fastphp.dat.gz\",true);\n\n");
$geoip = new GeoIP2Fast("geoip2fastphp.dat.gz",true);
$result = $geoip->speed_test(true);

print("\n\n>>> Speed test with country+asn IPv4 database: \$geoip = new GeoIP2Fast(\"geoip2fastphp-asn.dat.gz\",true);\n\n");
$geoip = new GeoIP2Fast("geoip2fastphp-asn.dat.gz",true);
$result = $geoip->speed_test(true);

print("\n\n>>> Speed test with country IPv6 database: \$geoip = new GeoIP2Fast(\"geoip2fastphp-ipv6.dat.gz\",true);\n\n");
$geoip = new GeoIP2Fast("geoip2fastphp-ipv6.dat.gz",true);
$result = $geoip->speed_test(true);

print("\n\n>>> Speed test with country+asn IPv6 database: \$geoip = new GeoIP2Fast(\"geoip2fastphp-asn-ipv6.dat.gz\",true);\n\n");
$geoip = new GeoIP2Fast("geoip2fastphp-asn-ipv6.dat.gz",true);
$result = $geoip->speed_test(true);

// print("\n\n>>> The non verbose mode just returns an array [lookup_per_seconds,seconds_per_lookup] : print_r(\$geoip->speed_test(false));\n\n");
// print_r($result);
// print("\n");

?>