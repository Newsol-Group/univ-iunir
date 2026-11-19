<?php
/**
 *
 *
 * @package     Matrícula
 * @subpackage  Regularidad
 * 
 */
class ci_pro_verificar_regularidad_alumno extends ci_nav_procesos_bk
{
	function set_nombre_proceso()
	{
		$this->s__nombre_proceso = 'pro_verificar_regularidad_alumno';
	}

	/**
	 * Se agrega esta funcion para mostrar los resultados en el form de resultados
	 */
	function evt__cuadro_resultados_ant__seleccion($seleccion)
	{
		$datos_log = toba::consulta_php('co_procesos_bk')->get_log_proceso($seleccion['log_proceso']);
		$carpeta_logs = guarani_logs::get_carpeta_logs() . $datos_log['carpeta_logs'];
		$this->s__ruta_archivo_resultados = $carpeta_logs . '/' . guarani_procesos_bk::archivo_resultados . '.xml';

		$this->s__gl = new guarani_logs_resultados('dummy');
		$datos = $this->s__gl->get('1', $this->s__ruta_archivo_resultados);

		$this->s__datos_resultados = current($datos['mensajes']);
		$this->s__modo_visualizacion = true;
		$this->set_pantalla('resultados_visualizacion');
	}

}
?>