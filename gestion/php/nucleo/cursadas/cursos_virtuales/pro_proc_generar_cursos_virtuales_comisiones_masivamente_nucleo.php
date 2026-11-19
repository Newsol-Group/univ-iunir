<?php
class pro_proc_generar_cursos_virtuales_comisiones_masivamente_nucleo extends guarani_procesos_bk
{
	//Parámetros
	//Constantes utilizadas en los índices de los diferentes reportes generados por el proceso.


	protected $cant_curso_error;
	protected $cant_curso;
	protected $cant_docentes;
	protected $cant_alumnos;
	//
	// Datos relacionados a Moodle.
	protected $s__comision;
	protected $s__nombre_curso;
	protected $s__elemento_nombre;
	protected $s__tipo_documento;
	protected $s__elemento_codigo;
	protected $s__alumnos_comision;
	protected $s__alumnos_curso_virtual_guarani;
	protected $s__alumnos_curso_virtual_moodle;
	protected $s__alumnos_eliminados_comision;
	protected $s__docentes_comision;
	protected $s__docentes_curso_virtual_guarani;
	protected $s__docentes_curso_virtual_moodle;
	protected $s__datos_plataforma;
	protected $s__roles_docentes;
	/**
	 * @var moodle $moodle_obj Objeto de la clase moodle
	 */
	protected $moodle_obj = null;
	protected $s__datos_curso_virtual;
	protected $personas_para_crear = [];
	protected $alumnos_para_matricular = [];
	protected $alumnos_para_desmatricular = [];
	protected $docentes_para_matricular = [];
	protected $docentes_para_desmatricular = [];
	protected $crear_curso = false;
	protected $vincular_curso = false;
	protected $datos_form_crear;
	protected $datos_form_vincular;
	protected $datos;
	protected $datos_formulario;

	const rep_curso = 1;
	const rep_docentes = 2;
	const rep_alumnos = 3;
	const reporte_resultados = 4;
	const nombre = 'pro_proc_generar_cursos_virtuales_comisiones_masivamente';
	const cant_personas_lote = 1000;

	function set_datos($datos)
	{
		$this->datos = $datos['datos'];
		$this->datos_formulario = $datos['datos_formulario'];
		$this->s__datos_plataforma = $datos['datos_plataforma'];

		$this->cant_curso = 0;
		$this->cant_curso_error = 0;
		$this->alumnos_sincronizados = 0;
		$this->alumnos_sincronizados_error = 0;
		$this->docentes_sincronizados = 0;
		$this->docentes_sincronizados_error = 0;
	}

