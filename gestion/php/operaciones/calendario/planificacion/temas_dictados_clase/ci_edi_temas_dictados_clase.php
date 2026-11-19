<?php
/**
 * 
 * 
 * @package 	Calendario Académico
 * @subpackage 	Planificación
 *
 */
class ci_edi_temas_dictados_clase extends ci_edicion_simple 
{
	protected $ev_info_clase;
	
	//-----------------------------------------------------------------------------------
	//---- ev_info_clase ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function ev_info_clase()
	{
		if (!isset($this->ev_info_clase)) {
			$this->ev_info_clase = guarani::ev_info_datos();
			$this->ev_info_clase->set_columnas_a_mostrar(array('fecha', 'hora_inicio', 'hora_finalizacion'));
			
			$this->ev_info_clase->set_columnas_fila(array('fecha', 'hora_inicio', 'hora_finalizacion'), 1);
			
			$this->ev_info_clase->set_max_columnas_por_fila(3);
			
			$this->ev_info_clase->set_etiqueta('fecha', 'Fecha');
			$this->ev_info_clase->set_etiqueta('hora_inicio', 'Inicio');
			$this->ev_info_clase->set_etiqueta('hora_finalizacion', 'Fin');
			
			$this->ev_info_clase->set_td_etiqueta_width('30px');
			$this->ev_info_clase->set_td_valores_width('200px');
		}
		return $this->ev_info_clase;
	}
	
	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__pant_datos()
	{
		$this->controlador()->ev_info()->set_datos($this->cn()->get_ev_info_datos());
		$this->ev_info_clase()->set_datos($this->cn()->get_ev_info_datos_clase());
	}
	
	function conf__pant_docentes()
	{
		$this->controlador()->ev_info()->set_datos($this->cn()->get_ev_info_datos());
		$this->ev_info_clase()->set_datos($this->cn()->get_ev_info_datos_clase());
	}
}
?>