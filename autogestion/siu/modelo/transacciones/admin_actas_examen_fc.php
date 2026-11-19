<?php
namespace siu\modelo\transacciones;

use SIU\Chulupi\kernel;
use SIU\Chulupi\ErrorKernel;
use siu\guarani;
use siu\errores\error_guarani;
use siu\modelo\datos\catalogo;
use siu\modelo\entidades\parametro;
use siu\modelo\entidades\rs_consulta;

class admin_actas_examen_fc extends transaccion
{
	protected $operacion = '800SIUEXA026';
	protected $cache_request_acta_cabecera = array();
	
	static protected $ejecutar_controles;

	const CANT_MESES_SUGERIDOS_ANTES_FECHA_EXAMEN = 3;

	function iniciar()
	{
		
	}

	function generacion_nro_acta_es_manual($propuestas)
	{
		return (parametro::get_valor('exa_carga_manual_nro_acta_examen', $propuestas) == \comunes::si);
	}

	function get_datos_acta($id_acta)
	{
		$parametros = array('id_acta' => $id_acta);
		return catalogo::consultar('acta', 'get_datos_acta', $parametros);
	}

	//--------------------------------------------------------------------------
	//---- INFO
	//--------------------------------------------------------------------------

	function get_cant_meses_sugeridos_antes_fecha_examen(){
		return static::CANT_MESES_SUGERIDOS_ANTES_FECHA_EXAMEN;
	}

	function get_datos_completos_acta($id_acta)
	{
		$datos = array();
		$datos['datos_acta'] = $this->get_datos_acta($id_acta);
		$datos['instancias'] = $this->info__acta_instancias($id_acta);
		$datos['alumnos'] = $this->info__acta_alumnos($id_acta);
		$datos['docentes'] = $this->info__acta_docentes($id_acta);
		return $datos;
	}

	function get_datos_acta_instancias($id_acta)
	{
		$datos_acta = catalogo::consultar('acta', 'get_datos_acta_instancias', array('id_acta' => $id_acta));

		$nuevo = array();
		foreach ($datos_acta as $datos) {
			$nuevo[$datos['instancia']] = $datos;
		}
		return $nuevo;
	}

	function info__acta_instancias($id_acta = null)
	{
		$parametros = array('instancia' => null);
		$instancias = catalogo::consultar('acta', 'get_instancias_examen', $parametros);
		if (isset($id_acta)) {
			$datos_acta_instancias = $this->get_datos_acta_instancias($id_acta);
			foreach ($instancias as $key => $instancia) {
				if (isset($datos_acta_instancias[$instancia['instancia']])) {
					$instancias[$key]['activo'] = 'S';
					$instancias[$key]['escala_nota'] = $datos_acta_instancias[$instancia['instancia']]['escala_nota'];
					;
				} else {
					$instancias[$key]['activo'] = 'N';
				}
			}
		}
		return $instancias;
	}

	function info__acta_alumnos($id_acta)
	{
		$parametros = array('id_acta' => $id_acta);
		$alumnos = catalogo::consultar('acta', 'get_datos_acta_alumnos', $parametros);
		return $alumnos;
	}

	function info__acta_docentes($id_acta)
	{
		return catalogo::consultar('acta', 'get_datos_acta_docentes', array('id_acta' => $id_acta));
	}

	function info__link_mesa($datos)
	{
		$persona = guarani::persona()->get_id();
		$rs = new rs_consulta('mesa', 'info_link_mesa', array('persona' => $persona, 'mesa_examen' => $datos['mesa_examen']));
		$rs->set_columna_id('llamado_mesa');
		$rs->agregar_entidad('llamado_mesa');
		return $rs;
	}

	function info__link_acta($datos)
	{
		$rs = new rs_consulta('acta', 'info_link_acta', array('id_acta' => $datos['acta']));
		$rs->set_columna_id('id_acta');
		return $rs;
	}

	function get_escala_nota_defecto($actividad)
	{
		return catalogo::consultar('unidad_academica', 'get_escala_nota_defecto_actividad', array('actividad' => $actividad));
	}

