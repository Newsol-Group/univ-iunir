<?php

require_once(realpath(dirname(__FILE__) . '/../bootstrap.php'));

ini_set("memory_limit", "256M");
// Ver: http://php.net/manual/es/timezones.america.php
date_default_timezone_set('America/Argentina/Buenos_Aires');
ini_set("default_charset", "ISO-8859-1");

\siu\bootstrap::cargar();
\SIU\Chulupi\nucleo\AccesoWeb::procesar();