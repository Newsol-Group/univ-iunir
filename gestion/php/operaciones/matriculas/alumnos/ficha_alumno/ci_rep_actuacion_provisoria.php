<?php
class ci_rep_actuacion_provisoria extends ci_reporte_ficha_alumno
{
	//--------------------------------------------------------------------------
	//---- cuadro --------------------------------------------------------------
	//--------------------------------------------------------------------------

	/*
	 * Cuadro de actuación provisoria de cursada
	 */
	function conf__cuadro(toba_ei_cuadro $cuadro)
	{		
		$datos = $this->get_datos('');
		$cuadro->set_datos($datos);
		if (empty($datos)) {
			$msg = guarani_mensajes::get('800CUR_eof_act_provisoria');
			$cuadro->set_eof_mensaje($msg);
			$this->pantalla('pant_inicial')->eliminar_evento('pdf');
		}
	}
	
	function get_datos($filtro)
	{
		$persona = $this->controlador()->controlador()->get_persona_seleccionada();
		return toba::consulta_php('co_alumnos')->get_actuacion_provisoria_cursada($persona);
	}
	
	/*
	 * Cuadro de actuación provisoria de promocion
	 */
	function conf__cuadro_promociones(toba_ei_cuadro $cuadro)
	{		
		$persona = $this->controlador()->controlador()->get_persona_seleccionada();
		$datos = toba::consulta_php('co_alumnos')->get_actuacion_provisoria_promocion($persona);
		$cuadro->set_datos($datos);
		if (empty($datos)) {
			$msg = guarani_mensajes::get('800CUR_eof_act_provisoria');
			$cuadro->set_eof_mensaje($msg);			
		}
	}
	
	/*
	 * Cuadro de actuación provisoria de examenes
	 */
	function conf__cuadro_examenes(toba_ei_cuadro $cuadro)
	{		
		$persona = $this->controlador()->controlador()->get_persona_seleccionada();
		$datos = toba::consulta_php('co_alumnos')->get_actuacion_provisoria_examen($persona);
		$cuadro->set_datos($datos);
		if (empty($datos)) {
			$msg = guarani_mensajes::get('800CUR_eof_act_provisoria');
			$cuadro->set_eof_mensaje($msg);			
		}
	}

}
?>