	function hay_alumnos($id_acta, $instancia)
	{
		$parametros = array(
			'id_acta' => $id_acta,
			'instancia' => $instancia
		);
		$resultado = catalogo::consultar('acta', 'cantidad_alumnos_instancia', $parametros);
		return $resultado['count'] > 0;
	}

	/**
	 * Genera un nuevo numero de acta
	 */
	function generar_nro_acta($tipo_acta, $llamado_mesa)
	{
		$parametros = array('origen' => \acta::origen_examen,
			'tipo_acta' => $tipo_acta,
			'llamado_mesa' => $llamado_mesa
		);
		try {
			return \toba::consulta_php('co_actas')->generar_nro_acta($parametros);
		} catch (error_guarani $e) {
			throw guarani::error('800_error_generar_nro_acta');
		}
	}

	function determinar_instancia($actividad, $llamado_mesa, $alumno, $plan_version)
	{
		$instancias = catalogo::consultar('inscripcion_examen', 'get_instancias_llamado_mesa', array('llamado_mesa' => $llamado_mesa));
		$acumulacion_mensajes = array();
		foreach ($instancias as $instancia) {
			$texto_a_agregar = "Instancia {$instancia['instancia_nombre']}:";
			try {
				// Para determinar la instancia, se ejecutan los controles a?n cuando el par?metro exa_ejecuta_controles este seteado en N
				list($resultado_controles, $mensajes_controles) = $this->control__seleccion_instancia($actividad, $llamado_mesa, $instancia['instancia'], $alumno, $plan_version);
				$acumulacion_mensajes = array_merge($acumulacion_mensajes, guarani::agregar_texto_inicio_mensajes($mensajes_controles, $texto_a_agregar));
				kernel::log()->add_debug('############ SE DETERMINO LA INSTANCIA #############', $instancia);
				return array($instancia['instancia'], $acumulacion_mensajes, $resultado_controles);
			} catch (error_guarani $error_instancia) {
				if (!isset($e)) {
					//@TODO Pasar a mensajes 800_inscripcion_fallo_ctrl
					$e = new error_guarani('No puedes inscribirte a ninguna instancia');
				}
				$e->add_mensajes_usuario(guarani::agregar_texto_inicio_mensajes($error_instancia->get_mensajes_usuario(), $texto_a_agregar));
				$mensajes = guarani::agregar_texto_inicio_mensajes($error_instancia->get_mensajes_usuario(), $texto_a_agregar);
				$acumulacion_mensajes = array_merge($acumulacion_mensajes, $mensajes);
			}
		}
		kernel::log()->add_debug('############ NINGUNA INSTANCIA PASO LOS CONTROLES #############', $acumulacion_mensajes);
		throw $e;
	}

