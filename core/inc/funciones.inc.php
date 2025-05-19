<?php

/*
* ip_in_range.php - Function to determine if an IP is located in a
*                   specific range as specified via several alternative
*                   formats.
*
* Network ranges can be specified as:
* 1. Wildcard format:     1.2.3.*
* 2. CIDR format:         1.2.3/24  OR  1.2.3.4/255.255.255.0
* 3. Start-End IP format: 1.2.3.0-1.2.3.255
*
* Return value BOOLEAN : ip_in_range($ip, $range);
*
* Copyright 2008: Paul Gregg <pgregg@pgregg.com>
* 10 January 2008
* Version: 1.2
*
* Source website: http://www.pgregg.com/projects/php/ip_in_range/
* Version 1.2
*
* This software is Donationware - if you feel you have benefited from
* the use of this tool then please consider a donation. The value of
* which is entirely left up to your discretion.
* http://www.pgregg.com/donate/
*
* Please do not remove this header, or source attibution from this file.
*/


// decbin32
// In order to simplify working with IP addresses (in binary) and their
// netmasks, it is easier to ensure that the binary strings are padded
// with zeros out to 32 characters - IP addresses are 32 bit numbers
// decbin32: convierte decimal a binario en 32 bits
function decbin32($dec) {
    return str_pad(decbin($dec), 32, '0', STR_PAD_LEFT);
}

// Comprueba si una IP está dentro de un rango
function ip_in_range($ip, $range) {
    if (strpos($range, '/') !== false) {
        list($range, $netmask) = explode('/', $range, 2);
        if (strpos($netmask, '.') !== false) {
            $netmask = str_replace('*', '0', $netmask);
            $netmask_dec = ip2long($netmask);
            return ((ip2long($ip) & $netmask_dec) == (ip2long($range) & $netmask_dec));
        } else {
            $x = explode('.', $range);
            while (count($x) < 4) $x[] = '0';
            list($a, $b, $c, $d) = $x;
            $range = sprintf("%u.%u.%u.%u", empty($a)?'0':$a, empty($b)?'0':$b, empty($c)?'0':$c, empty($d)?'0':$d);
            $range_dec = ip2long($range);
            $ip_dec = ip2long($ip);
            $wildcard_dec = pow(2, (32 - $netmask)) - 1;
            $netmask_dec = ~ $wildcard_dec;
            return (($ip_dec & $netmask_dec) == ($range_dec & $netmask_dec));
        }
    } else {
        if (strpos($range, '*') !== false) {
            $lower = str_replace('*', '0', $range);
            $upper = str_replace('*', '255', $range);
            $range = "$lower-$upper";
        }

        if (strpos($range, '-') !== false) {
            list($lower, $upper) = explode('-', $range, 2);
            $lower_dec = (float)sprintf("%u", ip2long($lower));
            $upper_dec = (float)sprintf("%u", ip2long($upper));
            $ip_dec = (float)sprintf("%u", ip2long($ip));
            return ($ip_dec >= $lower_dec && $ip_dec <= $upper_dec);
        }

        return false;
    }
}

// Valida si la IP está en alguno de los rangos
function ip_in_ranges($ip, $ranges_array) {
    if (!is_array($ranges_array) || empty($ranges_array)) {
        return false;
    }

    foreach ($ranges_array as $range) {
        if (ip_in_range($ip, $range)) {
            return true;
        }
    }

    return false;
}

// Escribe un log en un archivo de texto
function crear_editar_log($ruta_archivo, $contenido, $tipo, $ip, $referer, $useragent) {
    $arr_tipo_log = array("[info]:", "[notice]:", "[warning]:", "[error]:");
    $now = DateTime::createFromFormat('U.u', microtime(true));
    $now->setTimeZone(new DateTimeZone('America/El_Salvador'));

    $linea = PHP_EOL . $now->format("m-d-Y H:i:s.u T") . " $ip {$arr_tipo_log[$tipo]} referer: $referer $contenido $useragent";

    $directorio = dirname($ruta_archivo);
    if (!file_exists($directorio)) {
        mkdir($directorio, 0777, true);
    }

    $archivo = @fopen($ruta_archivo, file_exists($ruta_archivo) ? "a" : "w+");
    if ($archivo) {
        fwrite($archivo, $linea);
        fclose($archivo);
    } else {
        error_log("No se pudo abrir el archivo de log: $ruta_archivo");
    }
}
?>
