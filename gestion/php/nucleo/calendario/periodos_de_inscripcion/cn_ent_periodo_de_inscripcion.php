<?php
/**
 * 	Representa un Período de Inscripción
 *
 * @package Calendario
 * @subpackage Actualizaciones
 */
class cn_ent_periodo_de_inscripcion extends cn_entidad
{
	protected $hay_que_generar_aplanado = true;

	function cargar($id)
	{
		$this->tabla('per_inscr_entidad')->persistidor()->activar_perfil_de_datos();
		$this->tabla('per_inscr_ubicacion')->persistidor()->activar_perfil_de_datos();
		parent::cargar($id);
	}

	function hay_que_generar_aplanado()
	{
		return $this->hay_que_generar_aplanado;
	}

	function set_datos($datos, $tabla = null, $validar = true)
	{
		$this->validar_datos_per_inscr($datos);
		unset($datos['anio_academico']);
		unset($datos['periodo_insc_tipo']);
		unset($datos['fecha_cierre_requisitos']);
		unset($datos['fecha_ingreso_alumnos']);
		unset($datos['habilitado']);

		$datos['periodo_generico_tipo'] = periodo_generico::tipo_propuesta;

		$this->tabla('per_inscripcion')->set($datos);
	}

	function get_datos($tabla = null)
	{
		return $this->tabla('per_inscripcion')->get();
	}

	function set_datos_per_inscr_prop($datos)
	{
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
		$this->validar_eliminacion_periodo_insc();
		$this->tabla('per_inscr_fechas')->eliminar_fila($seleccion);
	}