	function crear_encabezado($datos)
	{
		// Atributos para la resolución de parámetros
		$propuestas_elemento = catalogo::consultar('atributos_params', 'get_propuestas_elemento', array('elemento' => $datos['datos_acta']['actividad']));
		$ras_elemento = catalogo::consultar('atributos_params', 'get_propuestas_ras', array('propuestas' => $propuestas_elemento)); 
		
		$parametros_mesa = array(
			'nombre' => 'Especial',
			'elemento' => $datos['datos_acta']['actividad'],
			'catedra' => null,
			'ubicacion' => $datos['datos_acta']['ubicacion'],
			'observaciones' => $datos['datos_acta']['observaciones'],
			'mesa_en_turno_examen' => \comunes::no,
			'anio_academico' => $datos['datos_acta']['anio_academico'],
			'division' => null,
			'fecha' => $datos['datos_acta']['fecha'],
			'hora_inicio' => $datos['datos_acta']['hora_inicio'],
			'hora_finalizacion' => $datos['datos_acta']['hora_finalizacion'] == '' ? null : $datos['datos_acta']['hora_finalizacion'],
			'espacio' => null,
		);
		// INSTANCIAS
		$inst_str = '{';
		foreach ($datos['instancias'] as $instancia) {
			if ($instancia['activo'] == \comunes::si) {
				$inst_str .= '{' . $instancia['instancia'] . ',' . $instancia['escala_nota'] . '},';
			}
		}

		$inst_str = substr($inst_str, 0, -1) . '}';

		$parametros_mesa['instancias'] = $inst_str;

		// DOCENTES
		$doc_str = '{';
		foreach ($datos['docentes'] as $docente) {
			if (!empty($docente['docente']) && !empty($docente['rol'])) {
				$doc_str .= '{' . $docente['docente'] . ',' . $docente['rol'] . '},';
			}
		}
		$doc_str = substr($doc_str, 0, -1) . '}';

		$parametros_mesa['docentes'] = $doc_str;
		$parametros_mesa['modalidad'] = '{P,D}'; //@TODO
		$parametros_acta = array();
		$parametros_acta['instancias'] = $inst_str;
		$parametros_acta['fecha'] = $datos['datos_acta']['fecha'];
		$parametros_acta['renglones_folio'] = parametro::get_valor('exa_lineas_folio_acta_examen', $propuestas_elemento);
		$parametros_acta['digital'] = parametro::get_valor('exa_utiliza_acta_digital', $ras_elemento);
		$parametros_acta['observaciones_acta'] = $datos['datos_acta']['observaciones'];

		kernel::db()->abrir_transaccion();
		
		try {
			
			$resultado = catalogo::consultar('mesa', 'insertar_mesa', $parametros_mesa);
			if (($resultado['resultado'] < 0)) {
				kernel::db()->abortar_transaccion();
				$e = new error_guarani(kernel::traductor()->trans('800EXA_mesa_examen_alta_error_db'));
				throw $e;
			}
			
			//---- Número de Acta ----------------------------------------------

			$parametros_acta['evaluacion'] = $resultado['evaluacion'];
			$parametros_acta['llamado_mesa'] = $resultado['llamado_mesa'];
			if ($this->generacion_nro_acta_es_manual($propuestas_elemento)) {
				$parametros_acta['nro_acta'] = $datos['datos_acta']['nro_acta'];
			} else {
				$parametros_acta['nro_acta'] = $this->generar_nro_acta(\acta::tipo_normal, $resultado['llamado_mesa']);
			}

			$resultado = catalogo::consultar('acta', 'insertar_acta', $parametros_acta);
			if (($resultado['resultado'] < 0)) {
				kernel::db()->abortar_transaccion();
				$e = new error_guarani(kernel::traductor()->trans('800EXA_acta_alta_error_db'));
				throw $e;
			}

			kernel::db()->cerrar_transaccion();
			// commit transaccion
			return $resultado['id_acta'];
		} catch (ErrorKernel $e) {
			kernel::db()->abortar_transaccion();
			kernel::log()->add_error($e);
			$e = new error_guarani(kernel::traductor()->trans('800EXA_acta_alta_error_db'));
			throw $e;
		}
	}

