<?php
/**
 * Catálogo de tipos de procesos
 *
 *	Atencion: esta clase se instancia con: guarani::catalogo_procesos()
 *
 * @package 
 */
abstract class guarani_procesos_bk
{
	protected $id_log_proceso;
	protected $carpeta_log;
	
	protected $path_proceso_serializado;
	protected $log;
	protected $log_estado;

	protected $reportes;
	protected $usuario;
	
	protected $abortar = false;

	const archivo_resultados = 'resultados';
	const archivo_estado = 'estado';
	
	function __construct()
	{
		$proceso = get_class($this);
		$this->carpeta_log = $proceso . '__'. date("Ymd_His___" . rand(1000, 9999));
		$this->path_proceso_serializado = guarani_logs::get_carpeta_logs() . $this->carpeta_log . "/" . uniqid($proceso."_");
		$this->id_log_proceso = guarani::act('act_procesos_bk')->insertar_log_proceso($proceso, $this->carpeta_log);
		// Archivos de log
		$this->log = new guarani_logs_resultados(self::archivo_resultados, false, $this->carpeta_log);
		$this->log_estado = new guarani_logs_estados(self::archivo_estado, false, $this->carpeta_log);
		// Usuario que ejecutó el procoeso
		$this->usuario = toba::usuario()->get_id();
	}
	
	function get_id_log_proceso()
	{
		return $this->id_log_proceso;
	}
	
	function get_nombre_carpeta()
	{
		return $this->carpeta_log;
	}
	
	function get_nombre()
	{
		return self::nombre;
	}
	
	function detener_procesamiento()
	{
		$this->log_estado->abortar();
	}
	
	function get_resultado($reporte)
	{
		return $this->log->get($reporte);
	}

	function get_estado()
	{
		$mensajes = $this->log_estado->get();
		if (!empty($mensajes)) {
			return $mensajes;
		} else {
			return array();
		}
	}

	function get_progreso()
	{
		return $this->log_estado->get_progreso();
	}

	function get_path_proceso_serializado()
	{
		return $this->path_proceso_serializado;
	}
	
	function get_clase()
	{
		return get_class($this);
	}
	
	function set_usuario($usuario)
	{
		$this->usuario = $usuario;
	}

	function get_usuario()
	{
		return $this->usuario;
	}
}