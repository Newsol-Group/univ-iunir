<?php
/**
 *
 * @package		Calendario
 * @subpackage	Períodos Lectivos
 */
class pro_calcular_coeficientes_inscripcion_nucleo extends guarani_procesos_bk
{
	protected $alumnos;

	const reporte_resultados = 1;
	const nombre = 'pro_calcular_coeficientes_inscripcion';

	function set_datos($datos = array())
	{
		$this->alumnos = $datos['alumnos'];
	}

	function procesar()
	{
		$cant_alumnos_procesados = 0;
		$paso = 100/guarani_arreglos::count($this->alumnos);
		foreach ($this->alumnos as $alumno) {
			$estado = $this->get_estado();
			if (isset($estado['abortar']) && $estado['abortar']) {
				$this->abortar = true;
				break;
			}
			$cant_alumnos_procesados++;
			try {
				toba::db()->abrir_transaccion();
				$coeficiente = alumno::get_coeficiente_inscripcion($alumno['alumno']);
				guarani::act('act_alumnos')->actualizar_coeficiente_inscripcion($alumno['alumno'], $coeficiente);
				toba::db()->cerrar_transaccion();
			} catch (toba_error $e) {
				$this->log_estado->set_error();
				toba::db()->abortar_transaccion();
			}
			$this->log_estado->incrementar_progreso($paso);
		}
		$mensaje = array();
		$mensaje['alumnos_procesados'] = $cant_alumnos_procesados;
		$this->log->set($mensaje, array(self::reporte_resultados));
		$this->log->guardar();
	}
}
?>