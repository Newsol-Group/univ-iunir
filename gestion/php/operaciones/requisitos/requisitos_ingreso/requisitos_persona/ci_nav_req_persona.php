<?php
class ci_nav_req_persona extends ci_nav_sin_listado_inicial
{
	// Componente visual de información.
	protected $ev_info;
	
	// Parámetros de navegación
	protected $s__operacion_a_volver;
	protected $s__celda_a_restaurar;
	protected $s__persona;
	protected $s__propuesta_aspira;
	
	// Navegación a operaciones de inscripción
	protected $s__navegar;
	protected $s__vinculo;

	function ini__operacion()
	{
		$this->s__persona = toba::memoria()->get_parametro('persona');
		if (isset($this->s__persona)) {
			$this->s__operacion_a_volver = toba::memoria()->get_parametro('operacion_a_volver');
			$this->s__celda_a_restaurar = toba::memoria()->get_parametro('celda_a_restaurar');
			$this->s__propuesta_aspira = toba::memoria()->get_parametro('propuesta_aspira');
			$this->evt__cuadro__seleccion(array('persona' => $this->s__persona));
		}
	}
	
	//--------------------------------------------------------------------------
	//---- filtro --------------------------------------------------------------
	//--------------------------------------------------------------------------
	
	function conf__filtro(toba_ei_filtro $filtro)
	{
		if (guarani::parametros()->get_valor('usa_repositorio_digital') != comunes::si) {
			$filtro->eliminar_columnas(array('con_doc_sin_validar'));
		} else {
			$filtro->columna('con_doc_sin_validar')->set_condicion_fija('es_igual_a');
		}
		parent::conf__filtro($filtro);
	}
	
	function evt__filtro__filtrar($datos)
	{
		if (!empty($datos['con_doc_sin_validar'])) {
			$filtro_doc_sin_validar = "EXISTS (
				SELECT 	1
				FROM 	sga_requisitos_digitales_draft
				WHERE 	sga_requisitos_digitales_draft.persona = vw_personas.persona
			)";
		} else {
			$filtro_doc_sin_validar = '';
		}
		$this->dep('filtro')->columna('con_doc_sin_validar')->resetear_estado();
		
		parent::evt__filtro__filtrar($datos);
		
		if ($filtro_doc_sin_validar) {
			if (!empty($this->s__filtro)) {
				$this->s__filtro .= ' AND ';
			}
			$this->s__filtro .= $filtro_doc_sin_validar;
		}
	}

	//--------------------------------------------------------------------------
	//---- ev_info -------------------------------------------------------------
	//--------------------------------------------------------------------------

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('documento_desc', 'apellido_nombres', 'nombre_requisito'));

			$this->ev_info->set_columnas_fila(array('documento_desc'), 1);
			$this->ev_info->set_columnas_fila(array('apellido_nombres'), 2);
			$this->ev_info->set_columnas_fila(array('nombre_requisito'), 3);

			$this->ev_info->set_max_columnas_por_fila(1);

			$this->ev_info->set_etiqueta('apellido_nombres', 'Apellido y Nombres');
			$this->ev_info->set_etiqueta('documento_desc', 'Nro. de Documento Principal');
			$this->ev_info->set_etiqueta('nombre_requisito', 'Requisito');

			$this->ev_info->set_td_etiqueta_width('190px');
		}
		return $this->ev_info;
	}

	function get_datos_ev_info()
	{
		$datos = array();

		$datos_persona = guarani::co('co_personas')->get_datos_persona($this->cn()->entidad()->get_persona_seleccionada());
		$datos['documento_desc'] = $datos_persona['documento'];
		$datos['apellido_nombres'] = $datos_persona['nombre_completo'];

		$requisito_seleccionado = $this->dep('editor')->dep('editor_documentales')->get_requisito_seleccionado();
		if (isset($requisito_seleccionado)) {
			$datos_req = current(guarani::co('co_requisitos')->get_datos_requisito($requisito_seleccionado));
			$datos['nombre_requisito'] = $datos_req['nombre_requisito'];
		}

		return $datos;
	}
	
	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__guardar()
	{
		try {
			// Sincronización
			$this->cn()->guardar();
			// Redirección a pantalla de inicio u operación originaria
			if (empty($this->s__operacion_a_volver)) {
				if ($this->volver_al_guardar) {
					$this->cn()->resetear();
					$this->set_pantalla('seleccion');
				}
				toba::notificacion()->agregar($this->get_mensaje('guardar_ok'), 'info');
			} else {
				$this->evt__volver_navegacion();
			}
		} catch (guarani_error $e)	{
			throw $e;
		} 
	}
	
	function evt__volver_navegacion()
	{
		$params = array();
		$params['celda_a_restaurar'] = toba::memoria()->get_celda_memoria_actual_id();
		$params['persona'] = $this->s__persona;
		$params['propuesta_aspira'] = $this->s__propuesta_aspira;
		$this->s__vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), $this->s__operacion_a_volver, $params);
		$this->s__navegar = true;
	}

	//--------------------------------------------------------------------------
	//---- Configuraciones -----------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		$datos = $this->get_datos_ev_info();
		$this->ev_info()->set_datos($datos);
		
		if (!isset($this->s__operacion_a_volver)) {
			$pantalla->eliminar_evento('volver_navegacion');
		} else {
			$pantalla->eliminar_evento('cancelar');
			$mensaje = 'Se guardarán los datos y se retornará a la operación de Inscripción. ¿Desea continuar?';
			$pantalla->evento('guardar')->set_msg_confirmacion($mensaje);
		}
		$this->hay_cambios = $this->cn()->entidad()->hay_cambios();
	}

	function extender_objeto_js()
	{
		$msj_confirmacion = guarani_mensajes::get('800_abandono_edicion');
		$msj_sin_cambios = guarani_mensajes::get('800_no_hubo_cambios');

		if ($this->hay_cambios || ($this->cn()->entidad_cargada() && ($this->cn()->entidad()->hay_requisitos_a_exceptuar()))) {
			$confirmar = 'var confirmar = true;';
		} else {
			$confirmar = 'var confirmar = hay_algun_cambio || toba.hay_cambios();';
		}

		echo $this->js_evt_cancelar();
		echo $this->js_evt_guardar();
		echo "
			var hay_algun_cambio = false;
                
			function confirmar_cambios(proyecto, operacion, url, es_popup) {
				var resp = true;
				if (! es_popup || typeof(es_popup) == 'undefined') {
					$confirmar
					if (confirmar) {
						resp = confirm('$msj_confirmacion');
					}
				}
				hay_algun_cambio = false;
				return resp;
			}
			
			function confirmar_no_cambios(proyecto, operacion, url, es_popup) {
				var resp = true;
				if (! es_popup || typeof(es_popup) == 'undefined') {
					$confirmar
					if (! confirmar) {
						alert('$msj_sin_cambios');
						resp = false;
					}
				}
				hay_algun_cambio = false;
				return resp;
			}
			
            toba.set_callback_menu(confirmar_cambios);
		";
		
		// Si es necesario, se navega a operación de inscripción
		if ($this->s__navegar) {
			unset($this->s__navegar);
			echo "document.location.href='$this->s__vinculo';";
		}
	}

	function js_evt_guardar()
	{
		return "";
	}

}
?>