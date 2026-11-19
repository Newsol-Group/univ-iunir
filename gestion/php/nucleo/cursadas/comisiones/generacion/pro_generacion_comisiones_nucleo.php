<?php
class pro_generacion_comisiones_nucleo extends guarani_procesos_bk
{
	protected $actividades = array();
	protected $parametros = array();
	protected $turnos = array();
	protected $instancias = array();
	protected $modalidades = array();
	protected $destino;
	protected $planes;
	protected $gen_com_siempre = false;
	protected $habilitar_inscripcion;

	// Constantes utilizadas en los índices de los reportes generados durante el proceso
	const reporte_actividades_con_comisiones = 1;
	const reporte_comisiones = 2;
	const reporte_comisiones_con_obs = 3;
	const reporte_actividades_sin_comisiones = 4;
	const reporte_resultados = 5;
	const nombre = 'pro_generacion_comisiones';

	function set_datos($datos = array())
	{
		$this->actividades = $datos['actividades'];
		$this->parametros = $datos['parametros'];
		$this->turnos = $datos['turnos'];
		$this->instancias = $datos['instancias'];
		$this->modalidades = $datos['modalidades'];
		$this->planes = $datos['planes'];
		$this->destino = $datos['destino'];
		$this->gen_com_siempre = $datos['gen_com_siempre'];
		$this->habilitar_inscripcion = $datos['habilitar_inscripcion'];
		$this->asistencia_hr = $datos['asist_computa_inasis_por_horas'];
		$this->asist_cant_inasist = $datos['asist_indicar_cantidad_inasistencias'];
	}

