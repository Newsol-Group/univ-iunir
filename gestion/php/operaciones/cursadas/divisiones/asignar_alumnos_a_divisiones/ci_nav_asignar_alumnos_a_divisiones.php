<?php
/**
 *
 *
 * @package 	Cursadas
 * @subpackage 	Divisiones
 * 
 */
class ci_nav_asignar_alumnos_a_divisiones extends ci_navegacion
{
	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		if ($this->dep('editor')->get_turno_estricto()) {
			$mje = guarani_mensajes::get('800CUR_info_elemento_distinto_turno', array('alumnos'));
			$pantalla->evento('guardar')->set_msg_confirmacion($mje);
		}
	}

	function evt__edicion__salida()
	{
		$this->dep('editor')->resetear_variables();
	}

	function get_seleccion_division()
	{
		return $this->s__seleccion;
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__guardar()
	{
		if ($this->dep('editor')->get_turno_estricto()) {
			if ($this->dep('editor')->get_turno_estricto_alumnos_divisiones() == comunes::si) {
				$mje = guarani_mensajes::get('800CUR_error_elemento_distinto_turno', array('alumnos'));
				throw guarani::error($mje);
			}
		}
		parent::evt__guardar();
	}

	function js_evt_guardar()
	{
		return '';
	}

}
?>