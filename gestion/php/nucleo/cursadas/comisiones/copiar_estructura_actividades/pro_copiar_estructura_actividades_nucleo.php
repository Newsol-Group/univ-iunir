<?php
/**
 *
 *
 * @package		Cursadas
 * @subpackage	Comisiones
 * 
 */
class pro_copiar_estructura_actividades_nucleo extends guarani_procesos_bk
{
	protected $parametros = array();

	// Constantes utilizadas en los índices de los reportes generados durante el proceso
	const reporte_comisiones = 1;
	const reporte_actividades_con_comisiones = 2;
	const reporte_resultados = 3;
	const nombre = 'pro_copiar_estructura_actividades';

	function set_datos($datos = array())
	{
		$this->origen = $datos['info_origen'];
		$this->destino = $datos['info_destino'];
		$this->actividades = $datos['actividades'];
		$this->comisiones = $datos['comision'];
	}

	function procesar()
	{
		// Se inicializan los contadores 
		$cant_comisiones = 0;
		$cant_actividades_con_comisiones = 0;
		$paso = 100 / guarani_arreglos::count($this->actividades);

		/*
		 * Para cada Comisión origen a copiar, y por cada Actividad destino seleccionada aplico la copia a través de una 
		 * función postgres.
		 */
		foreach ($this->actividades as $actividad) {
			//Contador de comisiones creadas con éxito (con o sin observaciones) para la actividad.
			$comisiones_copiadas_activ = 0;
			$estado = $this->get_estado();
			if ((isset($estado['abortar'])) && ($estado['abortar'])) {
				$this->abortar = true;
				break;
			}

			foreach ($this->comisiones as $comision) {
				$propuestas = aplanar_matriz(toba::consulta_php('co_comisiones')->get_propuestas_comision($comision), 'propuesta');
				
				// Parámetro de sistema para Asistencia por hora o por clase y el parámetro de porcentaje.
				$asistencia_hr = guarani::parametros()->get_valor('asist_computa_inasis_por_horas', $propuestas);
				$asist_cant_inasist = guarani::parametros()->get_valor('asist_indicar_cantidad_inasistencias', $propuestas);
				
				$retorno = guarani::act('act_comisiones')->copiar_estructura_comision($comision, $actividad['elemento'], $this->destino['copiar_subcomisiones'], $this->destino['docentes_comisiones'], $this->destino['bh_comisiones'], $this->destino['copiar_division'], $asistencia_hr, $asist_cant_inasist);

				// Parámetros devueltos
				$parametros = current($retorno);
				$codigo_retorno = $parametros['codigo_retorno'];
				$observaciones = $parametros['observaciones'];

				// Datos para el reporte
				$datos_comision = current(toba::consulta_php('co_comisiones')->get_datos_comision($comision));
				$mensaje = array();
				$mensaje['actividad'] = $actividad['actividad_descr'];
				$mensaje['comision'] = $datos_comision['comision_nombre'];
				$mensaje['turno'] = $datos_comision['turno_nombre'];
				$mensaje['catedra'] = $datos_comision['catedra_nombre'];
				$mensaje['ubicacion'] = $datos_comision['ubicacion_nombre'];
				$mensaje['cupo'] = $datos_comision['cupo'];
				$mensaje['instancias'] = $datos_comision['instancias_nombre'];
				$mensaje['modalidad'] = $datos_comision['modalidad_nombre'];
				$mensaje['fecha_inicio'] = $datos_comision['fecha_inicio_dictado'];
				$mensaje['fecha_fin'] = $datos_comision['fecha_fin_dictado'];

				// Dependiendo del código retornado por la función, se establecen los datos en el reporte que corresponda
				switch ($codigo_retorno) {
					case 0:
						// Comisión copiada ok: reporte de Comisiones
						$this->log->set($mensaje, array(self::reporte_comisiones));
						$comisiones_copiadas_activ++;
						$cant_comisiones++;
						break;
				}

				$this->log->guardar();
			}

			// Si a la actividad en curso se le pudo copiar al menos una comisión, 
			// se la agrega al reporte de actividades con comisiones creadas
			if ($comisiones_copiadas_activ > 0) {
				// Se arma el registro para el reporte de Actividades con comisiones
				$mensaje = array();
				$mensaje['elemento'] = $actividad['elemento'];
				$mensaje['elemento_descr'] = $actividad['actividad_descr'];
				$mensaje['cant_comisiones'] = $comisiones_copiadas_activ;
				$this->log->set($mensaje, array(self::reporte_actividades_con_comisiones));
				$this->log->guardar();
				$cant_actividades_con_comisiones++;
			}

			$this->log_estado->incrementar_progreso($paso);
		}

		// Registro para el reporte de resultados
		$mensaje = array();
		$mensaje['comisiones'] = $cant_comisiones;
		$mensaje['actividades_con_comisiones'] = $cant_actividades_con_comisiones;
		$this->log->set($mensaje, array(self::reporte_resultados));
		$this->log->guardar();
	}

}
?>
