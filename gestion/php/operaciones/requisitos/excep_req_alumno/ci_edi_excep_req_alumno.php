<?php
/**
 * 
 *
 * @package 	Requisitos
 * @subpackage 	-
 * 
 */
class ci_edi_excep_req_alumno extends ci_edicion_simple 
{
	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	 * Método de carga para el combo Actividad (para seleccionar una actividad específica a exceptuar del requisito).
	 * Retorna las actividades correspondientes al plan actual del alumno.
	 */
	function get_actividades_plan_actual_alumno()
	{
		$datos_alumno = $this->controlador()->get_datos_alumno();
		return toba::consulta_php('co_elementos')->get_actividades_plan_version($datos_alumno['plan_version']);
	}
	
	function conf__formulario(toba_ei_formulario $form)
	{
		if(isset($this->datos_temp)) {
			$this->controlador->set_hubo_cambios();
			$datos = $this->datos_temp;
		} else {
			$datos = $this->cn()->entidad()->get_datos();
		}
		$form->set_datos($datos);

		// Si la excepción está vigente (fecha desde <= hoy < fecha hasta) sólo se puede modificar la fecha hasta y las observaciones.
		if (! $this->cn()->entidad_es_nueva() && guarani_fecha::esta_entre_fechas(guarani_fecha::get_hoy(), $datos['fecha_desde'], $datos['fecha_hasta'])) {
			$form->set_solo_lectura(array('accion', 'requisito', 'fecha_desde'));
		}
	}

	function evt__formulario__modificacion($datos)
	{
		$persona = toba::consulta_php('co_personas')->get_persona_x_usuario(toba::usuario()->get_id());
		$datos['persona'] = $persona['persona'];
		$datos['alumno'] = $this->controlador()->get_preseleccion();
		$datos_alumno = $this->controlador()->get_datos_alumno();
		$datos['plan_version'] = $datos_alumno['plan_version'];

		parent::evt__formulario__modificacion($datos);
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_requisitos_plan_version_accion($accion)
	{
		$datos_alumno = $this->controlador()->get_datos_alumno();
		return toba::consulta_php('co_requisitos')->get_requisitos_plan_version_accion($datos_alumno['plan_version'], $accion);
	}

	function get_acciones()
	{
		return toba::consulta_php('co_acciones')->get_listado();
	}
}
?>