	function procesar()
	{
		$paso = 100 / guarani_arreglos::count($this->datos);

		$tipo_documento = toba::consulta_php('co_personas')->get_tipos_documentos();
		foreach ($tipo_documento as $dato) {//'valor', 'descr_ab'
			$this->s__tipo_documento[$dato['valor']] = $dato['descr_ab'];
		}
		//si Agrupar Comisiones de misma Actividad y Crear un solo Curso Virtual checked
		if ($this->datos_formulario['agrupar_comisiones'] == true) {
			$agrupado = array();
			foreach ($this->datos as $curso) {
				//solamente los que no tengan ya un curso creado en moodle se va a poder agrupar
				if ($curso['curso_creado'] == comunes::no_desc) {
					$nombre_curso = $curso['periodo_nombre'] . ' - (' . $curso['elemento_codigo'] . ') - ' . $curso['elemento_nombre'];

					$agrupado[$curso['elemento']]['comision'][] = $curso['comision'];
					$agrupado[$curso['elemento']]['comision_nombre'][] = $curso['comision_nombre'];
					$agrupado[$curso['elemento']]['nombre_curso'] = $nombre_curso;
					$agrupado[$curso['elemento']]['elemento_nombre'] = $curso['elemento_nombre'];
					$agrupado[$curso['elemento']]['elemento_codigo'] = $curso['elemento_codigo'];
					$agrupado[$curso['elemento']]['fecha_fin_dictado'][] = $curso['fecha_fin_dictado'];
					$agrupado[$curso['elemento']]['fecha_inicio_dictado'][] = $curso['fecha_inicio_dictado'];
					$agrupado[$curso['elemento']]['comision'] = array_unique($agrupado[$curso['elemento']]['comision']);
				} else {

					$error = strip_tags(guarani_mensajes::get("800_fallo_creacion_curso_moodle", ["-"]));
					$reporte_curso = array('elemento_codigo' => $curso['elemento_codigo'], 'actividad' => $curso['elemento_nombre'], 'curso_virtual' => '', 'error' => $error . 'Curso virtual ya creado');
					$this->log->set($reporte_curso, array(self::rep_curso));
					$this->cant_curso_error++;
				}
			}
			if (guarani_arreglos::count($agrupado) > 0) {
				//alumnos por comision_guarani
				$paso = 100 / guarani_arreglos::count($agrupado);
				foreach ($agrupado as $curso_actividad) {
					$this->s__roles_docentes = toba::consulta_php('co_configuracion')->get_roles_docentes_by_plataforma($this->s__datos_plataforma['plataforma'], 'int_pv_roles_docentes.responsabilidad IS NOT NULL');

					// Si desde la aplicación se mandó señal de corte, no se continúa con 
					// el procesamiento (lo procesado hasta el momento no se revierte).
					$estado = $this->get_estado();
					if ((isset($estado['abortar'])) && ($estado['abortar'])) {
						$this->abortar = true;
						break;
					}
					try {
						$min = min($curso_actividad['fecha_inicio_dictado']);
						$max = max($curso_actividad['fecha_fin_dictado']);
						if (!is_null($min)) {
							$fecha_inicio_obj = DateTime::createFromFormat('Y-m-d', $min);
							$startdate = $fecha_inicio_obj->getTimestamp();

							// Si ingresó "Fecha Inicio" y "Fecha Fin"
							if (!is_null($max)) {
								$fecha_fin_obj = DateTime::createFromFormat('Y-m-d', $max);
								$enddate = $fecha_fin_obj->getTimestamp();
							}
						}

						$nombre_curso = $curso_actividad['nombre_curso']; //$curso['periodo_nombre'] . ' - (' . $curso['elemento_codigo'] . ') - ' . $curso['elemento_nombre'] . ' - ' . $curso['comision_nombre'];
						$this->s__nombre_curso = $nombre_curso;
						$this->s__elemento_nombre = $curso_actividad['elemento_nombre'];
						$this->s__elemento_codigo = $curso_actividad['elemento_codigo'];
						$summary ="Comisiones :". implode(" - ", $curso_actividad['comision_nombre']);	
		

						list($crear_curso_ok, $messageMoodle) = $this->crear_curso_moodle($curso_actividad['comision'], $nombre_curso, $nombre_curso, $this->datos_formulario['categoria'], $summary, 1, $startdate, $enddate);

						// Si el curso se creo bien
						if ($crear_curso_ok) {

							toba::notificacion()->info(guarani_mensajes::get("800_creacion_exitosa_curso_moodle"));
							$this->s__alumnos_curso_virtual_guarani = [];
							$this->s__alumnos_curso_virtual_moodle = [];
							$this->s__docentes_curso_virtual_guarani = [];
							$this->s__docentes_curso_virtual_moodle = [];
							$this->cant_curso++;
							$reporte_curso = array('elemento_codigo' => $curso_actividad['elemento_codigo'], 'actividad' => $curso_actividad['elemento_nombre'], 'curso_virtual' => $nombre_curso, 'error' => '');
							$this->log->set($reporte_curso, array(self::rep_curso));
							//Si se debe importar contenido de otro curso
							//					
							//$importacionOk = $this->get_moodle_obj()->importarContenidoCurso($datos['copiar_de'], $this->s__datos_curso_virtual['id_curso_externo']);
							// Si falló la importación se notifica
							//if (!$importacionOk) {
							//	toba::notificacion()->warning(guarani_mensajes::get("800_fallo_importacion_curso_moodle"));
							//}
							//					
							// Se matriculan los docentes

							if ($this->datos_formulario['generar_grupos'] == true) {
								
								foreach ($curso_actividad['comision'] as $cursos_comisiones) {
									$this->s__datos_curso_virtual = toba::consulta_php('co_comisiones')->get_curso_virtual_by_comision($cursos_comisiones);
									$descripcion = 'SIN DESCRIPCION';
									$datos_curso = toba::consulta_php('co_comisiones')->get_nombre_comision($cursos_comisiones);
					
									$this->crear_grupo_curso_moodle(
							
										$cursos_comisiones,
										nl2br($descripcion),
										$this->s__datos_curso_virtual['id_curso_externo'],
										$datos_curso,
										$cursos_comisiones		
									);
								}
							}

							if ($this->datos_formulario['act_matricula_docentes'] == true) {

								foreach ($curso_actividad['comision'] as $cursos_comisiones) {
									$this->s__datos_curso_virtual = toba::consulta_php('co_comisiones')->get_curso_virtual_by_comision($cursos_comisiones);
									//docentes						
									$this->s__docentes_comision = toba::consulta_php('co_comisiones')->get_docentes_comision_moodle($cursos_comisiones);
									$this->s__docentes_curso_virtual_guarani = toba::consulta_php('co_comisiones')->get_docentes_curso_moodle($this->s__datos_curso_virtual['curso'], 'int_pv_usuarios.id_usuario_externo IS NOT NULL');
									//dato actualizado del moodle
									$this->s__docentes_curso_virtual_moodle = $this->get_docentes_curso_virtual();
									$this->actualizar_docentes_moodle();
								}
							}

							// Si se deben matricular los alumnos
							if ($this->datos_formulario['act_matricula_alumnos'] == true) {

								// Se matriculan los alumnos $curso_actividad['comision']
								foreach ($curso_actividad['comision'] as $cursos_comisiones) {
									$this->s__datos_curso_virtual = toba::consulta_php('co_comisiones')->get_curso_virtual_by_comision($cursos_comisiones);
									$this->s__alumnos_comision = toba::consulta_php('co_comisiones')->get_alumnos_inscriptos_a_comision_moodle($cursos_comisiones);
									$this->s__alumnos_eliminados_comision = toba::consulta_php('co_comisiones')->get_alumnos_eliminados_a_comision_moodle($cursos_comisiones, 'alumnos_eliminados.usuario_moodle IS NOT NULL');
									$this->s__alumnos_curso_virtual_guarani = toba::consulta_php('co_comisiones')->get_alumnos_inscriptos_a_curso_moodle($this->s__datos_curso_virtual['curso'], 'int_pv_usuarios.id_usuario_externo IS NOT NULL');
									//dato actualizado del moodle
									$this->s__alumnos_curso_virtual_moodle = $this->get_alumnos_curso_virtual();
									$this->actualizar_alumnos_moodle();
								}
							}
							
						} else {


							$this->cant_curso_error++;
							$error = strip_tags(guarani_mensajes::get("800_fallo_creacion_curso_moodle", [$messageMoodle]));
							$reporte_curso = array('elemento_codigo' => $curso['elemento_codigo'], 'actividad' => $curso['elemento_nombre'], 'curso_virtual' => $nombre_curso, 'error' => $error);
							$this->log->set($reporte_curso, array(self::rep_curso));
							
						}
						
					} catch (toba_error $e) {
						$this->log_estado->set_error();
						$curso['observaciones'] = $e->get_mensaje();
						//	$this->log->set($curso, array(self::rep_curso));
					}

					$this->log->guardar();
					if (!$this->abortar) {
						$this->log_estado->incrementar_progreso($paso);
					}
				}
			} else {
				$this->log_estado->incrementar_progreso(100);
			}
		} else {
			$paso = 100 / guarani_arreglos::count($this->datos);
			foreach ($this->datos as $curso) {
				//alumnos por comision_guarani
				$this->s__comision = $curso['comision'];

				$this->s__docentes_comision = toba::consulta_php('co_comisiones')->get_docentes_comision_moodle($curso['comision'], $this->s__datos_plataforma['plataforma']);
				$this->s__roles_docentes = toba::consulta_php('co_configuracion')->get_roles_docentes_by_plataforma($this->s__datos_plataforma['plataforma'], 'int_pv_roles_docentes.responsabilidad IS NOT NULL');

				// Si desde la aplicación se mandó señal de corte, no se continúa con 
				// el procesamiento (lo procesado hasta el momento no se revierte).
				$estado = $this->get_estado();
				if ((isset($estado['abortar'])) && ($estado['abortar'])) {
					$this->abortar = true;
					break;
				}
				try {

					if ($curso['curso_creado'] == comunes::no_desc) {
						if (!is_null($curso['fecha_inicio_dictado'])) {
							$fecha_inicio_obj = DateTime::createFromFormat('Y-m-d', $curso['fecha_inicio_dictado']);
							$startdate = $fecha_inicio_obj->getTimestamp();

							// Si ingresó "Fecha Inicio" y "Fecha Fin"
							if (!is_null($curso['fecha_fin_dictado'])) {
								$fecha_fin_obj = DateTime::createFromFormat('Y-m-d', $curso['fecha_fin_dictado']);
								$enddate = $fecha_fin_obj->getTimestamp();
							}
						}

						$nombre_curso = $curso['periodo_nombre'] . ' - (' . $curso['elemento_codigo'] . ') - ' .substr( $curso['elemento_nombre'],0,150) . ' - ' . substr( $curso['comision_nombre'],0,100);
						$nombre_curso_abreviado = substr($curso['periodo_nombre'] . ' - (' . $curso['elemento_codigo'] . ') - '.$curso['elemento_nombre']. ' - '  , 0, 59). substr( $curso['comision_nombre'],0,40);
						$this->s__nombre_curso = $nombre_curso;
						$this->s__elemento_nombre = $curso['elemento_nombre'];
						$this->s__elemento_codigo = $curso['elemento_codigo'];
						
						list($crear_curso_ok, $messageMoodle) = $this->crear_curso_moodle($curso['comision'], $nombre_curso, $nombre_curso_abreviado, $this->datos_formulario['categoria'], '', 1, $startdate, $enddate);

						// Si el curso se creo bien
						if ($crear_curso_ok) {
							toba::notificacion()->info(guarani_mensajes::get("800_creacion_exitosa_curso_moodle"));


							$this->s__datos_curso_virtual = toba::consulta_php('co_comisiones')->get_curso_virtual_by_comision($curso['comision']);
							$this->s__alumnos_comision = toba::consulta_php('co_comisiones')->get_alumnos_inscriptos_a_comision_moodle($curso['comision']);
							$this->s__alumnos_curso_virtual_guarani = [];
							$this->s__alumnos_curso_virtual_moodle = [];
							$this->s__docentes_curso_virtual_guarani = [];
							$this->s__docentes_curso_virtual_moodle = [];
							$this->cant_curso++;
							$reporte_curso = array('elemento_codigo' => $curso['elemento_codigo'], 'actividad' => $curso['elemento_nombre'], 'curso_virtual' => $nombre_curso, 'error' => '');
							$this->log->set($reporte_curso, array(self::rep_curso));
							//Si se debe importar contenido de otro curso
							//					
							//$importacionOk = $this->get_moodle_obj()->importarContenidoCurso($datos['copiar_de'], $this->s__datos_curso_virtual['id_curso_externo']);
							// Si falló la importación se notifica
							//if (!$importacionOk) {
							//	toba::notificacion()->warning(guarani_mensajes::get("800_fallo_importacion_curso_moodle"));
							//}
							//					

							// Si se deben crear grupo  $idnumber, $description, $courseid, $name, $comision
							if ($this->datos_formulario['generar_grupos'] == true){
								$datos_curso_virtual = toba::consulta_php('co_comisiones')->get_curso_virtual_by_comision($curso['comision']);
								$this->crear_grupo_curso_moodle(
								$curso['comision'],
								nl2br($this->s__nombre_curso),
								$datos_curso_virtual['id_curso_externo'],
								$nombre_curso_abreviado,
								$curso['comision']					
							);
							}
							
							// Se matriculan los docentes
							if ($this->datos_formulario['act_matricula_docentes'] == true) {
								$this->actualizar_docentes_moodle();
							}

							// Si se deben matricular los alumnos
							if ($this->datos_formulario['act_matricula_alumnos'] == true) {
								// Se matriculan los alumnos
								$this->actualizar_alumnos_moodle();
							}
							
						} else {
							
							$this->cant_curso_error++;
							$error = strip_tags(guarani_mensajes::get("800_fallo_creacion_curso_moodle", [$messageMoodle]));
							$reporte_curso = array('elemento_codigo' => $curso['elemento_codigo'], 'actividad' => $curso['elemento_nombre'], 'curso_virtual' => $nombre_curso, 'error' => $error);
							$this->log->set($reporte_curso, array(self::rep_curso));
							
						}
					} else {

						$this->s__datos_curso_virtual = toba::consulta_php('co_comisiones')->get_curso_virtual_by_comision($curso['comision']);
						$this->s__alumnos_comision = toba::consulta_php('co_comisiones')->get_alumnos_inscriptos_a_comision_moodle($curso['comision']);
						$this->s__alumnos_curso_virtual_guarani = toba::consulta_php('co_comisiones')->get_alumnos_inscriptos_a_curso_moodle($this->s__datos_curso_virtual['curso'], 'int_pv_usuarios.id_usuario_externo IS NOT NULL');
						$this->s__alumnos_eliminados_comision = toba::consulta_php('co_comisiones')->get_alumnos_eliminados_a_comision_moodle($curso['comision'], 'alumnos_eliminados.usuario_moodle IS NOT NULL');
						$this->s__alumnos_curso_virtual_moodle = $this->get_alumnos_curso_virtual();
						$this->s__docentes_curso_virtual_guarani = toba::consulta_php('co_comisiones')->get_docentes_curso_moodle($this->s__datos_curso_virtual['curso'], 'int_pv_usuarios.id_usuario_externo IS NOT NULL');
						$this->s__docentes_curso_virtual_moodle = $this->get_docentes_curso_virtual();

						$nombre_curso = $curso['periodo_nombre'] . ' - (' . $curso['elemento_codigo'] . ') - ' . $curso['elemento_nombre'] . ' - ' . $curso['comision_nombre'];
						$this->s__nombre_curso = $nombre_curso;
						$this->s__elemento_nombre = $curso['elemento_nombre'];
						$this->s__elemento_codigo = $curso['elemento_codigo'];

						if ($this->datos_formulario['generar_grupos'] == true){

							$this->crear_grupo_curso_moodle(
							$curso['comision'],
							nl2br($this->s__elemento_nombre),
							$this->s__datos_curso_virtual['id_curso_externo'],
							$nombre_curso ,
							$curso['comision']					
							);
						}
						// Se matriculan los docentes
						if ($this->datos_formulario['act_matricula_docentes'] == true) {
							$this->actualizar_docentes_moodle();
						}

						// Si se deben matricular los alumnos
						if ($this->datos_formulario['act_matricula_alumnos'] == true) {
							// Se matriculan los alumnos
							$this->actualizar_alumnos_moodle();
						}
					}
				} catch (toba_error $e) {
					$this->log_estado->set_error();
					$curso['observaciones'] = $e->get_mensaje();
					//$this->log->set($curso, array(self::rep_curso));
				}

				$this->log->guardar();
				if (!$this->abortar) {
					$this->log_estado->incrementar_progreso($paso);
				}
			}
		}

		$mensaje_resultado['cursos_creados_vinculados'] = $this->cant_curso;
		$mensaje_resultado['cursos_creados_vinculados_error'] = $this->cant_curso_error;
		$mensaje_resultado['alumnos_sincronizados'] = $this->alumnos_sincronizados;
		$mensaje_resultado['alumnos_sincronizados_error'] = $this->alumnos_sincronizados_error;
		$mensaje_resultado['docentes_sincronizados'] = $this->docentes_sincronizados;
		$mensaje_resultado['docentes_sincronizados_error'] = $this->docentes_sincronizados_error;
		$this->log->set($mensaje_resultado, array(self::reporte_resultados));
		$this->log->guardar();
	}

