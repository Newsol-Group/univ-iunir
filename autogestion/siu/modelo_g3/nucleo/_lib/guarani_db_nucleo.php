<?php
class guarani_db_nucleo
{

	static function consultar($sql, $tipo_fetch = null, $filtrar_perfil_de_datos = true, $usar_cache = false, $clave_cache = '', $id_fuente = null)
	{

		/*
		 * @see http://php.net/manual/es/functions.arguments.php
		 */
		$tipo_fetch = (is_null($tipo_fetch))? PDO::FETCH_ASSOC : $tipo_fetch;

		if ($filtrar_perfil_de_datos) {
			toba::manejador_sesiones()->set_perfiles_datos_activos(toba::manejador_sesiones()->get_perfiles_datos());
			$sql = toba::perfil_de_datos()->filtrar($sql);
		} else {
			// Solo para Gestión, Autogestión ya registra el SQL en los logs y se duplicaría.
			if (comunes::es_interfaz_gestion()){
				toba::logger()->debug('SQL sin perfil de datos: ' .$sql);
			}
		}
		
		if ($usar_cache) {
			if (empty($clave_cache)) {
				$clave_cache = guarani::cache()->generar_clave($sql);
			}
			if (guarani::cache()->existe($clave_cache)) {
				$result = guarani::cache()->buscar($clave_cache);
				toba::logger()->info('[SQL resuelta por caché] ' . $sql);
			} else {
				$result = toba::db($id_fuente)->consultar($sql, $tipo_fetch);
				guarani::cache()->guardar($clave_cache, $result);
			}
		} else {
			$result = toba::db($id_fuente)->consultar($sql, $tipo_fetch);
		}
		return $result;
	}

	static function consultar_cache($sql, $clave_cache = '', $filtrar_perfil_de_datos = true, $id_fuente = null)
	{
		return static::consultar($sql, PDO::FETCH_ASSOC, $filtrar_perfil_de_datos, true, $clave_cache, $id_fuente);
	}

	static function consultar_fila($sql, $tipo_fetch = null, $filtrar_perfil_de_datos = true, $usar_cache = false, $clave_cache = '', $id_fuente = null)
	{
		
		/*
		 * @see http://php.net/manual/es/functions.arguments.php
		 */
		$tipo_fetch = (is_null($tipo_fetch))? PDO::FETCH_ASSOC : $tipo_fetch;
		
		if ($filtrar_perfil_de_datos) {
			$sql = toba::perfil_de_datos()->filtrar($sql);
		} else {
			// Solo para Gestión, Autogestión ya registra el SQL en los logs y se duplicaría.
			if (comunes::es_interfaz_gestion()){
				toba::logger()->debug('SQL sin perfil de datos: ' .$sql);
			}
		}
		
		if ($usar_cache) {
			if (empty($clave_cache)) {
				$clave_cache = guarani::cache()->generar_clave($sql);
			}
			if (guarani::cache()->existe($clave_cache)) {
				$result = guarani::cache()->buscar($clave_cache);
				toba::logger()->info('[SQL resuelta por caché] ' . $sql);
			} else {
				$result = toba::db($id_fuente)->consultar_fila($sql, $tipo_fetch);
				guarani::cache()->guardar($clave_cache, $result);
			}
		} else {
			$result = toba::db($id_fuente)->consultar_fila($sql, $tipo_fetch);
		}
		return $result;
	}
	
	static function consultar_fila_cache($sql, $clave_cache = '', $filtrar_perfil_de_datos = true, $id_fuente = null)
	{
		return static::consultar_fila($sql, PDO::FETCH_ASSOC, $filtrar_perfil_de_datos, true, $clave_cache, $id_fuente);
	}

	static function ejecutar($sql, $retornar_resultado = false, $deshabilitar_transaccion = false, $tipo_fetch = null, $filtrar_perfil_de_datos = false, $id_fuente = null)
	{
		/*
		 * @see http://php.net/manual/es/functions.arguments.php
		 */
		$tipo_fetch = is_null($tipo_fetch) ? PDO::FETCH_ASSOC : $tipo_fetch;

		if ($filtrar_perfil_de_datos) {
			$sql = toba::perfil_de_datos()->filtrar($sql);
		} else {
			// Solo para Gestión, Autogestión ya registra el SQL en los logs y se duplicaría.
			if (comunes::es_interfaz_gestion()){
				$sql_log = $sql;
				// Puede venir un array de queries
				if (is_array($sql_log)) {
					$sql_log = implode("; ", $sql_log);
				}
				toba::logger()->debug('SQL sin perfil de datos: ' .$sql_log);
			}
		}
		
		if ($retornar_resultado) {
			if (!$deshabilitar_transaccion) {
				toba::db($id_fuente)->abrir_transaccion();
			}
			$resultado = toba::db($id_fuente)->consultar($sql, $tipo_fetch);
			if (!$deshabilitar_transaccion) {
				toba::db($id_fuente)->cerrar_transaccion();
			}
		} else {
			$resultado = toba::db($id_fuente)->ejecutar($sql);
		}
		return $resultado;
	}
}