<?php
/**
 *
 *
 * @package Asistencias
 * @subpackage -
 */
class ci_edi_ausencias_examen extends ci_edicion_simple
{
	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		if ($this->cn()->entidad()->es_nueva()) {
			$this->controlador()->pantalla('edicion')->eliminar_evento('eliminar');
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Pantalla Inicial -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	//---- formulario -------------------------------------------------------------------

	function conf__formulario(toba_ei_formulario $form)
	{
		$form->set_datos_defecto(array('estado' => asistencia::estado_ausencia_aceptado));
		parent::conf__formulario($form);
	}

	function evt__formulario__modificacion($datos)
	{
		$datos_acta =  $this->controlador()->get_datos_acta();
		$datos['id_acta'] = $datos_acta['id_acta'];
		$datos['alumno'] = $this->controlador()->get_preseleccion();
		parent::evt__formulario__modificacion($datos);
	}
}
?>