	function editar_encabezado($datos)
	{
		$hubo_cambios = false;
		$datos_encabezado = array_merge($datos['datos_acta'], $datos['where']);	
		$propuestas = catalogo::consultar('atributos_params', 'get_propuestas_llamado_mesa', array('llamado_mesa' => $datos['where']['llamado_mesa']));
		
		// unset el nro_acta si es automatico
		if (!$this->generacion_nro_acta_es_manual($propuestas)) {
			$datos_encabezado['nro_acta'] = null;
		}

		$datos_acta = catalogo::consultar('acta', 'get_datos_acta', array('id_acta' => $datos['where']['id_acta']));

		kernel::db()->abrir_transaccion();
		try {
			if ($datos_acta['mesa_en_turno_examen'] == \comunes::si) {
				if ($datos_encabezado['observaciones'] != $datos_acta['observaciones']) {
					$hubo_cambios = true;
				}
				catalogo::consultar('acta', 'modificar_encabezado_acta_en_turno', $datos_encabezado);
			} else {
				// DATOS DEL ACTA
				if ($datos_encabezado['hora_finalizacion'] == '') {
					$datos_encabezado['hora_finalizacion'] = null;
				}
				
				// INSTANCIAS
				$instancias = ml_modif($datos['instancias']);
				$instancias_alta = array();
				$instancias_baja = array();
				$instancias_modificacion = array();
				$parametros['mesa_examen'] = $datos['where']['mesa_examen'];
				$parametros['id_acta'] = $datos['where']['id_acta'];

				foreach ($instancias as $instancia) {
					$campos_modificados = explode(',', $instancia[info_analisis_columna]);
					foreach ($campos_modificados as $campo) {
						switch ($campo) {
							case 'activo':
								if ($instancia['activo'] == 'S') {
									$instancias_alta[$instancia['instancia']] = $instancia;
								} else {
									$instancias_baja[$instancia['instancia']] = $instancia;
								}
								break;
							case 'escala_nota':
								// Se verifica qeu no sea ni alta ni baja  => es modificación
								if (!isset($instancias_alta[$instancia['instancia']]) && !isset($instancias_baja[$instancia['instancia']])) {
									$instancias_modificacion[$instancia['instancia']] = $instancia;
								}
								break;
						}
					}
				}

				// DOCENTES
				$docentes_alta = ml_altas($datos['docentes']);
				$docentes_modificacion = ml_modif($datos['docentes']);
				$docentes_baja = ml_baja($datos['docentes']);
				$parametros['llamado_mesa'] = $datos['where']['llamado_mesa'];

				if (!is_null($datos_encabezado['nro_acta']) && ($datos_encabezado['nro_acta'] != $datos_acta['nro_acta'])) {
					$hubo_cambios = true;
				} else {
					$datos_encabezado['nro_acta'] = null;
				}

				if ($datos_encabezado['anio_academico'] != $datos_acta['anio_academico']) {
					$hubo_cambios = true;
				} else {
					$datos_encabezado['anio_academico'] = null;
				}

				if ($datos_encabezado['ubicacion'] != $datos_acta['ubicacion']) {
					$hubo_cambios = true;
				} else {
					$datos_encabezado['ubicacion'] = null;
				}

				if ($datos_encabezado['fecha'] != $datos_acta['fecha']) {
					$hubo_cambios = true;
				} else {
					$datos_encabezado['fecha'] = null;
				}

				if ($datos_encabezado['observaciones'] != $datos_acta['observaciones']) {
					$hubo_cambios = true;
				} else {
					$datos_encabezado['observaciones'] = null;
				}

				if ($datos_encabezado['hora_inicio'] == $datos_acta['hora_inicio']) {
					$datos_encabezado['hora_inicio'] = null;
				}

				if ($datos_encabezado['hora_finalizacion'] == $datos_acta['hora_finalizacion']) {
					$datos_encabezado['hora_finalizacion'] = null;
				}

				// Encabezado
				$filas_modificadas = catalogo::consultar('acta', 'modificar_encabezado_acta', $datos_encabezado);

                                $hubo_cambios_instancias = false;

				// Instancias
				if (!empty($instancias_alta)) {
					$parametros['instancias'] = $instancias_alta;
					catalogo::consultar('acta', 'insertar_instancias', $parametros);
					$hubo_cambios = true;
                                        $hubo_cambios_instancias = true;
				}
				if (!empty($instancias_baja)) {
					//@TODO	antes de eliminar las instancias hacer el chequeo de que no haya alumnos!!!!!!!!
					$parametros['instancias'] = $instancias_baja;
					catalogo::consultar('acta', 'eliminar_instancias', $parametros);
					$hubo_cambios = true;
                                        $hubo_cambios_instancias = true;
				}
				if (!empty($instancias_modificacion)) {
					$parametros['instancias'] = $instancias_modificacion;
					catalogo::consultar('acta', 'modificar_instancias', $parametros);
					$hubo_cambios = true;
                                        $hubo_cambios_instancias = true;
				}

                                // Si hubo cambios en las "Instancias" elimino la cache de escalas de notas, sino no carga la "Nota" ni "Resultado" en la solapa "Cargar Notas"
                                if ($hubo_cambios_instancias) {
                                    catalogo::limpiar_cache('carga_notas_mesa_examen', 'escala_notas', ['llamado_mesa' => $datos['where']['llamado_mesa']]);
                                    catalogo::limpiar_cache('carga_notas_mesa_examen', 'resultados', ['llamado_mesa' => $datos['where']['llamado_mesa']]);
                                }

				// Docentes
				if (!empty($docentes_alta)) {
					$parametros['docentes'] = $docentes_alta;
					catalogo::consultar('acta', 'insertar_docentes', $parametros);
					$hubo_cambios = true;
				}
				if (!empty($docentes_baja)) {
					$parametros['docentes'] = $docentes_baja;
					catalogo::consultar('acta', 'eliminar_docentes', $parametros);
					$hubo_cambios = true;
				}
				if (!empty($docentes_modificacion)) {
					$parametros['docentes'] = $docentes_modificacion;
					catalogo::consultar('acta', 'modificar_docentes', $parametros);
					$hubo_cambios = true;
				}
			}

			//Si hubo cambios incremento la versión del acta
			if ($hubo_cambios) {
				catalogo::consultar('acta', 'incrementar_version_acta', array('id_acta' => $datos['where']['id_acta']));
			}

			kernel::db()->cerrar_transaccion();
		} catch (ErrorKernel $e) {
			kernel::db()->abortar_transaccion();
			throw $e;
		}
	}
	
