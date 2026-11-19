<?php
/**
 * 	Manejador de Asignaciones de Espacios
 *
 * @package		Unidades de Gestión
 * @subpackage  Espacios
 */
class cn_man_asignaciones_espacios_masiva extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'asignacion_espacio_masiva';
	}
	
	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------	
	function crear_asignaciones($datos_formulario, $datos_banda_horaria) 
	{
		try {
			$conjunto_clases = toba::consulta_php('co_bandas_horarias')->get_clases_de_bh_entre_fechas($datos_banda_horaria['banda_horaria'], $datos_formulario['fecha_desde'], $datos_formulario['fecha_hasta']);
			$clases = array_column($conjunto_clases, 'clase');
			$fechas = array_column($conjunto_clases, 'fecha');
			//validacion de la duración de las asignaciones de esas clases
			if($this->validar_duracion_clases($clases, $datos_banda_horaria['asignacion'], $datos_formulario['hora_inicio'], $datos_formulario['hora_fin'])) {
				//validacion del horario de esas clases
				if($this->validar_horario_clases($clases, $datos_formulario['hora_inicio'], $datos_formulario['hora_fin'])) {
					//validacion de disponibilidad del espacio en las fechas y el estado de asignación especificados
					if($this->validar_disponibilidad_espacio($fechas, $datos_formulario['espacio'], $datos_formulario['hora_inicio'], $datos_formulario['hora_fin'], $datos_formulario['estado'])) 
					{
						//se obtienen las clases de otras comisiones que compartan la banda horaria
						$otras_clases_de_la_bh = toba::consulta_php('co_asignaciones_espacios')->get_clases_de_otras_comisiones_de_la_bh($datos_banda_horaria['asignacion'], $datos_formulario['fecha_desde'], $datos_formulario['fecha_hasta'], $datos_banda_horaria['comision']);
						
						try {
							guarani::act('act_asignaciones_espacio')->insertar_asignaciones_multiples_clases(
								$conjunto_clases, $datos_formulario['espacio'], $datos_formulario['hora_inicio'], $datos_formulario['hora_fin'], $datos_formulario['estado']
							);
							
							if( (isset($otras_clases_de_la_bh)) && (guarani_arreglos::count($otras_clases_de_la_bh) > 0) ) {
								guarani::act('act_asignaciones_espacio')->insertar_asignaciones_multiples_clases(
									$otras_clases_de_la_bh, $datos_formulario['espacio'], $datos_formulario['hora_inicio'], $datos_formulario['hora_fin'],  $datos_formulario['estado']
								);
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
				toba::notificacion()->agregar($this->get_mensaje('800UNG_duracion_excedida'), 'error');	
			} 
		} catch ( guarani_error $e ) {
			throw $e;
		}
	}
	
	function modificar_asignaciones($datos_formulario, $datos_banda_horaria) 
	{
		try {
			$conjunto_clases = toba::consulta_php('co_bandas_horarias')->get_clases_de_bh_entre_fechas($datos_banda_horaria['banda_horaria'], $datos_formulario['fecha_desde'], $datos_formulario['fecha_hasta']);
			$clases = array_column($conjunto_clases, 'clase');
			$fechas = array_column($conjunto_clases, 'fecha');
			// se obtienen todas las asignaciones de espacios de comisiones que comparten bh, entre los días ingresados
			$asignaciones_clases = toba::consulta_php('co_asignaciones_espacios')->get_asig_espacios_de_comisiones_que_comparten_bh($datos_banda_horaria['asignacion'], $datos_formulario['fecha_desde'], $datos_formulario['fecha_hasta']);
			
			//validacion de la duración de las asignaciones de esas clases
			if($this->validar_duracion_clases($clases, $datos_banda_horaria['asignacion'], $datos_formulario['hora_inicio'], $datos_formulario['hora_fin'], $asignaciones_clases)) {
				//validacion del horario de esas clases
				if($this->validar_horario_clases($clases, $datos_formulario['hora_inicio'], $datos_formulario['hora_fin'], $asignaciones_clases)) {
					//validacion de disponibilidad del espacio en las fechas y el estado de asignación especificados
					if($this->validar_disponibilidad_espacio($fechas, $datos_formulario['espacio'], $datos_formulario['hora_inicio'], $datos_formulario['hora_fin'], $datos_formulario['estado'], $asignaciones_clases)) {
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
						
					} else {
						toba::notificacion()->agregar($this->get_mensaje('800CUR_horario_aula_superpuesto'), 'error');	
					}
				} else {
					toba::notificacion()->agregar($this->get_mensaje('800UNG_asig_espacio_superpuesta'), 'error');
				}
			} else {
				toba::notificacion()->agregar($this->get_mensaje('800UNG_duracion_excedida'), 'error');	
			}
		} catch ( guarani_error $e ) {
			throw $e;
		}
	}

	function eliminar_asignaciones($datos_formulario) 
	{	
		$datos_banda_horaria = $this->datos_banda_horaria($datos_formulario['banda_horaria']);
		try {
			$asignaciones_clases = toba::consulta_php('co_asignaciones_espacios')->get_asig_espacios_de_comisiones_que_comparten_bh($datos_banda_horaria['asignacion'], $datos_formulario['fecha_desde'], $datos_formulario['fecha_hasta']);

			guarani::act('act_asignaciones_espacio')->eliminar_asignaciones_multiples_clases($asignaciones_clases);
			
			toba::notificacion()->agregar($this->get_mensaje('eliminar_ok'), 'info');
		} catch ( guarani_error $e ) {
			throw $e;
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- Validaciones -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	function validar_disponibilidad_espacio($fechas, $espacio, $hora_ini, $hora_fin, $estado, $asignaciones_espacio = null) 
	{
		$espacio = toba::db()->quote($espacio);
		
		// en la validación de disponibilidad de un espacio, sólo se contemplan las asignaciones confirmadas
		$condicion = "
			sga_espacios_asignacion.espacio = $espacio
			AND sga_espacios_asignacion.estado = '" . asignacion_espacio::estado_confirmada . "'
			AND " . guarani_sql::armar_condicion_in($fechas, 'sga_espacios_asignacion', 'fecha');
		
		if(isset($asignaciones_espacio)) {
			$condicion .= " AND " . guarani_sql::armar_condicion_in($asignaciones_espacio, 'sga_espacios_asignacion', 'asignacion_espacio', 'asignacion_espacio', true);
		}
		
		$disponibilidad = toba::consulta_php('co_asignaciones_espacios')->obtener_disponibilidad($hora_ini, $hora_fin, $condicion);
		
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
			// en tal caso, sólo puede tratarse de asignaciones provisorias
			$resultado = $estado == asignacion_espacio::estado_provisoria;
		} else {
			// sino, el espacio está disponible
			$resultado = true;
		}
		
		return $resultado;
	}
	
	function validar_horario_clases($clases, $hora_ini, $hora_fin, $asignaciones_espacio = null) 
	{
		$condicion = guarani_sql::armar_condicion_in($clases, 'sga_espacios_asignacion', 'clase');
		
		if(isset($asignaciones_espacio)) {
			$condicion .= " AND " . guarani_sql::armar_condicion_in($asignaciones_espacio, 'sga_espacios_asignacion', 'asignacion_espacio', 'asignacion_espacio', true);
		}
		
		$disponibilidad = toba::consulta_php('co_asignaciones_espacios')->obtener_disponibilidad($hora_ini, $hora_fin, $condicion);
		
		return (guarani_arreglos::count($disponibilidad) == 0);
	}
	
	function validar_duracion_clases($clases, $asignacion, $hora_inicio, $hora_fin, $asignaciones_espacio = null) 
	{
		return toba::consulta_php('co_asignaciones_espacios')->validar_duracion_clases($hora_inicio, $hora_fin, $clases, $asignacion, $asignaciones_espacio);
	}
	
	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	function datos_banda_horaria($banda_horaria)
	{
		$parametros = explode(",", $banda_horaria);
		
		$datos_banda_horaria = array();
		$datos_banda_horaria['comision'] = $parametros[0];
		$datos_banda_horaria['asignacion'] = $parametros[1];
		$datos_banda_horaria['banda_horaria'] = $parametros[2];
		$datos_banda_horaria['hora_inicio'] = $parametros[3];
		$datos_banda_horaria['hora_fin'] = $parametros[4];
		$datos_banda_horaria['fecha_desde'] = $parametros[5];
		$datos_banda_horaria['fecha_hasta'] = $parametros[6];

		return $datos_banda_horaria;
	}
}
?>