	function coinciden_alumnos_moodle()
	{
		list(	$coinciden_alumnos_moodle,
				$this->personas_para_crear,
				$this->alumnos_para_matricular,
				$this->alumnos_para_desmatricular) = moodle::coincidenAlumnosMoodle($this->s__alumnos_comision, $this->s__alumnos_eliminados_comision, $this->s__alumnos_curso_virtual_guarani, $this->s__alumnos_curso_virtual_moodle);
		
		return $coinciden_alumnos_moodle;
	}

	function actualizar_alumnos_moodle()
	{
		//toba::db()->abrir_transaccion();

		try {

			$coinciden_alumnos_moodle = $this->coinciden_alumnos_moodle();

			// Si no hay que actualizar en Moodle
			if ($coinciden_alumnos_moodle) {

				return true;
				
			} else { // sino
				// Si hay usuarios para crear en Moodle
				if (!empty($this->personas_para_crear)) {
					$personas_ids = array_column($this->personas_para_crear, 'persona');
					$emails_personas = toba::consulta_php('co_personas')->get_emails_personas($personas_ids, false);

					$usuarios = [];
					$personas_usuarios = [];
					$personas_sin_email = [];

					foreach ($this->personas_para_crear as $key => $persona) {

						// Si la persona no tiene email asignado no lo matriculo en Moodle
						if (empty($emails_personas[$persona['persona']]['email'])) {
							
							$personas_sin_email[] = "{$persona['apellido']} {$persona['nombres']}";
							$error = strip_tags(guarani_mensajes::get("800_personas_falta_email_matriculacion_moodle", ['alumnos/s', "{$persona['apellido']} {$persona['nombres']}"]));
							
							if ($persona['propuesta_tipo'] == propuesta::tipo_curso) {
								$role_moodle = 'Alumno curso';
							} else { // sino
								$role_moodle = 'Alumno';
							}
							
							$reporte_alumnos = array(
								'elemento_codigo' => $this->s__elemento_codigo,
								'actividad' => $this->s__elemento_nombre,
								'curso_virtual' => $this->s__nombre_curso,
								'accion' => 'Crear en Moodle',
								'Apellido_Nombre' => $persona['apellido'] . ' ' . $persona['nombres'],
								'identificacion' => $this->s__tipo_documento[$persona['tipo_documento']] . ' N° ' . $persona['nro_documento'],
								'usuario' => $persona['usuario'],
								'rol' => $role_moodle,
								'error' => $error);
							$this->log->set($reporte_alumnos, array(self::rep_alumnos));
							continue;
						}

						$usuarios[$key]['username'] = $persona['usuario'];
						$usuarios[$key]['firstname'] = $persona['nombres'];
						$usuarios[$key]['lastname'] = $persona['apellido'];
						$usuarios[$key]['email'] = $emails_personas[$persona['persona']]['email'];
						$usuarios[$key]['idnumber'] = $persona['persona'];

						$personas_usuarios[$persona['usuario']] = $persona;
					}
					
					// Si hay personas sin email asignado muestro mensaje y salgo
					if (!empty($personas_sin_email)) {
						toba::logger()->error(guarani_mensajes::get("800_personas_falta_email_matriculacion_moodle", ['alumnos/s', implode(', ', $personas_sin_email)]));
						$this->alumnos_sincronizados_error = $this->alumnos_sincronizados_error + guarani_arreglos::count($personas_sin_email);
						return false;
					}
                    
					$lote_usuarios = array_chunk($usuarios, static::cant_personas_lote, true);
                    foreach ($lote_usuarios as $usuarios) { 
						
						try {

							$usuarios_moodle = $this->get_moodle_obj()->crearUsuarios($usuarios, true);

							// Si la creación en Moodle salió bien
							foreach ($usuarios as $usuario) {

								$reporte_alumnos = array(
									'elemento_codigo' => $this->s__elemento_codigo,
									'actividad' => $this->s__elemento_nombre,
									'curso_virtual' => $this->s__nombre_curso,
									'accion' => 'Crear en Moodle',
									'Apellido_Nombre' => $usuario['lastname'] . ' ' . $usuario['firstname'],
									'identificacion' => $usuario['email'],
									'usuario' => $usuario['username'],
									'rol' => '',
									'error' => '');
								$this->log->set($reporte_alumnos, array(self::rep_alumnos));

							}

						} catch (Exception $e) { // Si falló la creación en Moodle

							$error = strip_tags(guarani_mensajes::get("800_fallo_actualizacion_alumnos_moodle", [$e->getMessage()]));
							foreach ($usuarios as $usuario) {

								$reporte_alumnos = array(
									'elemento_codigo' => $this->s__elemento_codigo,
									'actividad' => $this->s__elemento_nombre,
									'curso_virtual' => $this->s__nombre_curso,
									'accion' => 'Crear en Moodle',
									'Apellido_Nombre' => $usuario['lastname'] . ' ' . $usuario['firstname'],
									'identificacion' => $usuario['email'],
									'usuario' => $usuario['username'],
									'rol' => '',
									'error' => $error);
								$this->log->set($reporte_alumnos, array(self::rep_alumnos));

							}
							$this->alumnos_sincronizados_error = $this->alumnos_sincronizados_error + guarani_arreglos::count($usuarios);
							// Relanzo la excepción
							throw $e;

						}

						// Si hay usuarios a guardar en la DB
						if (!empty($usuarios_moodle)) {

							$usuarios_pv = [];
							foreach ($usuarios_moodle as $usuario_moodle) {

								// Agrego los usuarios para matricular y luego los guardo en la DB
								$usuarios_pv[] = [$this->s__datos_plataforma['plataforma'], $personas_usuarios[$usuario_moodle['username']]['persona'], $usuario_moodle['id']];
								$personas_usuarios[$usuario_moodle['username']]['usuario_moodle'] = $usuario_moodle['id'];
								$this->alumnos_para_matricular[] = $personas_usuarios[$usuario_moodle['username']];

							}

							guarani::act('act_plataformas_virtuales')->insertar_usuarios_pv_masivo($usuarios_pv);
						}
                    }	
				}

				// Si hay usuarios para matricular en Moodle
				if (!empty($this->alumnos_para_matricular)) {

					$matriculaciones = [];
					$matriculaciones_grupo = [];

					foreach ($this->alumnos_para_matricular as $key => $alumno_para_matricular) {

						// Si se trata de una propuesta tipo curso
						if ($alumno_para_matricular['propuesta_tipo'] == propuesta::tipo_curso) {
							$role_moodle = $this->s__datos_plataforma['rol_alumno_curso'];
						} else { // sino
							$role_moodle = $this->s__datos_plataforma['rol_alumno'];
						}

						$matriculaciones[$key]['roleid'] = $role_moodle;
						$matriculaciones[$key]['userid'] = $alumno_para_matricular['usuario_moodle'];
						$matriculaciones[$key]['courseid'] = $this->s__datos_curso_virtual['id_curso_externo'];
						$matriculaciones_grupo[$key]['groupid'] = $this->s__datos_curso_virtual['grupo'];
						$matriculaciones_grupo[$key]['userid'] = $alumno_para_matricular['usuario_moodle'];
						
					}
					
					$lote_matriculaciones = array_chunk($matriculaciones, static::cant_personas_lote, true);
                    foreach ($lote_matriculaciones as $matriculaciones) { 
						
						try {

							$this->get_moodle_obj()->matricularUsuarios($matriculaciones, true);

							// Si la matriculación en Moodle salió bien
							foreach ($this->alumnos_para_matricular as $matricula) {

								if ($matricula['propuesta_tipo'] == propuesta::tipo_curso) {
									$role_moodle = 'Alumno curso';
								} else { // sino
									$role_moodle = 'Alumno';
								}

								$reporte_alumnos = array(
									'elemento_codigo' => $this->s__elemento_codigo,
									'actividad' => $this->s__elemento_nombre,
									'curso_virtual' => $this->s__nombre_curso,
									'accion' => 'Matricular en Moodle',
									'Apellido_Nombre' => $matricula['apellido'] . ' ' . $matricula['nombres'],
									'identificacion' => $this->s__tipo_documento[$matricula['tipo_documento']] . ' N° ' . $matricula['nro_documento'],
									'usuario' => $matricula['usuario'],
									'rol' => $role_moodle,
									'error' => '');

								$this->log->set($reporte_alumnos, array(self::rep_alumnos));
							}

							$this->alumnos_sincronizados = $this->alumnos_sincronizados + guarani_arreglos::count($matriculaciones);

						} catch (Exception $e) { // Si falló la matriculación en Moodle

							$error = strip_tags(guarani_mensajes::get("800_fallo_actualizacion_alumnos_moodle", [$e->getMessage()]));
							foreach ($this->alumnos_para_matricular as $matricula) {

								if ($matricula['propuesta_tipo'] == propuesta::tipo_curso) {
									$role_moodle = 'Alumno curso';
								} else { // sino
									$role_moodle = 'Alumno';
								}

								$reporte_alumnos = array(
									'elemento_codigo' => $this->s__elemento_codigo,
									'actividad' => $this->s__elemento_nombre,
									'curso_virtual' => $this->s__nombre_curso,
									'accion' => 'Matricular en Moodle',
									'Apellido_Nombre' => $matricula['apellido'] . ' ' . $matricula['nombres'],
									'identificacion' => $this->s__tipo_documento[$matricula['tipo_documento']] . ' N° ' . $matricula['nro_documento'],
									'usuario' => $matricula['usuario'],
									'rol' => $role_moodle,
									'error' => $error);

								$this->log->set($reporte_alumnos, array(self::rep_alumnos));
							}

							$this->alumnos_sincronizados_error = $this->alumnos_sincronizados_error + guarani_arreglos::count($matriculaciones);
							// Relanzo la excepción
							throw $e;
						}
                    }
					$lote_matriculaciones_grupo = array_chunk($matriculaciones_grupo, 1000, true);
					foreach ($lote_matriculaciones_grupo as $key => $matriculaciones_grupo) { 
						if ($this->s__datos_curso_virtual['grupo'] != comunes::no){
							$this->get_moodle_obj()->matricularGrupoUsuarios($matriculaciones_grupo, true);
						}
					}
				}

				// Si hay usuarios para desmatricular en Moodle
				if (!empty($this->alumnos_para_desmatricular)) {

					$desmatriculaciones = [];
					$personas_desmatricular = [];
					foreach ($this->s__alumnos_eliminados_comision as $persona_desmatricular) {
						$personas_desmatricular[$persona_desmatricular['usuario_moodle']] = $persona_desmatricular;
					}
					foreach ($this->alumnos_para_desmatricular as $key => $alumno_para_desmatricular) {
						$desmatriculaciones[$key]['userid'] = $alumno_para_desmatricular;
						$desmatriculaciones[$key]['courseid'] = $this->s__datos_curso_virtual['id_curso_externo'];
					}
					
					$lote_desmatriculaciones = array_chunk($desmatriculaciones, static::cant_personas_lote, true);
                    foreach ($lote_desmatriculaciones as $desmatriculaciones) { 

						try {

							$this->get_moodle_obj()->desmatricularUsuarios($desmatriculaciones, true);

							// Si la desmatriculación en Moodle salió bien
							foreach ($desmatriculaciones as $matricula) {
								$reporte_alumnos = array(
									'elemento_codigo' => $this->s__elemento_codigo,
									'actividad' => $this->s__elemento_nombre,
									'curso_virtual' => $this->s__nombre_curso,
									'accion' => 'Desmatricular en Moodle',
									'Apellido_Nombre' => $personas_desmatricular[$matricula['userid']]['apellido'] . ' ' . $personas_desmatricular[$matricula['userid']]['nombres'],
									'identificacion' => $this->s__tipo_documento[$personas_desmatricular[$matricula['userid']]['tipo_documento']] . ' N° ' . $personas_desmatricular[$matricula['userid']]['nro_documento'],
									'usuario' => $personas_desmatricular[$matricula['userid']]['usuario'],
									'rol' => 'Alumno',
									'error' => '');
								$this->log->set($reporte_alumnos, array(self::rep_alumnos));
							}
							$this->alumnos_sincronizados = $this->alumnos_sincronizados + guarani_arreglos::count($desmatriculaciones);

						} catch (Exception $e) { // Si falló la desmatriculación en Moodle

							$error = strip_tags(guarani_mensajes::get("800_fallo_actualizacion_alumnos_moodle", [$e->getMessage()]));
							foreach ($desmatriculaciones as $matricula) {
								$reporte_alumnos = array(
									'elemento_codigo' => $this->s__elemento_codigo,
									'actividad' => $this->s__elemento_nombre,
									'curso_virtual' => $this->s__nombre_curso,
									'accion' => 'Desmatricular en Moodle',
									'Apellido_Nombre' => $personas_desmatricular[$matricula['userid']]['apellido'] . ' ' . $personas_desmatricular[$matricula['userid']]['nombres'],
									'identificacion' => $this->s__tipo_documento[$personas_desmatricular[$matricula['userid']]['tipo_documento']] . ' N° ' . $personas_desmatricular[$matricula['userid']]['nro_documento'],
									'usuario' => $personas_desmatricular[$matricula['userid']]['usuario'],
									'rol' => 'Alumno',
									'error' => $error);
								$this->log->set($reporte_alumnos, array(self::rep_alumnos));
							}
							$this->alumnos_sincronizados_error = $this->alumnos_sincronizados_error + guarani_arreglos::count($desmatriculaciones);
							// Relanzo la excepción
							throw $e;

						}
                    }
				}
			}

			//toba::db()->cerrar_transaccion();

			return true;
		} catch (Exception $e) {
			
			//toba::db()->abortar_transaccion();
			toba::logger()->error(guarani_mensajes::get("800_fallo_actualizacion_alumnos_moodle", [$e->getMessage()]));
			return false;
			
		}
	}

