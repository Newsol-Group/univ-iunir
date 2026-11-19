<?php
class ci_nav_rechazo_insc_examenes_pendientes extends ci_nav_sin_listado_inicial
{
	protected $s__motivo_rechazo;

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		// El formulario de motivo de rechazo se muestra cuando hay datos en el cuadro
		if (empty($this->dep('cuadro')->get_datos())) {
			$pantalla->eliminar_dep('form_motivo_rechazo');
			$pantalla->eliminar_evento('rechazar');
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_motivo_rechazo ----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__form_motivo_rechazo__modificacion($datos)
	{
		$this->s__motivo_rechazo = $datos;
	}

	function conf__form_motivo_rechazo(toba_ei_formulario $form)
	{
		if (isset($this->s__motivo_rechazo)) {
			$form->set_datos($this->s__motivo_rechazo);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('anio_academico')->set_condicion_fija('es_igual_a');
		$filtro->columna('turno_examen')->set_condicion_fija('es_igual_a');
		$filtro->columna('actividad')->set_condicion_fija('contiene');

		// Si se filtró, se colapsa el filtro para dejar como elementos centrales
		// al formulario de motivo de rechazo y los alumnos seleccionables
		if (!empty($this->s__datos)) {
			$filtro->colapsar();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cuadro__seleccion($datos)
	{
		$this->s__seleccion = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__rechazar()
	{
		if (empty($this->s__seleccion)) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('una inscripción'));
			throw guarani::error($mje);
		}
		$this->cn()->rechazar_inscripciones($this->s__seleccion, $this->s__motivo_rechazo['motivo_rechazo']);
	}

}
?>