<?php
/**
 *
 * 
 * @package		Cursadas
 * @subpackage	Inscripciones
 * 
 */
class ci_nav_revertir_rechazo_insc_actividades extends ci_navegacion
{
	function conf()
	{
		parent::conf();
		$this->set_sin_listado_inicial();
	}
	
	//-----------------------------------------------------------------------------------
	//---- Cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_msg_eof($etiqueta = null)
	{
		$indx_msg = $this->indx_msg_eof;
		if (isset($this->s__filtro) && !empty($this->s__filtro)) {
			$indx_msg = $this->indx_msg_eof . '_filtrado';
		}
		return guarani_mensajes::get($indx_msg, array('Rechazos a Inscripción de Actividades'));
	}

	/*
	 * Se setea el msj de confirmación del evento rechazar con los datos correspondientes al 
	 * registro de incripción de cada fila.
	 */
	function conf_evt__cuadro__revertir(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dependencia('cuadro')->get_datos();
		$comision = $datos[$fila]['comision_nombre'];
		$alumno = $datos[$fila]['apellido_y_nombre'];
		$msj = guarani_mensajes::get('800CUR_confirmar_revertir', array($comision, $alumno));
		$evento->set_msg_confirmacion($msj);
	}

	function evt__cuadro__revertir($seleccion)
	{
		if (!empty($seleccion)) {
			$this->cn()->rechazar_inscripciones(current($seleccion));
		}
	}

}
?>