	function get_alumnos_curso_virtual()
	{
		$curso_externo = $this->s__datos_curso_virtual['id_curso_externo'];
		$rol_alumno = $this->s__datos_plataforma['rol_alumno'];
		$rol_curso = $this->s__datos_plataforma['rol_alumno_curso'];

		return $this->get_moodle_obj()->getAlumnosByCurso($curso_externo, $rol_alumno, $rol_curso);
	}

	function crear_curso_moodle($comision, $fullname, $shortname, $categoryid, $summary, $visible, $startdate = null, $enddate = null)
	{ //como viene de proceso masivo seteo interactivo = false
		list($creacion_ok, $messageMoodle) = $this->get_moodle_obj()->crearAsociarCursoComision(
				$this->s__datos_plataforma['plataforma'], $comision, $fullname, $shortname, $categoryid, $summary, $visible, $startdate, $enddate, true, false);

		if ($creacion_ok) {
			return [true, "-"];
		} else {
			toba::logger()->error(guarani_mensajes::get("800_fallo_creacion_curso_moodle", [$messageMoodle]));
			return [false, $messageMoodle];
		}
	}

	function coinciden_docentes_moodle()
	{
		list(	$coinciden_docentes_moodle,
				$this->personas_para_crear,
				$this->docentes_para_matricular,
				$this->docentes_para_desmatricular) = moodle::coincidenDocentesMoodle($this->s__docentes_comision, $this->s__docentes_curso_virtual_guarani, $this->s__docentes_curso_virtual_moodle);

		return $coinciden_docentes_moodle;
	}