	function set_cursor_periodo($id_fila)
	{
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

	//-------- Modalidades ------------------------------------------------

	function set_datos_per_inscr_modalidad($datos)
	{
		$this->tabla('per_inscr_modalidad')->procesar_filas($datos);
	}

	function get_datos_per_inscr_modalidad()
	{
		return $this->tabla('per_inscr_modalidad')->get_filas();
	}

	function hay_cambios_modalidades()
	{
		return $this->tabla('per_inscr_modalidad')->hay_cambios();
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

	function eliminar_fila_modalidad_asignada($seleccion)
	{
		$this->tabla('per_inscr_modalidad')->eliminar_fila($seleccion);
	}

	function agregar_fila_modalidad($fila)
	{
		$this->tabla('per_inscr_modalidad')->nueva_fila($fila);
	}

	//------------- Ubicaciones -------------------------------------------

	function set_datos_per_inscr_ubicacion($datos)
	{
		$this->tabla('per_inscr_ubicacion')->procesar_filas($datos);
	}

	function get_datos_per_inscr_ubicacion()
	{
		return $this->tabla('per_inscr_ubicacion')->get_filas();
	}

	function hay_cambios_ubicaciones()
	{
		return $this->tabla('per_inscr_ubicacion')->hay_cambios();
	}

	function esta_cargada_per_inscr_ubicacion()
	{
		return $this->tabla('per_inscr_ubicacion')->get_cantidad_filas() > 0;
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

	function eliminar_fila_ubicacion_asignada($seleccion)
	{
		$this->tabla('per_inscr_ubicacion')->eliminar_fila($seleccion);
	}

	function agregar_fila_ubicacion($fila)
	{
		$this->tabla('per_inscr_ubicacion')->nueva_fila($fila);
	}

	//--------------------------------------------------------------------------

	function get_datos_per_inscr_subtipos()
	{
		return $this->tabla('per_inscr_subtipos')->get_filas();
	}

	function get_datos_per_inscr_entidad()
	{
		return $this->tabla('per_inscr_entidad')->get_filas();
	}

	function es_subtipo_requerido($subtipo)
	{
		return $this->tabla('per_inscr_subtipos')->existe_fila_condicion(array(array('entidad_subtipo', '==', $subtipo)));
	}

	function es_entidad_requerida($entidad)
	{
		return $this->tabla('per_inscr_entidad')->existe_fila_condicion(array(array('entidad', '==', $entidad)));
	}

	function es_para_institucion_gestionante()
	{
		if (!$this->tabla('per_inscr_entidad')->esta_cargada() && !$this->tabla('per_inscr_entidad')->hay_cambios())
			return false;
		$entidades = $this->get_datos_per_inscr_entidad();
		if (!isset($entidades[0]['entidad']))
			return false;
		return ($entidades[0]['entidad'] == institucion::get_entidad_gestionante());
	}

	function set_datos_entidad_para_todo()
	{
		$this->tabla('per_inscr_entidad')->eliminar_filas();
		$this->tabla('per_inscr_subtipos')->eliminar_filas();
		$this->set_datos_entidades(array(array('entidad' => institucion::get_entidad_gestionante(), 'requerido' => true)));
	}

	function set_datos_subtipos($datos)
	{
		$this->tabla('per_inscr_entidad')->eliminar_filas(); //Se eliminan las entidades que tenga asociadas
		$this->tabla('per_inscr_subtipos')->eliminar_filas(); //Se eliminan los subtipos que tenga asociados
		foreach ($datos as $subtipo) {
			if ($subtipo['requerido'] == '1') {
				$cursor = $this->tabla('per_inscr_subtipos')->nueva_fila();
				$this->tabla('per_inscr_subtipos')->set_cursor($cursor);
				$this->tabla('per_inscr_subtipos')->set(array('entidad_subtipo' => $subtipo['entidad_subtipo']));
			}
		}
	}

	function set_datos_entidades($datos)
	{
		$this->tabla('per_inscr_entidad')->eliminar_filas(); //Se eliminan las entidades que tenga asociadas
		$this->tabla('per_inscr_subtipos')->eliminar_filas(); //Se eliminan los subtipos que tenga asociados
		foreach ($datos as $entidad) {
			if ($entidad['requerido'] == '1') {
				$cursor = $this->tabla('per_inscr_entidad')->nueva_fila();
				$this->tabla('per_inscr_entidad')->set_cursor($cursor);
				$this->tabla('per_inscr_entidad')->set(array('entidad' => $entidad['entidad']));
			}
		}
	}

	// Validaciones Tabla per_inscripcion ------------------------------------------------

	function validar_datos_per_inscr($datos)
	{
		$datos['periodo_inscripcion'] = $this->tabla('per_inscripcion')->get_columna('periodo_inscripcion');
		$this->validar_nombre_periodo_de_inscripcion($datos['nombre'], $datos['periodo_inscripcion']);
		if (!empty($datos['fecha_cierre_requisitos'])) {
			$this->validar_fechas_cierre_requisitos_fin_periodos($datos['fecha_cierre_requisitos']);
		}
		if (!empty($datos['fecha_ingreso_alumnos'])) {
			$this->validar_fecha_ingreso_alumnos($datos['fecha_ingreso_alumnos'], $datos['fecha_cierre_requisitos']);
		}

		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	function validar_nombre_periodo_de_inscripcion($nombre, $periodo_inscripcion)
	{
		if (!toba::consulta_php('co_periodos_de_inscripcion')->validar_nombre_periodo_de_inscripcion_propuestas($nombre, $periodo_inscripcion)) {
			$this->set_error('dato_duplicado', array('nombre', 'período de inscripción'));
		}
	}

	function validar_fechas_cierre_requisitos_fin_periodos($fecha_cierre_requisitos)
	{
		$fechas = $this->get_datos_per_inscr_fechas();
		foreach ($fechas as $fecha) {
			if (guarani_fecha::es_menor($fecha_cierre_requisitos, $fecha['fecha_fin'])) {
				$this->set_error('800_control_fechas', array('de Cierre Requisitos', 'menor', 'Fin de cualquiera de los Períodos de Inscripción ingresados.'));
			}
		}
	}

	function validar_fecha_ingreso_alumnos($fecha_ingreso_alumnos, $fecha_cierre_requisitos)
	{
		if (guarani_fecha::es_mayor($fecha_ingreso_alumnos, $fecha_cierre_requisitos)) {
			$dia = guarani_fecha::get_parte($fecha_ingreso_alumnos, 'dia');
			$mes = guarani_fecha::get_parte($fecha_ingreso_alumnos, 'mes');
			$anio = guarani_fecha::get_parte($fecha_ingreso_alumnos, 'anio');
			$this->set_error('800_control_fechas', array("de Fecha Ingreso Alumnos : $dia/$mes/$anio", 'mayor', ' cierre de requisitos.'));
		}
	}

	// Validaciones al guardar un subarbol : Período [Inicio - Fin] junto con sus subtipos ó entidades

	function validar_datos_subarbol($datos, $datos_ml_asociados, $tipo)
	{
		$this->validar_fechas_inicio_fin($datos['fecha_inicio'], $datos['fecha_fin']);

		$periodo_inscr_prop = $this->get_datos_per_inscr_prop();
		$this->validar_fechas_cierre_requisitos_fin($periodo_inscr_prop['fecha_cierre_requisitos'], $datos['fecha_fin']);

		if ($tipo == g3entidad::nivel_aplicacion_tipo_subtipo) {
			$this->validar_subtipos($datos_ml_asociados);
		}
		if ($tipo == g3entidad::nivel_aplicacion_tipo_entidad) {
			$this->validar_entidades($datos_ml_asociados);
		}

		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
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

	function validar_eliminacion_periodo_insc()
	{
		$datos_fechas = $this->get_datos_per_inscr_fecha();
		if (!empty($datos_fechas['periodo_insc'])) {
			$this->validar_inscripciones_a_propuesta($datos_fechas['periodo_insc']);
			if ($this->hay_errores()) {
				throw guarani::error($this->errores);
			}
		}
	}

	function validar_inscripciones_a_propuesta($periodo_insc)
	{
		if (toba::consulta_php('co_periodos_de_inscripcion')->tiene_inscripciones_a_propuesta($periodo_insc)) {
			$this->set_error('800CAL_periodo_con_inscripciones');
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
			$this->set_error('dato_faltante', array('Al menos ' . $tipo));
		}
	}

	// Validaciones Tabla per_inscr_ubicacion ------------------------------------------------

	function validar_ubicaciones_duplicadas($datos)
	{
		$this->validar_duplicados($datos, 'ubicacion');
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	// Validaciones Tabla per_inscr_modalidad ------------------------------------------------

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
	 * 	Extensión de la función del cn_entidad para realizar controles previos a la sincronización de datos.
	 * 	Se controla que exista al menos un Período de Inscripción definido.
	 * 	Se controla que existan Ubicaciones y Modalidades asociadas, sino (y si es nuevo) asocia todas por defecto.
	 */
	function pre_sincronizar()
	{
		$datos_fechas = $this->get_datos_per_inscr_fechas();
		if (empty($datos_fechas)) {
			$this->set_error('800CAL_periodo_faltante');
		}

		$datos_ubicaciones = $this->get_datos_per_inscr_ubicacion();
		if (empty($datos_ubicaciones)) {
			$this->set_error('800_falta_ubicacion');
		}

		$datos_modalidades = $this->get_datos_per_inscr_modalidad();
		if (empty($datos_modalidades)) {
			$this->set_error('800_falta_modalidad');
		}

		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

}