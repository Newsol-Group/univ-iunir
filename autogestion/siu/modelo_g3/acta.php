<?php

namespace siu\modelo_g3;

use SIU\Chulupi\kernel;

class acta extends \acta 
{
       
    protected static function get_path_reporte_jasper($plantilla)
    {
        return estatica('guarani', 'get_path_rep_jasper', [$plantilla]);
    }
    
    protected static function get_path_logo_impresion($logo = '')
    {
        if (empty($logo)) {
            $logo = 'logo-transparente.png';
        }
        return kernel::localizador()->encontrar_path_archivo('www/img', $logo);
    }

	/**
	  * Genera el documento, y devuelve la URL de donde descargar el archivo.
	  *
	  * @param string $archivo Archivo a subir en SIU-Araí Documentos.
	  *
	  * @return string URL de donde descargar el archivo.
	  */
    public static function get_url_file($archivo)
    {
        $dir_attachment = kernel::proyecto()->get_dir_attachment();
		$path_carpeta = $dir_attachment . '/adjuntos_documentos/' . uniqid();
		if (!file_exists($path_carpeta)) {
			//Se crea una carpeta única para el archivo a subir
			mkdir($path_carpeta, 0777, true);
		}
		$nombre_archivo = basename($archivo);
		$size_archivo = filesize($archivo);
		$ext_archivo = pathinfo($archivo, PATHINFO_EXTENSION);
		$path_archivo = $path_carpeta. '/' . $nombre_archivo;
		copy($archivo, $path_archivo);
		// Guardo el adjunto en la tabla 'rep_archivos'.
		$id_archivo = \guarani::act('act_mensajes')->insertar_rep_archivo($path_archivo, $nombre_archivo, $size_archivo, $ext_archivo);
		$datos_adjunto = \toba::consulta_php('co_mensajes')->get_archivo_adjunto($id_archivo);

		return $datos_adjunto['url_archivo'];
    }
    
}