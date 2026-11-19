<?php
/**
 *
 *
 * @package 	Reconocimiento de Actividades
 * @subpackage 	Equivalencias
 */
class pro_cargar_equiv_masiva_nucleo extends guarani_procesos_bk
{

	// Código interno de la operación que invocó el proceso (necesario para recuperar los controles de cada punto).
	protected $operacion;
	protected $usuario;

	protected $datos_equiv = array();
	protected $datos_grales = array();
	
    const reporte_cantidades = 1;
    const reporte_equiv_otorgadas_x_act = 2;
    const reporte_equiv_otorgadas_x_alu = 3;
    const reporte_errores = 4;
	const nombre = 'pro_cargar_equiv_masiva';
    const reporte_resultados = self::reporte_cantidades;


    function set_datos($datos=array())
	{
		// Reordeno el arreglo por alumnos
		$this->operacion = $datos['operacion'];
		$this->usuario = $datos['usuario'];
		$this->datos_equiv = $this->ordenar_datos($datos['equivs']);
		unset($datos['equivs']);
		unset($datos['operacion']);
		$this->datos_grales = $datos;
	}

	function procesar()
	{
				
		if (!empty($this->datos_equiv)) {
			$paso = 100 / guarani_arreglos::count($this->datos_equiv);
		} else {
			$paso = 100;
			$this->log_estado->incrementar_progreso($paso);
		}

		//Contadores para las cantidades
		$procesados = 0;
		$cant_equiv_otorg = 0;
		$cant_alumnos_ok = 0;
		$cant_errores = 0;
		
		/**
		 * Para cada alumno se validan los puntos de control y se generarn las equivalencias correspondientes.
		 */
		foreach ($this->datos_equiv as $alumno => $datos) {            
            // Control de aborto en pro
            $estado = $this->get_estado();
            if ((isset($estado['abortar'])) && ($estado['abortar'])) {
                $this->abortar = true;
                break;
            }
            
			$procesados++;
			$contar_alumno = true;
			
			// Validar puntos de control de operacion persona y alumno
			$retorno = $this->validar_puntos_de_control_operacion($alumno);
			$mensajes = $retorno['mensajes'];
			$ret = $retorno['retorno'];

			if ($ret == guarani_punto_de_control::autorizado) {
				
				// Recorrer las propuestas validando los puntos de control
				foreach ($datos as $dato) {
					
					// Validar puntos de control
					$retorno = $this->validar_puntos_de_control_actividad($alumno, $dato);
					$mensajes = $retorno['mensajes'];
					$ret = $retorno['retorno'];
					
					// Obtengo la descripcion del resultado
					$resultado_desc = current(toba::consulta_php('co_escalas_notas')->get_escalas_notas_resultados_nombre($dato['resultado']));
					
					//Armo el arreglo de datos para los reportes
					$mensaje = array();
					$mensaje['alumno'] 				= $alumno;
					$mensaje['apellido_nombres'] 	= $dato['apellido_y_nombre'];
					$mensaje['identificacion']		= $dato['identificacion'];
					$mensaje['nota']				= $dato['nota'];
					$mensaje['resultado']			= $dato['resultado'];
					$mensaje['resultado_nombre']	= $resultado_desc['resultado_nombre'];
					$mensaje['actividad']			= $dato['actividad'];
					$mensaje['elemento']			= $dato['elemento'];
					$mensaje['observaciones']		= $mensajes;

					if ($ret == guarani_punto_de_control::autorizado) {
						
						//Abrir tramite equivalencia y registrarla
						try {
							toba::db()->abrir_transaccion();

							// Genero la cabecera de la equivalencia							
							$persona = toba::consulta_php('co_personas')->get_persona_x_usuario($this->usuario);
							$operador = $persona['persona'];
							$id_equivalencia = guarani::act('act_equivalencias')->generar_tramite_equivalencia_cabecera($alumno, $this->datos_grales['plan_version'], $this->datos_grales['fecha'], $operador, $this->datos_grales['institucion'], $this->datos_grales['responsable_academica']);
							
							// Genero el contenido del tramite
							if (isset($dato['nota'])) {
								$nota =  $dato['nota'];
								$escala_nota = $dato['escala_nota'];
							} else {
								$nota = 'NULL';
								$escala_nota = 'NULL';
							}
							$equivalencia = guarani::act('act_equivalencias')->generar_equiv_otorgada($id_equivalencia, $dato['elemento'], $dato['fecha'], $escala_nota, $nota, $dato['resultado']);

							// Genero equivalencias externas si corresponde
							$generar_equiv_externa = false;
							if (isset($dato['acta'])) {
								$acta = $dato['acta'];
								$generar_equiv_externa = true;
							} else {
								$acta = 'NULL';
							}
							if (isset($dato['libro'])) {
								$libro = $dato['libro'];
								$generar_equiv_externa = true;
							} else {
								$libro = 'NULL';
							}
							if (isset($dato['folio'])) {
								$folio = $dato['folio'];
								$generar_equiv_externa = true;
							} else {
								$folio = 'NULL';							
							}
							if ($generar_equiv_externa) {
								guarani::act('act_equivalencias')->generar_equiv_externa($equivalencia, $dato['actividad'], $dato['fecha'], $nota, $acta, $libro, $folio);
							}
															
							//Registro los datos en los reportes correspondientes
							//Registro en el reporte de Equivalencias por Alumnos 
							$this->log->set($mensaje, array(self::reporte_equiv_otorgadas_x_alu));
							if ($contar_alumno) {
								$cant_alumnos_ok++;
								$contar_alumno = false;
							}

							//Registro en el reporte de Equivalencias por Actividad
							$this->log->set($mensaje, array(self::reporte_equiv_otorgadas_x_act));
							$cant_equiv_otorg++;
							
							toba::db()->cerrar_transaccion();
						} catch (guarani_error $e) {
							toba::db()->abortar_transaccion();
							throw new guarani_error($e->getMessage());
						}
					} else {
						//Registro en el reporte de Errores
						unset($mensaje['nota']);
						unset($mensaje['resultado']);
						unset($mensaje['resultado_nombre']);						
						$this->log->set($mensaje, array(self::reporte_equiv_otorgadas_x_alu));
						$this->log->set($mensaje, array(self::reporte_equiv_otorgadas_x_act));
						$this->log->set($mensaje, array(self::reporte_errores));
						$cant_errores++;
					}
				}
			} else {
				
				//Registro en el reporte de Errores
				$mensaje = array();
				$mensaje['alumno'] 			= $datos[0]['apellido_y_nombre'];
				$mensaje['identificacion'] 	= $datos[0]['identificacion'];
				$mensaje['actividad']		= $datos[0]['actividad'];
				$mensaje['observaciones'] 	= $mensajes;
				$this->log->set($mensaje, array(self::reporte_equiv_otorgadas_x_alu));
				$this->log->set($mensaje, array(self::reporte_equiv_otorgadas_x_act));
				$this->log->set($mensaje, array(self::reporte_errores));
				$cant_errores++;
			}
			
			//Guardo en el log
			$this->log->guardar();
			$this->log_estado->incrementar_progreso($paso);
		}
		
		//Registro en el reporte de cantidades de alumnos
		$mensaje = array();
		$mensaje['procesados'] = $procesados;
		$mensaje['cant_equiv_otorgada'] = $cant_equiv_otorg ;
		$mensaje['cant_alumnos_equiv_otorgada'] = $cant_alumnos_ok;
		$mensaje['errores'] = $cant_errores;
		$this->log->set($mensaje, array(self::reporte_cantidades));
		$this->log->guardar();
	}
	
