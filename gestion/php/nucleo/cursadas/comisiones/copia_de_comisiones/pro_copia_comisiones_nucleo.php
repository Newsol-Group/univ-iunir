<?php
class pro_copia_comisiones_nucleo extends guarani_procesos_bk
{
	protected $comisiones;
	protected $per_lec_destino;
	protected $ubicacion_nombre;
	protected $ubicacion;
	protected $copiar_bandas;
	protected $copiar_docentes;
	protected $copiar_espacios;
	protected $copiar_evaluaciones;


	// Constantes utilizadas en los índices de los reportes generados durante el proceso
	const reporte_comisiones = 1;
	const reporte_comisiones_no_copiadas = 2;
	const reporte_comisiones_con_obs = 3;
	const reporte_actividades_con_comisiones = 4;
	const reporte_resultados = 5;
	const nombre = 'pro_copia_comisiones';

	function set_datos($datos = array())
	{
		$this->comisiones = $datos['comisiones'];
		$this->per_lec_destino = $datos['periodo_lectivo_destino'];
		$this->ubicacion_nombre = $datos['ubicacion_nombre'];
		$this->ubicacion = $datos['ubicacion'];
		$this->copiar_bandas = (($datos['copiar_bandas']) == 1) ? 'true' : 'false';
		$this->copiar_docentes = (($datos['copiar_docentes']) == 1) ? 'true' : 'false';
		$this->copiar_evaluaciones = (($datos['copiar_evaluaciones']) == 1) ? 'true' : 'false';
		$this->copiar_espacios = (($datos['copiar_espacios']) == 1) ? 'true' : 'false';
		
	}

	function procesar()
	{
		// Inicialización de los contadores 
		$cant_comisiones = 0;
		$cant_comisiones_con_obs = 0;
		$cant_comisiones_no_copiadas = 0;
		$comisiones_copiadas_actividad = array();

		$paso = 100/guarani_arreglos::count($this->comisiones);

		foreach ($this->comisiones as $comision) {
			$estado = $this->get_estado();
			if (isset($estado['abortar']) && $estado['abortar']) {
				$this->abortar = true;
				break;
			}

			$propuestas = aplanar_matriz(toba::consulta_php('co_comisiones')->get_propuestas_comision($comision), 'propuesta');

			// Se consulta el parámetro de sistema para asistencia por hora o por clase y el parámetro de porcentaje.
			$asistencia_hr = guarani::parametros()->get_valor('asist_computa_inasis_por_horas', $propuestas);
			$asist_cant_inasist = guarani::parametros()->get_valor('asist_indicar_cantidad_inasistencias', $propuestas);

			$retorno = guarani::act('act_comisiones')->copiar_comision($comision, $this->per_lec_destino, $asistencia_hr, $asist_cant_inasist, $this->copiar_docentes, $this->copiar_bandas, $this->copiar_evaluaciones, $this->copiar_espacios);

			// Parámetros devueltos
			$parametros = current($retorno);
			$codigo_retorno = $parametros['codigo_retorno'];
			$observaciones = $parametros['observaciones'];

			// Datos para el reporte
			$mensaje = array();
			$datos_comision = current(toba::consulta_php('co_comisiones')->get_datos_comision($comision));
			$datos_per_lec_destino = toba::consulta_php('co_periodos_lectivos')->get_datos_periodo_lectivo($this->per_lec_destino);
			$mensaje['actividad'] = $datos_comision['actividad_desc'];
			$mensaje['comision'] = $datos_comision['comision_nombre'];
			$mensaje['turno'] = $datos_comision['turno_nombre'];
			$mensaje['catedra'] = $datos_comision['catedra_nombre'];
			$mensaje['ubicacion'] = $this->ubicacion_nombre;
			$mensaje['cupo'] = $datos_comision['cupo'];
			$mensaje['instancias'] = $datos_comision['instancias_nombre'];
			$mensaje['fecha_inicio'] = $datos_per_lec_destino['fecha_inicio_dictado'];
			$mensaje['fecha_fin'] = $datos_per_lec_destino['fecha_fin_dictado'];

			// Dependiendo del código retornado por la función, se establecen los datos en el reporte que corresponda
			switch ($codigo_retorno) {
				case 0:
					// Comisión copiada ok: reporte de Comisiones
					$this->log->set($mensaje, array(self::reporte_comisiones));
					$cant_comisiones++;
					if (isset($comisiones_copiadas_actividad[$datos_comision['elemento']])) {
						$comisiones_copiadas_actividad[$datos_comision['elemento']]['cant']++;
					} else {
						$comisiones_copiadas_actividad[$datos_comision['elemento']] = array('nombre' => $datos_comision['actividad_desc'], 'cant' => 1);
					}
					break;
				case 1:
					// Comisión no copiada: reporte de Comisiones no copiadas
					$mensaje['observaciones'] = $observaciones;
					$this->log->set($mensaje, array(self::reporte_comisiones_no_copiadas));
					$cant_comisiones_no_copiadas++;
					break;
				case 2:
					// Comisión copiada con errores: reporte de Comisiones con observaciones
					$mensaje['observaciones'] = $observaciones;
					$this->log->set($mensaje, array(self::reporte_comisiones_con_obs));
					$cant_comisiones_con_obs++;
					break;
			}
			$this->log->guardar();
			$this->log_estado->incrementar_progreso($paso);
		}

		foreach ($comisiones_copiadas_actividad as $clave => $valor) {
			// Registro para el reporte de Actividades con comisiones
			$mensaje = array();
			$mensaje['elemento'] = $clave;
			$mensaje['elemento_descr'] = $valor['nombre'];
			$mensaje['cant_comisiones'] = $valor['cant'];
			$this->log->set($mensaje, array(self::reporte_actividades_con_comisiones));
			$this->log->guardar();
		}

		// Se arma el registro para el reporte de resultados
		$mensaje = array();
		$mensaje['comisiones'] = $cant_comisiones;
		$mensaje['comisiones_con_obs'] = $cant_comisiones_con_obs;
		$mensaje['comisiones_no_copiadas'] = $cant_comisiones_no_copiadas;
		$mensaje['actividades_con_comisiones'] = guarani_arreglos::count($comisiones_copiadas_actividad);
		$this->log->set($mensaje, array(self::reporte_resultados));
		$this->log->guardar();
	}
}
?>