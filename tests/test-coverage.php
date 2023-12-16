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

$geoip = new GeoIP2Fast("geoip2fastphp-ipv6.dat.gz",false);

$geoip->get_coverage($verbose=true,$detailed=false);

?>