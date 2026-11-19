<?php

/**
 * Utilidades Gráficas
 * 
 * @package Centrales
 * @subpackage Utilidades
 */

class guarani_grafica
{
	
	static function txt($texto='',$size=10,$shadow=false,$color_R=0,$color_G=0,$color_B=0,$fuente='kartika',$kerning=0)
	{
		$path_fuentes=toba::proyecto()->get_path().'/fuentes';
		$fuente = "$path_fuentes/$fuente.ttf";
		$texto = html_entity_decode($texto);
		if (trim($texto == '')) return;
		//Busco el tamaño de la caja contenedora
		$simtxt = 'AGQqjbftgl';
		$box = imagettfbbox ( $size , 0, $fuente, $simtxt );
		$height = abs($box[1]) + abs($box[7]);
		if ($height < $size) {
			$size = $size * 1.5;
			$box = imagettfbbox ( $size , 0, $fuente, $simtxt );
			$height = abs($box[1]) + abs($box[7]);
		}	
		$height = $height * 1.15;
		$box = imagettfbbox ( $size , 0, $fuente, $texto );
		$width = (abs($box[0]) + abs($box[2])) * 1.05 + ((strlen($texto) - 1) * $kerning);
		$baseline =  abs($height * 0.75);
		$img = imagecreatetruecolor($width, $height);
		imagesavealpha($img, true);
		$black = imagecolorallocate($img, 0, 0, 0);
		$transp = imagecolorallocatealpha($img, 0, 0, 0, 127);
		imagefill($img, 0, 0, $transp);
		$white = imagecolorallocate($img, 255, 255, 255);
		$color = imagecolorallocate($img, $color_R, $color_G, $color_B);
		$sombra = imagecolorallocate($img, 168, 168, 168);
		if ($kerning == 0) {
			if ($shadow) {
				imagettftext($img, $size, 0, 1, $baseline + 1, $sombra, $fuente, $texto);
			}	
			imagettftext($img, $size, 0, 0, $baseline, $color, $fuente, $texto);
		} else {
			$chars = str_split($texto);
			$hpos = 0;
			foreach($chars as $char) {
				if ($shadow) {
					imagettftext($img, $size, 0, $hpos + 1, $baseline + 1, $sombra, $fuente, $char);
				}	
			    $bbox = imagettftext( $img, $size, 0, $hpos, $baseline, $color, $fuente, $char );
			    $hpos = $bbox[2]+$kerning;
			}
		}	
		$temp_nombre = md5(uniqid(time()));
		$temp_archivo = toba::proyecto()->get_www_temp($temp_nombre);
		$temp_fp = fopen($temp_archivo['path'], 'w');
		imagepng($img, $temp_archivo['path'],5);
		fclose($temp_fp);
		imagedestroy($img);		
		return "<img src='{$temp_archivo['url']}' style='border:none'>";
	}
	
}

?>