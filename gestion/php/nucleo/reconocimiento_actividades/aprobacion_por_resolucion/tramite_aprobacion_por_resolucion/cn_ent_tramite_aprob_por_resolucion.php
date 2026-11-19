<?php
/**
 *
 * 
 * @package 	Reconocimiento de Actividades
 * @subpackage 	Aprobación por Resolución
 * 
 */
class cn_ent_tramite_aprob_por_resolucion extends cn_entidad
{
	function cargar_atributos_parametros($propuesta)
	{
		$this->resetear_atributos_parametros();
		$operacion = guarani::get_id_operacion();
		
		$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras(array($propuesta));
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_ra, $ras);
		
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_propuesta, array($propuesta));
	}
	
	function pre_sincronizar()
	{
		$datos = $this->get_datos_generales();
		$tipo_reconocimiento = $datos['tipo_reconocimiento'];

		if ($tipo_reconocimiento == aprobacion_por_resolucion::activ_modulos) {
			$actividades = $this->get_datos_actividades_reconocidas();
			if (empty($actividades)) {
				$mensaje = guarani_mensajes::get('800_no_seleccion', array('una actividad'));
				throw guarani::error($mensaje);
			}
		} else {
			if (empty($datos['total_creditos']) || $datos['total_creditos'] == 0) {
				$mensaje = guarani_mensajes::get('800EQU_creditos_globales_invalidos', array(guarani::co('co_alumnos')->get_alumno_nombre($datos['alumno'])));
				throw guarani::error($mensaje);
			}
		}
	}

	//--------------------------------------------------------------------------
	//---- Trámites de Aprobación por Resolución
	//--------------------------------------------------------------------------

	function get_datos_generales()
	{
		return $this->tabla('reconocimiento')->get();
	}

	function set_datos_generales($datos)
	{
		// Si el campo alumno es un arreglo es porque se seleccionó uno nuevo con el popup (ya sea en alta o edición).
		// En este caso deben modificarse los datos id de alumno y plan versión en la tabla sga_reconocimiento.
		if (isset($datos['alumno']) && is_array($datos['alumno'])) {
			// El popup de alumnos retorna varias claves con diferentes datos del mismo. 
			// En este caso solo se utiliza el id de alumno (offset 0) y de su plan_version (offset 3).
			$datos['plan_version'] = $datos['alumno'][3];
			$datos['alumno'] = $datos['alumno'][0];
			
			$datos_alumno = toba::consulta_php('co_alumnos')->get_datos_basicos_alumno($datos['alumno']);
			$this->cargar_atributos_parametros($datos_alumno['propuesta']);	
		}
		$this->tabla('reconocimiento')->set($datos);
	}

	function get_columna_reconocimiento($columna)
	{
		return $this->tabla('reconocimiento')->get_columna($columna);
	}

	//--------------------------------------------------------------------------
	//---- Documentación respaldatoria de las Actividades Reconocidas
	//--------------------------------------------------------------------------

	function get_datos_documentos()
	{
		return $this->tabla('documentos')->get_filas();
	}

	function set_datos_documento($datos)
	{
		if ($this->hay_documento_seleccionado()) {
			$this->validar_datos_documento($datos);
			$this->tabla('documentos')->set($datos);
		}
	}

	function seleccionar_documento($id_fila_documento)
	{
		$this->tabla('documentos')->set_cursor($id_fila_documento);
	}

	function deseleccionar_documento()
	{
		// Se resetean los cursores tanto de la tabla de documentos como de la de documentos digitales (que puede o no tener valor).
		$this->tabla('documentos')->resetear_cursor();
		$this->tabla('documentos_digitales')->resetear_cursor();
	}

	function get_datos_documento_seleccionado()
	{
		if ($this->hay_documento_seleccionado()) {
			return $this->tabla('documentos')->get();
		}
	}

	function hay_documento_seleccionado()
	{
		return $this->tabla('documentos')->hay_cursor();
	}

	function eliminar_documento_seleccionado()
	{
		if ($this->hay_documento_seleccionado()) {
			$id_fila = $this->tabla('documentos')->get_cursor();
			$this->tabla('documentos')->eliminar_fila($id_fila);
		}
	}

	function nuevo_documento($datos)
	{
		$this->validar_datos_documento($datos);
		$id = $this->tabla('documentos')->nueva_fila($datos);
		$this->seleccionar_documento($id);
	}

	/**
	 * Obtiene la sumatoria de todos los creditos asociados a la documentación respaldatoria de las actividades
	 * realizadas. 
	 */
	function get_sumatoria_creditos_activ_realizadas_docs()
	{
		$actividades_docs = $this->get_datos_documentos();
		$suma_creditos = 0;
		foreach ($actividades_docs as $doc) {
			if (isset($doc['creditos'])) {
				$suma_creditos = $suma_creditos + $doc['creditos'];
			}
		}
		return $suma_creditos;
	}

	function validar_datos_documento($datos)
	{
		if (!isset($datos['nombre'])) {
			$this->set_error('dato_faltante', array('Nombre'));
		}
		if (!isset($datos['carga_horaria'])) {
			$this->set_error('dato_faltante', array('Carga Horaria'));
		}
		if (!isset($datos['tipo_actividad'])) {
			$this->set_error('dato_faltante', array('Tipo Actividad'));
		} else {
			switch ($datos['tipo_actividad']) {
				case aprobacion_por_resolucion::ayudantia:
					if (!isset($datos['comision'])) {
						$this->set_error('dato_faltante', array('Comisión'));
					} else {
						if (!isset($datos['docente_responsable'])) {
							$this->set_error('dato_faltante', array('Profesor Responsable'));
						}
					}
					break;
				case aprobacion_por_resolucion::curso:
					if (!isset($datos['responsable'])) {
						$this->set_error('dato_faltante', array('Responsable'));
					}
					if (!isset($datos['institucion'])) {
						$this->set_error('dato_faltante', array('Institución'));
					}
					break;
				case aprobacion_por_resolucion::investigacion:
					if (!isset($datos['responsable'])) {
						$this->set_error('dato_faltante', array('Responsable'));
					}
					break;
				case aprobacion_por_resolucion::pasantia:
					if (!isset($datos['responsable'])) {
						$this->set_error('dato_faltante', array('Responsable'));
					}
					if (!isset($datos['institucion'])) {
						$this->set_error('dato_faltante', array('Institución'));
					}
					break;
				case aprobacion_por_resolucion::seminario:
					if (!isset($datos['responsable'])) {
						$this->set_error('dato_faltante', array('Responsable'));
					}
					if (!isset($datos['institucion'])) {
						$this->set_error('dato_faltante', array('Institución'));
					}
					break;
				case aprobacion_por_resolucion::trabajo_profesional:
					if (!isset($datos['responsable'])) {
						$this->set_error('dato_faltante', array('Responsable'));
					}
					if (!isset($datos['institucion'])) {
						$this->set_error('dato_faltante', array('Institución'));
					}
					break;
				case aprobacion_por_resolucion::tutoria:
					if (!isset($datos['comision'])) {
						$this->set_error('dato_faltante', array('Comisión'));
					} else {
						if (!isset($datos['docente_responsable'])) {
							$this->set_error('dato_faltante', array('Profesor Responsable'));
						}
					}
					break;
			}
		}
		if ($this->hay_errores) {
			throw guarani::error($this->errores);
		}
	}

	//--------------------------------------------------------------------------
	//---- documentos_digital --------------------------------------------------
	//--------------------------------------------------------------------------

	function set_datos_doc_digital($datos)
	{
		/**
		 * En caso de edición se trabaja con la fila correspondiente al documento padre seleccionado (se posicionó 
		 * el cursor en el get). En caso de alta al realizar el set se crea la nueva fila.
		 */
		if (isset($datos['nombre_archivo'])) {
			$handle = fopen($datos['nombre_archivo']['tmp_name'], 'rb');

			$archivo = array();
			$archivo['nombre_archivo'] = $datos['nombre_archivo']['name'];
			$archivo['fecha_upload'] = 'now()';

			$this->tabla('documentos_digitales')->set($archivo);
			$this->tabla('documentos_digitales')->set_blob('archivo', $handle);
		}
	}

	function get_datos_doc_digital()
	{
		/**
		 * La tabla de documentos digitales tendrá varias filas, pero al estar el cursor posicionado en el 
		 * documento padre, sólo recuperará el archivo asociado a este último si es que existe.
		 * Se setea el cursor en este documento digital para poder trabajar con esa fila.
		 */
		$id_filas = $this->tabla('documentos_digitales')->get_id_filas(true);
		if (!empty($id_filas)) {
			$this->tabla('documentos_digitales')->set_cursor(current($id_filas));
		}

		$datos_doc = $this->tabla('documentos_digitales')->get();
		if (!empty($datos_doc)) {
			$datos_doc['blob'] = $this->tabla('documentos_digitales')->get_blob('archivo');
		}
		return $datos_doc;
	}

	function eliminar_datos_doc_digital()
	{
		// La tabla tendrá seleccionada la fila correpondiente al documento padre que se está editando.
		$id_fila = $this->tabla('documentos_digitales')->get_cursor();
		$this->tabla('documentos_digitales')->eliminar_fila($id_fila);
	}

	//--------------------------------------------------------------------------
	//---- Actividades reconocidas por el Trámite de Aprobación por Resolución
	//--------------------------------------------------------------------------

	function get_datos_actividades_reconocidas()
	{
		$actividades = $this->tabla('actividades')->get_filas();
		$plan_version = $this->get_columna_reconocimiento('plan_version');
		if (! empty($plan_version)) {
			$creditos = rs_convertir_asociativo_matriz(toba::consulta_php('co_elementos')->get_creditos_min_max_elementos_plan_version($plan_version), array('elemento'));
			foreach ($actividades as $clave => $valor) {
				$actividades[$clave]['creditos_max'] = $creditos[$valor['elemento']]['creditos_max'];
				$actividades[$clave]['creditos_min'] = $creditos[$valor['elemento']]['creditos_min'];
			}
		}
		return $actividades;
	}

	/**
	 * Recupera el valor máximo de créditos para el id de actividad dado. 
	 * Si no están seteados estos valores se retorna -1.
	 */
	function get_creditos_minimos_maximos_actividad($elemento, $plan_version)
	{
		$cred = toba::consulta_php('co_elementos')->get_creditos_min_max_elemento_plan_version($elemento, $plan_version);
		if (!isset($cred['creditos_max'])) {
			$cred['creditos_max'] = -1;
		}
		if (!isset($cred['creditos_min'])) {
			$cred['creditos_min'] = 0;
		}
		return $cred;
	}

	function set_datos_actividades_reconocidas($datos)
	{
		$this->validar_datos_actividades_reconocidas($datos);
		$this->tabla('actividades')->procesar_filas($datos);
	}

	/**
	 * 
	 * @param $activ
	 * @param $plan_version Se agrega una nueva actividad como reconocida al trámite de aprobación por resolución actual
	 */
	function agregar_actividad_reconocida($activ, $plan_version)
	{
		// Se asigna como valor por defecto en los créditos de la actividad reconocida los máximos posibles.
		$cred = $this->get_creditos_minimos_maximos_actividad($activ['elemento'], $plan_version);
		$cred_max = $cred['creditos_max'];
		$cred_min = $cred['creditos_min'];
		if ($cred_max != -1) {
			$activ['creditos'] = $cred_max;
			// Se setea también un ef oculto utilizado para realizar la validación de los créditos al momento de grabar.
			$activ['creditos_max'] = $cred_max;
		}
		if ($cred_min != 0) {
			// Se setea un ef oculto utilizado para realizar la validación de los créditos al momento de grabar.
			$activ['creditos_min'] = $cred_min;
		}
		$this->tabla('actividades')->nueva_fila($activ);
	}

	function eliminar_actividad_reconocida($activ_seleccionada)
	{
		$this->tabla('actividades')->eliminar_fila($activ_seleccionada);
	}

	function eliminar_actividades_reconocidas()
	{
		$this->tabla('actividades')->eliminar_filas();
	}

	function validar_datos_actividades_reconocidas($datos_actividades)
	{
		$this->validar_reconocimiento_total_actividades_reconocidas($datos_actividades);
		$this->validar_notas_actividades_reconocidas($datos_actividades);
		$this->validar_creditos_actividades_reconocidas($datos_actividades);
		if ($this->hay_errores) {
			throw guarani::error($this->errores);
		}
	}

	/**
	 * Valida que no existan simultáneamente reconocimientos parciales y totales para la misma actividad.
	 *
	 * @param array $datos_actividades Conjunto de actividades a validar.
	 */
	function validar_reconocimiento_total_actividades_reconocidas($datos_actividades)
	{
		$datos_generales = $this->get_datos_generales();
		foreach ($datos_actividades as $actividad) {
			if ($actividad['elemento_tipo'] == g3entidad::tipo_modulo && $actividad['reconocimiento_total'] == comunes::si) {
				$reconocimiento = toba::consulta_php('co_aprobacion_por_resolucion')->get_reconocimiento_alumno_modulo($datos_generales['alumno'], $actividad['elemento']);
				// Cuando el módulo tiene algún reconocimiento parcial en otro trámite ya no puede reconocerse en forma total.
				if (!empty($reconocimiento)) {
					$this->set_error('800EQU_reconocimiento_total_invalido', array($actividad['actividad_descr']));
				}
			}
		}
	}

	/**
	 * Valida que la nota ingresada para cada actividad pertenezca a la escala y corresponda al resultado "Aprobado".
	 *
	 * @param array $datos_actividades Conjunto de actividades a validar.
	 */
	function validar_notas_actividades_reconocidas($datos_actividades)
	{
		foreach ($datos_actividades as $actividad) {
			if (isset($actividad['escala_nota']) && isset($actividad['nota'])) {
				$nota = $actividad['nota'];
				$escala = $actividad['escala_nota'];
				// Valida que la nota se corresponda a la escala seleccionada.
				$validador = escalas_notas::validador_escalas_notas();
				if (!$validador->es_nota_valida($nota, $escala)) {
					$this->set_error('800EQU_nota_invalida_escala', array($actividad['actividad_descr']));
				}
				// Valida que el resultado asociado a la nota y escalas seleccionadas sea Aprobado.
				$resultado = $validador->get_resultado_escala_nota($escala, $nota);
				if (!empty($resultado) && $resultado != escalas_notas::resultado_aprobado) {
					$this->set_error('800EQU_nota_invalida_resultado', array($actividad['actividad_descr']));
				}
			}
		}
	}

	/**
	 * Valida que la cantidad de créditos ingresada para cada actividad esté entre el mínimo y el máximo definido en el plan.
	 *
	 * @param array $datos_actividades Conjunto de actividades a validar.
	 */
	function validar_creditos_actividades_reconocidas($datos_actividades)
	{
		foreach ($datos_actividades as $actividad) {
			if (isset($actividad['creditos']) && isset($actividad['creditos_max'])) {
				// Se verifica que la cantidad de créditos asignada a la actividad no sea mayor a la cantidad máxima para la misma.
				$cred = $actividad['creditos'];
				$cred_max = $actividad['creditos_max'];
				$actividad_descr = $actividad['actividad_descr'];
				if ($cred > $cred_max) {
					$this->set_error('800EQU_actividad_creditos_max_invalidos', array($actividad_descr, $cred_max));
				}
			}
			if (isset($actividad['creditos']) && isset($actividad['creditos_min'])) {
				// Se verifica que la cantidad de créditos asignada a la actividad no sea menor a la cantidad mínima para la misma.
				$cred = $actividad['creditos'];
				$cred_min = $actividad['creditos_min'];
				$actividad_descr = $actividad['actividad_descr'];
				if ($cred > $cred_max) {
					$this->set_error('800EQU_actividad_creditos_min_invalidos', array($actividad_descr, $cred_min));
				}
			}
		}
	}

}
?>
