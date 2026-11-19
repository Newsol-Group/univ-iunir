<?php
/**
 *
 *
 * @package 	Constancias y Certificados
 * @subpackage 	Solicitar Constancias
 * 
 */
class ci_edi_solicitar_constancias_nsg extends ci_edi_solicitar_constancias
{
	protected $datos_form_temp;
	protected $s__objeto = '';
	
	protected $s__params = array();
	
	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Función que permite obtener el id del alumno seleccionado para el alta o edición de una solicitud de 
	 * constancia. Puede utilizarse desde el ci externo agregado por una constancia particular.
	 *
	 */
	function get_alumno_seleccionado()
	{
		return $this->controlador()->get_preseleccion();	
	}
	
	function resetear_variables()
	{
		if ($this->existe_dependencia($this->s__objeto)) {
			$this->dependencia($this->s__objeto)->limpiar_datos();
		}
		$this->s__objeto='';
		$this->s__params = array();
		unset($this->datos_form_temp);
	}
	
	function get_params_a_guardar()
	{
		$params = array();
		if ($this->existe_dependencia($this->s__objeto)) {
			$this->dependencia($this->s__objeto)->validar_datos();
			$params = $this->dependencia($this->s__objeto)->get_datos();
		}
		return $params;
	}
	
	/**
	 * A partir de la matriz compuesta por los datos del DT de parámetros de la solicitud, construye el arreglo de datos
	 * iniciales que se pasa al ci de carga dinámica, y debe tener el formato de arreglo asociativo array(campo=>valor).
	 *
	 */
	function array_campos_params($datos_params)
	{
		$campos = array();
		foreach ($datos_params as $param) {
			$campos[$param['campo']] = $param['valor']; 
		}
		return $campos;
	}

	function get_params_form_dinamico()
	{
		return $this->s__params;
	}
		
	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------
		
