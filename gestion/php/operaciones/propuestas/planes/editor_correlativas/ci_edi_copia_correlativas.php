<?php
class ci_edi_copia_correlativas extends ci_navegacion
{
	protected $s__seleccion;
	
	function set_seleccion($seleccion)
	{
		$this->s__seleccion = $seleccion;
	}
	
	function unset_vars()
	{
		unset($this->s__elemento);
	}
	
	/**
	 * Obtiene un listado de actividades asociadas al plan_version seleccionado,
	 * que tengan ya asignadas correlativas, excluyendo a la actividad seleccionada
	 * para realizar la copia
	 * 
	 */
	function get_datos()
	{
		$filtro = empty($this->s__filtro) ? '' : $this->s__filtro;
		if (!empty($filtro)) {
			$filtro .= ' AND ';
		}
		$filtro .= 'sga_elementos.elemento <> '.toba::db()->quote($this->s__seleccion['elemento']);
		$elementos = $this->cn()->get_correlativas_plan_version($filtro);
		
		$datos = array();
		// Sólo se recuperan actividades que tengan correlatividades asignadas
		foreach ($elementos as $key => $dato) {
			if (($dato['cond_aprobada'] + $dato['cond_cursada']) > 0) {
				$datos[] = $dato;
			}
		}
		return $datos;
	}
	
	function conf()
	{
		$actividad = guarani::co('co_elementos')->get_nombre_y_codigo_actividad($this->s__seleccion['elemento']);
		$this->agregar_notificacion('Seleccione la actividad de origen para copiar correlativas a <strong>' . $actividad . '</strong>');
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__cuadro(toba_ei_cuadro $cuadro) 
	{
		$datos = $this->get_datos();
		$cuadro->set_datos($datos);
	}
	
	function evt__cuadro__seleccion($seleccion)
	{
		$elemento_origen = $seleccion['elemento'];
		
		if (empty($elemento_origen)) {
			throw guarani::error(guarani_mensajes::get('800_no_seleccion_unica', array('una <strong>Actividad de Origen</strong>')));
		}
		
		$res = $this->cn()->copiar_correlativas($elemento_origen, $this->s__seleccion['elemento']);
		
		if ($res == 1) {
			$this->unset_vars();
			// Se redirige a la pantalla de edición
			$this->cn()->seleccionar_elemento($this->s__seleccion);
			$this->controlador()->dep('editor_copia')->set_seleccion($this->s__seleccion);
			$this->controlador()->set_pantalla('edicion');
		}	
	}
	
	function conf_evt__cuadro__seleccion(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro')->get_datos();
		$evento->set_msg_confirmacion('¿Confirma que desea copiar correlativas de '.$datos[$fila]['nombre'].'?');
	}

}
?>