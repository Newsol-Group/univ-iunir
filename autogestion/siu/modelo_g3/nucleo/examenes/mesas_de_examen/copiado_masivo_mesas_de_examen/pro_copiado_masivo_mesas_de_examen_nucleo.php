<?php

class pro_copiado_masivo_mesas_de_examen_nucleo extends guarani_procesos_bk
{
	//Parámetros
	protected $anio_origen;
	protected $turno_origen;
	protected $ubicacion;
	protected $anio_destino;
	protected $turno_destino;
	protected $criterio;
	protected $criterio_dia_no_habil;
	protected $mesas_en_destino;
	protected $actividades	= array();
	
	//Constantes utilizadas en los índices de los diferentes reportes generados por el proceso.
	const reporte_activ_con_mesas	= 1;
	const reporte_mesas_copiadas 	= 2;
	const reporte_mesas_no_copiadas = 3;
	const reporte_mesas_con_observ 	= 4;
	const reporte_resultados		= 5;
	
	const nombre = 'pro_copiado_masivo_mesas_de_examen';
	
	function set_datos($datos = array())
	{
		$this->anio_origen			= $datos['origen']['anio_academico_origen'];
		$this->turno_origen			= $datos['origen']['turno_origen'];
		$this->turno_origen_nombre	= $datos['origen']['turno_examen_nombre'];
		$this->ubicacion			= $datos['origen']['ubicacion'];
		$this->ubicacion_nombre 	= $datos['origen']['ubicacion_nombre'];
		$this->anio_destino			= $datos['destino']['anio_academico_destino'];
		$this->turno_destino		= $datos['destino']['turno_destino'];
		$this->turno_destino_nombre	= $datos['destino']['turno_destino_nombre'];
		$this->criterio				= $datos['destino']['criterio'];
		$this->mesas_en_destino 	= $datos['destino']['mesas_destino'];
		$this->actividades			= $datos['actividades'];
		$this->criterio_dia_no_habil= $datos['destino']['criterio_dia_no_habil'];
	}
	
