<?php
/**
 *	Representa un Período Lectivo
 *
 * @package Calendario
 * @subpackage Períodos Lectivos
 */
class cn_ent_periodo_lectivo extends cn_entidad
{
	protected $hay_que_generar_aplanado = true;

	function cargar($id)
	{
		$this->tabla('per_inscr_entidades')->persistidor()->activar_perfil_de_datos();
		parent::cargar($id);
	}

	function hay_que_generar_aplanado() 
	{
		return $this->hay_que_generar_aplanado;
	}
	
	//---- Tabla periodo
	
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos_periodo($datos);
		$this->tabla('periodo')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('periodo')->get();
	}
	
	//---- Tabla per_lectivo
	
	function set_datos_per_lectivo($datos)
	{
		$this->tabla('per_lectivo')->set($datos);
	}
	
	function get_datos_per_lectivo()
	{
		return $this->tabla('per_lectivo')->get();
	}
	
	//---- Tabla per_lectivo_tramos
	
	function set_datos_per_lectivo_tramos($datos)
	{
		$this->validar_per_lectivo_tramos($datos);
		$this->tabla('per_lectivo_tramos')->procesar_filas($datos);
	}

	function get_datos_per_lectivo_tramos()
	{
        return $this->tabla('per_lectivo_tramos')->get_filas();
	}

	//---- Tabla per_inscr

	function get_datos_per_inscr()
	{
		return $this->tabla('per_inscr')->get_filas();
	}

	function set_datos_per_inscr($datos)
	{
		if ($this->hay_per_inscr_seleccionado()) {
			$datos['periodo_generico_tipo'] = periodo_generico::tipo_periodo_lectivo;
			$this->validar_datos_per_inscr($datos);
			$this->tabla('per_inscr')->set($datos);
		}
	}

	function seleccionar_per_inscr($id_fila_per_inscr)
	{
		$this->tabla('per_inscr')->set_cursor($id_fila_per_inscr);
		$id_filas = $this->tabla('per_inscr_fechas')->get_id_filas();
		if (! empty($id_filas)) {
			$this->tabla('per_inscr_fechas')->set_cursor(current($id_filas));
		}
	}

	function deseleccionar_per_inscr()
	{
		$this->tabla('per_inscr')->resetear_cursor();
		$this->tabla('per_inscr_fechas')->resetear_cursor();
	}

	function get_datos_per_inscr_seleccionado()
	{
		if ($this->hay_per_inscr_seleccionado()) {
			return $this->tabla('per_inscr')->get();
		}
	}

	function hay_per_inscr_seleccionado()
	{
		return $this->tabla('per_inscr')->hay_cursor();
	}

	function eliminar_per_inscr_seleccionado()
	{
		if ($this->hay_per_inscr_seleccionado()) {
			$id_fila = $this->tabla('per_inscr')->get_cursor();
			$this->tabla('per_inscr')->eliminar_fila($id_fila);
		}
	}

	function nuevo_per_inscr($datos)
	{
		$this->validar_datos_per_inscr($datos);
		$datos['periodo_generico_tipo'] = periodo_generico::tipo_periodo_lectivo;
		$id = $this->tabla('per_inscr')->nueva_fila($datos);
		$this->seleccionar_per_inscr($id);
	}

	//---- Tabla per_inscr_fechas

	function set_datos_per_inscr_fechas($datos)
	{
		$this->validar_datos_per_inscr_fechas($datos);
		$this->tabla('per_inscr_fechas')->set($datos);
	}

	function get_datos_per_inscr_fechas()
	{
        return $this->tabla('per_inscr_fechas')->get();
	}

	//---- Tabla per_inscr_subtipos

	function set_datos_per_inscr_subtipos($datos)
	{
		$this->validar_subtipos($datos);
		$this->tabla('per_inscr_subtipos')->procesar_filas($datos);
	}

	function get_datos_per_inscr_subtipos()
	{
        return $this->tabla('per_inscr_subtipos')->get_filas();
	}

	//---- Tabla per_inscr_entidades

	function set_datos_per_inscr_entidades($datos)
	{
		$this->validar_entidades($datos);
		$this->tabla('per_inscr_entidades')->procesar_filas($datos);
	}

	function get_datos_per_inscr_entidades()
	{
        return $this->tabla('per_inscr_entidades')->get_filas();
	}

	//---- Tabla per_inscr_modalidades

	function get_datos_per_inscr_modalidades()
	{
        return $this->tabla('per_inscr_modalidades')->get_filas();
	}

	function set_datos_per_inscr_modalidades($datos)
	{
		$this->validar_datos_per_inscr_modalidades($datos);

		$modalidades = $this->tabla('per_inscr_modalidades')->get_filas();
		$modalidades_aplanadas = aplanar_matriz($modalidades, 'modalidad');
		$modalidades_a_borrar = array_diff($modalidades_aplanadas, $datos);
		$modalidades_a_insertar = array_diff($datos, $modalidades_aplanadas);

		$modalidades_asociativas = rs_convertir_asociativo($modalidades, array('modalidad'), apex_datos_clave_fila);
		foreach ($modalidades_a_borrar as $modalidad) {
			$this->tabla('per_inscr_modalidades')->eliminar_fila($modalidades_asociativas[$modalidad]);
		}

		foreach ($modalidades_a_insertar as $modalidad) {
			$this->tabla('per_inscr_modalidades')->nueva_fila(array('modalidad' => $modalidad));
		}
	}

	//---- Tabla per_inscr_ubicaciones

	function get_datos_per_inscr_ubicaciones()
	{
        return $this->tabla('per_inscr_ubicaciones')->get_filas();
	}

	function set_datos_per_inscr_ubicaciones($datos)
	{
		$this->validar_datos_per_inscr_ubicaciones($datos);

		$ubicaciones = $this->tabla('per_inscr_ubicaciones')->get_filas();
		$ubicaciones_aplanadas = aplanar_matriz($ubicaciones, 'ubicacion');
		$ubicaciones_a_borrar = array_diff($ubicaciones_aplanadas, $datos);
		$ubicaciones_a_insertar = array_diff($datos, $ubicaciones_aplanadas);

		$ubicaciones_asociativas = rs_convertir_asociativo($ubicaciones, array('ubicacion'), apex_datos_clave_fila);
		foreach ($ubicaciones_a_borrar as $ubicacion) {
			$this->tabla('per_inscr_ubicaciones')->eliminar_fila($ubicaciones_asociativas[$ubicacion]);
		}

		foreach ($ubicaciones_a_insertar as $ubicacion) {
			$this->tabla('per_inscr_ubicaciones')->nueva_fila(array('ubicacion' => $ubicacion));
		}
	}

	//---- Eliminaciones de Entidades/Subtipos - Períodos Inscripción Fechas
	
	function eliminar_subtipo_entidad($tipo, $subtipo_entidad, $per_insc_fecha)
	{
		if ($tipo == g3entidad::nivel_aplicacion_tipo_subtipo) {
			//El nivel de aplicación es un subtipo: Elimino el registro correspondiente de Periodos Inscripcion Subtipos
			$this->eliminar_fila_subtipo_entidad('per_inscr_subtipos', 'entidad_subtipo', $subtipo_entidad, $per_insc_fecha);
		}
		if ($tipo == g3entidad::nivel_aplicacion_tipo_entidad) {
			//El nivel de aplicación es una entidad: Elimino el registro correspondiente de Periodos Inscripcion Entidades
			$this->eliminar_fila_subtipo_entidad('per_inscr_entidades', 'entidad', $subtipo_entidad, $per_insc_fecha);
		}
	}
	
	function eliminar_fila_subtipo_entidad($tabla, $campo, $subtipo_entidad, $per_insc_fecha)
	{
		$this->set_cursor_periodo($per_insc_fecha);
		$id_fila = $this->tabla($tabla)->get_id_fila_condicion(array(array($campo, '==', $subtipo_entidad)));
		$fila = $this->tabla($tabla)->get_fila(current($id_fila));
		$this->tabla($tabla)->eliminar_fila(current($id_fila));
		$datos_subtipos_entidades = $this->tabla($tabla)->get_filas();
		
		if (guarani_arreglos::count($datos_subtipos_entidades) == 0) {
			//En caso de que no existan mas Subtipos/Entidades borro el Período Inscripción Fecha
			$this->eliminar_fila_per_inscr_fecha($per_insc_fecha);
			$per_insc = $this->get_datos_per_inscr();
			$datos_per_insc_fec = $this->tabla('per_inscr_fechas')->get_filas();
			
			if (guarani_arreglos::count($datos_per_insc_fec) == 0) {
				//Si no existen mas Períodos Inscripción Fechas borro el Período Inscripción junto con sus Ubicaciones y Modalidades
				$this->eliminar_fila_per_inscr();
				$this->eliminar_ubicaciones();
				$this->eliminar_modalidades();
			}
		}
	}
	
	function tiene_excepcion_asociada_a_comision($per_insc_fecha)
	{
		$this->set_cursor_periodo($per_insc_fecha);
		$periodo_inscripcion = $this->get_datos_per_inscr_fecha();
		$posee_excepcion = toba::consulta_php('co_periodos_lectivos')->tiene_excepcion_en_comision($periodo_inscripcion['periodo_insc']);
		
		return $posee_excepcion;
	}
	
	function get_comisiones_periodo($periodo)
	{
		$where = "sga_periodos.periodo = $periodo";
		return toba::consulta_php('co_comisiones')->get_listado($where);
	}
	
	//---- Consultas
	
	function es_subtipo_requerido($subtipo) {
		return $this->tabla('per_inscr_subtipos')->existe_fila_condicion(array(array('entidad_subtipo', '==', $subtipo)));
	}
	
	function es_entidad_requerida($entidad) {
		return $this->tabla('per_inscr_entidades')->existe_fila_condicion(array(array('entidad', '==', $entidad)));
	}
	
	function es_para_institucion_gestionante() 
	{  
		if (!$this->tabla('per_inscr_entidades')->esta_cargada() &&  !$this->tabla('per_inscr_entidades')->hay_cambios()) return false;
		$entidades = $this->get_datos_per_inscr_entidades();
		if (!isset($entidades[0]['entidad'])) return false;
		return ($entidades[0]['entidad'] == institucion::get_entidad_gestionante());
	}
	
	//---- Actualizaciones de Entidades/Subtipos
	
	function set_datos_entidad_para_todo()
	{
		$this->set_datos_entidades(array(array('entidad' => institucion::get_entidad_gestionante(),'requerido' => true)));
	}
	
	function set_datos_subtipos($datos)
	{
		$this->validar_subtipos($datos);
		$this->tabla('per_inscr_entidades')->eliminar_filas();	//Se eliminan las entidades que tenga asociadas
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
		$this->validar_entidades($datos);
		$this->tabla('per_inscr_entidades')->eliminar_filas();	//Se eliminan las entidades que tenga asociadas
		$this->tabla('per_inscr_subtipos')->eliminar_filas();	//Se eliminan los subtipos que tenga asociados
		foreach($datos as $entidad) {
			if ($entidad['requerido'] == '1') {
				$cursor = $this->tabla('per_inscr_entidades')->nueva_fila();
				$this->tabla('per_inscr_entidades')->set_cursor($cursor);
				$this->tabla('per_inscr_entidades')->set(array('entidad' => $entidad['entidad']));
			}
		}
	}

	//---- Validaciones del período lectivo
	
	function validar_datos_periodo($datos)
	{
		$datos['periodo_lectivo'] = $this->tabla('per_lectivo')->get_columna('periodo_lectivo');
		$this->validar_nombre_periodo_lectivo($datos['nombre'], $datos['anio_academico'], $datos['periodo_lectivo']);
		$this->validar_anios_ini_fin_periodo($datos['fecha_inicio'], $datos['fecha_fin'], $datos['anio_academico']);
		$this->validar_fechas_ini_fin_periodo($datos['fecha_inicio'], $datos['fecha_fin']);
		$this->validar_fechas_ini_fin_dictado($datos['fecha_inicio_dictado'], $datos['fecha_fin_dictado']);
		$this->validar_fecha_tope_movimientos($datos['fecha_tope_movimientos'], $datos['fecha_inicio'], $datos['fecha_fin']);
		$this->validar_fecha_inactivacion($datos['fecha_inactivacion'], $datos['fecha_fin_dictado']);
		if (isset($datos['fecha_publicacion_comisiones'])) {
			$this->validar_fecha_publicacion_comisiones($datos['fecha_publicacion_comisiones'], $datos['fecha_inicio_dictado']);
		}

		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}
	
	function validar_nombre_periodo_lectivo($nombre, $anio_academico, $periodo)
	{
		if (!toba::consulta_php('co_periodos_lectivos')->validar_nombre_periodo_lectivo($nombre, $anio_academico, $periodo)) {
			$this->set_notificacion('800CAL_periodo_duplicado');
		}
	}
	
	function validar_anios_ini_fin_periodo($fecha_inicio, $fecha_fin, $anio_acad)
	{
		$anio_ini = guarani_fecha::get_parte($fecha_inicio, 'anio');
		$anio_fin = guarani_fecha::get_parte($fecha_fin, 'anio');
		$ini_in_range = in_array($anio_ini, range($anio_acad - 1, $anio_acad + 1));
		$fin_in_range = in_array($anio_fin, range($anio_acad - 1, $anio_acad + 1));
		
		if ((!$ini_in_range) || (!$fin_in_range)) {
			$this->set_error('800_control_anios', array('[Inicio Período, Fin Período]', 'distintos (ó +/- 1)', 'Académico'));
		}
	}
	
	function validar_fechas_ini_fin_periodo($fecha_inicio, $fecha_fin)
	{
		if (guarani_fecha::es_mayor($fecha_inicio, $fecha_fin)) {
			$this->set_error('800_control_fechas', array('Inicio Período', 'mayor', 'Fin Período'));
		}
	}
	
	function validar_fechas_ini_fin_dictado($fecha_ini_dictado, $fecha_fin_dictado)
	{
		if (guarani_fecha::es_mayor($fecha_ini_dictado, $fecha_fin_dictado)) {
			$this->set_error('800_control_fechas', array('Inicio Dictado', 'mayor', 'Fin Dictado'));
		}
	}

	function validar_fecha_tope_movimientos($fecha_tope_mov, $fecha_inicio, $fecha_fin)
	{
		if (! guarani_fecha::esta_entre_fechas($fecha_tope_mov, $fecha_inicio, $fecha_fin)) {
			$this->set_error('800_control_fecha_entre', array('Tope Movimientos', 'Inicio Período', 'Fin Período'));
		}
	}

	function validar_fecha_inactivacion($fecha_inactivacion, $fecha_fin_dictado)
	{
		// Se valida que la fecha de inactivación sea igual o posterior a la fecha de fin de dictado del periodo lectivo.
		if (guarani_fecha::es_menor($fecha_inactivacion, $fecha_fin_dictado)) {
			$this->set_error('800_control_fechas', array('Inactivación', 'menor', 'Fin de Dictado'));
		}
	}

	function validar_fecha_publicacion_comisiones($fecha_publicacion, $fecha_inicio_dictado)
	{
		if (! guarani_fecha::es_menor($fecha_publicacion, $fecha_inicio_dictado)) {
			$this->set_error('800_control_fechas', array('Publicación de Comisiones', 'mayor o igual', 'Inicio de Dictado'));
		}
	}
	
	//---- Validaciones de tramos del período lectivo

	function validar_per_lectivo_tramos($datos)
	{
		$this->validar_tramos($datos);
		
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	function validar_tramos($tramos)
	{
		//Fechas de Inicio y fin Dictado
		$periodo_lectivo = $this->get_datos_per_lectivo();
		$fecha_inicio_periodo_lec = $periodo_lectivo['fecha_inicio_dictado'];
		$fecha_fin_periodo_lec = $periodo_lectivo['fecha_fin_dictado'];
		
		//Verifico si existen tramos duplicados
		$count_values  = array_count_values(aplanar_matriz($tramos, 'nro_tramo'));
		$unique_values = array_unique($count_values);
		$tramos_duplicados = guarani_arreglos::count($unique_values) > 1 || (guarani_arreglos::count($unique_values) == 1 && current($unique_values) > 1);
		
		if ($tramos_duplicados) {
			$this->set_error('800CAL_tramos_duplicados');
		}
		
		//Controles sobre las fechas
		foreach ($tramos as $tramo) {
			if ($tramo['apex_ei_analisis_fila'] != comunes::estado_baja) {
				$numero = $tramo['nro_tramo'];
				
				//Fecha Inicio del Tramo menor que Fecha Fin
				if (guarani_fecha::es_menor_igual($tramo['fecha_fin'], $tramo['fecha_inicio'])) {
					$this->set_error('800_control_fechas', array("de Fin del Tramo de Asistencia número $numero", 'menor o igual', 'de Inicio'));
				}
				
				//Fecha Fin del Tramo contenida dentro de las fechas del Período Lectivo
				if (guarani_fecha::es_menor($tramo['fecha_fin'], $fecha_inicio_periodo_lec) || guarani_fecha::es_mayor($tramo['fecha_fin'], $fecha_fin_periodo_lec)) {
					$this->set_error('800_control_fecha_entre', array("Fin del Tramo de Asistencia número $numero", 'Inicio Período', 'Fin Período'));
				}
				
				//Fecha Inicio del Tramo contenida dentro de las fechas del Período Lectivo
				if (guarani_fecha::es_menor($tramo['fecha_inicio'], $fecha_inicio_periodo_lec) || guarani_fecha::es_mayor($tramo['fecha_inicio'], $fecha_fin_periodo_lec)) {
					$this->set_error('800_control_fecha_entre', array("Inicio del Tramo de Asistencia número $numero", 'Inicio Período', 'Fin Período'));
				}
			}
		}
		
		//Rangos de Fecha Inicio - Fecha Fin de Tramo solapados
		if ($this->existen_rangos_solapados($tramos)) {
			$this->set_error('800_rangos_fechas_solapados', array('Tramos de Asistencia'));
		}
	}
	
	function existen_rangos_solapados($tramos)
	{
		foreach ($tramos as $tramo) {
			if ($tramo['apex_ei_analisis_fila'] != comunes::estado_baja) {
				if ($this->check_rangos_solapados($tramo, $tramos)) {
					return true;
				}
			}
		}
	}
	
	function check_rangos_solapados($tramo, $tramos)
	{
		foreach ($tramos as $clave => $valor) {
			if ($valor['apex_ei_analisis_fila'] != comunes::estado_baja) {
				if ($tramo['nro_tramo'] != $valor['nro_tramo']) {
					$desde1 = $tramo['fecha_inicio'];
					$hasta1 = $tramo['fecha_fin'];
					$desde2 = $valor['fecha_inicio'];
					$hasta2 = $valor['fecha_fin'];
					if (guarani_fecha::rango_solapado_en_rango($desde1, $hasta1, $desde2, $hasta2)) {
						return true;
					}
				}
			}
		}
	}

	//---- Validaciones de períodos de inscripción

	function validar_datos_per_inscr($datos)
	{
		if (! isset($datos['nombre'])) {
			$this->set_error('dato_faltante', array('Nombre'));
		}
		if ($this->hay_errores) {
			throw guarani::error($this->errores);
		}
	}

	//---- Validaciones de modalidades por período de inscripción

	function validar_datos_per_inscr_modalidades($datos)
	{
		if (empty($datos)) {
			$this->set_error('dato_faltante', array('Modalidad'));
		}
		if ($this->hay_errores) {
			throw guarani::error($this->errores);
		}
	}

	//---- Validaciones de ubicaciones por período de inscripción

	function validar_datos_per_inscr_ubicaciones($datos)
	{
		if (empty($datos)) {
			$this->set_error('dato_faltante', array('Ubicación'));
		}
		if ($this->hay_errores) {
			throw guarani::error($this->errores);
		}
	}

	//---- Validaciones del rango de fechas de un período de inscripción
	
	function validar_datos_per_inscr_fechas($datos)
	{
		if (! isset($datos['fecha_inicio'])) {
			$this->set_error('dato_faltante', array('Fecha (y hora) Inicio'));
		}
		if (! isset($datos['fecha_fin'])) {
			$this->set_error('dato_faltante', array('Fecha (y hora) Fin'));
		}
		if (! isset($datos['fecha_tope_bajas'])) {
			$this->set_error('dato_faltante', array('Tope de Bajas (fecha y hora)'));
		}

		$periodo = $this->get_datos();
		$this->validar_fechas_ini_fin_per_insc($datos['fecha_inicio'], $datos['fecha_fin']);
		$this->validar_fechas_ini_per_insc_fin_per_lec($datos['fecha_inicio'], $periodo['fecha_fin']);
		$this->validar_fechas_fin_per_insc_lec($datos['fecha_fin'], $periodo['fecha_fin']);
		$this->validar_fechas_ini_tope_bajas_per_insc($datos['fecha_inicio'], $datos['fecha_tope_bajas']);
		$this->validar_fechas_tope_bajas_fin_per_lec($datos['fecha_tope_bajas'], $periodo['fecha_fin']);

		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}
	
	function validar_fechas_ini_fin_per_insc($fecha_inicio, $fecha_fin)
	{
		if (guarani_fecha::es_mayor_timestamp($fecha_inicio, $fecha_fin)) {
			$this->set_error('800_control_fechas', array('de Inicio del Período de Inscripción', 'mayor', 'de Fin del Período de Inscripción'));
		}
	}

	function validar_fechas_ini_per_insc_fin_per_lec($fecha_inicio_per_insc, $fecha_fin_per_lec)
	{
		if (guarani_fecha::es_menor($fecha_fin_per_lec, $fecha_inicio_per_insc)) {
			$this->set_error('800_control_fechas', array('de Fin del Período Lectivo', 'menor', 'de Inicio del Período de Inscripción'));
		}
	}
	
	function validar_fechas_fin_per_insc_lec($fecha_fin_per_insc, $fecha_fin_per_lectivo)
	{
		if (guarani_fecha::es_mayor($fecha_fin_per_insc, $fecha_fin_per_lectivo)) {
			$this->set_error('800_control_fechas', array('de Fin del Período de Inscripción', 'mayor', 'de Fin del Período Lectivo'));
		}
	}

	function validar_fechas_ini_tope_bajas_per_insc($fecha_inicio, $fecha_tope_bajas)
	{
		if (guarani_fecha::es_mayor_igual_timestamp($fecha_inicio, $fecha_tope_bajas)) {
			$this->set_error('800_control_fechas', array('de Inicio del Período de Inscripción', 'mayor o igual', 'de Tope de Bajas del Período de Inscripción'));
		}
	}

	function validar_fechas_tope_bajas_fin_per_lec($fecha_tope_bajas, $fecha_fin_per_lec)
	{
		if (guarani_fecha::es_mayor($fecha_tope_bajas, $fecha_fin_per_lec)) {
			$this->set_error('800_control_fechas', array('de Tope de Bajas del Período de Inscripción', 'mayor', 'de Fin del Período Lectivo'));
		}
	}

	//---- Validaciones de subtipos asociados al rango de fechas de un período de inscripción

	function validar_subtipos($datos)
	{
		$this->verificar_existencia_requeridos($datos, 'un subtipo');
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	//---- Validaciones de entidades asociadas al rango de fechas de un período de inscripción

	function validar_entidades($datos)
	{
		$this->verificar_existencia_requeridos($datos, 'una entidad');
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	function verificar_existencia_requeridos($datos, $tipo)
	{
		$datos_requerido = array();
		foreach ($datos as $clave => $valor) {
			if ($valor['requerido']) {
				return;
			}
		}
		$this->set_error('dato_faltante', array('Al menos '.$tipo));
	}

	//---- Validaciones generales

	function validar_fecha_fin_con_periodos_inscr($fecha_fin)
	{
		$periodos_inscripcion = $this->get_datos_per_inscr();
		foreach ($periodos_inscripcion as $per_inscr) {
			$this->seleccionar_per_inscr($per_inscr[apex_datos_clave_fila]);
			$per_inscr_fechas = $this->get_datos_per_inscr_fechas();
			if (guarani_fecha::es_mayor($per_inscr_fechas['fecha_fin'], $fecha_fin)) {
				$this->set_error('800CAL_error_fecha_fin_periodo');
				break;
			}
		}
	}

	function validar_fechas_ini_fin_dictado_con_tramos($fecha_inicio_dictado, $fecha_fin_dictado)
	{
		$datos_tramos = $this->get_datos_per_lectivo_tramos();
		
		/**
		 * Si existe al menos un Tramo de Asistencia cuya Fecha Fin supera la
		 * Fecha Fin Dictado del Período Lectivo ó la Fecha Inicio es menor que la
		 * Fecha Inicio Dictado del Período Lectivo entonces emito un mensaje de error
		 */
		foreach ($datos_tramos as $tramo) {
			$sobrepasa_ini = guarani_fecha::es_menor($tramo['fecha_inicio'], $fecha_inicio_dictado);
			$sobrepasa_fin = guarani_fecha::es_mayor($tramo['fecha_fin'], $fecha_fin_dictado);
			
			if ($sobrepasa_ini || $sobrepasa_fin) {
				$this->set_error('800CAL_error_fecha_dictado_periodo');
				break;	
			}
		}
	}

	/**
	 *	Extensión de la función del cn_entidad para realizar controles previos a la sincronización de datos.
	 *	Se controla que exista al menos un Tramo de Asistencia definido, caso contrario se agrega.
	 */
	function pre_sincronizar()
	{
		$periodo = $this->get_datos();
		$this->validar_fecha_fin_con_periodos_inscr($periodo['fecha_fin']);

		$periodo_lectivo = $this->get_datos_per_lectivo();
		$this->validar_fechas_ini_fin_dictado_con_tramos($periodo_lectivo['fecha_inicio_dictado'], $periodo_lectivo['fecha_fin_dictado']);

		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}

		$datos_tramos = $this->get_datos_per_lectivo_tramos();
		if ($this->controlador->entidad_es_nueva() && empty($datos_tramos)) {
			// Por defecto se asocia un Tramo de Asistencia con Fecha de Inicio y Fin de dictado.
			$tramo['nro_tramo']		= 1;
			$tramo['fecha_inicio'] 	= $periodo_lectivo['fecha_inicio_dictado'];
			$tramo['fecha_fin'] 	= $periodo_lectivo['fecha_fin_dictado'];
			$tramo['apex_ei_analisis_fila'] = 'A';
			$this->set_datos_per_lectivo_tramos(array(0 => $tramo));
		}
	}
}
?>