	function editar_eval_detalle_examenes($datos)
	{
		$hubo_cambios = false;

		// Recupero la escala de notas de cada intancia en un array para usarlo en las consultas
		$escala_nota = array();
                // Limpio la cache, ya que si en la edición se agregan más "Instancias" no las devuelve y da error SQL
		catalogo::limpiar_cache('carga_notas_mesa_examen', 'get_instancias_llamado_mesa', ['llamado_mesa' => $datos['llamado_mesa']]);
		$instancias = catalogo::consultar('carga_notas_mesa_examen', 'get_instancias_llamado_mesa', ['llamado_mesa' => $datos['llamado_mesa']]);
		foreach ($instancias as $instancia) {
			$escala_nota[$instancia['instancia']] = $instancia['escala_nota'];
		}

		$param_validacion = $parametros = array(
			'evaluacion' => $datos['evaluacion'],
			'id_acta' => $datos['id_acta'],
			'fecha' => $datos['fecha'],
		);

		$param_validacion['actividad'] = $datos['actividad'];
		$param_validacion['llamado_mesa'] = $datos['llamado_mesa'];

		$alumnos_alta = array();
		$alumnos_baja = array();
		$alumnos_modificacion = array();
		foreach ($datos['alumnos'] as $alumno) {
			switch ($alumno[analisis_columna]) {
				case analisis_alta:
					$alumno['escala_nota'] = $escala_nota[$alumno['instancia']];
					$alumnos_alta[] = $alumno;
					break;
				case analisis_baja:
					$alumnos_baja[] = $alumno;
					break;
				case analisis_modificacion:
					$alumno['escala_nota'] = $escala_nota[$alumno['instancia']];
					$alumnos_modificacion[] = $alumno;
					break;
				case analisis_sin_cambios:
					break;
			}
		}

		$this->validar_alta_alumnos($param_validacion, $alumnos_alta);
		kernel::db()->abrir_transaccion();
		try {
			if (!empty($alumnos_alta)) {
				$parametros['alumnos'] = $alumnos_alta;
				catalogo::consultar('acta', 'insertar_eval_detalle_examenes', $parametros);
				$hubo_cambios = true;
			}
			if (!empty($alumnos_baja)) {
				$parametros['alumnos'] = $alumnos_baja;
				catalogo::consultar('acta', 'eliminar_eval_detalle_examenes', $parametros);
				$hubo_cambios = true;
			}
			if (!empty($alumnos_modificacion)) {
				$parametros['alumnos'] = $alumnos_modificacion;
				catalogo::consultar('acta', 'modificar_eval_detalle_examenes', $parametros);
				$hubo_cambios = true;
			}

			// Si hubo cambios incremento la versión del acta
			if ($hubo_cambios) {
				catalogo::consultar('acta', 'incrementar_version_acta', array('id_acta' => $datos['id_acta']));
			}

			kernel::db()->cerrar_transaccion();
		} catch (ErrorKernel $e) {
			kernel::db()->abortar_transaccion();
			throw $e;
		}
	}

