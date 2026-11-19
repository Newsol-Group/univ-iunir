<?php
class guarani_ci extends toba_ci
{
	protected $_pantalla_id_servicio;
	protected $_wizard_toc_sentido = 'horizontal';
	protected $s__datos_filtros;
	protected $s__filtros;
	protected $s__datos_cuadro_salida_xml = array();

	function set_wizard_toc_horizontal()
	{
		$this->_wizard_toc_sentido = 'horizontal';
	}

	function set_wizard_toc_vertical()
	{
		$this->_wizard_toc_sentido = 'vertical';
	}

	function get_wizard_toc_sentido()
	{
		return $this->_wizard_toc_sentido;
	}

	function disparar_eventos()
	{
		try {
			parent::disparar_eventos();
		} catch (guarani_error $e) {
			if ($e->procesar_con_metodo_anterior()) {
				toba::logger()->error($e->getMessage());
				throw $e;
			} else {
				$e->notificar();
				//Si hay mensajes criticos tirar la excepción para arriba
				if ($e->hay_error_critico()) {
					//Sin texto porque sino se duplican los mensajes
					throw new toba_error('');
				}
			}
		}
	}

	//--------------------------------------------------------------------------
	//---- API Pantallas -------------------------------------------------------
	//--------------------------------------------------------------------------
	/**
	 * Retorna la referencia a la pantalla a graficar
	 * Una vez que se invoca este metodo se fija la pantalla para el resto del pedido de pagina
	 * Es importante relegar esta desicion en caso de querer variar la pantalla a mostrar dinamicamente
	 * @return toba_ei_pantalla
	 */
	function pantalla()
	{
		if (!isset($this->_pantalla_servicio)) {

			$this->_log->debug($this->get_txt() . "Pantalla de servicio: '{$this->_pantalla_id_servicio}'", 'toba');
			$id_pantalla = $this->get_id_pantalla();
			if (!isset($id_pantalla)) {
				//Se esta consumiendo la pantalla antes de la configuracion,
				//y sin un set_pantalla de por medio: utilizo la misma pantalla de los eventos.
				$id_pantalla = $this->_pantalla_id_eventos;
			}
			$info_pantalla = $this->get_info_pantalla($id_pantalla);
			$obj_pantalla = $this->get_info_objetos_asoc_pantalla($id_pantalla);
			$evt_pantalla = $this->get_info_eventos_pantalla($id_pantalla);
			$info = array(
				'_info' => $this->_info,
				'_info_ci' => $this->_info_ci,
				'_info_eventos' => $this->_info_eventos,
				'_info_ci_me_pantalla' => $this->_info_ci_me_pantalla
			);
			$info['_info_pantalla'] = $info_pantalla;
			$info['_objetos_pantalla'] = $obj_pantalla;
			$info['_eventos_pantalla'] = $evt_pantalla;
			$info['_const_instancia_numero'] = 0;
			if (isset($info_pantalla['subclase_archivo'])) {
				$pm = toba::puntos_montaje()->get_por_id($info_pantalla['punto_montaje']);
				$path = $pm->get_path_absoluto() . '/' . $info_pantalla['subclase_archivo'];
				require_once($path);
			}
			$clase = 'guarani_ei_pantalla';
			if (isset($info_pantalla['subclase'])) {
				$clase = $info_pantalla['subclase'];
			}
			$this->_pantalla_servicio = new $clase($info, $this->_submit, $this->objeto_js);
			$this->_pantalla_servicio->set_controlador($this, $id_pantalla);
			$this->_pantalla_servicio->pre_configurar();
			//Se le pasan las notificaciones
			foreach ($this->_notificaciones as $notificacion) {
				$this->_pantalla_servicio->agregar_notificacion($notificacion['mensaje'], $notificacion['tipo']);
			}
			$this->_notificaciones = array();
		}
		return $this->_pantalla_servicio;
	}

	function get_id_pantallas()
	{
		return aplanar_matriz($this->_info_ci_me_pantalla, 'identificador');
	}

	function get_id_pantallas_posteriores($pantalla_actual = null, $incluir_actual = false)
	{
		if (!$pantalla_actual) {
			$pantalla_actual = $this->get_id_pantalla();
		}
		$pantallas = $this->get_id_pantallas();
		$pant_posteriores = array();
		$es_posterior = false;
		foreach ($pantallas as $pantalla) {
			if ($es_posterior) {
				$pant_posteriores[] = $pantalla;
			} else {
				if ($pantalla == $pantalla_actual) {
					if ($incluir_actual) {
						$pant_posteriores[] = $pantalla;
					}
					$es_posterior = true;
				}
			}
		}
		return $pant_posteriores;
	}

