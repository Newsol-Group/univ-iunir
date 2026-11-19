<?php
/**
 *
 * 
 * @package 	Cursadas
 * @subpackage 	Inscripciones
 * 
 */
class ci_edi_excepcion_insc_pendientes extends guarani_pers_ci
{
	protected $s__inscripciones_a_exceptuar;

	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		if (guarani_arreglos::count($this->cn()->get_insc_no_exitosas()) == 0) {
			$pantalla->eliminar_dep('form_autocompletar');
			$pantalla->eliminar_dep('form_ml_pendientes');
		}
	}

	function get_inscripciones_a_exceptuar()
	{
		return $this->s__inscripciones_a_exceptuar;
	}

	function resetear()
	{
		unset($this->s__inscripciones_a_exceptuar);
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_aceptadas -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_aceptadas(toba_ei_cuadro $cuadro)
	{
		$etiqueta = guarani_etiquetas::get_tabla('sga_insc_cursada');
		$cuadro->set_eof_mensaje(guarani_mensajes::get('800_eof_cuadro', array($etiqueta->get_enf('plural'))));

		// Obtengo el listado de inscr. que superaron todas las validaciones y fueron aceptadas.
		$insc_aceptadas = $this->cn()->get_insc_exitosas();
		$where = guarani_sql::armar_condicion_in($insc_aceptadas, 'sga_insc_cursada', 'inscripcion', 'inscripcion');
		$datos = guarani::co('co_inscripciones_cursadas')->get_listado_inscripciones($where);
		$cuadro->set_datos($datos);
	}

	//-----------------------------------------------------------------------------------
	//---- form_ml_pendientes -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_ml_pendientes(toba_ei_formulario_ml $form_ml)
	{
		if (!isset($this->s__inscripciones_a_exceptuar)) {
			$insc_pendientes = $this->cn()->get_insc_no_exitosas();
			$where = guarani_sql::armar_condicion_in($insc_pendientes, 'sga_insc_cursada', 'inscripcion', 'inscripcion');
			$this->s__inscripciones_a_exceptuar = guarani::co('co_inscripciones_cursadas')->get_listado_inscripciones($where);
		}
		$form_ml->set_datos($this->s__inscripciones_a_exceptuar);
	}

	function evt__form_ml_pendientes__modificacion($datos)
	{
		$this->s__inscripciones_a_exceptuar = $datos;
	}

	function evt__form_ml_pendientes__info_errores($seleccion)
	{
		$inscripcion = $this->s__inscripciones_a_exceptuar[$seleccion];

		$mensajes = $this->cn()->get_mensajes_error($inscripcion['inscripcion']);
		validador_puntos_de_control::agregar_al_notificador(toba::notificacion(), $mensajes);

		throw new guarani_error(null, null, 'Controles Pendientes:');
	}

}
?>