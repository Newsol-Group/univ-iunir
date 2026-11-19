<?php
/**
 *
 *
 * @package Asistencias
 * @subpackage -
 */
class ci_edi_justificaciones_ausencias_clases extends ci_edicion_simple
{
	//-----------------------------------------------------------------------------------
	//---- Pantalla Inicial -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	//---- formulario -------------------------------------------------------------------

	function conf__formulario(toba_ei_formulario $form)
	{
		// Si debe indicarse la cantidad de inasistencias, se desactivan los campos correspondientes.
		if (!$this->controlador()->indica_cantidad_inasistencias()) {
			$form->desactivar_efs(array('cant_inasistencias', 'cant_justificadas'));
		}
		parent::conf__formulario($form);
	}
	
	function evt__formulario__modificacion($datos)
	{
		if (!isset($datos['cant_justificadas'])) {
				$datos['cant_justificadas'] = 1;
		}
		
		if ($datos['estado_justificacion'] == asistencia::estado_ausencia_rechazado) {
			$datos['cant_justificadas'] = 0;
		} 

		try {
			$this->cn()->entidad()->set_datos($datos);	
		} catch ( guarani_error $e ) {
			$this->datos_temp = $datos;
			throw $e;
		}
	}

}
?>
