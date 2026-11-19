<?php
class ci_edi_asignaciones_espacios extends guarani_pers_ci
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

	function get_espacio_asignada($actividad)
	{	
		$datos_filtros = $this->controlador()->datos_filtros();
		$datos_actividad = $this->cn()->datos_actividad_asignada($actividad);
		
		return toba::consulta_php('co_asignaciones_espacios')->get_espacios_activos($datos_filtros['edificio'], $datos_actividad['espacio']);
	}
	
	function get_actividades_pendientes()
	{
		$datos_filtros = $this->controlador()->datos_filtros();
		$resultado = array();
		$actividades = null;
		$subcomision = null;
		$hora_ini = null;
		$hora_fin = null;
		
		if (isset($datos_filtros)) {
			$actividades = toba::consulta_php('co_asignaciones_espacios')->get_actividades_pendientes($datos_filtros['fecha'], $datos_filtros['edificio'], in_array(asignacion_espacio::tipo_examen, $datos_filtros['asignar']), in_array(asignacion_espacio::tipo_comision, $datos_filtros['asignar']));

			if (isset($actividades[asignacion_espacio::tipo_comision])) {
				foreach ($actividades[asignacion_espacio::tipo_comision] as $clase) {
					$actividad = array();					
					$actividad['valor'] = asignacion_espacio::tipo_comision . ',' . $clase['clase'] . ',' . $clase['comision'] . ',' . $clase['asignacion'];

					isset($clase['subcomision_nombre']) ? $subcomision = $clase['subcomision_nombre']." - " : $subcomision = '';
					isset($clase['hora_inicio']) ? $hora_ini = substr($clase['hora_inicio'], 0, 5) : $hora_ini = '---';
					isset($clase['hora_finalizacion']) ? $hora_fin = substr($clase['hora_finalizacion'], 0, 5) : $hora_fin = '---';

					$actividad['descr'] = $clase['elemento_nombre'] . ' - Comision: ' . $clase["comision_nombre"] . ' - ' . $subcomision . $hora_ini . ' a ' . $hora_fin;
					array_push($resultado, $actividad);
				}
			}

			if (isset($actividades[asignacion_espacio::tipo_examen])) {
				foreach ($actividades[asignacion_espacio::tipo_examen] as $examen) {
					$actividad = array();
					$actividad['valor'] = asignacion_espacio::tipo_examen . ',' . $examen['mesa_examen'] . ',' . $examen['llamado_mesa'] . ',' . $examen['hora_inicio'] . ',' . $examen['fecha'];

					isset($examen['hora_inicio']) ? $hora_ini = substr($examen['hora_inicio'], 0, 5) : $hora_ini = '---';
					isset($examen['hora_finalizacion']) ? $hora_fin = substr($examen['hora_finalizacion'], 0, 5) : $hora_fin = '---';

					$actividad['descr'] = $examen['elemento_nombre'] . ' - Examen: ' . $examen['nombre'] . ' - ' . $hora_ini . ' a ' . $hora_fin;
					array_push($resultado, $actividad);
				}
			}
		}
		
		return $resultado;
	}

	function get_actividades_asignadas()
	{
		$datos_filtros = $this->controlador()->datos_filtros();
		$resultado = array();
		$actividades = null;
		$hora_inicio = null;
		$hora_fin = null;

		if (isset($datos_filtros)) {
			$actividades = toba::consulta_php('co_asignaciones_espacios')->get_actividades_asignadas($datos_filtros['fecha'], $datos_filtros['edificio'], in_array(asignacion_espacio::tipo_examen, $datos_filtros['asignar']), in_array(asignacion_espacio::tipo_comision, $datos_filtros['asignar']));
			
			foreach ($actividades as $act) {
				isset($act['hora_inicio']) ? $hora_inicio = substr($act['hora_inicio'], 0, 5) : $hora_inicio = '---';
				isset($act['hora_finalizacion']) ? $hora_fin = substr($act['hora_finalizacion'], 0, 5) : $hora_fin = '---';

				if(isset($act['clase'])) {
					$actividad['valor'] = $act['asignacion_tipo'] . ',' . $act['asignacion_espacio'] . ',' . $act['espacio'] . ',' . $hora_inicio . ',' . $hora_fin . ',' . $act['estado'] . ',' . $act['fecha'] . ',' . $act['clase'] . ',' . $act['comision'] . ',' . $act['asignacion'];
					$actividad['descr'] = $act['elemento_nombre'] . ' - Comision: ' . $act['comision_nombre'] . ' - ' . $act['espacio_nombre'] . ' de ' . $hora_inicio . ' a ' . $hora_fin;
				} else {
					$actividad['valor'] = $act['asignacion_tipo'] . ',' . $act['asignacion_espacio'] . ',' . $act['espacio'] . ',' . $hora_inicio . ',' . $hora_fin . ',' . $act['estado'] . ',' . $act['fecha'] . ',' . $act['llamado_mesa'] . ',' . $act['mesa_examen'];
					$actividad['descr'] = $act['elemento_nombre'] . ' - Examen: ' . $act['examen_nombre'] . ' - ' . $act['espacio_nombre'] . ' de ' . $hora_inicio . ' a ' . $hora_fin;
				}
				array_push($resultado, $actividad);
			}
		}
		
		return $resultado;
	}
	
	function get_detalles_pendiente($actividad)
	{
		$datos_actividad = $this->cn()->datos_actividad_pendiente($actividad);
		
		$detalle = "";

		if (isset($datos_actividad)) {
			if ($datos_actividad['tipo'] == asignacion_espacio::tipo_comision) {
				$detalle = toba::consulta_php('co_asignaciones_espacios')->get_detalle_comision($datos_actividad['comision'], $datos_actividad['asignacion']);
			} else { //es de tipo examen
				$detalle = toba::consulta_php('co_asignaciones_espacios')->get_detalle_examen($datos_actividad['llamado_mesa']);
			}
		}

		return $detalle;
	}

	function get_detalles_asignada($actividad)
	{
		$datos_actividad = $this->cn()->datos_actividad_asignada($actividad);
		
		$detalle = "";
		
		if ($datos_actividad['tipo'] == asignacion_espacio::tipo_comision) {
			$detalle = toba::consulta_php('co_asignaciones_espacios')->get_detalle_comision($datos_actividad['comision'], $datos_actividad['asignacion']);
		} else { //es de tipo examen
			$detalle = toba::consulta_php('co_asignaciones_espacios')->get_detalle_examen($datos_actividad['llamado_mesa']);
		}

		return $detalle;
	}
	
	function get_fecha_hasta_pendiente($actividad)
	{
		$datos_actividad = $this->cn()->datos_actividad_pendiente($actividad);
		$resultado = "";

		if ($datos_actividad['tipo'] == asignacion_espacio::tipo_comision) {
			$resultado = toba::consulta_php('co_asignaciones_espacios')->get_fecha_hasta_comision($datos_actividad['comision']);
			$resultado = guarani_fecha::convertir_fecha_formato_frontend($resultado[0]['fecha_hasta']);
		}

		return $resultado;
	}
	
	function get_hora_inicio_pendiente($actividad)
	{
		$datos_actividad = $this->cn()->datos_actividad_pendiente($actividad);
		$resultado = toba::consulta_php('co_asignaciones_espacios')->get_hora_inicio($datos_actividad);
		
		return substr($resultado[0]['hora_inicio'], 0, 5);
	}
	
	function get_hora_fin_pendiente($actividad)
	{
		$datos_actividad = $this->cn()->datos_actividad_pendiente($actividad);
		$resultado = toba::consulta_php('co_asignaciones_espacios')->get_hora_fin($datos_actividad);

		return substr($resultado[0]['hora_finalizacion'], 0, 5);
	}
	
	function get_fecha_hasta_asignada($actividad)
	{
		$datos_actividad = $this->cn()->datos_actividad_asignada($actividad);
		$resultado = "";
			
		if ($datos_actividad['tipo'] == asignacion_espacio::tipo_comision) {
			$resultado = toba::consulta_php('co_asignaciones_espacios')->get_fecha_hasta_comision($datos_actividad['comision']);
			$resultado = guarani_fecha::convertir_fecha_formato_frontend($resultado[0]['fecha_hasta']);
		}

		return $resultado;
	}
	
	function get_hora_inicio_asignada($actividad)
	{
		$datos_actividad = $this->cn()->datos_actividad_asignada($actividad);
		return $datos_actividad['hora_inicio'];
	}
	
	function get_hora_fin_asignada($actividad)
	{
		$datos_actividad = $this->cn()->datos_actividad_asignada($actividad);
		return $datos_actividad['hora_fin'];
	}
	
	function get_estado_asignada($actividad)
	{
		$datos_actividad = $this->cn()->datos_actividad_asignada($actividad);
				
		if($datos_actividad['estado'] == 'C') {
			$resultado = array(	array('valor' => 'C', 'descr' => 'Confirmada'),
								array('valor' => 'P', 'descr' => 'Provisoria'));
		} else {
			$resultado = array(	array('valor' => 'P', 'descr' => 'Provisoria'),
								array('valor' => 'C', 'descr' => 'Confirmada'));
		}
		
		return $resultado;
	}
	
	function get_datos_visualizador()
	{
		$resultado = array();
		$datos_filtros = $this->controlador()->datos_filtros();
		
		if(isset($datos_filtros)) {
			$datos_base = toba::consulta_php('co_asignaciones_espacios')->get_datos_visualizador($datos_filtros['fecha'], $datos_filtros['edificio'], in_array(asignacion_espacio::tipo_examen, $datos_filtros['asignar']), in_array(asignacion_espacio::tipo_comision, $datos_filtros['asignar']));
			
			$resultado['dia'] = intval(substr($datos_filtros['fecha'], 8, 2));
			$resultado['mes'] = intval(substr($datos_filtros['fecha'], 5, 2));
			$resultado['anio'] = intval(substr($datos_filtros['fecha'], 0, 4));

			foreach($datos_base['espacios'] as $espacio) {
				$espacio["nombre"] = utf8_encode($espacio["nombre"]);
			}
			$resultado['espacios'] = $datos_base['espacios'];

			$asignaciones = array();
			foreach($datos_base['asignaciones'] as $asignacion) {
				$asig = array();

				$asig['data_elemento'] = utf8_encode($asignacion['elemento_nombre_abrev']);

				if($asignacion['estado'] == 'P') {
					$asig['estado'] = utf8_encode('La asignación es provisoria.');
					$asig['elemento_nombre_abrev'] = utf8_encode($asignacion['elemento_nombre_abrev'] . ' *');
				} else {
					$asig['estado'] = utf8_encode('La asignación está confirmada.');
					$asig['elemento_nombre_abrev'] = utf8_encode($asignacion['elemento_nombre_abrev']);
				}

				if(isset($asignacion['clase'])) {
					$asig['tipo'] = 'clase';
					$asig['elemento_nombre'] = utf8_encode('Clase: ' . $asignacion['elemento_nombre']);
					$asig['cantidad_insc'] = toba::consulta_php('co_asignaciones_espacios')->get_cant_inscriptos(asignacion_espacio::tipo_comision, $asignacion['asignacion']);
				} else {
					$asig['tipo'] = 'examen';
					$asig['elemento_nombre'] = utf8_encode('Examen: ' . $asignacion['elemento_nombre']);
					$asig['cantidad_insc'] = toba::consulta_php('co_asignaciones_espacios')->get_cant_inscriptos(asignacion_espacio::tipo_examen, $asignacion['llamado_mesa']);
				}

				$asig['espacio'] = utf8_encode($asignacion['espacio_nombre']);
				$asig['duracion_detallada'] = utf8_encode('De '.substr($asignacion['hora_inicio'], 0, 5).' hs a '.substr($asignacion['hora_finalizacion'], 0, 5).' hs.');
				$asig['inicio_horas'] = intval(substr($asignacion['hora_inicio'], 0, 2));
				$asig['inicio_minutos'] = intval(substr($asignacion['hora_inicio'], 3, 2));
				$asig['fin_horas'] = intval(substr($asignacion['hora_finalizacion'], 0, 2));
				$asig['fin_minutos'] = intval(substr($asignacion['hora_finalizacion'], 3, 2));
				$asig['tipo'] = isset($asignacion['clase']) ? 'clase' : 'examen';
				$asig['capacidad'] = $asignacion['capacidad'];

				array_push($asignaciones, $asig);
			}
			$resultado['asignaciones'] = $asignaciones;
		}
		
		return $resultado;
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
				$datos_filtros = $this->controlador()->datos_filtros();
				$datos_actividad = $this->cn()->datos_actividad_pendiente($datos['actividad']);

				$this->cn()->crear_asignacion($datos, $datos_filtros, $datos_actividad);
				$this->datos_temp = null;
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
				$datos_filtros = $this->controlador()->datos_filtros();
				$datos_actividad = $this->cn()->datos_actividad_asignada($datos['actividad']);
				
				$this->cn()->modificar_asignacion($datos, $datos_filtros, $datos_actividad);
				$this->datos_temp = null;
			}
		} catch ( guarani_error $e ) {
			throw new guarani_error($e->getMessage());
		}
	}
	
	function evt__formulario_asignadas__eliminar($datos)
	{
		$this->datos_temp = $datos;
	
		try {
			$this->cn()->eliminar_asignacion($datos);
			$this->datos_temp = null;
		} catch ( guarani_error $e ) {
			throw new guarani_error($e->getMessage());
		}
	}
}
?>