	function procesar ()
	{
		$paso = 100 / guarani_arreglos::count($this->actividades);
		
		/**
		 * Se recorren todas las Actividades, recuperando todas sus mesas y copiando cada una en el turno y año destino 
		 * para la actividad dada, de acuerdo al criterio seleccionado por el usuario.
		 */
		//Contadores para los totales a mostrar en los campos de resultados.
		$total_activ_con_mesas		= 0;
		$total_mesas_copiadas 		= 0;
		$total_mesas_no_copiadas	= 0;
		$total_mesas_con_observ		= 0; 
		foreach ($this->actividades as $actividad) {
            // Control de aborto en pro
            $estado = $this->get_estado();
            if ((isset($estado['abortar'])) && ($estado['abortar'])) {
                $this->abortar = true;
                break;
            }
			//Contador de mesas creadas con éxito (con o sin observaciones) para la actividad.
			$mesas_copiadas_activ = 0;
			/**
			 * Se obtienen las mesas correspondientes a la actividad con las condiciones pedidas.
			 * Se sabe que la actividad tendrá al menos una Mesa de Examen con dichas condiciones, ya que de no 
			 * ser así no se hubiese recuperado la Actividad dentro del conjunto que se pasa al proceso.
			 */
			$mesas_actividad  = toba::consulta_php('co_mesas_de_examen')->get_mesas_actividad_en_turno_ubicacion($actividad['elemento'], $this->turno_origen, $this->ubicacion);			
			//Armo el registro para los reportes de Actividades, según corresponda con el resultado de la copia de la mesa.
			$mensaje = array();
			$mensaje['elemento']		= $actividad['elemento'];
			$mensaje['elemento_descr']  = '(' . $actividad['codigo'] . ') ' . $actividad['nombre'];
			/**
			 * Si el usuario seleccionó el criterio 'Sin mesas en Destino', debe chequearse si la Actividad tiene Mesas 
			 * definidas en el Turno Destino. De ser así, las Mesas del Origen no se copiarán.
			 */
			$copiar = false;
			if ($this->mesas_en_destino == comunes::no) {
				//Se recuperan, si existen, las mesas definidas para la misma actividad en el turno destino.
				$mesas_destino = toba::consulta_php('co_mesas_de_examen')->get_mesas_actividad_en_turno_ubicacion($actividad['elemento'], $this->turno_destino, $this->ubicacion);
				if (empty($mesas_destino)) {
					$copiar = true;
				}
			} else {
				$copiar = true;
			}
			
			foreach ($mesas_actividad as $clave => $mesa_origen) {
				if ($copiar == false) {
					//Se insertan las Mesas definidas en el Origen para la Actividad en el informe de Mesas no creadas.
					$mensaje['mesa']				= $mesa_origen['mesa_examen'];
					$mensaje['mesa_nombre']			= $mesa_origen['nombre'];
					$mensaje['ubicacion_nombre']	= $mesa_origen['ubicacion_nombre'];
					$mensaje['catedra_nombre'] 		= $mesa_origen['catedra_nombre'];
					$mensaje['instancias']			= $mesa_origen['instancias_nombres'];
					$mensaje['observaciones'] 		= "Se seleccionó el criterio de Sin Mesas en el Destino y la Actividad tiene Mesas definidas en el mismo";
					$this->log->set($mensaje, array(self::reporte_mesas_no_copiadas));
					$this->log->guardar();
					$total_mesas_no_copiadas++;
				} else {
					//Copio la Mesa de Examen.
					$llamados_copiados = guarani::act('act_mesas_de_examen')->copiar_mesa($mesa_origen['mesa_examen'], $this->turno_destino, $this->criterio, $this->criterio_dia_no_habil);
					//Se recorren los registros retornados por la función luego de intentar copiar la mesa.
					foreach ($llamados_copiados as $llamado_retorno) {
						//Parámetros devueltos por la función.
						$codigo_ret	= $llamado_retorno['codigo_retorno'];
						//Armo el array para el reporte
						$mensaje['mesa']		= $llamado_retorno['mesa'];
						$mensaje['mesa_nombre'] = $mesa_origen['nombre'];
						//Dependiendo del código retornado se agregan al mensaje los datos que correspondan en el reporte indicado.
						switch ($codigo_ret) {
							case 0:
								//La mesa de examen se copió normalmente. Datos para el informe de Mesas Creadas.
								$mensaje['llamado'] 			= $llamado_retorno['nombre_llamado'];
								$mensaje['fecha']				= $llamado_retorno['fecha'];
								$mensaje['hora_inicio']			= $llamado_retorno['hora_inicio'];
								$mensaje['hora_fin']			= $llamado_retorno['hora_finalizacion'];
								$mensaje['catedra_nombre']		= $mesa_origen['catedra_nombre'];
								$mensaje['instancias']			= $mesa_origen['instancias_nombres'];
								$mensaje['ubicacion_nombre']	= $mesa_origen['ubicacion_nombre'];
								$this->log->set($mensaje, array(self::reporte_mesas_copiadas));
								$this->log->guardar();
								$total_mesas_copiadas++;
								$mesas_copiadas_activ++;
								break;	
							case 1:	
								//La mesa de examen no pudo copiarse. Se insertan los datos de la Mesa Origen en Mesas No Creadas y el motivo.
								$mensaje['catedra_nombre']		= $mesa_origen['catedra_nombre'];
								$mensaje['ubicacion_nombre']	= $mesa_origen['ubicacion_nombre'];
								$mensaje['observaciones']		= $llamado_retorno['observaciones'];
								$this->log->set($mensaje, array(self::reporte_mesas_no_copiadas));
								$this->log->guardar();
								$total_mesas_no_copiadas++;
								break;
							case 2:	
								//La mesa de examen se copió pero observaciones, se registran los datos en el informe de Mesas con Observaciones y el motivo.
								$mensaje['llamado'] 			= $llamado_retorno['nombre_llamado'];
								$mensaje['fecha']				= $llamado_retorno['fecha'];
								$mensaje['hora_inicio']			= $llamado_retorno['hora_inicio'];
								$mensaje['hora_fin']			= $llamado_retorno['hora_finalizacion'];
								$mensaje['catedra_nombre']		= $mesa_origen['catedra_nombre'];
								$mensaje['instancias']			= $mesa_origen['instancias_nombres'];
								$mensaje['ubicacion_nombre']	= $mesa_origen['ubicacion_nombre'];
								$mensaje['observaciones']		= $llamado_retorno['observaciones'];
								$this->log->set($mensaje, array(self::reporte_mesas_con_observ));
								$this->log->guardar();
								$total_mesas_con_observ++;
								$mesas_copiadas_activ++;
								break;
						}
					}
				}
			}
			if ($mesas_copiadas_activ > 0) {
				//Si para la actividad se pudo copiar al menos una mesa, con o sin observaciones, se cuenta en la lista de activ con mesas creadas.
				$total_activ_con_mesas++;
				$mensaje['cant_mesas'] = $mesas_copiadas_activ;
				$this->log->set($mensaje, array(self::reporte_activ_con_mesas));
				$this->log->guardar();
			}
			
			$this->log_estado->incrementar_progreso($paso);
			$this->log->guardar();		
		}
			
		/**
		 * Se setean las cantidades totales resultantes para los contadores de mesas copiadas, sin copiar, copiadas con
		 * observaciones y actividades con mesas copiadas.
		 */
		$mensaje_resultado = array();
		$mensaje_resultado['cant_activ_con_mesas']		= $total_activ_con_mesas;
		$mensaje_resultado['cant_mesas_copiadas']		= $total_mesas_copiadas;
		$mensaje_resultado['cant_mesas_no_copiadas']	= $total_mesas_no_copiadas;
		$mensaje_resultado['cant_mesas_con_observ']		= $total_mesas_con_observ;
		$this->log->set($mensaje_resultado,array(self::reporte_resultados));
		$this->log->guardar();
	}
}
?>