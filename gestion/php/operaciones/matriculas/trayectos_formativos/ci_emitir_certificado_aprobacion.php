<?php
class ci_emitir_certificado_aprobacion extends ci_rep_sin_listado_inicial
{
	protected $ev_info_datos;
	protected $s__datos_cuadro = array();
	
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_td_etiqueta_width('120px');
			$this->ev_info->set_columnas_a_mostrar(array('apellido_nombres_elegido', 'identificacion'));
			$this->ev_info->set_etiqueta('apellido_nombres_elegido', 'Nombre y Apellido');
			$this->ev_info->set_etiqueta('identificacion', 'Identificación');
		}
		return $this->ev_info;
	}

	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		if (isset($this->s__filtro)) {
			$persona = $this->s__datos['persona']['valor'];
			$datos = toba::consulta_php('co_personas')->get_datos_persona($persona);
			$this->ev_info()->set_datos($datos);
		}
	}

	public function get_datos_persona($persona)
	{
		$datos = toba::consulta_php('co_personas')->get_datos_persona($persona);
		return $datos['apellido_nombres_elegido'];
	}

        //--------------------------------------------------------------------------
	//---- filtro --------------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('persona')->set_condicion_fija('es_igual_a');
	}

	public function get_combo_persona($filtro)
	{
		$filtro_condicion = new toba_filtro_condicion('contiene', 'ILIKE', '%', '%', '', '');
		$where = $filtro_condicion->get_sql('f_limpiar_acentos(apellido_nombres_elegido::varchar)', guarani_texto::limpiar_acentos($filtro));
		return toba::consulta_php('co_personas')->get_personas_alumnos($where);
	}

	public function get_certificados($propuesta)
	{
		return toba::consulta_php('co_certificados')->get_certificados_por_propuestas($propuesta);
	}

	public function get_datos($filtro)
	{		
		$this->s__datos_cuadro = $this->cn()->get_datos($filtro);		
		return $this->s__datos_cuadro;
	}

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_filtro_asociado($this->dep('filtro'));
		$cuadro->set_usar_ordenamiento_con_cortes();
		/**
		 * 	Si no está seteado el filtro entonces es la carga inicial del cuadro (el usuario no presionó filtrar).
		 * 	No se traen datos (no se ejecuta la consulta) y se pone un mensaje en el cuadro que indica al usuario
		 * 	que debe filtrar para obtener datos.
		 */
		$this->msj_eof = '800_eof_cuadro';
		if (!isset($this->s__filtro)) {
			$this->indx_msg_eof .= '_sinfiltro';
			$cuadro->set_eof_mensaje(guarani_mensajes::get($this->indx_msg_eof));
		} else {
			//El usuario presiono filtrar (ya sea con datos o con el filtro vacío).
			$datos = $this->get_datos($this->s__filtro);
			if (!$this->cn()->get_rtf_tiene()) {
				$cuadro->set_eof_mensaje(guarani_mensajes::get('800_rtf_no_tiene'));
			}
			$cuadro->set_datos($datos);
		}
	}

	public function get_tabla_principal()
	{
		return 'mdp_personas';
	}

	public function get_valor_filtro()
	{
		if (!isset($this->s__filtro)) {
			return '';
		} else {
			return $this->s__filtro;
		}
	}
	
	/*
	 * Funciones que se utilizan para la impresion de jasper
	 */
	public function datos_encabezado()
	{
		return $this->ev_info->get_datos();
	}

	public function ancho_etiquetas_encabezado()
	{
		return 110;
	}

	public function get_template()
	{
		return toba::proyecto()->get_path() . '/exportaciones/jasper/' . 'template_guarani_firma.jrxml';
	}

	public function get_titulo()
	{
		return 'Certificado de Aprobación de Trayectos Formativos';
	}

}