	function actualizar_docentes_moodle()
	{
		try {

			$coinciden_docentes_moodle = $this->coinciden_docentes_moodle();
			
			// Si no hay que actualizar en Moodle
			if ($coinciden_docentes_moodle) {

				return true;
				
			} else { // sino
				// Si hay usuarios para crear en Moodle
				//obtengo las responsabilidades para el reporte 
				$responsabilidades = toba::consulta_php('co_responsabilidades_docentes')->get_listado();
				foreach ($responsabilidades as $dato) {//'valor', 'descr_ab'
					$responsabilidades[$dato['responsabilidad']] = $dato['descripcion'];
				}
				
				if (!empty($this->personas_para_crear)) {

					$personas_ids = array_column($this->personas_para_crear, 'persona');
					$emails_personas = toba::consulta_php('co_personas')->get_emails_personas($personas_ids, false);

					$usuarios = [];
					$personas_usuarios = [];
					$personas_sin_email = [];

					foreach ($this->personas_para_crear as $key => $persona) {

						// Si la persona no tiene email asignado no lo matriculo en Moodle
						if (empty($emails_personas[$persona['persona']]['email'])) {
							$personas_sin_email[] = "{$persona['apellido']} {$persona['nombres']}";
							$error = strip_tags(guarani_mensajes::get("800_personas_falta_email_matriculacion_moodle", ['docente/s', "{$persona['apellido']} {$persona['nombres']}"]));
							$reporte_docentes = array(
								'elemento_codigo' => $this->s__elemento_codigo,
								'actividad' => $this->s__elemento_nombre,
								'curso_virtual' => $this->s__nombre_curso,
								'accion' => 'Crear en Moodle',
								'Apellido_Nombre' => $persona['apellido'] . ' ' . $persona['nombres'],
								'identificacion' => $this->s__tipo_documento[$persona['tipo_documento']] . ' N° ' . $persona['nro_documento'],
								'usuario' => $persona['usuario'],
								'rol' => $responsabilidades[$persona['responsabilidad_docente']],
								'error' => $error);
							$this->log->set($reporte_docentes, array(self::rep_docentes));
							continue;
						}

						$usuarios[$key]['username'] = $persona['usuario'];
						$usuarios[$key]['firstname'] = $persona['nombres'];
						$usuarios[$key]['lastname'] = $persona['apellido'];
						$usuarios[$key]['email'] = $emails_personas[$persona['persona']]['email'];
						$usuarios[$key]['idnumber'] = $persona['persona'];

						$personas_usuarios[$persona['usuario']] = $persona;
					}

					// Si hay personas sin email asignado muestro mensaje y salgo
					if (!empty($personas_sin_email)) {
						toba::logger()->error(guarani_mensajes::get("800_personas_falta_email_matriculacion_moodle", ['docente/s', implode(', ', $personas_sin_email)]));
						$this->docentes_sincronizados_error = $this->docentes_sincronizados_error + guarani_arreglos::count($personas_sin_email);
						return false;
					}
					
					$lote_usuarios = array_chunk($usuarios, static::cant_personas_lote, true);
                    foreach ($lote_usuarios as $usuarios) { 
                                    
						try {

							$usuarios_moodle = $this->get_moodle_obj()->crearUsuarios($usuarios, true);

							// Si la creación en Moodle salió bien
							foreach ($usuarios as $usuario) {

								$reporte_docentes = array(
									'elemento_codigo' => $this->s__elemento_codigo,
									'actividad' => $this->s__elemento_nombre,
									'curso_virtual' => $this->s__nombre_curso,
									'accion' => 'Crear en Moodle',
									'Apellido_Nombre' => $usuario['lastname'] . ' ' . $usuario['firstname'],
									'identificacion' => $usuario['email'],
									'usuario' => $usuario['username'],
									'rol' => '',
									'error' => '');
								$this->log->set($reporte_docentes, array(self::rep_docentes));

							}

						} catch (Exception $e) { // Si falló la creación en Moodle

							$error = strip_tags(guarani_mensajes::get("800_fallo_actualizacion_docentes_moodle", [$e->getMessage()]));
							foreach ($usuarios as $usuario) {

								$reporte_docentes = array(
									'elemento_codigo' => $this->s__elemento_codigo,
									'actividad' => $this->s__elemento_nombre,
									'curso_virtual' => $this->s__nombre_curso,
									'accion' => 'Crear en Moodle',
									'Apellido_Nombre' => $usuario['lastname'] . ' ' . $usuario['firstname'],
									'identificacion' => $usuario['email'],
									'usuario' => $usuario['username'],
									'rol' => '',
									'error' => $error);
								$this->log->set($reporte_docentes, array(self::rep_docentes));

							}
							$this->docentes_sincronizados_error = $this->docentes_sincronizados_error + guarani_arreglos::count($usuarios);
							// Relanzo la excepción
							throw $e;

						}

						// Si hay usuarios a guardar en la DB
						if (!empty($usuarios_moodle)) {
							$usuarios_pv = [];
							foreach ($usuarios_moodle as $usuario_moodle) {
								// Agrego los usuarios para matricular y luego los guardo en la DB
								$usuarios_pv[] = [$this->s__datos_plataforma['plataforma'], $personas_usuarios[$usuario_moodle['username']]['persona'], $usuario_moodle['id']];
								$personas_usuarios[$usuario_moodle['username']]['usuario_moodle'] = $usuario_moodle['id'];
								$this->docentes_para_matricular[] = $personas_usuarios[$usuario_moodle['username']];
							}

							guarani::act('act_plataformas_virtuales')->insertar_usuarios_pv_masivo($usuarios_pv);
						}
                    }
				}

				// Si hay usuarios para matricular en Moodle
				if (!empty($this->docentes_para_matricular)) {

					$matriculaciones = [];
					$matriculaciones_grupo = [];

					foreach ($this->docentes_para_matricular as $key => $docente_para_matricular) {
						$matriculaciones[$key]['roleid'] = $docente_para_matricular['rol_moodle'];
						$matriculaciones[$key]['userid'] = $docente_para_matricular['usuario_moodle'];
						$matriculaciones[$key]['courseid'] = $this->s__datos_curso_virtual['id_curso_externo'];
						$matriculaciones_grupo[$key]['groupid'] = $this->s__datos_curso_virtual['grupo'];
						$matriculaciones_grupo[$key]['userid'] = $docente_para_matricular['usuario_moodle'];
					}
					
					$lote_matriculaciones = array_chunk($matriculaciones, static::cant_personas_lote, true);
                    foreach ($lote_matriculaciones as $matriculaciones) {
						
						try {

							$this->get_moodle_obj()->matricularUsuarios($matriculaciones, true);

							// Si la matriculación en Moodle salió bien
							foreach ($this->docentes_para_matricular as $matricula) {
								$reporte_docentes = array(
									'elemento_codigo' => $this->s__elemento_codigo,
									'actividad' => $this->s__elemento_nombre,
									'curso_virtual' => $this->s__nombre_curso,
									'accion' => 'Matricular en Moodle',
									'Apellido_Nombre' => $matricula['apellido'] . ' ' . $matricula['nombres'],
									'identificacion' => $this->s__tipo_documento[$matricula['tipo_documento']] . ' N° ' . $matricula['nro_documento'],
									'usuario' => $matricula['usuario'],
									'rol' => $responsabilidades[$matricula['responsabilidad_docente']],
									'error' => '');
								$this->log->set($reporte_docentes, array(self::rep_docentes));
							}
							$this->docentes_sincronizados = $this->docentes_sincronizados + guarani_arreglos::count($matriculaciones);

						} catch (Exception $e) { // Si falló la matriculación en Moodle

							$error = strip_tags(guarani_mensajes::get("800_fallo_actualizacion_docentes_moodle", [$e->getMessage()]));
							foreach ($this->docentes_para_matricular as $matricula) {

								$reporte_docentes = array(
									'elemento_codigo' => $this->s__elemento_codigo,
									'actividad' => $this->s__elemento_nombre,
									'curso_virtual' => $this->s__nombre_curso,
									'accion' => 'Matricular en Moodle',
									'Apellido_Nombre' => $matricula['apellido'] . ' ' . $matricula['nombres'],
									'identificacion' => $this->s__tipo_documento[$matricula['tipo_documento']] . ' N° ' . $matricula['nro_documento'],
									'usuario' => $matricula['usuario'],
									'rol' => $responsabilidades[$matricula['responsabilidad_docente']],
									'error' => $error);
								$this->log->set($reporte_docentes, array(self::rep_docentes));
							}
							$this->docentes_sincronizados_error = $this->docentes_sincronizados_error + guarani_arreglos::count($matriculaciones);
							// Relanzo la excepción
							throw $e;
						}
                    }
				
					$lote_matriculaciones_grupo = array_chunk($matriculaciones_grupo,1000, true);
					foreach ($lote_matriculaciones_grupo as $key => $matriculaciones_grupo) { 
						toba::logger()->debug("TRAIGO DOCENTES GRUPO DEL CURSO");
						toba::logger()->var_dump($this->s__datos_curso_virtual['grupo']);
						if ($this->s__datos_curso_virtual['grupo'] != comunes::no){
							$this->get_moodle_obj()->matricularGrupoUsuarios($matriculaciones_grupo, true);
						}
					}
				}

				// Si hay usuarios para desmatricular en Moodle
				if (!empty($this->docentes_para_desmatricular)) {

					$desmatriculaciones = [];

					foreach ($this->docentes_para_desmatricular as $key => $docente_para_desmatricular) {
						$desmatriculaciones[$key]['userid'] = $docente_para_desmatricular;
						$desmatriculaciones[$key]['courseid'] = $this->s__datos_curso_virtual['id_curso_externo'];
					}
                    
					$lote_desmatriculaciones = array_chunk($desmatriculaciones, static::cant_personas_lote, true);
                    foreach ($lote_desmatriculaciones as $desmatriculaciones) { 

						try {

							$this->get_moodle_obj()->desmatricularUsuarios($desmatriculaciones, true);

							// Si la desmatriculación en Moodle salió bien
							foreach ($desmatriculaciones as $matricula) {
								$reporte_docentes = array(
									'elemento_codigo' => $this->s__elemento_codigo,
									'actividad' => $this->s__elemento_nombre,
									'curso_virtual' => $this->s__nombre_curso,
									'accion' => 'Desmatricular en Moodle',
									'Apellido_Nombre' => '',
									'identificacion' => '',
									'usuario' => $matricula['userid'],
									'rol' => $matricula['roleid'],
									'error' => '');
								$this->log->set($reporte_docentes, array(self::rep_docentes));
							}
							$this->docentes_sincronizados = $this->docentes_sincronizados + guarani_arreglos::count($desmatriculaciones);

						} catch (Exception $e) { // Si falló la desmatriculación en Moodle

							$error = strip_tags(guarani_mensajes::get("800_fallo_actualizacion_docentes_moodle", [$e->getMessage()]));

							foreach ($desmatriculaciones as $matricula) {
								$reporte_docentes = array(
									'elemento_codigo' => $this->s__elemento_codigo,
									'actividad' => $this->s__elemento_nombre,
									'curso_virtual' => $this->s__nombre_curso,
									'accion' => 'Desmatricular en Moodle',
									'Apellido_Nombre' => '',
									'identificacion' => '',
									'usuario' => $matricula['userid'],
									'rol' => $matricula['roleid'],
									'error' => $error);
								$this->log->set($reporte_docentes, array(self::rep_docentes));
							}
							$this->docentes_sincronizados_error = $this->docentes_sincronizados_error + guarani_arreglos::count($desmatriculaciones);
							// Relanzo la excepción
							throw $e;

						}
                    }
				}
			}

			//toba::db()->cerrar_transaccion();

			return true;
			
		} catch (Exception $e) {

			//toba::db()->abortar_transaccion();
			toba::logger()->error(guarani_mensajes::get("800_fallo_actualizacion_docentes_moodle", [$e->getMessage()]));
			return false;
			
		}
	}

