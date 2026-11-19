<?php
class cn_ent_periodo_de_inscripcion_fechas extends cn_entidad
{
	protected $hay_que_generar_aplanado = true;
	protected $usar_ubicaciones = false;
	protected $usar_modalidades = false;

	function cargar($id, $activar_perfil_de_datos=true)
	{
		if ($activar_perfil_de_datos) {
			$this->tabla('per_insc_ubicacion')->persistidor()->activar_perfil_de_datos();
		}
		parent::cargar($id);
	}

	function hay_que_generar_aplanado()
	{
		return $this->hay_que_generar_aplanado;
	}

	function verificar_editabilidad()
	{
		$periodo = $this->tabla('per_inscripcion')->get_columna('periodo');
		$llamado = turno_de_examen::get_llamado_id_de_periodo($periodo);
		//Si ya finalizó entonces no se puede editar ni agregar llamados
		if (turno_de_examen::llamado_esta_finalizado($llamado)) {
			$this->bloquear_edicion();
		} else {
			$this->habilitar_edicion();
		}
	}

	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_editabilidad();
		if ($validar) {
			$this->validar_datos($datos);
		}
		if (isset($datos['entidad_tipo']) && isset($datos['fecha_fin'])) {
			$this->validar_superposicion_periodos($datos['entidad_tipo']);
		}
		
		if (isset($datos['tipo'])) {
			if ($datos['para_todo']) {
				$this->marcar_eliminacion_entidades();
				$this->marcar_eliminacion_subtipos();
				$this->set_datos_entidades(array(array('entidad' => institucion::get_entidad_gestionante(),'requerido' => true)));
				unset($datos['para_todo']);
			} else {
				//Si no está definiendo para toda la institución
				//y estaba cargada para toda la institución, entonces
				//borro la entidad cargada de la instit. gestionante
				if ($this->es_para_institucion_gestionante()) {
					$this->marcar_eliminacion_entidades();
				}
			}
		}
		//$this->validar_datos_per_inscr($datos);

		unset($datos['anio_academico']);
		unset($datos['periodo_insc_tipo']);
		unset($datos['fecha_cierre_requisitos']);

		$datos['periodo_generico_tipo'] = periodo_generico::tipo_llamado;
		
