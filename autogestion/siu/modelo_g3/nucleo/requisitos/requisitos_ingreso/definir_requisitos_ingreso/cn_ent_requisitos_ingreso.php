<?php
/**
 *
 * Representa un la definición de un Requisito de Ingreso
 *
 * 
 * @package 	Requisitos
 * @subpackage 	Requisitos de Ingreso
 * 
 */
class cn_ent_requisitos_ingreso extends cn_entidad
{
	protected $hay_que_generar_aplanado = true;

	function hay_que_generar_aplanado()
	{
		return $this->hay_que_generar_aplanado;
	}

	function agregar_requisito($requisito)
	{	
		$this->set_datos(array('requisito' => $requisito['requisito']));
	}

	function set_datos($datos, $tabla = null, $validar=true)
	{	
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
		if ($this->controlador()->modo_egreso()) {
			$datos['requisito_para'] = requisito::requisito_propuesta_para_egreso;
			// Las columnas 'restrictivo' y 'doc_digital_obligatorio_preinscripcion' (que no se editan desde la interfaz para egreso) toman los valores por defecto de la BD.
			$datos['visible_en_preinscripcion'] = comunes::no;
		} else {
			$datos['requisito_para'] = requisito::requisito_propuesta_para_ingreso;
		}
		$this->tabla('req_ingreso')->set($datos);
	}
	
	function get_datos($dato=null)
	{
		$datos = $this->tabla('req_ingreso')->get();
		if ($dato) return $datos[$dato];
		return $datos;
	}

	function get_tipo()
	{
		return $this->get_datos('tipo');
	}
	
	function get_requisito_tipo()
	{
		return $this->get_datos('requisito_tipo');
	}
	
	function es_requisito_documental()
	{
		return ($this->get_requisito_tipo() == requisito::tipo_documentales);
	}

	function get_datos_subtipos()
	{
		return $this->tabla('req_ingreso_subtipo')->get_filas();
	}
	
	function get_datos_entidades()
	{
		return $this->tabla('req_ingreso_entidad')->get_filas();
	}

	function es_tipo_subtipos()
	{
		return ($this->get_tipo() == g3entidad::nivel_aplicacion_tipo_subtipo);
	}
	
	function es_tipo_entidades()
	{
		return ($this->get_tipo() == g3entidad::nivel_aplicacion_tipo_entidad);
	}

	function es_subtipo_requerido($subtipo)
	{
		return $this->tabla('req_ingreso_subtipo')->existe_fila_condicion(array(array('entidad_subtipo','==',$subtipo)));
	}

	function es_entidad_requerida($entidad)
	{
		return $this->tabla('req_ingreso_entidad')->existe_fila_condicion(array(array('entidad','==',$entidad)));
	}
	
	function es_para_institucion_gestionante() 
	{  
		if (! $this->tabla('req_ingreso_entidad')->esta_cargada() &&  ! $this->tabla('req_ingreso_entidad')->hay_cambios()) return false;
		$entidades = $this->get_datos_entidades();
		if (! isset($entidades[0]['entidad'])) return false;
		return ($entidades[0]['entidad'] == institucion::get_entidad_gestionante());
	}
	
	function set_datos_subtipos($datos)
	{
		$this->marcar_eliminacion_entidades();
		if ($this->tabla('req_ingreso_subtipo')->esta_cargada() || $this->tabla('req_ingreso_subtipo')->hay_cambios()) {
			$subtipos = $this->analizar_datos_subtipos($datos);
			$this->tabla('req_ingreso_subtipo')->procesar_filas($subtipos);
		} else {
			foreach ($datos as $subtipo) {
				if ($subtipo['requerido'] == '1') {
					$cursor = $this->tabla('req_ingreso_subtipo')->nueva_fila();
					$this->tabla('req_ingreso_subtipo')->set_cursor($cursor);
					$this->tabla('req_ingreso_subtipo')->set(array('entidad_subtipo' => $subtipo['entidad_subtipo']));
				}
			}
		}	
	}
	
	function marcar_eliminacion_subtipos()
	{
		if (! $this->tabla('req_ingreso_subtipo')->esta_cargada() && ! $this->tabla('req_ingreso_subtipo')->hay_cambios()) return;
		$subtipos_dt = $this->get_datos_subtipos();
		$datos = array();
		foreach ($subtipos_dt as $subtipo_dt) {
			$datos[] = array('entidad_subtipo' => $subtipo_dt['entidad_subtipo'],'apex_ei_analisis_fila'=>'B','x_dbr_clave' => $subtipo_dt['x_dbr_clave']);
		}
		$this->tabla('req_ingreso_subtipo')->procesar_filas($datos);
	}

	function marcar_eliminacion_entidades()
	{
		if (! $this->tabla('req_ingreso_entidad')->esta_cargada() && ! $this->tabla('req_ingreso_entidad')->hay_cambios()) return;
		$entidades_dt = $this->get_datos_entidades();
		$datos = array();
		foreach ($entidades_dt as $entidad_dt) {
			$datos[] = array('entidad' => $entidad_dt['entidad'],'apex_ei_analisis_fila'=>'B','x_dbr_clave' => $entidad_dt['x_dbr_clave']);
		}
		$this->tabla('req_ingreso_entidad')->procesar_filas($datos);
	}