	function procesar()
	{
		// Se setean los nombres para las comisiones
		comision::set_nombres_default_comisiones();

		// Parámetros generales para la función
		$inscripcion_habilitada = $this->habilitar_inscripcion ? comunes::si : comunes::no;
		$utilizar_docentes = $this->parametros['utilizar_docentes'] ? 'true' : 'false';
		$per_lectivo_destino = $this->destino['periodo_lectivo_destino'];
		$ubicacion = $this->destino['ubicacion'];
		$ubicacion_nombre = toba::consulta_php('co_ubicaciones')->get_ubicacion_nombre($ubicacion);

		$datos_per = toba::consulta_php('co_periodos_lectivos')->get_datos_periodo_lectivo($per_lectivo_destino);
		$paso = 100 / guarani_arreglos::count($this->actividades);

		// Inicialización de los contadores 
		$cant_comisiones = 0;
		$cant_comisiones_con_obs = 0;
		$cant_actividades_con_comisiones = 0;
		$cant_actividades_sin_comisiones = 0;

		// Se recorren las Actividades generando sus Comisiones
		foreach ($this->actividades as $actividad) {
			$estado = $this->get_estado();
			if ((isset($estado['abortar'])) && ($estado['abortar'])) {
				$this->abortar = true;
				break;
			}
			
			// Contador de comisiones creadas con éxito (con o sin observaciones) para la actividad
			$comisiones_generadas_activ = 0;

			// Se inicializa contador de Comisiones en la posición siguiente a la última del arreglo de nombres
			$cont_comisiones = $this->get_posicion_comision_de_actividad_en_per_lect($actividad['elemento'], $per_lectivo_destino);

			// Declaración de arreglos
			$catedras = array();

			// Se obtienen las Cátedras
			if ($this->parametros['catedras_activas']) {
				// Se obtienen las Cátedras de la actividad. En caso de no poseer ninguna, y habiendo seleccionado el checkbox 
				// 'Crear Comisión si la Actividad no tiene Cátedra' se le crea una comisión sin cátedra.
				$catedras = toba::consulta_php('co_catedras')->get_catedras_actividad_para_comisiones($actividad['elemento']);
				if (empty($catedras) && $this->parametros['crear_comision_x_catedra_act']) {
					$catedras[]['valor'] = 'null';
				}
			} else {
				$catedras[]['valor'] = 'null';
			}

			// Se recorren las Instancias y se arman los arreglos para pasar a la función
			$instancia = array();
			$escala = array();
			$porc_asistencia = array();
			foreach ($this->instancias as $clave => $valor) {
				// Se crea la Comisión si la Instancia es Regularidad y la Actividad Requiere Cursada,
				// o bien la Instancia es Promoción y la Actividad Requiere Cursada y Permite Promoción
				if ($valor['instancia'] == comision::inst_cursar && $actividad['requiere_cursada'] == comunes::si ||
						$valor['instancia'] == comision::inst_promocionar && $actividad['requiere_cursada'] == comunes::si && $actividad['permite_promocion'] == comunes::si) {

					// Parámetros para la función (Instancia)
					if (isset($valor['escala_nota'])) {
						$escala[] = $valor['escala_nota'];
					} elseif ($valor['instancia'] == comision::inst_cursar && isset($actividad['escala_nota_cursada_regular'])) {
						$escala[] = $actividad['escala_nota_cursada_regular'];
					} elseif ($valor['instancia'] == comision::inst_promocionar && isset($actividad['escala_nota_cursada_promocion'])) {
						$escala[] = $actividad['escala_nota_cursada_promocion'];
					} else {
						continue;
					}
					$instancia[] = $valor['instancia'];
					$porc_asistencia[] = is_null($valor['porc_asistencia']) ? 0 : $valor['porc_asistencia'];
				}
			}

			// Se recorren las Cátedras
			foreach ($catedras as $catedra) {
				// Parámetros para la función (Cátedra)
				$id_catedra = $catedra['valor'];

				// Se recorren los Turnos
				foreach ($this->turnos as $turno) {
					$cant_comisiones_turno = $turno['cantidad'];
					for ($cont_comision_turno = 1; $cont_comision_turno <= $cant_comisiones_turno; $cont_comision_turno++) {
						// Se chequea si siempre se deben generar las comisiones, si no se controla que no 
						// exista alguna comisión en el período y turno especificado
						if (($this->gen_com_siempre) || ($this->puede_crear_com_turno($turno['valor'], $actividad['elemento']))) {
							// Parámetros para la función (Turno)
							$id_turno = $turno['valor'];
							if (!empty($instancia)) {
								// Parámetros para la función
								$nombre = comision::get_nombre_default_comision($cont_comisiones);
								try {
									// Se genera la Comisión
									$retorno = current(guarani::act('act_comisiones')->generar_comision($nombre, $per_lectivo_destino, $actividad['elemento'], $id_turno, $id_catedra, $inscripcion_habilitada, $ubicacion, $instancia, $escala, $porc_asistencia, $this->planes, $this->modalidades, $utilizar_docentes, $this->asistencia_hr, $this->asist_cant_inasist));
								} catch (toba_error $e) {
									// Si se produce algún error sigue con la siguiente
									echo $e->get_mensaje();
									continue;
								}

								// Se contabiliza la Comisión
								$cont_comisiones++;

								// Parámetros devueltos
								$codigo_retorno = $retorno['codigo_retorno'];
								$comision = $retorno['comision'];
								$nombre_comision = $retorno['nombre_comision'];
								$observaciones = $retorno['observaciones'];

								// Datos para el reporte
								$mensaje = array();
								$mensaje['actividad'] = '(' . $actividad['codigo'] . ')' . $actividad['nombre'];
								$mensaje['comision'] = $nombre_comision;
								$mensaje['turno'] = $turno['descr'];
								$mensaje['catedra'] = isset($catedra['descr']) ? $catedra['descr'] : '';
								$mensaje['ubicacion'] = $ubicacion_nombre;
								$mensaje['fecha_inicio'] = $datos_per['fecha_inicio_dictado'];
								$mensaje['fecha_fin'] = $datos_per['fecha_fin_dictado'];
								$mensaje['instancias'] = ($instancia == 1) ? 'Regularidad' : 'Promoción';
								
								// El cupo se setea vacio ya que actualmente no se genera y se desea que no aparezca 'null' en la impresion del reporte
								$mensaje['cupo'] = '';

								// Dependiendo del código retornado se registran los datos en el reporte que corresponda
								switch ($codigo_retorno) {
									case 0:
										// Comisión generada ok: se registra en el reporte de Comisiones y se contabiliza
										$reporte_comision = self::reporte_comisiones;
										$cant_comisiones++;
										$comisiones_generadas_activ++;
										break;
									case 1:
										// Comisión generada con errores: se registra en el reporte de Comisiones con observaciones y se contabiliza
										$reporte_comision = self::reporte_comisiones_con_obs;
										$cant_comisiones_con_obs++;
										$comisiones_generadas_activ++;
										break;
								}

								$this->log->set($mensaje, array($reporte_comision));
								$this->log->guardar();
							}
						}
					}
				}
			}

			// Registro con la actividad para el array de Actividades que corresponda
			$mensaje = array();
			$mensaje['elemento_descr'] = '(' . $actividad['codigo'] . ')' . $actividad['nombre'];

			if ($comisiones_generadas_activ > 0) {
				// Si para la actividad se pudo generar al menos una comisión, con o sin observaciones, 
				// se cuenta en la lista de actividades con comisiones creadas
				$mensaje['cant_comisiones'] = $comisiones_generadas_activ;
				$reporte_actividades = self::reporte_actividades_con_comisiones;
				$cant_actividades_con_comisiones++;
			} else {
				// En caso contrario, se cuenta en la lista de activ sin comisiones creadas
				$reporte_actividades = self::reporte_actividades_sin_comisiones;
				$cant_actividades_sin_comisiones++;
			}

			$this->log->set($mensaje, array($reporte_actividades));
			$this->log->guardar();
			$this->log_estado->incrementar_progreso($paso);
		}

		// Se arma el registro para el reporte de resultados
		$mensaje = array();
		$mensaje['comisiones'] = $cant_comisiones;
		$mensaje['comisiones_con_obs'] = $cant_comisiones_con_obs;
		$mensaje['actividades_sin_comisiones'] = $cant_actividades_sin_comisiones;
		$mensaje['actividades_con_comisiones'] = $cant_actividades_con_comisiones;
		$this->log->set($mensaje, array(self::reporte_resultados));
		$this->log->guardar();
	}

	function get_posicion_comision_de_actividad_en_per_lect($elemento, $per_lectivo_destino)
	{
		return toba::consulta_php('co_comisiones')->get_cant_comisiones_de_actividad_en_per_lectivo($elemento, $per_lectivo_destino);
	}

	/**
	 * Devuelve true o false dependiendo si se puede crear o no una comisión para
	 * una actividad en un período lectivo, y en un turno -mañana, tarde o noche-
	 *
	 * @param 	integer  $turno
	 * @param 	integer  $elemento
	 * @return 	boolean
	 */
	function puede_crear_com_turno($turno, $elemento)
	{
		$filtro = "sga_comisiones.elemento = $elemento";
		$filtro .= 'AND	sga_comisiones.periodo_lectivo	= ' . $this->destino['periodo_lectivo_destino'];
		$filtro .= ($turno != 'null') ? "AND sga_comisiones.turno = $turno" : 'AND sga_comisiones.turno IS NULL';

		$com = toba::consulta_php('co_comisiones')->get_listado_simple($filtro);
		return empty($com);
	}

}
?>