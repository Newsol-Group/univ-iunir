<?php
class ci_edi_asignaciones_espacios_masiva extends guarani_pers_ci
{
	protected $datos_temp;
	
	// Constantes propias
	const chars_maximo_combo = 120;

	//-----------------------------------------------------------------------------------
	//---- formulario_pendientes --------------------------------------------------------
	//-----------------------------------------------------------------------------------
	function conf__formulario_pendientes(guarani_pers_ei_formulario $form)
	{
		toba_ef_combo::set_maximo_descripcion(self::chars_maximo_combo);
		
		if(isset($this->datos_temp)){
			$form->set_datos($this->datos_temp);
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- formulario_asignadas ---------------------------------------------------------
	//-----------------------------------------------------------------------------------
	function conf__formulario_asignadas(guarani_pers_ei_formulario $form)
	{
		toba_ef_combo::set_maximo_descripcion(self::chars_maximo_combo);
		
		if(isset($this->datos_temp)){
			$form->set_datos($this->datos_temp);
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- Métodos get para los efs de las dos pantallas y para el visualizador ---------
	//-----------------------------------------------------------------------------------
	function get_espacios()
	{
		$datos_filtros = $this->controlador()->datos_filtros();
		$resultado = null;

		if (isset($datos_filtros)) {
			$resultado = toba::consulta_php('co_asignaciones_espacios')->get_espacios_activos($datos_filtros['edificio']);
		}

		return $resultado;
	}
	
	function get_bandas_horarias($pendientes)
	{
		$datos_filtros = $this->controlador()->datos_filtros();
		$datos = [];
		$resultado = [];
		
		if (isset($datos_filtros)) {
			$datos = toba::consulta_php('co_comisiones')->get_comisiones_bh_actividad_periodo_lectivo_ubicacion($datos_filtros['periodo_lectivo'], $datos_filtros['elemento'], $datos_filtros['ubicacion']);
		}
		
		if($pendientes) {
			$datos = array_filter($datos, function($dato) { return ($dato['cant_clases_de_la_bh_con_ae_completa'] < $dato['cant_clases_de_la_bh']); });
		} else {
			$datos = array_filter($datos, function($dato) { return ($dato['cant_ae_clases_de_la_bh'] > 0); });
		}
		
		foreach($datos as $dato) {
			$valor = $dato['comision'] . ',' . $dato['asignacion'] . ',' . $dato['banda_horaria']. ',' . $dato['hora_inicio']. ',' . $dato['hora_finalizacion']. ',' . $dato['fecha_desde']. ',' . $dato['fecha_hasta'];; 
			$descr = $dato['banda_horaria']. ' - ' .$dato['comision_nombre'] . ': ' . $dato['clases_tipos_nombre'] . ' (' . $dato['dia_semana'] . ' de ' . $dato['hora_inicio'] . ' a ' . $dato['hora_finalizacion'] . ' hs - ' . $dato['periodicidad'] . ')';
			array_push($resultado, ['valor' => $valor, 'descr' => $descr]);
		}
		
		return $resultado;
	}
	
	function get_bandas_horarias_pendientes()
	{
		return $this->get_bandas_horarias(true);
	}
	
	function get_bandas_horarias_asignadas()
	{
		return $this->get_bandas_horarias(false);
	}
	
	function get_detalles($banda_horaria)
	{
		$banda_horaria = $this->cn()->datos_banda_horaria($banda_horaria);
		
		$detalle = "";

		if (isset($banda_horaria)) {
			$detalle = toba::consulta_php('co_asignaciones_espacios')->get_detalle_comision($banda_horaria['comision'], $banda_horaria['asignacion']);
			$detalle .= "\r\n------------------------------------------------------------------------------------------------------------------------------------------------------\r\n";
			$detalle .= toba::consulta_php('co_asignaciones_espacios')->get_asignaciones_espacios_por_comision($banda_horaria['comision'], $banda_horaria['banda_horaria']);
		}

		return $detalle;
	}
	
	function get_hora_inicio($banda_horaria)
	{
		$datos_banda_horaria = $this->cn()->datos_banda_horaria($banda_horaria);
		return $datos_banda_horaria['hora_inicio'];
	}
	
	function get_hora_fin($banda_horaria)
	{
		$datos_banda_horaria = $this->cn()->datos_banda_horaria($banda_horaria);
		return $datos_banda_horaria['hora_fin'];
	}
	
	function get_fecha_desde($banda_horaria)
	{
		$datos_banda_horaria = $this->cn()->datos_banda_horaria($banda_horaria);
		return guarani_fecha::convertir_fecha_formato_frontend($datos_banda_horaria['fecha_desde']);
	}
	
	function get_fecha_hasta($banda_horaria)
	{
		$datos_banda_horaria = $this->cn()->datos_banda_horaria($banda_horaria);
		return guarani_fecha::convertir_fecha_formato_frontend($datos_banda_horaria['fecha_hasta']);
	}
	
	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	function evt__formulario_pendientes__asignar($datos)	
	{
		$this->datos_temp = $datos;
		
		try {
			if( date('H:i', strtotime($datos['hora_inicio'])) >= date('H:i', strtotime($datos['hora_fin'])) ) {
				toba::notificacion()->agregar($this->get_mensaje('800_error_rango_horas'), 'error');	
			} else if( date('H:i', strtotime($datos['hora_inicio'])) < date('H:i', strtotime('06:00')) ) {
				toba::notificacion()->agregar($this->get_mensaje('800UNG_asig_espacio_hora_inicio'), 'error');	
			} else if( date('H:i', strtotime($datos['hora_fin'])) > date('H:i', strtotime('23:00')) ) {
				toba::notificacion()->agregar($this->get_mensaje('800UNG_asig_espacio_hora_fin'), 'error');	
			} else {
				$datos_banda_horaria = $this->cn()->datos_banda_horaria($datos['banda_horaria']);
				
				if( date($datos['fecha_desde']) < date($datos_banda_horaria['fecha_desde']) ) {
				//toba::notificacion()->agregar($this->get_mensaje('800_error_rango_horas'), 'error');	
				} else if( date($datos['fecha_hasta']) > date($datos_banda_horaria['fecha_hasta']) ) {
					//toba::notificacion()->agregar($this->get_mensaje('800UNG_asig_espacio_hora_inicio'), 'error');	
				} else if( date($datos['fecha_desde']) > date($datos['fecha_hasta']) ) {
					//toba::notificacion()->agregar($this->get_mensaje('800UNG_asig_espacio_hora_fin'), 'error');	
				} else {
					$this->cn()->crear_asignaciones($datos, $datos_banda_horaria);
					$this->datos_temp = null;
				}
			}
		} catch ( guarani_error $e ) {
			throw new guarani_error($e->getMessage());
		}
	}
	
	function evt__formulario_asignadas__modificar($datos)
	{
		$this->datos_temp = $datos;
		
		try {
			if( date('H:i', strtotime($datos['hora_inicio'])) >= date('H:i', strtotime($datos['hora_fin'])) ) {
				toba::notificacion()->agregar($this->get_mensaje('800_error_rango_horas'), 'error');	
			} else if( date('H:i', strtotime($datos['hora_inicio'])) < date('H:i', strtotime('06:00')) ) {
				toba::notificacion()->agregar($this->get_mensaje('800UNG_asig_espacio_hora_inicio'), 'error');	
			} else if( date('H:i', strtotime($datos['hora_fin'])) > date('H:i', strtotime('23:00')) ) {
				toba::notificacion()->agregar($this->get_mensaje('800UNG_asig_espacio_hora_fin'), 'error');	
			} else {
				$datos_banda_horaria = $this->cn()->datos_banda_horaria($datos['banda_horaria']);
				
				if( date($datos['fecha_desde']) < date($datos_banda_horaria['fecha_desde']) ) {
				//toba::notificacion()->agregar($this->get_mensaje('800_error_rango_horas'), 'error');	
				} else if( date($datos['fecha_hasta']) > date($datos_banda_horaria['fecha_hasta']) ) {
					//toba::notificacion()->agregar($this->get_mensaje('800UNG_asig_espacio_hora_inicio'), 'error');	
				} else if( date($datos['fecha_desde']) > date($datos['fecha_hasta']) ) {
					//toba::notificacion()->agregar($this->get_mensaje('800UNG_asig_espacio_hora_fin'), 'error');	
				} else {
					$this->cn()->modificar_asignaciones($datos, $datos_banda_horaria);
					$this->datos_temp = null;
				}
			}
		} catch ( guarani_error $e ) {
			throw new guarani_error($e->getMessage());
		}
	}
	
	function evt__formulario_asignadas__eliminar($datos)
	{
		$this->datos_temp = $datos;
	
		try {
			$this->cn()->eliminar_asignaciones($datos);
			$this->datos_temp = null;
		} catch ( guarani_error $e ) {
			throw new guarani_error($e->getMessage());
		}
	}
}
?>