	//--------------------------------------------------------------------------
	//---- VALIDACIONES
	//--------------------------------------------------------------------------

	protected function validar_alta_alumnos($datos_acta, $alumnos)
	{
		foreach ($alumnos as $alumno) {
			$datos_alumno = catalogo::consultar('alumno', 'get_datos', $alumno); // Esta consulta esta cacheada
			$datos_alumno['instancia'] = $alumno['instancia'];
			$this->validar_alumno_en_acta_abierta($datos_alumno['alumno'], $datos_acta['evaluacion']);
			$this->validar_alumno_en_acta_cerrada($datos_alumno['alumno'], $datos_acta['evaluacion']);

			// Se vuelven a correr los controles ya que los alumnos pueden ser agregados por el usuario saltenado la interfaz
			list($resultado_controles_instancia, $mensajes_controles_instancia) = $this->control__seleccion_instancia($datos_acta['actividad'], $datos_acta['llamado_mesa'], $datos_alumno['instancia'], $datos_alumno['alumno'], $datos_alumno['plan_version']);
			list($resultado_controles, $mensajes_controles) = $this->control__agregar_alumno($datos_alumno['persona'], $datos_alumno['alumno'], $datos_alumno['propuesta'], $datos_acta['actividad'], $datos_alumno['plan_version'], $datos_acta['llamado_mesa']);
			$hubo_advertencias = $resultado_controles == \guarani_punto_de_control::advertido || $resultado_controles_instancia !== \guarani_punto_de_control::autorizado;
			if ($hubo_advertencias) {
				$e = new error_guarani(kernel::traductor()->trans('error_ejecutando_controles'));
				$e->add_mensajes_usuario($mensajes_controles);
				throw $e;
			}
		}
	}

	function validar_alumno($datos_alumno, $datos_acta)
	{
		$this->validar_alumno_en_otra_acta_abierta($datos_alumno['alumno'], $datos_acta['evaluacion'], $datos_acta['id_acta']);
		$this->validar_alumno_en_acta_cerrada($datos_alumno['alumno'], $datos_acta['evaluacion']);
	}

	protected function validar_alumno_en_otra_acta_abierta($alumno, $evaluacion, $id_acta)
	{
		$resultado = catalogo::consultar('alumno', 'alumno_en_acta_abierta', array('alumno' => $alumno, 'evaluacion' => $evaluacion, 'id_acta' => $id_acta));
		if ($resultado) {
			$e = new error_guarani('Error al agregar alumno');
			$e->add_mensaje_usuario(guarani::crear_mensaje(guarani::control_error, kernel::traductor()->trans('admin_actas_examen_fc.alumno_en_evaluacion')));
			throw $e;
		}
	}

	protected function validar_alumno_en_acta_abierta($alumno, $evaluacion)
	{
		$resultado = catalogo::consultar('alumno', 'alumno_en_acta_abierta', array('alumno' => $alumno, 'evaluacion' => $evaluacion, 'id_acta' => null));
		if ($resultado) {
			$e = new error_guarani('Error al agregar alumno');
			$e->add_mensaje_usuario(guarani::crear_mensaje(guarani::control_error, kernel::traductor()->trans('admin_actas_examen_fc.alumno_en_evaluacion')));
			throw $e;
		}
	}

	protected function validar_alumno_en_acta_cerrada($alumno, $evaluacion)
	{
		$resultado = catalogo::consultar('alumno', 'alumno_en_acta_cerrada', array('alumno' => $alumno, 'evaluacion' => $evaluacion));
		if ($resultado) {
			$e = new error_guarani('Error al agregar alumno');
			$e->add_mensaje_usuario(guarani::crear_mensaje(guarani::control_error, kernel::traductor()->trans('admin_actas_examen_fc.alumno_en_acta_cerrada')));
			throw $e;
		}
	}

	//--------------------------------------------------------------------------
	//---- CONTROLES
	//--------------------------------------------------------------------------

