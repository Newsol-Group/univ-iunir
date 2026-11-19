<?php

/**
 * Manipulación de imagenes
 * 
 * @package Centrales
 * @subpackage Utilidades
 */

class guarani_imgs
{
	static function redimensionar_imagen($foto)
	{
		if (extension_loaded('gd')) {
			$tipo = $foto['type'];
			//Se agregan varios mime types para considerar los sufijos posibles en diferentes browsers y versiones.
			if ($tipo == 'image/jpeg' || $tipo == 'image/jpg' || $tipo == 'imagen/jfif' || $tipo == 'imagen/jpe'
				|| $tipo == 'image/pjpeg' || $tipo == 'pjp') {
				$imagen = imagecreatefromjpeg($foto['tmp_name']);
			} elseif ($tipo == 'image/gif') {
				$imagen = imagecreatefromgif($foto['tmp_name']);
			} elseif ($tipo == 'image/png' || $tipo == 'image/x-png') {
				$imagen = imagecreatefrompng($foto['tmp_name']);
			} else {
				//El mime type recuperado no es ninguno de los anteriores. Puede deberse a una incompatibilidad del 
				//navegador o a una falla en la imágen.
				throw new guarani_error(guarani_mensajes::get('800_error_recuperacion_archivo', array('Imagen')));
			}
			$width = imagesx($imagen);
			$height = imagesy($imagen);
			$new_width = 180;
			$new_height = 180;
			$image_resized = imagecreatetruecolor($new_width, $new_height);
			imagecopyresampled($image_resized, $imagen, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
			imagejpeg($image_resized, $foto['tmp_name']);
		} else {
			toba::logger()->warning('No esta cargada la extension GD, no se redimensiona la imagen');
		}
		return fopen($foto['tmp_name'], "r");
	}
	
}

?>