<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos de Ingreso
 * 
 */
class cn_ent_prorrogar_presentacion extends cn_entidad
{
	
	function set_datos_prorrogar_presentacion($datos, $periodo_inscripcion)
	{
		$this->validar_datos_periodos($datos, $periodo_inscripcion);
		$this->tabla('requisitos_ingreso_excep')->procesar_filas($datos);
	}

	function get_datos($tabla=null)
	{
		return $this->tabla('requisitos_ingreso_excep')->get_filas();
	}

	/**
	 * Se redefine el método porque el original retorna el identificador del registro agregado en la tabla cabecera de la relación.
	 * En este caso, la tabla cabecera (única en la relación) posee más de un registro.
	 * 
	 */
	function sincronizar()
	{
		$this->dep('datos')->sincronizar();
	}

	//--------------------------------------------------------------------------
	//------ Validaciones ------------------------------------------------------
	//-------------------------------------------------------------------------- 

	function validar_datos_periodos($datos, $periodo_inscripcion)
	{
		$periodo_inscripcion = toba::consulta_php('co_periodos_de_inscripcion')->get_datos_periodo_inscripcion_propuesta($periodo_inscripcion);

		foreach ($datos as $d) {
			// La fecha tope de presentación del requisito de ingreso deber ser distinta a la fecha de cierre de requisitos del período de inscripción.
			if (guarani_fecha::es_igual($d['fecha_tope'], $periodo_inscripcion['fecha_cierre_requisitos'])) {
				$this->set_error('800_control_fechas', array('tope de presentación', 'igual', 'de cierre de presentación de requisitos del período de inscripción.'));
				break;
			}
		}

		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}
}
?>