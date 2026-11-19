<?php
/**
 * 
 */
class invocador_procesos_bk
{
	static protected $instancia;
	
	const nombre_archivo_log_ejecucion = 'log_ejecucion.txt';

	static function instancia()
	{
		if (!isset(self::$instancia)) {
			self::$instancia = new invocador_procesos_bk();
		}
		return self::$instancia;	
	}

	private function __construct()
	{
	
	}
	
	public function ejecutar_proceso($proceso)
	{
		// Serializo el Proceso
		$proc_serializado = serialize($proceso);
		// Se guarda el proceso serializado con path que se genero en la creacion del porceso
		$path_procesos_serializado = $proceso->get_path_proceso_serializado();
		toba_manejador_archivos::crear_archivo_con_datos($path_procesos_serializado, $proc_serializado);
		// Se dispara el item de consola con el ID
		
		if (toba_manejador_archivos::es_windows()) {
			$script = 'script_proceso.bat';
		} else {
			$script = 'script_proceso.sh';
		}
		$path_script = toba::proyecto()->get_path_php() . "/nucleo/_lib/procesos_bk/";
		$path_toba = toba_dir();
		$path_temp = toba::proyecto()->get_path_temp();
		$path_instalacion = toba::proyecto()->get_path() . '/instalacion';
		$instancia = toba::instancia()->get_id();
		$path_carpeta_procesos_bk = guarani_logs::get_carpeta_logs();
		$path_archivo_log = $path_carpeta_procesos_bk . $proceso->get_nombre_carpeta() . '/' . self::nombre_archivo_log_ejecucion;
		$usuario = toba::usuario()->get_id();
		
		$comando = $path_script . "$script $path_toba $path_carpeta_procesos_bk $path_instalacion $instancia 5000118 $path_procesos_serializado $path_archivo_log $usuario";
		
		toba_manejador_procesos::background($comando);
	}
}
?>