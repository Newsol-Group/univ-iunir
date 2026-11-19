<?php
/**
 * Control de morosidad para las incorporaciones a evaluaciones.
 */
class cn_ent_carga_notas_evaluaciones_nsg extends cn_ent_carga_notas_evaluaciones
{
	function pre_sincronizar()
	{
		parent::pre_sincronizar();
		$this->validar_incorporaciones_no_morosas();
	}

	function cerrar_evaluacion()
	{
		// El cierre base sincroniza el DR directamente, sin pre_sincronizar.
		$this->validar_incorporaciones_no_morosas();
		parent::cerrar_evaluacion();
	}

	protected function validar_incorporaciones_no_morosas()
	{
		// Se revisan las filas que se van a persistir, no las selecciones previas.
		$morosos = toba::consulta_php('co_cobros')->get_morosos_para_incorporar_evaluacion(
			$this->get_detalles(), $this->get_columna_evaluacion('evaluacion')
		);
		if (empty($morosos)) {
			return;
		}

		$nombres = array();
		foreach ($morosos as $moroso) {
			$nombre = isset($moroso['alumno_nombre']) ? $moroso['alumno_nombre'] : $moroso['alumno'];
			$nombres[$nombre] = $nombre;
		}
		throw guarani::error('No se pueden agregar a la evaluación los siguientes alumnos porque se encuentran bloqueados por deuda: '
			. implode(', ', $nombres) . '.');
	}
}
?>
