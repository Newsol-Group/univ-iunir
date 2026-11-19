<?php
//include para Gestión
if(file_exists(dirname(__FILE__).'/../../3ros/phpqrcode/qrlib.php')) include_once(dirname(__FILE__).'/../../3ros/phpqrcode/qrlib.php');
//include para 3W
if(file_exists(dirname(__FILE__).'/../../../lib/phpqrcode/qrlib.php')) include_once(dirname(__FILE__).'/../../../lib/phpqrcode/qrlib.php');

class guarani_qrcode_nucleo
{
	static function generar_codigo_qr($path, $texto, $tamanio = 3)
	{
		if (!file_exists($path)) {
			QRcode::png($texto, $path, QR_ECLEVEL_L, $tamanio);
		} 
	}
}
?>