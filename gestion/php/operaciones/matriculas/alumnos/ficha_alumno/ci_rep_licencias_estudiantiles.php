<?php
class ci_rep_licencias_estudiantiles extends ci_reporte_ficha_alumno
{
	protected $s__datos_cuadro;

	//--------------------------------------------------------------------------
	//---- cuadro --------------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{		
		$datos = $this->get_datos('');
		$cuadro->set_datos($datos);
		if (empty($datos)) {
			$msg = guarani_mensajes::get('800_eof_cuadro', array('Licencias Estudiantiles cargadas'));
			$cuadro->set_eof_mensaje($msg);
			$this->pantalla('pant_inicial')->eliminar_evento('pdf');
		}
	}
	
	function conf_evt__cuadro__seleccion(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->cuadro()->get_datos();
		$datos_fila = $datos[$fila];		
		$evento->vinculo()->agregar_parametro(utf8_encode('licencia'), $datos_fila['licencia']);
	}
	
	function get_datos($filtro)
	{		
		$persona = $this->controlador()->controlador()->get_persona_seleccionada();
		$where = ' persona = '.quote($persona);
		return toba::consulta_php('co_licencias')->get_listado($where);
	}
}
?>