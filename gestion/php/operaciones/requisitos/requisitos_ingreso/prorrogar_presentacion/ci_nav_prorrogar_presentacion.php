<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos de Ingreso
 * 
 */
class ci_nav_prorrogar_presentacion extends ci_navegacion
{
	/**
	 *	Permite recuperar el mensaje toba actualmente asociado al eof del cuadro.
	 */	
	function get_msg_eof($dep=null)
	{
		$indx_msg = $this->indx_msg_eof;
		if (isset($this->s__filtro) && ! empty($this->s__filtro)) {
			$indx_msg = $this->indx_msg_eof . '_filtrado';
		}
		return guarani_mensajes::get($indx_msg, array('Períodos de Inscripción a Propuestas'));
	}
	
	function conf__filtro(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos)) {
			$filtro->set_datos($this->s__datos);
		} else {
			$filtro->set_datos(array('anio_academico' => array('condicion' => 'es_igual_a', 'valor' => guarani_fecha::get_hoy_parte('anio'))));
			parent::evt__filtro__filtrar($filtro->get_datos());
		}	
	}
	
	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->get_listado_prorrogar_presentacion($this->get_filtro_condicion());
		$cuadro->set_datos($datos);
		$msj = $this->get_msg_eof();
		$cuadro->set_eof_mensaje($msj);
	}

	function get_periodo_inscripcion_seleccionado()
	{
		return $this->s__seleccion['periodo_inscripcion'];
	}
	
	function evt__cancelar()
	{
		parent::evt__cancelar();
		$this->dep('editor')->unset_vars();
	}
	
}
?>