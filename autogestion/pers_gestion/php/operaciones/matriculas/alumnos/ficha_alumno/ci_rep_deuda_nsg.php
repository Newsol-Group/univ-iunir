<?php
class ci_rep_deuda_nsg extends ci_reporte_ficha_alumno
{
	//--------------------------------------------------------------------------
	//---- cuadro --------------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{		
		$datos = $this->get_datos('');
		$cuadro->set_datos($datos);
		if (empty($datos)) {
			$msg = guarani_mensajes::get('800_eof_cuadro', array('Inscripciones y/o Materias aprobadas'));
			$cuadro->set_eof_mensaje($msg);
			$this->pantalla('pant_inicial')->eliminar_evento('pdf');
		}
	}
	
	function get_datos($filtro)
	{
		$persona = $this->controlador()->controlador()->get_persona_seleccionada();
		return toba::consulta_php('co_facturacion')->get_deuda($persona);
	}		

}
?>