	function conf()
	{
		// Si se está editando una solicitud la constancia asociada se obtiene de la BD.
		if ($this->cn()->entidad_es_nueva()) {
			$constancia = $this->dep('form_solicitud')->ef('constancia')->get_estado();
		} else {
			$datos = $this->cn()->entidad()->get_datos_solicitud();
			$constancia = $datos['constancia'];
		}
	
		if (!is_null($constancia)) {
		
			// Newsol 04-2022 - Se agregó para utilizar la cantidad de copias como cantidad de programas en el caso de la Solicitud de Programas USI.
			$etiqueta = $this->dep('form_solicitud')->ef('cant_copias_solicitadas')->get_etiqueta();

			if ($constancia == '112' || $constancia == '118')
				$this->dep('form_solicitud')->ef('cant_copias_solicitadas')->set_etiqueta('Cantidad de Programas');
			else
				$this->dep('form_solicitud')->ef('cant_copias_solicitadas')->set_etiqueta('Cantidad de Copias');

			
			// Se agrega como dependencia el ci asociado al tipo de constancia seleccionado, si la misma tiene estado.
			// Debe chequearse primero que la constancia tenga asociado algún objeto que defina sus parámetros de solicitud.
			$datos_constancia = toba::consulta_php('co_constancias')->get_datos_constancia($constancia);
			
			if (!$this->controlador()->get_es_alumno_autogestion() || (!is_null($datos_constancia) && !toba::consulta_php('co_constancias')->get_se_puede_descargar_constancia($datos_constancia['constancia']))){
				$this->controlador()->pantalla('edicion')->eliminar_evento('imprimir');
			}else{
				$this->controlador()->pantalla('edicion')->eliminar_evento('guardar');
			}
			
			/**
			 * Si ya existía un objeto anterior agregado, y se modifica la constancia agregando un objeto nuevo, o sin
			 * objeto asociado, se resetean los valores del objeto correspondiente a la constancia anterior.
			 */
			if (!empty($this->s__objeto) && $this->s__objeto != $datos_constancia['objeto']) {
				if ($this->existe_dependencia($this->s__objeto)) {
					$this->dependencia($this->s__objeto)->limpiar_datos();
				}
				$this->s__objeto = '';
				$this->s__params = array();
			}
				
			if (isset($datos_constancia['objeto'])) {
				$this->s__objeto = $datos_constancia['objeto'];
				// Se agrega el ci como dependencia del ci actual, y luego se asocia a la pantalla actual.
				$this->pantalla()->agregar_dep($this->s__objeto);
				
				/**
				 * Si existe un ci asociado para los parámetros de la solicitud, en caso de edición, se pasan los valores
				 * de los mismos (cargados en el DT hijo de la solicitud), para que el ci externo pueda mostrar los campos
				 * correspondientes de acuerdo a dichos valores y campos almacenados en sga_constancias_solicitud_param.
				 */
				$datos_params = $this->cn()->entidad()->get_datos_params();
				if (!empty($datos_params)) {
					$datos_solicitud = $this->array_campos_params($datos_params);
					$this->s__params = $datos_solicitud;
				}
			}
		} else {
			if ($this->controlador()->get_es_alumno_autogestion()){
				$this->controlador()->pantalla('edicion')->eliminar_evento('imprimir');
				$this->controlador()->pantalla('edicion')->eliminar_evento('guardar');
			}
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- Pantalla Inicial -------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		$datos_alumno = $this->controlador()->get_datos_alumno();
		$this->controlador()->ev_info()->set_datos($datos_alumno);
	}
	
	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__form_solicitud(toba_ei_formulario $form)
	{ 
		/**
		 * Los campos obligatorios no utilizan la obligatoriedad de toba porque se generan errores con el
		 * agregado dinámico del ci de la constancia. Por este motivo se setean sus etiquetas con el estilo
		 * de obligatorias, pero el hecho de que se hayan completado se verifica sólo antes de sincronizar.
		 */
		$form->ef('presentar_a')->set_estilo_etiqueta('ei-form-etiq-oblig');
		$datos = $this->cn()->entidad()->get_datos_solicitud();
		
		//Si el alumno es de autogestión y se permite descargar las constancias, no se pide la cantidad de copias
		if ((!is_null($datos) && !toba::consulta_php('co_constancias')->get_se_puede_descargar_constancia($datos['constancia']))){			
			$etiqueta = $form->ef('cant_copias_solicitadas')->get_etiqueta();

			if (!is_null($datos)) {
				$datos_constancia = toba::consulta_php('co_constancias')->get_datos_constancia($datos['constancia']);
				// Se indica en el label la cantidad máxima de copias que pueden solicitarse según la constancia.
				$etiqueta .= ' (Máx. ' . $datos_constancia['max_cant_copias'] . ')';
			}
			$form->ef('cant_copias_solicitadas')->set_etiqueta($etiqueta);
		} else {
			$form->desactivar_efs(array('cant_copias_solicitadas'));
		}
		
		$form->set_datos_defecto(array('presentar_a'=>'Quien corresponda'));
		
		if (isset($this->datos_form_temp)) {
			$this->controlador->set_hubo_cambios();
			$datos = $this->datos_form_temp;
		} else {
			$datos = $this->cn()->entidad()->get_datos_solicitud();
		}
		$form->set_datos($datos);
		// Si se está editando una solicitud la constancia asociada no puede modificarse.
		if (!$this->cn()->entidad_es_nueva()) {
			$form->ef('constancia')->set_solo_lectura();
		} 
	}

	function evt__form_solicitud__modificacion($datos)
	{
		try {
			if ($this->cn()->entidad_es_nueva()) {
				// Si se está agregando una entidad nueva se debe setear en la Solicitud el alumno de la preselección.
				$datos['alumno'] = $this->get_alumno_seleccionado();
			}
			
			//Si el alumno es de autogestión y se permite descargar las constancias, no se pide la cantidad de copias y el estado de la solicitud es ONLINE
			if (toba::consulta_php('co_constancias')->get_se_puede_descargar_constancia($datos['constancia'])){
				$datos['cant_copias_obligatorio'] = false;				
				$datos['codigo_verificacion'] = constancias::generar_codigo_verificacion();
			}else{
				$datos['cant_copias_obligatorio'] = true;                
			}
			
			if ($this->controlador()->get_es_alumno_autogestion()){
				$datos['interfaz'] = 2;
                $datos['estado'] = constancias::estado_solicitud_online;
			}
			$this->cn()->entidad()->set_copias_obligatorio($datos['cant_copias_obligatorio']);
			$this->cn()->entidad()->set_datos_solicitud($datos);
		} catch (guarani_error $e) {
			$this->datos_form_temp = $datos;
			throw $e;
		}
	}
}
?>