	function get_docentes_curso_virtual()
	{
		$curso_externo = $this->s__datos_curso_virtual['id_curso_externo'];
		$roles_docentes = array_unique(array_column($this->s__roles_docentes, 'rol_externo'));

		return $this->get_moodle_obj()->getDocentesByCurso($curso_externo, $roles_docentes);
	}

	function get_moodle_obj()
	{
		if (!isset($this->moodle_obj)) {
			$this->moodle_obj = new moodle($this->s__datos_plataforma['host'], $this->s__datos_plataforma['url'], $this->s__datos_plataforma['token']);
		}
		return $this->moodle_obj;
	}

	function crear_grupo_curso_moodle($idnumber, $description, $courseid, $name, $comision)
	{
		
		list($creacion_ok, $messageMoodle) = $this->get_moodle_obj()->CrearAsociarGrupoComision(
				$idnumber,
				$description, 
				$courseid, 
				$name, 
				$comision
		);
		if ($creacion_ok) {
			return true;
		} else {
			toba::notificacion()->error(guarani_mensajes::get('800_fallo_creacion_grupo_curso_moodle', [$messageMoodle]));
			return false;
		}
	}

	function eliminar_grupo_curso_moodle($id)
	{
		
		list($creacion_ok, $messageMoodle) = $this->get_moodle_obj()->EliminarAsociarGrupoComision($id);
		if ($creacion_ok) {
			return true;
		} else {
			toba::notificacion()->error(guarani_mensajes::get('800_fallo_eliminacion_grupo_curso_moodle', [$messageMoodle]));
			return false;
		}
	}

}
