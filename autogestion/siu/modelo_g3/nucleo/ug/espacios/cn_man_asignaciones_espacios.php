<?php
/**
 * 	Manejador de Asignaciones de Espacios
 *
 * @package		Unidades de Gestión
 * @subpackage  Espacios
 */
class cn_man_asignaciones_espacios extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'asignacion_espacio';
	}
	
	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------	
	function crear_asignacion($datos_formulario, $datos_filtros, $datos_actividad) 
	{
		try {
			if($this->es_duracion_valida($datos_actividad, $datos_formulario['hora_inicio'], $datos_formulario['hora_fin'])) {
			
				if($datos_formulario['aplicar_clases_restantes'] == 'N') {
					if($datos_actividad['tipo'] == asignacion_espacio::tipo_comision) {
						$horario_valido = $this->validar_horario_clase($datos_actividad['clase'], $datos_filtros['fecha'], $datos_formulario['hora_inicio'], $datos_formulario['hora_fin']);
					} else {
						$horario_valido = $this->validar_horario_examen($datos_actividad['llamado_mesa'], $datos_filtros['fecha'], $datos_formulario['hora_inicio'], $datos_formulario['hora_fin']);
					}
					
					if($horario_valido) {
						if($this->validar_disponibilidad_espacio($datos_actividad['tipo'], $datos_formulario['espacio'], $datos_filtros['fecha'], $datos_formulario['hora_inicio'], $datos_formulario['hora_fin'], $datos_formulario['estado'])) {
							$nueva_asignacion = array();

							$nueva_asignacion['fecha'] = $datos_filtros['fecha'];
							$nueva_asignacion['espacio'] = $datos_formulario['espacio'];
							$nueva_asignacion['asignacion_tipo'] = $datos_actividad['tipo'];
							$nueva_asignacion['hora_inicio'] = $datos_formulario['hora_inicio'];
							$nueva_asignacion['hora_finalizacion'] = $datos_formulario['hora_fin'];
							$nueva_asignacion['estado'] = $datos_formulario['estado'];

							if($nueva_asignacion['asignacion_tipo'] == asignacion_espacio::tipo_comision){
								$nueva_asignacion['clase'] = $datos_actividad['clase'];
								$nueva_asignacion['llamado_mesa'] = null;
								$otras_clases_de_la_bh = toba::consulta_php('co_asignaciones_espacios')->get_clases_de_otras_comisiones_de_la_bh($datos_actividad['asignacion'], $datos_filtros['fecha'], $datos_filtros['fecha'], $datos_actividad['comision']);
							} else { // es de tipo examen
								$nueva_asignacion['clase'] = null;
								$nueva_asignacion['llamado_mesa'] = $datos_actividad['llamado_mesa'];
							}

							try {
								guarani::act('act_asignaciones_espacio')->insertar_asignacion_espacio(
									$nueva_asignacion['espacio'],
									$nueva_asignacion['asignacion_tipo'],
									$nueva_asignacion['fecha'],
									$nueva_asignacion['hora_inicio'],
									$nueva_asignacion['hora_finalizacion'],
									$nueva_asignacion['clase'],
									$nueva_asignacion['llamado_mesa'],
									$nueva_asignacion['estado']
								);

								if( (isset($otras_clases_de_la_bh)) && (guarani_arreglos::count($otras_clases_de_la_bh) > 0) ) {
									guarani::act('act_asignaciones_espacio')->insertar_asignaciones_multiples_clases($otras_clases_de_la_bh, $nueva_asignacion['espacio'], $nueva_asignacion['hora_inicio'], $nueva_asignacion['hora_finalizacion'], $nueva_asignacion['estado']);
								}
								
								toba::notificacion()->agregar($this->get_mensaje('guardar_ok'), 'info');	
							} catch ( guarani_error $e ) {
								throw $e;
							}
						} else {
							toba::notificacion()->agregar($this->get_mensaje('800CUR_horario_aula_superpuesto'), 'error');	
						}
					} else {
						toba::notificacion()->agregar($this->get_mensaje('800UNG_asig_espacio_superpuesta'), 'error');	
					}
				} else {
					$clases = toba::consulta_php('co_asignaciones_espacios')->get_clases_de_otras_comisiones_de_la_bh($datos_actividad['asignacion'], $datos_filtros['fecha'], $datos_formulario['fecha_hasta']);

					$asignacion_posible = true;
					$indice =  0;
					$clase = array();
					$clase['tipo'] = asignacion_espacio::tipo_comision;
					$clase['comision'] = $datos_actividad['comision'];
					$clase['asignacion'] = $datos_actividad['asignacion'];
					
					while($asignacion_posible && ($indice < guarani_arreglos::count($clases))) {
						$clase['clase'] = $clases[$indice]['clase'];
						$asignacion_posible = $this->validar_horario_clase($clase['clase'], $datos_filtros['fecha'], $datos_formulario['hora_inicio'], $datos_formulario['hora_fin']);
						$asignacion_posible = $asignacion_posible && $this->validar_disponibilidad_espacio(asignacion_espacio::tipo_comision, $datos_formulario['espacio'], $clases[$indice]['fecha'], $datos_formulario['hora_inicio'], $datos_formulario['hora_fin'], $datos_formulario['estado']);
						$asignacion_posible = $asignacion_posible && $this->es_duracion_valida($clase, $datos_formulario['hora_inicio'], $datos_formulario['hora_fin']);

						if($asignacion_posible) {
							$indice++;
						}
					}

					if($asignacion_posible) {
						try {
							guarani::act('act_asignaciones_espacio')->insertar_asignaciones_multiples_clases($clases, $datos_formulario['espacio'], $datos_formulario['hora_inicio'], $datos_formulario['hora_fin'], $datos_formulario['estado']);
							
							toba::notificacion()->agregar($this->get_mensaje('guardar_ok'), 'info');	
						} catch ( guarani_error $e ) {
							throw $e;
						}
					} else {
						toba::notificacion()->agregar($this->get_mensaje('800UNG_error_asignar_restantes'), 'error');	
					}
				}
			} else {
				toba::notificacion()->agregar($this->get_mensaje('800UNG_duracion_excedida'), 'error');	
			} 
		} catch ( guarani_error $e ) {
			throw $e;
		}
	}
	
	function modificar_asignacion($datos_formulario, $datos_filtros, $datos_actividad) 
	{
		if(	$datos_formulario['espacio'] != $datos_actividad['espacio'] || $datos_formulario['hora_inicio'] != $datos_actividad['hora_inicio'] ||
			$datos_formulario['hora_fin'] != $datos_actividad['hora_fin'] || $datos_formulario['estado'] != $datos_actividad['estado']) {
			
			if($this->es_duracion_valida($datos_actividad, $datos_formulario['hora_inicio'], $datos_formulario['hora_fin'], $datos_actividad['asignacion_espacio'])) {
				if($datos_formulario['aplicar_clases_restantes'] == 'N') {
					if($datos_actividad['tipo'] == asignacion_espacio::tipo_comision) {
						$horario_valido = $this->validar_horario_clase($datos_actividad['clase'], $datos_filtros['fecha'], $datos_formulario['hora_inicio'], $datos_formulario['hora_fin'], $datos_actividad['asignacion_espacio']);
					} else {
						$horario_valido = $this->validar_horario_examen($datos_actividad['llamado_mesa'], $datos_filtros['fecha'], $datos_formulario['hora_inicio'], $datos_formulario['hora_fin'], $datos_actividad['asignacion_espacio']);
					}

					if($horario_valido) {
						if($this->validar_disponibilidad_espacio($datos_actividad['tipo'], $datos_formulario['espacio'], $datos_actividad['fecha'], $datos_formulario['hora_inicio'], $datos_formulario['hora_fin'], $datos_formulario['estado'], $datos_actividad['asignacion_espacio'])) {
							try {
								if($datos_actividad['tipo'] == asignacion_espacio::tipo_comision) {
									$clase = $datos_actividad['clase'];
									$llamado_mesa = null;	
									$otras_asignaciones_de_la_bh = toba::consulta_php('co_asignaciones_espacios')->get_asignaciones_de_otras_comisiones_de_la_bh($datos_actividad['asignacion'], $datos_actividad['comision'], $datos_actividad['hora_inicio'], $datos_actividad['hora_fin'], $datos_filtros['fecha'], $datos_filtros['fecha']);
								} else {
									$clase = null;
									$llamado_mesa = $datos_actividad['llamado_mesa'];
								}
								guarani::act('act_asignaciones_espacio')->actualizar_asignacion_espacio(
									$datos_actividad['asignacion_espacio'],
									$datos_formulario['espacio'],
									$datos_actividad['tipo'],
									$datos_actividad['fecha'],
									$datos_formulario['hora_inicio'],
									$datos_formulario['hora_fin'],
									$clase,
									$llamado_mesa,
									$datos_formulario['estado']
								);

								if(isset($otras_asignaciones_de_la_bh)) {
									foreach($otras_asignaciones_de_la_bh as $otra) {
										guarani::act('act_asignaciones_espacio')->actualizar_asignacion_espacio(
											$otra['asignacion_espacio'],
											$datos_formulario['espacio'],
											$datos_actividad['tipo'],
											$datos_actividad['fecha'],
											$datos_formulario['hora_inicio'],
											$datos_formulario['hora_fin'],
											$otra['clase'],
											$llamado_mesa,
											$datos_formulario['estado']
										);
									}
								}

								toba::notificacion()->agregar($this->get_mensaje('guardar_ok'), 'info');
							} catch ( guarani_error $e ) {
								throw $e;
							}
						} else {
							toba::notificacion()->agregar($this->get_mensaje('800CUR_horario_aula_superpuesto'), 'error');	
						}
					} else {
						toba::notificacion()->agregar($this->get_mensaje('800UNG_asig_espacio_superpuesta'), 'error');
					}
				} else {
					$asignaciones_clases = toba::consulta_php('co_asignaciones_espacios')->get_asig_espacios_de_comisiones_que_comparten_bh($datos_actividad['asignacion'], $datos_actividad['fecha'], $datos_formulario['fecha_hasta'], $datos_actividad['hora_inicio'], $datos_actividad['hora_fin']);
					
					$asignacion_posible = true;
					$indice =  0;
					$clase = array();
					$clase['tipo'] = asignacion_espacio::tipo_comision;
					$clase['comision'] = $datos_actividad['comision'];
					$clase['asignacion'] = $datos_actividad['asignacion'];
					
					while($asignacion_posible && ($indice < guarani_arreglos::count($asignaciones_clases))) {
						$clase['clase'] = $asignaciones_clases[$indice]['clase'];
						$asignacion_posible = $this->validar_horario_clase($asignaciones_clases[$indice]['clase'], $datos_filtros['fecha'], $datos_formulario['hora_inicio'], $datos_formulario['hora_fin'], $asignaciones_clases[$indice]['asignacion_espacio']);
						$asignacion_posible = $asignacion_posible && $this->validar_disponibilidad_espacio(asignacion_espacio::tipo_comision, $datos_formulario['espacio'], $asignaciones_clases[$indice]['fecha'], $datos_formulario['hora_inicio'], $datos_formulario['hora_fin'], $datos_formulario['estado'], $asignaciones_clases[$indice]['asignacion_espacio']);
						$asignacion_posible = $asignacion_posible && $this->es_duracion_valida($clase, $datos_formulario['hora_inicio'], $datos_formulario['hora_fin'],	$asignaciones_clases[$indice]['asignacion_espacio']);

						if($asignacion_posible) {
							$indice++;
						}
					}
					
					if($asignacion_posible) {
						try {
							$ids_a_actualizar = array();
							$ids_a_eliminar = array();
							for ($i = 0; $i < guarani_arreglos::count($asignaciones_clases) ; $i++) {
								$id_clase = $asignaciones_clases[$i]['clase'];
								if( !isset($ids_a_actualizar[$id_clase]) ) {
									$ids_a_actualizar[$id_clase] = $asignaciones_clases[$i];
								} else {
									array_push($ids_a_eliminar, $asignaciones_clases[$i]);
								}
							}
							
							guarani::act('act_asignaciones_espacio')->actualizar_asignaciones_multiples_clases($ids_a_actualizar, $ids_a_eliminar, $datos_formulario['espacio'], $datos_formulario['hora_inicio'], $datos_formulario['hora_fin'], $datos_formulario['estado']);
							toba::notificacion()->agregar($this->get_mensaje('guardar_ok'), 'info');	
						} catch ( guarani_error $e ) {
							throw $e;
						}
					} else {
						toba::notificacion()->agregar($this->get_mensaje('800UNG_error_asignar_restantes'), 'error');	
					}
				}
			} else {
				toba::notificacion()->agregar($this->get_mensaje('800UNG_duracion_excedida'), 'error');	
			}
		} else {
			toba::notificacion()->agregar($this->get_mensaje('800_no_hubo_cambios'), 'info');
		}
	}
	
	function eliminar_asignacion($datos_formulario) 
	{	
		$datos_actividad = $this->datos_actividad_asignada($datos_formulario['actividad']);
		try {
			if($datos_formulario['aplicar_clases_restantes'] == 'N'){
				if($datos_actividad['tipo'] == asignacion_espacio::tipo_comision) {
					// Aunque no se aplique a clases restantes, se consulta get_asig_espacios_de_comisiones_que_comparten_bh con fecha de inicio = a fecha de fin,
					// para obtener todas las asignaciones de espacios de comisiones para ese día puntual (por si se llegara a compartir la bh)
					$asignaciones_clases = toba::consulta_php('co_asignaciones_espacios')->get_asig_espacios_de_comisiones_que_comparten_bh($datos_actividad['asignacion'], $datos_actividad['fecha'], $datos_actividad['fecha'], $datos_actividad['hora_inicio'], $datos_actividad['hora_fin']);

					guarani::act('act_asignaciones_espacio')->eliminar_asignaciones_multiples_clases($asignaciones_clases);
				} else {
					guarani::act('act_asignaciones_espacio')->eliminar_asignacion_espacio($datos_actividad['asignacion_espacio']);
				}
				toba::notificacion()->agregar($this->get_mensaje('eliminar_ok'), 'info');
			} else {
				$asignaciones_clases = toba::consulta_php('co_asignaciones_espacios')->get_asig_espacios_de_comisiones_que_comparten_bh($datos_actividad['asignacion'], $datos_actividad['fecha'], $datos_formulario['fecha_hasta'], $datos_actividad['hora_inicio'], $datos_actividad['hora_fin']);
				
				if(guarani_arreglos::count($asignaciones_clases) > 0) {
					try {
						guarani::act('act_asignaciones_espacio')->eliminar_asignaciones_multiples_clases($asignaciones_clases);

						toba::notificacion()->agregar($this->get_mensaje('eliminar_ok'), 'info');	
					} catch ( guarani_error $e ) {
						throw $e;
					}
				} else {
					toba::notificacion()->agregar(guarani_mensajes::get('800UNG_clases_restantes_vacio', array($datos_formulario['hora_inicio'], $datos_formulario['hora_fin'])), 'error');	
				}
			}
		} catch ( guarani_error $e ) {
			throw $e;
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- Validaciones -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	function validar_disponibilidad_espacio($tipo_actividad, $espacio, $fecha, $hora_inicio, $hora_fin, $estado, $asignacion_espacio = null) 
	{
		$espacio = toba::db()->quote($espacio);
		$fecha = toba::db()->quote($fecha);
		
		// en la validación de disponibilidad de un espacio, sólo se contemplan las asignaciones confirmadas
		$condicion = "
			sga_espacios_asignacion.espacio = $espacio
			AND sga_espacios_asignacion.fecha = $fecha
			AND sga_espacios_asignacion.estado = '" . asignacion_espacio::estado_confirmada . "'
		";
		
		// si se especificó una asignación de espacio, entonces se trata de una modificación y no debe ser tenida en cuenta
		if(isset($asignacion_espacio)) {
			$condicion .= " AND sga_espacios_asignacion.asignacion_espacio <> " . toba::db()->quote($asignacion_espacio);
		}
		
		$disponibilidad = toba::consulta_php('co_asignaciones_espacios')->obtener_disponibilidad($hora_inicio, $hora_fin, $condicion);
		
		$todos_examenes = true; 
		$i = 0;
		while( ($i < guarani_arreglos::count($disponibilidad)) && $todos_examenes ) {
			if($disponibilidad[$i]['asignacion_tipo'] == asignacion_espacio::tipo_comision) {
				$todos_examenes = false;
			} else {
				$i++;
			}
		}
		
		// si la query retornó datos entonces es que hay al menos una asignación confirmada
		if(guarani_arreglos::count($disponibilidad) > 0) {
			// en tal caso, si se quiere asignar el espacio a una clase, sólo puede tratarse de una asignación provisoria,
			// y si fuese para un examen, puede ser provisoria o asignada siempre y cuando todas las otras asignaciones encontradas sean de examenes.
			$resultado = ($tipo_actividad == asignacion_espacio::tipo_comision && $estado == asignacion_espacio::estado_provisoria) 
						|| ($tipo_actividad == asignacion_espacio::tipo_examen && $todos_examenes );
		} else {
			// sino, el espacio está disponible
			$resultado = true;
		}
		
		return $resultado;
	}
	
	function validar_horario_clase($clase, $fecha, $hora_inicio, $hora_fin, $asignacion_espacio = null) 
	{
		$clase = toba::db()->quote($clase);
		$fecha = toba::db()->quote($fecha);
		
		$condicion = "
			sga_espacios_asignacion.clase = $clase
			AND sga_espacios_asignacion.fecha = $fecha
		";
		
		// si se especificó una asignación de espacio, entonces se trata de una modificación y no debe ser tenida en cuenta
		if(isset($asignacion_espacio)) {
			$condicion .= " AND sga_espacios_asignacion.asignacion_espacio <> " . toba::db()->quote($asignacion_espacio);
		}
		
		$disponibilidad = toba::consulta_php('co_asignaciones_espacios')->obtener_disponibilidad($hora_inicio, $hora_fin, $condicion);
		
		return (guarani_arreglos::count($disponibilidad) == 0);
	}
	
	function validar_horario_examen($llamado_mesa, $fecha, $hora_inicio, $hora_fin, $asignacion_espacio = null) 
	{
		$llamado_mesa = toba::db()->quote($llamado_mesa);
		$fecha = toba::db()->quote($fecha);
		
		$condicion = "
			sga_espacios_asignacion.llamado_mesa = $llamado_mesa
			AND sga_espacios_asignacion.fecha = $fecha
		";
		
		// si se especificó una asignación de espacio, entonces se trata de una modificación y no debe ser tenida en cuenta
		if(isset($asignacion_espacio)) {
			$condicion .= " AND sga_espacios_asignacion.asignacion_espacio <> " . toba::db()->quote($asignacion_espacio);
		}
		
		$disponibilidad = toba::consulta_php('co_asignaciones_espacios')->obtener_disponibilidad($hora_inicio, $hora_fin, $condicion);
		
		return (guarani_arreglos::count($disponibilidad) == 0);
	}
	
	function es_duracion_valida($actividad, $hora_inicio, $hora_fin, $asignacion_espacio = null) 
	{
		$hora_inicio = toba::db()->quote($hora_inicio);
		$hora_fin = toba::db()->quote($hora_fin);
		
		if ($actividad['tipo'] == asignacion_espacio::tipo_comision) {
			$clase = toba::db()->quote($actividad['clase']);
			$asignacion = toba::db()->quote($actividad['asignacion']);
			
			$duracion_valida = toba::consulta_php('co_asignaciones_espacios')->validar_duracion_clase($hora_inicio, $hora_fin, $clase, $asignacion, $asignacion_espacio);
		} else {
			$llamado_mesa = toba::db()->quote($actividad['llamado_mesa']);
			
			$duracion_valida = toba::consulta_php('co_asignaciones_espacios')->validar_duracion_examen($hora_inicio, $hora_fin, $llamado_mesa, $asignacion_espacio);
		}
		
		return $duracion_valida;
	}
	
	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	function datos_actividad_pendiente($actividad)
	{
		$parametros = explode(",", $actividad);
		$datos_actividad = array();
		$datos_actividad['tipo'] = $parametros[0];

		if ($datos_actividad['tipo'] == asignacion_espacio::tipo_comision) {
			$datos_actividad['clase'] = $parametros[1];
			$datos_actividad['comision'] = $parametros[2];
			$datos_actividad['asignacion'] = $parametros[3];
		} else { //es de tipo examen
			$datos_actividad['mesa_examen'] = $parametros[1];
			$datos_actividad['llamado_mesa'] = $parametros[2];
			$datos_actividad['hora_inicio'] = $parametros[3];
			$datos_actividad['fecha'] = $parametros[4];
		}

		return $datos_actividad;
	}
	
	function datos_actividad_asignada($actividad)
	{
		$parametros = explode(",", $actividad); 
		$datos_actividad = array();
		$datos_actividad['tipo'] = $parametros[0];
		$datos_actividad['asignacion_espacio'] = $parametros[1];
		$datos_actividad['espacio'] = $parametros[2];	
		$datos_actividad['hora_inicio'] = $parametros[3];
		$datos_actividad['hora_fin'] = $parametros[4];
		$datos_actividad['estado'] = $parametros[5];
		$datos_actividad['fecha'] = $parametros[6];
		
		if ($datos_actividad['tipo'] == asignacion_espacio::tipo_comision) {
			$datos_actividad['clase'] = $parametros[7];
			$datos_actividad['comision'] = $parametros[8];
			$datos_actividad['asignacion'] = $parametros[9];
		} else { //es de tipo examen
			$datos_actividad['llamado_mesa'] = $parametros[7];
			$datos_actividad['mesa_examen'] = $parametros[8];
		}

		return $datos_actividad;
	}
}
?>