<?php
class ci_rep_excepciones extends ci_reporte_ficha_alumno
{
	//--------------------------------------------------------------------------
	//---- cuadro --------------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{		
		$datos = $this->get_datos('');
		$cuadro->set_datos($datos);
		if (empty($datos)) {
			$msg = guarani_mensajes::get('800_eof_cuadro', array('Excepciones'));
			$cuadro->set_eof_mensaje($msg);
			$this->pantalla('pant_inicial')->eliminar_evento('pdf');
		}
	}
	
	function get_datos($filtro)
	{
		$persona = $this->controlador()->controlador()->get_persona_seleccionada();
		$where = "sga_requisitos_excepciones.alumno IN (SELECT alumno FROM sga_alumnos WHERE sga_alumnos.persona = $persona)";
		return toba::consulta_php('co_requisitos')->get_requisitos_excepciones($where);
	}
}

?>