	function set_datos_entidades($datos)
	{
		$this->marcar_eliminacion_subtipos();
		//Esto es porque cuando cambia de entidad_tipo no recibe 
		//las entidades del tipo anterior y sino éstas no se eliminan
		$this->marcar_eliminacion_entidades();
		if ($this->tabla('req_ingreso_entidad')->esta_cargada() || $this->tabla('req_ingreso_entidad')->hay_cambios()) {
			$entidades = $this->analizar_datos_entidades($datos);
			$this->tabla('req_ingreso_entidad')->procesar_filas($entidades);
		} else {
			foreach ($datos as $entidad) {
				if ($entidad['requerido'] == '1') {
					$cursor = $this->tabla('req_ingreso_entidad')->nueva_fila();
					$this->tabla('req_ingreso_entidad')->set_cursor($cursor);
					$this->tabla('req_ingreso_entidad')->set(array('entidad' => $entidad['entidad']));
				}
			}
		}	
	}

	protected function analizar_datos_subtipos($datos_form)
	{
		$subtipos_dt = $this->get_datos_subtipos();
		$datos_dt[] = array();
		$cursores[] = array();
		foreach ($subtipos_dt as $subtipo_dt) {
			if (isset($subtipo_dt['entidad_subtipo'])) {
				$datos_dt[] = $subtipo_dt['entidad_subtipo'];
				$cursores[] = $subtipo_dt['x_dbr_clave'];
			}	
		}
		$datos = array();
		foreach ($datos_form as $subtipo) {
			if ($subtipo['requerido'] && !in_array($subtipo['entidad_subtipo'], $datos_dt)) {
				$datos[] = array('entidad_subtipo' => $subtipo['entidad_subtipo'],'apex_ei_analisis_fila'=>'A');
			} else {
				if ( ! $subtipo['requerido'] && in_array($subtipo['entidad_subtipo'], $datos_dt)) {
					$key = array_search($subtipo['entidad_subtipo'], $datos_dt);
					$datos[] = array('entidad_subtipo' => $subtipo['entidad_subtipo'],'apex_ei_analisis_fila'=>'B','x_dbr_clave' => $cursores[$key]);
				}
			}
		}
		return $datos;
	}

	protected function analizar_datos_entidades($datos_form)
	{
		$entidades_dt = $this->get_datos_entidades();
		$datos_dt[] = array();
		$cursores[] = array();
		if ($entidades_dt != array()) {
			foreach ($entidades_dt as $entidad_dt) {
				$datos_dt[] = $entidad_dt['entidad'];
				$cursores[] = $entidad_dt['x_dbr_clave'];
			}
		}	
		$datos = array();
		foreach ($datos_form as $entidad) {
			if ($entidad['requerido'] && !in_array($entidad['entidad'], $datos_dt)) {
				$datos[] = array('entidad' => $entidad['entidad'],'apex_ei_analisis_fila'=>'A');
			} else {
				if ($entidad['requerido'] == 0 && in_array($entidad['entidad'], $datos_dt)) {
					$key = array_search($entidad['entidad'], $datos_dt);
					$datos[] = array('entidad'=>$entidad['entidad'],'apex_ei_analisis_fila'=>'B','x_dbr_clave'=>$cursores[$key]);
				}
			}
		}
		return $datos;
	}

	function pre_sincronizar() 
	{
		$this->procesar_datos_preinscripcion();
		$this->validar_datos_requisitos();
	}

	function tiene_periodo_asociado($requisito) 
	{
		return $this->co()->tiene_fecha_tope_particular_en_periodo_vigente($requisito);
	}
	
	function tiene_aspirante_asociado($requisito) 
	{
		return $this->co()->tiene_aspirante_asociado_periodo_vigente($requisito);
	}

	function validar_parametros($parametros_actuales)
	{
		$parametros_formales = toba::consulta_php('co_requisitos')->get_requisito_regla_params_def($this->get_datos('requisito'));
		$i = 0;
		foreach ($parametros_formales as $parametro_formal) {
			regla::validar_parametro($parametro_formal, $parametros_actuales[$i]);
			$i++;
		}
	}

	function validar_datos_requisitos() 
	{
		if (! $this->es_para_institucion_gestionante()) {
			if ($this->es_tipo_subtipos()) {
				$this->validar_datos_subtipos();
			} else {	
				$this->validar_datos_entidades();
			}
		}
	}
		
	function validar_datos_subtipos()
	{
		$subtipos = $this->get_datos_subtipos();
		if ($subtipos == array()) {
			$this->set_error('800REQ_sin_configurar');
		}

		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	function validar_datos_entidades()
	{
		$entidades = $this->get_datos_entidades();
		if ($entidades == array()) {
			$this->set_error('800REQ_sin_configurar');
		}

		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}
	
	/**
	 * Configuración parametrizada que determina si se utiliza Repositorio Digital 
	 * Integrado (RDI) para almacenar los archivos asociados a requisitos documentales.
	 * 
	 * @return boolean
	 */
	function usa_repositorio_digital()
	{
		return (guarani::parametros()->get_valor('usa_repositorio_digital') == comunes::si);
	}
	
	function procesar_datos_preinscripcion()
	{
		$datos = $this->get_datos();
		
		// Si no es un requisito documental le pongo 'visible_en_preinscripcion' = 'N'
		if (!$this->es_requisito_documental()) {
			$this->tabla('req_ingreso')->set(['visible_en_preinscripcion' => comunes::no]);
		}
		
		// Si no es un requisito documental, o no usa repositorio digital o 'visible_en_preinscripcion' = 'N' le pongo 'doc_digital_obligatorio_preinscripcion' = 'N'
		if (!($this->es_requisito_documental() && $this->usa_repositorio_digital() && $datos['visible_en_preinscripcion'] == comunes::si)) {
			$this->tabla('req_ingreso')->set(['doc_digital_obligatorio_preinscripcion' => comunes::no]);
		}
	}
}