		$this->tabla('per_inscripcion')->set($datos);
		$this->tabla('per_inscr_fechas')->set($datos);
	}

	function get_datos($tabla=null)
	{
		$datos_inscr = $this->tabla('per_inscripcion')->get();
		$this->tabla('per_inscr_fechas')->set_cursor(0);
		$datos_inscr_fechas = $this->tabla('per_inscr_fechas')->get();
		if (!$datos_inscr) $datos_inscr = array();
		if (!$datos_inscr_fechas) $datos_inscr_fechas = array();
		return array_merge($datos_inscr,$datos_inscr_fechas);
	}

	function set_datos_per_inscr_prop($datos)
	{
		$this->validar_editabilidad();
		unset($datos['nombre']);
		unset($datos['descripcion']);
		unset($datos['habilitado']);

		$this->tabla('per_inscr_prop')->set($datos);
	}

	function get_datos_per_inscr_prop()
	{
		return $this->tabla('per_inscr_prop')->get();
	}

	function nueva_fila_per_inscr_fechas($datos, $datos_ml_asociados, $tipo)
	{
		$this->validar_datos_subarbol($datos, $datos_ml_asociados, $tipo);
		return $this->tabla('per_inscr_fechas')->nueva_fila($datos);
	}

	function set_datos_per_inscr_fechas($datos, $datos_ml_asociados, $tipo)
	{
		$this->validar_editabilidad();
		$datos_periodo = current($datos);
		$clave_periodo = array_keys($datos);

		$this->validar_datos_subarbol($datos_periodo, $datos_ml_asociados, $tipo);

		return $this->tabla('per_inscr_fechas')->procesar_filas(array(current($clave_periodo) => $datos_periodo));
	}

	function get_datos_per_inscr_fechas()
	{
		return $this->tabla('per_inscr_fechas')->get_filas(null, true);
	}

	function get_datos_per_inscr_fecha()
	{
		return $this->tabla('per_inscr_fechas')->get();
	}

	function eliminar_fila_periodo($seleccion)
	{
		$this->validar_editabilidad();
		$this->tabla('per_inscr_fechas')->eliminar_fila($seleccion);
	}

	function set_cursor_periodo($id_fila)
	{
		$this->validar_editabilidad();
		$this->tabla('per_inscr_fechas')->set_cursor($id_fila);
	}

	function hay_cursor_periodo()
	{
		return $this->tabla('per_inscr_fechas')->hay_cursor();
	}

	function unset_cursor_periodo()
	{
		$this->tabla('per_inscr_fechas')->resetear_cursor();
	}

	function set_datos_per_inscr_modalidad($datos)
	{
		$this->validar_editabilidad();
		$this->validar_modalidades_duplicadas($datos);
		$this->tabla('per_insc_modalidad')->procesar_filas($datos);
	}

	function get_datos_per_inscr_modalidad()
	{
		return $this->tabla('per_insc_modalidad')->get_filas();
	}

	function hay_cambios_modalidades()
	{
		return $this->tabla('per_insc_modalidad')->hay_cambios();
	}

	function obtener_modalidades()
	{
		$modalidades = toba::consulta_php('co_planes')->get_modalidades_cursadas();
		foreach ($modalidades as $clave => $valor) {
			$datos_modalidades[$clave]['modalidad'] = $valor['valor'];
			$datos_modalidades[$clave]['apex_ei_analisis_fila'] = 'A';
		}
		return $datos_modalidades;
	}

	function set_datos_per_inscr_ubicacion($datos)
	{
		$this->validar_editabilidad();
		$this->validar_ubicaciones_duplicadas($datos);
		$this->tabla('per_insc_ubicacion')->procesar_filas($datos);
	}

	function get_datos_per_inscr_ubicacion()
	{
		return $this->tabla('per_insc_ubicacion')->get_filas();
	}

	function hay_cambios_ubicaciones()
	{
		return $this->tabla('per_insc_ubicacion')->hay_cambios();
	}

	function obtener_ubicaciones()
	{
		$ubicaciones = toba::consulta_php('co_ubicaciones')->get_listado();
		foreach ($ubicaciones as $clave => $valor) {
			$datos_ubicaciones[$clave]['ubicacion'] = $valor['ubicacion'];
			$datos_ubicaciones[$clave]['apex_ei_analisis_fila'] = 'A';
		}
		return $datos_ubicaciones;
	}

	function get_datos_per_inscr_subtipos()
	{
		return $this->tabla('per_inscr_subtipos')->get_filas();
	}

	function get_datos_per_inscr_entidad()
	{
		return $this->tabla('per_inscr_entidad')->get_filas();
	}

	function es_subtipo_requerido($subtipo) {
		return $this->tabla('per_inscr_subtipos')->existe_fila_condicion(array(array('entidad_subtipo','==',$subtipo)));
	}

	function es_entidad_requerida($entidad) {
		return $this->tabla('per_inscr_entidad')->existe_fila_condicion(array(array('entidad','==',$entidad)));
	}

	function es_para_institucion_gestionante()
	{
		if (!$this->tabla('per_inscr_entidad')->esta_cargada() &&  !$this->tabla('per_inscr_entidad')->hay_cambios()) return false;
		$entidades = $this->get_datos_per_inscr_entidad();
		if (!isset($entidades[0]['entidad'])) return false;
		return ($entidades[0]['entidad'] == institucion::get_entidad_gestionante());
	}

	function set_datos_entidad_para_todo()
	{
		$this->validar_editabilidad();
		$this->tabla('per_inscr_entidad')->eliminar_filas();
		$this->tabla('per_inscr_subtipos')->eliminar_filas();
		$this->set_datos_entidades(array(array('entidad' => institucion::get_entidad_gestionante(),'requerido' => true)));
	}

	function set_datos_subtipos($datos)
	{
		$this->validar_editabilidad();
		$this->tabla('per_inscr_entidad')->eliminar_filas();	//Se eliminan las entidades que tenga asociadas
		$this->tabla('per_inscr_subtipos')->eliminar_filas();	//Se eliminan los subtipos que tenga asociados
		foreach($datos as $subtipo) {
			if ($subtipo['requerido'] == '1') {
				$cursor = $this->tabla('per_inscr_subtipos')->nueva_fila();
				$this->tabla('per_inscr_subtipos')->set_cursor($cursor);
				$this->tabla('per_inscr_subtipos')->set(array('entidad_subtipo' => $subtipo['entidad_subtipo']));
			}
		}
	}

	function set_datos_entidades($datos)
	{
		$this->validar_editabilidad();
		$this->tabla('per_inscr_entidad')->eliminar_filas();	//Se eliminan las entidades que tenga asociadas
		$this->tabla('per_inscr_subtipos')->eliminar_filas();	//Se eliminan los subtipos que tenga asociados
		foreach($datos as $entidad) {
			if ($entidad['requerido'] == '1') {
				$cursor = $this->tabla('per_inscr_entidad')->nueva_fila();
				$this->tabla('per_inscr_entidad')->set_cursor($cursor);
				$this->tabla('per_inscr_entidad')->set(array('entidad' => $entidad['entidad']));
			}
		}
	}
	
	//-------------------------------------------------------------------
	
	function get_datos_modalidades()
	{
		return $this->tabla('per_insc_modalidad')->get_filas();
	}
 	
	function set_datos_modalidades($datos)
	{
		$this->tabla('per_insc_modalidad')->procesar_filas($datos);
	}

	//-------------------------------------------------------------------
	
	function get_datos_ubicaciones()
	{
		return $this->tabla('per_insc_ubicacion')->get_filas();
	}
 	
	function set_datos_ubicaciones($datos)
	{
		$this->tabla('per_insc_ubicacion')->procesar_filas($datos);
	}

	//-------------------------------------------------------------------

	function marcar_eliminacion_subtipos()
	{
		$this->validar_editabilidad();
		if (! $this->tabla('per_inscr_subtipos')->esta_cargada() && ! $this->tabla('per_inscr_subtipos')->hay_cambios()) return;
		$subtipos_dt = $this->get_datos_per_inscr_subtipos();
		$datos = array();
		foreach ($subtipos_dt as $subtipo_dt) {
			$datos[] = array('entidad_subtipo' => $subtipo_dt['entidad_subtipo'],'apex_ei_analisis_fila'=>'B','x_dbr_clave' => $subtipo_dt['x_dbr_clave']);
		}
		$this->tabla('per_inscr_subtipos')->procesar_filas($datos);
	}

	function marcar_eliminacion_entidades()
	{
		$this->validar_editabilidad();
		if (! $this->tabla('per_inscr_entidad')->esta_cargada() && ! $this->tabla('per_inscr_entidad')->hay_cambios()) return;
		$entidades_dt = $this->get_datos_per_inscr_entidad();
		$datos = array();
		foreach ($entidades_dt as $entidad_dt) {
			$datos[] = array('entidad' => $entidad_dt['entidad'],'apex_ei_analisis_fila'=>'B','x_dbr_clave' => $entidad_dt['x_dbr_clave']);
		}
		$this->tabla('per_inscr_entidad')->procesar_filas($datos);
	}

	// Validaciones Tabla per_inscripcion ------------------------------------------------

	function validar_datos($datos)
	{
		$this->set_datos_obligatorios(array('fecha_inicio' => 'Fecha de inicio'));
        
        if (isset($datos['tipo_fin'])) {
            if ($datos['tipo_fin'] == turno_de_examen::tope_fecha) {
                $this->set_datos_obligatorios(array('fecha_fin' => 'Fecha de finalización'));
            } elseif ($datos['tipo_fin'] == turno_de_examen::tope_hs_antes || 
                      $datos['tipo_fin'] == turno_de_examen::tope_hs_antes_habiles) {
                $this->set_datos_obligatorios(array('hs_previas_fin' => 'Horas previas de finalización'));
            } elseif ($datos['tipo_fin'] == turno_de_examen::tope_dias_antes || 
                      $datos['tipo_fin'] == turno_de_examen::tope_dias_antes_habiles) {
                $this->set_datos_obligatorios(array('dias_previos_fin' => 'Días previos de finalización'));
            }
        }
        if (isset($datos['tipo_tope_bajas'])) {
            if ($datos['tipo_tope_bajas'] == turno_de_examen::tope_fecha) {
                $this->set_datos_obligatorios(array('fecha_tope_bajas' => 'Fecha de tope de bajas'));
            } elseif ($datos['tipo_tope_bajas'] == turno_de_examen::tope_hs_antes || 
                      $datos['tipo_tope_bajas'] == turno_de_examen::tope_hs_antes_habiles) {
                $this->set_datos_obligatorios(array('hs_previas_tope_bajas' => 'Horas previas de tope de bajas'));
            } elseif ($datos['tipo_tope_bajas'] == turno_de_examen::tope_dias_antes || 
                      $datos['tipo_tope_bajas'] == turno_de_examen::tope_dias_antes_habiles) {
                $this->set_datos_obligatorios(array('dias_previos_tope_bajas' => 'Días previos de tope de bajas'));
            }
        }
        
		//Verificar datos obligatorios faltantes
		$this->validar_datos_obligatorios($datos);

		//Fechas y horas
		$fecha_hr_ini = $datos['fecha_inicio'];
		$fecha_hr_fin = $datos['fecha_fin'];
		$fecha_hr_tope_bajas = $datos['fecha_tope_bajas'];
		
		//El rango de fechas tiene que ser valido.
		if (isset($fecha_hr_fin)) {
			if (guarani_fecha::es_menor($fecha_hr_fin, guarani_fecha::get_hoy())) {
				$this->set_error('800_control_fechas', array('Finalización', 'anterior', 'de hoy'));
			}

			//Que el inicio sea anterior al comienzo
			if (guarani_fecha::es_mayor($fecha_hr_ini, $fecha_hr_fin)) {
				$this->set_error('800_control_fechas',array('Inicio','posterior','Finalización'));
			}
		}

		$periodo = $this->controlador->get_llamado_periodo_id();

		//La fecha tope de bajas debe ser menor o igual a la de fin de inscripcion.
		if (isset($fecha_hr_fin) && isset($fecha_hr_tope_bajas)) {
			if (guarani_fecha::es_mayor_timestamp($fecha_hr_fin, $fecha_hr_tope_bajas)) {
				$this->set_error('800_control_fechas', array('Fecha Finalización', 'posterior', 'Tope de Bajas'));
			}
		}


		//?Las fechas no tienen que ser posteriores al llamado.
		$where = "sga_llamados_turno.periodo = $periodo";
		$datos_llamado = current(guarani::co('co_turnos_de_examen')->get_datos_llamados($where));
		if (guarani_fecha::es_mayor($fecha_hr_ini, $datos_llamado['fecha_fin'])) {
			$this->set_error('800_control_fechas', array('Inicio', 'posterior', 'Finalización del Llamado.'));
		}

		if (isset($fecha_hr_fin)) {
			if (guarani_fecha::es_mayor($fecha_hr_fin, $datos_llamado['fecha_fin'])) {
				$this->set_error('800_control_fechas', array('Finalización', 'posterior', 'Finalización del Llamado.'));
			}
		}
				
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}
	
	function validar_superposicion_periodos($entidad_tipo)
	{
		//Los rangos de fechas no se tienen que superponer.
		$periodo = $this->tabla('per_inscripcion')->get_columna('periodo');
		$periodo_insc = $this->tabla('per_inscr_fechas')->get_columna('periodo_insc');
		$fecha_inicio = $this->tabla('per_inscr_fechas')->get_columna('fecha_inicio');
		$fecha_fin = $this->tabla('per_inscr_fechas')->get_columna('fecha_fin');
			
		if (guarani::co('co_periodos_de_inscripcion')->validar_superposicion_periodos($periodo, $entidad_tipo, $fecha_inicio, $fecha_fin, $periodo_insc)) {
			$this->set_error('Hay superposición de Períodos de Inscripción del Llamado');
		}
	}

	// Validaciones al guardar un subarbol : Período [Inicio - Fin] junto con sus subtipos ó entidades

	function validar_datos_subarbol($datos, $datos_ml_asociados, $tipo)
	{
		$this->validar_fechas_inicio_fin($datos['fecha_inicio'], $datos['fecha_fin']);

		$periodo_inscr_prop = $this->get_datos_per_inscr_prop();
		$this->validar_fechas_cierre_fin($periodo_inscr_prop['fecha_cierre'], $datos['fecha_fin']);

		if ($tipo == g3entidad::nivel_aplicacion_tipo_subtipo) {
			$this->validar_subtipos($datos_ml_asociados);
		}
		if ($tipo == g3entidad::nivel_aplicacion_tipo_entidad) {
			$this->validar_entidades($datos_ml_asociados);
		}

		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	function validar_fechas_inicio_fin($fecha_inicio, $fecha_fin)
	{
		if (!isset($fecha_inicio)) {
			$this->set_error('dato_faltante', array('Fecha Inicio'));
		}
		if (!isset($fecha_fin)) {
			$this->set_error('dato_faltante', array('Fecha Fin'));
		}
		if (guarani_fecha::es_mayor($fecha_inicio, $fecha_fin)) {
			$this->set_error('800_control_fechas', array('Inicio', 'mayor', 'Finalización.'));
		}
	}

	function validar_fechas_cierre_requisitos_fin($fecha_cierre_requisitos, $fecha_fin)
	{
		if (guarani_fecha::es_menor($fecha_cierre_requisitos, $fecha_fin)) {
			$dia = guarani_fecha::get_parte($fecha_cierre_requisitos, 'dia');
			$mes = guarani_fecha::get_parte($fecha_cierre_requisitos, 'mes');
			$anio = guarani_fecha::get_parte($fecha_cierre_requisitos, 'anio');
			$this->set_error('800_control_fechas', array("de Cierre Requisitos: $dia/$mes/$anio", 'menor', 'Fin.'));
		}
	}


	// Validaciones Tabla per_inscr_subtipos ------------------------------------------------

	function validar_subtipos($datos)
	{
		$this->verificar_existencia_requeridos($datos, 'un subtipo');
	}

	// Validaciones Tabla per_inscr_entidades ------------------------------------------------

	function validar_entidades($datos)
	{
		$this->verificar_existencia_requeridos($datos, 'una entidad');
	}

	function verificar_existencia_requeridos($datos, $tipo)
	{
		$datos_requerido = array();
		foreach ($datos as $clave => $valor) {
			if ($valor['requerido']) {
				return;
			}
		}
		if ($datos_requerido == array()) {
			$this->set_error('dato_faltante', array('Al menos '.$tipo));
		}
	}

	// Validaciones Tabla per_inscr_ubicacion ------------------------------------------------

	function validar_ubicaciones_duplicadas($datos)
	{
		$this->validar_duplicados($datos, 'ubicacion');
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	// Validaciones Tabla per_inscr_modalidad ------------------------------------------------

	function validar_modalidades_duplicadas($datos)
	{
		$this->validar_duplicados($datos, 'modalidad');
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	function validar_duplicados($datos, $campo)
	{
		$elementos = array();
		foreach ($datos as $clave => $valor) {
			if ($valor['apex_ei_analisis_fila'] != 'B') {
				if (in_array($valor[$campo], $elementos)) {
					$this->set_error('dato_duplicado', array($campo, 'Período de Inscripción'));
					return;
				} else {
					$elementos[] = $valor[$campo];
				}
			}
		}
	}

	/**
	 *	Extensión de la función del cn_entidad para realizar controles previos a la sincronización de datos.
	 *	Se controla que exista al menos un Período de Inscripción definido.
	 * 	Se controla que existan Ubicaciones y Modalidades asociadas, sino (y si es nuevo) asocia todas por defecto.
	 */
	function pre_sincronizar()
	{
		$datos_fechas = $this->get_datos_per_inscr_fechas();
		$this->validar_existencia_periodos($datos_fechas);

		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}

		$datos_ubicaciones = $this->get_datos_per_inscr_ubicacion();
		if (empty($datos_ubicaciones)) {
			$this->set_error('800_no_seleccion', array('una Ubicación'));
		}
		
		if ($this->usar_ubicaciones) {
			if (($this->controlador->entidad_es_nueva()) && empty($datos_ubicaciones)) {//Por defecto se asocian todas las ubicaciones
				$datos_ubicaciones = $this->obtener_ubicaciones();
				$this->set_datos_per_inscr_ubicacion($datos_ubicaciones);
			}
		}

		$datos_modalidades = $this->get_datos_per_inscr_modalidad();
		if (empty($datos_modalidades)) {
			$this->set_error('800_no_seleccion', array('una Modalidad'));
		}
		
		if ($this->usar_modalidades) {
			if (($this->controlador->entidad_es_nueva()) && empty($datos_modalidades)) {//Por defecto se asocian todas las modalidades
				$datos_modalidades = $this->obtener_modalidades();
				$this->set_datos_per_inscr_modalidad($datos_modalidades);
			}
		}
		
		$datos = $this->get_datos();
		if ($datos['entidad_tipo'] != g3entidad::tipo_institucion) {
			if ($datos['tipo'] == 'S') {
				$subtipos = $this->get_datos_per_inscr_subtipos();
				if (empty($subtipos)) {
					$this->set_error('800_no_seleccion', array('un Tipo de Propuesta'));
				}
			} else {
				$entidades = $this->get_datos_per_inscr_entidad();
				if (empty($entidades)) {
					switch ($datos['entidad_tipo']) {
						case g3entidad::tipo_responsable_academica :
							$this->set_error('800_no_seleccion', array('una Responsable Académica'));
							break;
							
						case g3entidad::tipo_propuesta :
							$this->set_error('800_no_seleccion', array('una Propuesta'));
							break;
						
						case g3entidad::tipo_plan_estudio :
							$this->set_error('800_no_seleccion', array('un Plan de Estudio'));
							break;	
							
						case g3entidad::tipo_plan_estudio_version :
							$this->set_error('800_no_seleccion', array('una Versión de Plan de Estudio'));
							break;
					}
				}
			}
		}
		
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
		
	}

	function validar_existencia_periodos($datos_fechas)
	{
		if (guarani_arreglos::count($datos_fechas) == 0) {
			$this->set_error('800CAL_periodo_faltante');
		}
	}
	
}