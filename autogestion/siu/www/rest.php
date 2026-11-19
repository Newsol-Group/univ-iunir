<?php

require_once(realpath(dirname(__FILE__).'/../bootstrap.php'));

use siu\bootstrap;
use SIU\Chulupi\nucleo\AccesoRest;

// Ver: http://php.net/manual/es/timezones.america.php
date_default_timezone_set('America/Argentina/Buenos_Aires');
ini_set("default_charset", "ISO-8859-1");
// Ver: http://php.net/manual/es/function.mb-internal-encoding.php
mb_internal_encoding("ISO-8859-1");

bootstrap::cargar();
$acceso = new AccesoRest();
$acceso->procesar();