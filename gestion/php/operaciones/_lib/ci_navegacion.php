<?php 

class ci_navegacion extends guarani_pers_ci
{
	protected $s__filtro;
	protected $s__datos;
	protected $s__seleccion;
	protected $con_listado_inicial=true;
	protected $hay_cambios;
	protected $volver_al_guardar = true;
	protected $indx_msg_eof = '';
	protected $indx_msg_confirmar_eliminar = '';


	function conf()
	{
		$this->indx_msg_eof = '800_eof_cuadro';
		$this->indx_msg_confirmar_eliminar = '800_confirmar_elimin';
	}

	/**
	*	Permite recuperar el mensaje toba actualmente asociado al eof del cuadro.
	*/	
	function get_msg_eof($dep=null)
	{
		$indx_msg = $this->indx_msg_eof;
		if (isset($this->s__filtro) && ! empty($this->s__filtro)) {
			$indx_msg = $this->indx_msg_eof . '_filtrado';
		}
		$etiqueta = $this->cn()->etiquetas($dep)->get_enf('plural');
		return guarani_mensajes::get($indx_msg, array($etiqueta));
	}
	
	/**
	*	Permite recuperar el mensaje toba actualmente asociado a la confirmación de eliminación.
	*/	
	function get_msg_confirmar_eliminar($dep=null)
	{
		$etiqueta = $this->cn()->etiquetas($dep)->get('el');
		return guarani_mensajes::get($this->indx_msg_confirmar_eliminar, array($etiqueta));
	}
	
	function set_sin_listado_inicial()
	{
		$this->con_listado_inicial=false;
		$this->dep('filtro')->set_sin_filtrado_inicial();
		$this->dep('filtro')->set_etiquetas($this->cn()->etiquetas());
	}

	function con_listado_inicial()
	{
		return $this->con_listado_inicial;
	}

	//------ Filtro ----------------------

	function get_filtro_condicion()
	{
		return isset($this->s__filtro) ? $this->s__filtro : null;
	}

	function evt__filtro__filtrar($datos)
	{
		if (isset($datos) && !empty($datos)) {
			$this->s__datos = $datos;
			$this->s__filtro = $this->dep('filtro')->get_sql_where();
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}

	function evt__filtro__cancelar()
	{
		unset($this->s__datos);
		unset($this->s__filtro);
	}

	function conf__filtro(toba_ei_filtro $filtro)
	{
		// Si el perfil de datos del usuario pertenece al dominio "Propuestas" es obligatorio filtrar los conceptos por ubicación.
		if (usuario::tiene_perfil_por_propuesta()) {
			$filtro->set_ubicacion_obligatoria();
		}
		if (isset($this->s__datos)) {
			$filtro->set_datos($this->s__datos);
		}		
	}

	//------ Cuadro ----------------------

	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__seleccion = $seleccion;
		$this->cn()->cargar($seleccion);
		$this->set_pantalla('edicion');
	}
	
	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		/**
 		 *	Si no está seteado el filtro entonces es la carga inicial del cuadro (el usuario no presionó filtrar).
		 *	No se traen datos (no se ejecuta la consulta) y se pone un mensaje en el cuadro que indica al usuario
		 *	que debe filtrar para obtener datos.
		*/
		$this->indx_msj_eof = '800_eof_cuadro';
		if (!isset($this->s__filtro) && !$this->con_listado_inicial()) {
			$this->indx_msg_eof .= '_sinfiltro';
		} else {
			//El usuario presiono filtrar (ya sea con datos o con el filtro vacío).
			$datos = $this->cn()->get_listado($this->get_filtro_condicion());
			$cuadro->set_datos($datos);
		}
		$cuadro->set_eof_mensaje($this->get_msg_eof());
	}

	//------ Pantallas  ----------------------

	/**
	 *
	 * @todo Personalizar el mensaje de confirmación del evento 'eliminar' con la descripción de la entidad en particular.
	 */
	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		if ($this->pantalla('edicion')->existe_evento('eliminar')) {
			if ($this->cn()->entidad_es_nueva()) {
				$pantalla->eliminar_evento('eliminar');
			} else {
				$this->evento('eliminar')->set_msg_confirmacion($this->get_msg_confirmar_eliminar());
			}
		}
		$this->hay_cambios = $this->cn()->entidad()->dep('datos')->hay_cambios();
	}
	
	//------ Eventos  ----------------------

	function evt__agregar()
	{
		unset($this->s__seleccion);
		$this->cn()->nueva();
		$this->set_pantalla('edicion');
	}

	function evt__eliminar()
	{	
		try {
			$this->cn()->eliminar();
			toba::notificacion()->agregar($this->get_mensaje('eliminar_ok'), 'info');
			$this->set_pantalla('seleccion');
		} catch (guarani_error $e)	{
			$e->notificar();
		} catch (toba_error $e) {
			$this->cn()->resetear();
			$this->cn()->cargar($this->s__seleccion);
			toba::notificacion()->agregar($e->getMessage(), 'error');
			toba::logger()->error($e->getMessage());
		}
	}

	function evt__guardar()
	{
		try {
			$this->cn()->guardar();
			if ($this->volver_al_guardar) {
				$this->cn()->resetear();
				$this->set_pantalla('seleccion');
			}
			toba::notificacion()->agregar($this->get_mensaje('guardar_ok'), 'info');
		} catch (guarani_error $e)	{
			throw $e;
		} 
	}

	function evt__cancelar()
	{
		$this->cn()->resetear();
		$this->set_pantalla('seleccion');
	}

	//------ Seteo Comportamientos  ----------------------

	function set_volver_al_guardar()
	{
		$this->volver_al_guardar = true;
	}

	function set_no_volver_al_guardar()
	{
		$this->volver_al_guardar = false;
	}

	function set_hubo_cambios()
	{
		$this->hay_cambios = true;
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$msj_confirmacion = guarani_mensajes::get('800_abandono_edicion');
		$msj_sin_cambios = guarani_mensajes::get('800_no_hubo_cambios');
		if ($this->hay_cambios) {
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
	}
	
	// Se modulariza el evento js cancelar para poder personalizar su comportamiento al extender esta clase
	function js_evt_cancelar() 
	{
		return "
			{$this->objeto_js}.evt__cancelar = function() {
				return confirmar_cambios();
			}
			";
	}

	// Se modulariza el evento js guardar para poder personalizar su comportamiento al extender esta clase
	function js_evt_guardar() 
	{
		return "
			{$this->objeto_js}.evt__guardar = function() {
				return confirmar_no_cambios();
			}
			";
	}
	
}