	function get_id_pantallas_anteriores($pantalla_actual = null, $incluir_actual = false)
	{
		if (!$pantalla_actual) {
			$pantalla_actual = $this->get_id_pantalla();
		}
		$pantallas = $this->get_id_pantallas();
		$pant_anteriores = array();
		$es_anterior = true;
		foreach ($pantallas as $pantalla) {
			if ($pantalla == $pantalla_actual) {
				if ($incluir_actual) 
					$pant_anteriores[] = $pantalla;
				break;
			}
			$pant_anteriores[] = $pantalla;
		}
		return $pant_anteriores;
	}

	function get_id_pantalla_siguiente($pantalla_actual = null)
	{
		if (!$pantalla_actual) {
			$pantalla_actual = $this->get_id_pantalla();
		}
		$pantallas_siguientes = $this->get_id_pantallas_posteriores($pantalla_actual);
		return guarani_arreglos::count($pantallas_siguientes) == 0 ? null : current($pantallas_siguientes);
	}

	function get_id_pantalla_anterior($pantalla_actual = null)
	{
		$id_pantallas_anteriores = static::get_id_pantallas_anteriores($pantalla_actual);
		return empty($id_pantallas_anteriores) ? null : $id_pantallas_anteriores[guarani_arreglos::count($id_pantallas_anteriores) - 1];
	}

	//---- Comportamiento genérico filtros -------------------------------------

	function filtro_configurar($nombre, $colapsar_sin_datos = true)
	{
		if (isset($this->s__datos_filtros[$nombre])) {
			$this->dep($nombre)->set_datos($this->s__datos_filtros[$nombre]);
		} elseif ($colapsar_sin_datos) {
			$this->dep($nombre)->colapsar();
		}
	}

	function filtro_filtrar($nombre, $datos)
	{
		if (isset($datos) && !empty($datos)) {
			$this->s__datos_filtros[$nombre] = $datos;
			$this->s__filtros[$nombre] = $this->dep($nombre)->get_sql_where();
		} else {
			$this->filtro_limpiar($nombre);
			$this->s__filtros[$nombre] = '';
		}
	}

	function filtro_limpiar($nombre)
	{
		unset($this->s__datos_filtros[$nombre]);
		if ($this->dep($nombre)->con_filtrado_inicial()) {
			$this->s__filtros[$nombre] = '';
		} else {
			unset($this->s__filtros[$nombre]);
		}
	}

	function filtro_esta_cargado($nombre)
	{
		if ($this->get_filtro($nombre)) {
			return $this->dep($nombre)->esta_cargado();
		}
		return false;
	}

	function get_filtro($filtro)
	{
		return isset($this->s__filtros[$filtro]) ? $this->s__filtros[$filtro] : null;
	}

	function get_filtro_msg_eof($filtro, $concepto)
	{
		$indx_msj_eof = false;
		//Si esta seteado el filtro
		if (!isset($this->s__filtros[$filtro])) {
			//Si es con_filtrado_inicial
			if (!$this->dep($filtro)->con_filtrado_inicial()) {
				$indx_msj_eof = '800_eof_cuadro_sinfiltro'; //Debe filtrar para obtener datos.
			} else {
				$indx_msj_eof = '800_eof_cuadro'; //No se encontraron %1%.
			}
		} else {
			//Si está vacío
			if (empty($this->s__filtros[$filtro]) || $this->s__filtros[$filtro] == '1=1') {
				$indx_msj_eof = '800_eof_cuadro'; //No se encontraron %1%.
			} else {
				$indx_msj_eof = '800_eof_cuadro_filtrado'; //No se encontraron %1% con el filtro especificado.
			}
		}
		if ($indx_msj_eof) {
			$etiqueta = $this->cn()->etiquetas($concepto)->get_enf('plural');
			return guarani_mensajes::get($indx_msj_eof, array($etiqueta));
		}
		return false;
	}

	function get_datos_cuadro_salida_xml()
	{
		return $this->s__datos_cuadro_salida_xml;
	}

	function set_datos_cuadro_salida_xml($datos = array())
	{
		$this->s__datos_cuadro_salida_xml = $datos;
	}
	
	function xml_texto_fop($texto, $atts = [])
	{
		$texto = guarani_texto::reemplazar_caracteres_especiales_xml_fop($texto);
		return parent::xml_texto($texto, $atts);
	}

}