	function validar_puntos_de_control_operacion($alumno)
	{
		toba::solicitud()->set_datos_item(array('item_parametro_a' => $this->operacion));
		$persona = toba::consulta_php('co_alumnos')->get_persona_alumno($alumno);
		$params = array('fecha'			=>	$this->datos_grales['fecha'],
						'persona'		=>	$persona,
						'plan_version'	=>	$this->datos_grales['plan_version'],
						'alumno'		=>	$alumno);
		
		$ptos_control_a_validar = array(guarani_punto_de_control::alumno_entidad,
										guarani_punto_de_control::persona_operacion,
										guarani_punto_de_control::alumno_operacion);
		$retorno = guarani::validador_puntos_de_control($ptos_control_a_validar, $params, true)->controlar();
		$mensajes = guarani::validador_puntos_de_control()->get_mensajes(comunes::interfaz_autogestion);
		$mensajes = implode('||', aplanar_matriz($mensajes, 'mensaje'));
		return array('retorno' => $retorno, 'mensajes' => $mensajes);
	}

	function validar_puntos_de_control_actividad($alumno, $datos_actividad)
	{
		$params = array('fecha'			=>	$datos_actividad['fecha'],
						'plan_version'	=>	$this->datos_grales['plan_version'],
						'elemento'		=>	$datos_actividad['elemento'],
						'alumno'		=>	$alumno);
		
		$ptos_control_a_validar = array(guarani_punto_de_control::alumno_actividad);
		$retorno = guarani::validador_puntos_de_control($ptos_control_a_validar, $params, true)->controlar();
		$mensajes = guarani::validador_puntos_de_control()->get_mensajes(comunes::interfaz_autogestion);
		$mensajes = implode('||', aplanar_matriz($mensajes, 'mensaje'));
		return array('retorno' => $retorno, 'mensajes' => $mensajes);
	}
	
	function ordenar_datos($datos) {
		$sort = array();
		foreach($datos as $dato) {
			$t = explode('_', $dato['codigo']);
			$alumno = $t[0];
			$elemento = $t[1];
			
			$t = $dato;
			unset($t['codigo']);
			
			$sort[$alumno][] = $t;
		}
		return $sort;
	}
	
}
?>