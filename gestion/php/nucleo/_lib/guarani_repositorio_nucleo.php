<?php
class guarani_repositorio_nucleo
{
	//--------------------------------------------------------------------------
	//---- Repositorio de archivos adjuntos mensajería -------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Almacena en una carpeta temporal un archivo subido.
	 *
	 * @param	array	$datos	datos del archivo temporal.
	 * @return	string			path de la ubicación temporal del archivo.
	 */
	static function subir_archivo_temp($datos)
	{
		if (!empty($datos)) {
			$path_carpeta_temp = toba::proyecto()->get_path_temp().'/archivos/repositorio/'.uniqid();
			if (!file_exists($path_carpeta_temp)) {
				// Se crea una carpeta temporal única para el archivo a subir
				mkdir($path_carpeta_temp, 0700, true);
			}
			
			// Se mueven los archivos subidos al servidor del directorio temporal PHP al recientemente creado
			move_uploaded_file($datos['tmp_name'], $path_carpeta_temp.'/'.$datos['name']);
		}
		return $path_carpeta_temp . '/' . $datos['name'];
	}
	
	/**
	 * Elimina un archivo.
	 *
	 * @param 	string 	$path						path del archivo.
	 * @param 	boolean	$eliminar_carpeta_vacia		determina si se debe eliminar también la carpeta contenedora si 
	 * 												el archivo a eliminar era el único en esa carpeta.
	 */
	static function eliminar_archivo($path, $eliminar_carpeta_vacia=true)
	{
		// Se elimina el archivo
		if (file_exists($path)) {
			unlink($path);
			
			// Si la carpeta queda vacía, se elimina también la carpeta
			if ($eliminar_carpeta_vacia) {
				$carpeta = substr($path, 0, strrpos($path, '/'));
				$explorar = scandir($carpeta); 
				if (guarani_arreglos::count($explorar) == 2) {
					rmdir($carpeta);
				}
			}
		}
	}
	
	static function mover_temp_al_repositorio($path)
	{
		if (file_exists($path)) {
			$raiz_repositorio = guarani::parametros()->get_valor('repositorio_raiz', null, false);
			
			$partes = explode('/', $path);
			$cant_partes = guarani_arreglos::count($partes);
			$carpeta_repositorio = toba::proyecto()->get_path() . '/' . $raiz_repositorio . '/' . $partes[$cant_partes-2];
			
			if (!file_exists($carpeta_repositorio)) {
				mkdir($carpeta_repositorio, 0700, true);
			}
			
			$nuevo_path = $carpeta_repositorio . '/' . $partes[$cant_partes-1];
			
			copy($path, $nuevo_path);
			static::eliminar_archivo($path);
			return $nuevo_path;
		}
	}
	
	//--------------------------------------------------------------------------
	//---- Repositorio de documentos digitales ---------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * 
	 * Reubica un archivo temporal dentro del repositorio de documentos digitales
	 * 
	 * @param string $path_temp: path absoluto donde se encuentra actualmente el archivo
	 * @param string $nuevo_nombre: nombre que recibirá el archivo en el repositorio
	 * @param string $subcarpeta: subcapeta(s) dentro del repositorio de documentos digitales
	 * @return string: path de la nueva ubicación del archivo
	 */
	static function mover_temp_al_repositorio_doc_dig($path_temp, $nuevo_nombre, $subcarpeta='')
	{
		if (file_exists($path_temp)) {
			$carpeta_repositorio = guarani::parametros()->get_valor('repositorio_documentos_digitales', null, false);
			
			if (!empty($subcarpeta)) {
				$carpeta_repositorio .= '/' . $subcarpeta;
			}
			
			if (!file_exists($carpeta_repositorio)) {
				mkdir($carpeta_repositorio, 0700, true);
			}
			
			$nuevo_path = $carpeta_repositorio . '/' . $nuevo_nombre;
			
			copy($path_temp, $nuevo_path);
			static::eliminar_archivo($path_temp);
			return $nuevo_path;
		}
	}
}
?>