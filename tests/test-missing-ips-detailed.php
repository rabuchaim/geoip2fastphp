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

$geoip = new GeoIP2Fast("geoip2fastphp.dat.gz",true);

// print("\n>>> The verbose mode prints the output: \$geoip->get_missing_ips(true);\n\n");
$result = $geoip->get_missing_ips(true,true);

// print("\n\n>>> The non verbose mode just returns an array [missing_ips,percentage] : print_r(\$geoip->get_missing_ips(false));\n\n");
// print_r($result);
// print("\n");

?>