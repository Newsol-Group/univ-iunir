<?php
namespace siu\modelo\entidades;

use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;
use SIU\Chulupi\util\ManejadorArchivos;
use SIU\Chulupi\util\img;

class alumno_foto
{
	const TAMANIO_CHICA = 'chica';
	const TAMANIO_MEDIANA = 'mediana';
	const TAMANIO_GRANDE = 'grande';
	const IMG_DEFECTO = 'mm_medio.png';

	static function importar_foto_blob($persona, $blob, $ext)
	{
		$id = static::generar_id();
		$carpeta = static::generar_carpeta($persona);
		$id_imagen = $carpeta.'_'.$id.'.'.$ext;
		$res_blob = img::get_resource_blob($blob, $ext);

		$sizes = array(	'chica'  => array('dim' => 35, 'directorio' => self::TAMANIO_CHICA),
						'mediana'  => array('dim' => 90, 'directorio' => self::TAMANIO_MEDIANA),
						'grande' => array('dim' => 150, 'directorio' => self::TAMANIO_GRANDE));
		foreach ($sizes as $size) {
			$dir = kernel::proyecto()->get('imagen_alumno_dir'). '/' . $size['directorio']. '/' . $carpeta;
			ManejadorArchivos::crear_arbol_directorios($dir);
			$img_resized = img::resize_resource($res_blob['img'], $res_blob['width'], $res_blob['height'], $size['dim']);
			$path_resized = $dir.'/'.$id_imagen;
			img::guardar($img_resized, $path_resized);
            //Borro todas las imagenes previas para no acumularlas (mantiene solo la nueva)
            ManejadorArchivos::borrar_contenido_directorio($dir, array($id_imagen));
		}

		static::asociar_imagen($persona, $id_imagen);
	}

	protected static function generar_id()
	{
		return sha1(uniqid(rand(0, 100), true));
	}

	protected static function generar_carpeta($persona)
	{
		$salt = kernel::proyecto()->get('salt');
		return md5($salt . $persona);
	}

	protected static function asociar_imagen($persona, $id_imagen)
	{
		$parametros = array();
		$parametros['persona'] = $persona;
		$parametros['id_imagen'] = $id_imagen;
		catalogo::consultar('persona', 'asociar_foto', $parametros);
	}

    /**
     * URL imagen absoluta de una persona
     */
	static function url_imagen($id_imagen = null, $tamanio = self::TAMANIO_CHICA)
	{
        if(is_null($id_imagen)){
            return kernel::proyecto()->get('imagen_alumno_url').'/'.static::IMG_DEFECTO;
        }

		$carpeta = substr($id_imagen, 0, 32);
		return kernel::proyecto()->get('imagen_alumno_url')."/$tamanio/$carpeta/$id_imagen";
	}

    /**
     * URL imagen relativa de una persona
     */
    static function url_imagen_relativa_kolla($id_imagen = null, $tamanio = self::TAMANIO_MEDIANA)
    {
        if(is_null($id_imagen)){
            return static::IMG_DEFECTO;
        }

        $carpeta = substr($id_imagen, 0, 32);
        return "$tamanio/$carpeta/$id_imagen";
    }

}
?>