	function control__agregar_alumno($persona, $alumno, $propuesta, $actividad, $plan_version, $llamado_mesa)
	{
		list($puntos_de_control, $parametros) = $this->agregar_puntos_control_agregar_alumno($persona, $alumno, $propuesta, $actividad, $plan_version, $llamado_mesa);
		$proceso = $this->nuevo_proceso_control($puntos_de_control, $parametros);
		$resultado_controles = $proceso->ejecutar();
		$mensajes_controles = $proceso->get_mensajes();
		return array($resultado_controles, $mensajes_controles);
	}

	function control__seleccion_instancia($actividad, $llamado_mesa, $instancia, $alumno, $plan_version)
	{
		list($puntos_de_control, $parametros) = $this->agregar_puntos_control_instancia($actividad, $llamado_mesa, $instancia, $alumno, $plan_version);
		$proceso = $this->nuevo_proceso_control($puntos_de_control, $parametros);
		$resultado_controles = $proceso->ejecutar();
		$mensajes_controles = $proceso->get_mensajes();
		return array($resultado_controles, $mensajes_controles);
	}

	function agregar_puntos_control_agregar_alumno($persona, $alumno, $propuesta, $actividad, $plan_version, $llamado_mesa)
	{
		//(0 Alumno - Entidad) http://repositorio.siu.edu.ar/trac/guarani/wiki/Controles_Configurables_3#a0:Alumno-Entidad
		//(1 Persona - Operación) http://repositorio.siu.edu.ar/trac/guarani/wiki/Controles_Configurables_3#a1:Persona-Operaci%C3%B3n
		//(2 Alumno  - Operación) http://repositorio.siu.edu.ar/trac/guarani/wiki/Controles_Configurables_3#a2:Alumno-Operaci%C3%B3n
		//(17 Elegir Actividad Examen) http://repositorio.siu.edu.ar/trac/guarani/wiki/Controles_Configurables_3#a17:ElegirActividadExamen
		//(9  Alumno-Actividad-Mesa de Examen) http://repositorio.siu.edu.ar/trac/guarani/wiki/Controles_Configurables_3#a9:Elegirmesadeexamen
		//(18 Persona Propuesta) http://repositorio.siu.edu.ar/trac/guarani/wiki/Controles_Configurables_3#a18:Persona-Propuesta

		$puntos_de_control = array(\guarani_punto_de_control::alumno_entidad,
			\guarani_punto_de_control::persona_operacion,
			\guarani_punto_de_control::alumno_operacion,
			\guarani_punto_de_control::elegir_actividad_examen,
			\guarani_punto_de_control::alumno_mesa_examen,
			\guarani_punto_de_control::persona_propuesta,
		);

		$parametros = array('fecha' => \guarani_fecha::get_hoy(),
			'persona' => $persona,
			'alumno' => $alumno,
			'propuesta' => $propuesta,
			'plan_version' => $plan_version,
			'elemento' => $actividad,
			'llamado_mesa' => $llamado_mesa
		);


		return array($puntos_de_control, $parametros);
	}

	function agregar_puntos_control_instancia($actividad, $llamado_mesa, $instancia, $alumno, $plan_version, $puntos_de_control = array(), $parametros = array())
	{
		//(10  Alumno - Instancia Regular) http://repositorio.siu.edu.ar/trac/guarani/wiki/Controles_Configurables_3#a10:Alumno-InstanciaRegular
		//(11  Alumno - Instancia Libre) http://repositorio.siu.edu.ar/trac/guarani/wiki/Controles_Configurables_3#a11:Alumno-InstanciaLibre
		//(12  Alumno - Instancia RevalidadeCursada) http://repositorio.siu.edu.ar/trac/guarani/wiki/Controles_Configurables_3#a12:Alumno-InstanciaReválidadeCursada

		$puntos_de_control_instancia = \toba::consulta_php('co_puntos_de_control')->get_puntos_de_control_instancia($instancia);
		$puntos_de_control = array_merge($puntos_de_control_instancia, $puntos_de_control);

		$parametros = array_merge(array('fecha' => \guarani_fecha::get_hoy(),
			'alumno' => $alumno,
			'plan_version' => $plan_version,
			'elemento' => $actividad,
			'llamado_mesa' => $llamado_mesa), $parametros);

		return array($puntos_de_control, $parametros);
	}

}