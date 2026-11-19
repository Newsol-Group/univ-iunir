<?php
/**
 *
 * 
 * @package     Administración
 * @subpackage 	Parámetros
 * 
 */
class ci_edi_parametros_nivel_entidad extends guarani_pers_ci
{
	protected $valor_parametro_temp;
	protected $s__campo_valor;
	protected $s__sist_valores;
	protected $s__seleccion;
	
	// Componente visual de información.
	protected $ev_info;
	
	protected $s__nodo_editar;
	
	// Entidades que no se pueden seleccionar en la edición por nivel por tener 
	// definido ya otro valor para el mismo nivel
	protected $s__entidades_no_seleccionables;

	function unset_vars()
	{
		unset($this->s__campo_valor);
		unset($this->s__sist_valores);
		unset($this->s__seleccion);
		unset($this->s__nodo_editar);
	}

	/**
	 * Resetea la selección cuando cambia el valor del combo de nivel
	 */
	function ajax__unset_seleccion($parametros, toba_ajax_respuesta $respuesta)
	{
		unset($this->s__seleccion);
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('parametro', 'descripcion', 'referencia', 'operaciones', 'modulos', 'valor_default_desc'));
			$this->ev_info->set_max_columnas_por_fila(1);
			$this->ev_info->set_td_etiqueta_width('130px');
			$this->ev_info->set_etiqueta('parametro', 'Parámetro:');
			$this->ev_info->set_etiqueta('descripcion', 'Desc. abreviada:');
			$this->ev_info->set_etiqueta('referencia', 'Referencia:');
			$this->ev_info->set_etiqueta('operaciones', 'Usado en operaciones:');
			$this->ev_info->set_etiqueta('modulos', 'Usado en módulos:');
			$this->ev_info->set_etiqueta('valor_default_desc', 'Valor por Defecto:');
		}
		return $this->ev_info;
	}

	//--------------------------------------------------------------------------
	//---- PANTALLA seleccion_nivel_entidad (contenedor árbol) 
	//--------------------------------------------------------------------------

	function conf__seleccion_nivel_entidad()
	{
		$datos = $this->controlador()->get_datos_ev_info();
		$this->ev_info()->set_datos($datos);

		// Se activan los botones de la botonera principal
		$this->controlador()->controlador()->evento('cancelar')->activar();
		$this->controlador()->controlador()->evento('guardar')->activar();
	}

	//--------------------------------------------------------------------------
	//---- Árbol ---------------------------------------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Construcción del árbol de la pantalla de selección de Comisiones y Subcomisiones.
	 */
	function construir_arbol()
	{
		// Configuración del árbol 
		$constructor = guarani::constructor_arbol();

		// Íconos de acciones
		$img_editar = toba_recurso::imagen_proyecto('acciones/editar.gif');

		// Nivel Valor
		$nivel = guarani::arbol_nivel('tipo_entidad', 'clave', 'valor', 'Valor');
		$nivel->agregar_utileria($img_editar, 'editar', 'Editar el Nivel/Entidad');
		$constructor->set_nivel($nivel);

		// Nivel Entidad / Subtipo		
		$nivel = guarani::arbol_nivel('ent_subt_descr', 'ent_subt_descr', 'ent_subt_descr', 'Entidad / Subtipo', 'Valor:');
		$constructor->set_nivel($nivel);

		// Carga de datos
		$constructor->set_datos($this->get_datos_arbol());
		return $constructor;
	}

	function get_datos_arbol()
	{
		$parametro = $this->cn()->entidad()->get_datos();

		$datos = array();
		$param_valores = $this->ordenar_param_valores($this->cn()->entidad()->get_param_sist_valores());
		
		foreach ($param_valores as $param) {
			$dato = array();
			$dato['clave'] = $param['tipo'] . ',' . $param['entidad_tipo'] . ',' . $param['valor'];
			$desc = toba::consulta_php('co_parametros')->get_desc_enumerado_valor($parametro['parametro'], $parametro['tipo_de_parametro'], $param['valor']);
			$dato['valor'] = 'Valor = ';
			if (isset($desc['valor_default_desc'])) {
				$dato['valor'] .= $desc['valor_default_desc'];
			} else {
				$dato['valor'] .= $param['valor'];
			}
			$dato['ent_subt_descr'] = null;
			$param_valores[] = $dato;

			$this->cn()->entidad()->set_cursor($param['x_dbr_clave']);
			// Se cargan las entidades
			$entidades = $this->cn()->entidad()->get_param_sist_valores_entidad();
			foreach ($entidades as $entidad) {
				$res = current(toba::consulta_php('co_parametros')->get_nombre_entidad($entidad['entidad']));
				$dato['ent_subt_descr'] = $res['entidad_nombre'];
				$param_valores[] = $dato;
			}

			// Se cargan los subtipos
			$subtipos = $this->cn()->entidad()->get_param_sist_valores_subtipo();
			foreach ($subtipos as $subtipo) {
				$res = current(toba::consulta_php('co_parametros')->get_nombre_subtipo($subtipo['entidad_subtipo']));
				$dato['ent_subt_descr'] = $res['ent_subt_descr'];
				$param_valores[] = $dato;
			}

			$this->cn()->entidad()->unset_cursor();
		}

		return $param_valores;
	}
	
	/**
	 * Ordena los valores según el nivel: RA / Tipo de Propuesta / Propuesta / Plan
	 * 
	 * @param array $param_valores
	 * @return array
	 */
	protected function ordenar_param_valores($param_valores)
	{
		foreach ($param_valores as $key => $valor) {
			if (($valor['tipo'] == parametro::tipo_entidad) && ($valor['entidad_tipo'] == g3entidad::tipo_responsable_academica)) {
				// RA
				$clave_ord = 1;
			} elseif (($valor['tipo'] == parametro::tipo_subtipo)) {
				// Tipo de propuesta
				$clave_ord = 2;
			} elseif (($valor['tipo'] == parametro::tipo_entidad) && ($valor['entidad_tipo'] == g3entidad::tipo_propuesta)) {
				// Propuesta
				$clave_ord = 3;
			} else {
				// Plan
				$clave_ord = 4;
			}
			
			$param_valores[$key]['clave_ord'] = $clave_ord;
		}
		
		$param_valores = rs_ordenar_por_columnas($param_valores, array('clave_ord', 'valor'));
		return $param_valores;
	}

	//--------------------------------------------------------------------------
	//---- Eventos árbol -------------------------------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * En cada evento del árbol lo primero que se realiza es un actualizar datos que realiza el sincronizar de los datos que 
	 * se estaban editando o agregando antes de cambiar de nodo en el árbol. Además se actualiza las variable de sesión
	 * que mantiene que dependencia debe mostrarse (pantalla de comisión o pantalla de subcomisión o ninguna de ellas). Ante 
	 * el evento de eliminación de un nodo, no debe mostrarse ninguna pantalla y por lo tanto no debe agregarse ninguna 
	 * dependencia.
	 */
	function nodo__tipo_entidad__editar($nodo)
	{
		$this->s__nodo_editar = $nodo;
		$params = explode(',', $nodo);
		$this->cn()->entidad()->seleccionar_param_sist_valores($params[0], $params[1], $params[2]);
		$this->set_pantalla('edicion_nivel_entidad');
	}

	//--------------------------------------------------------------------------
	//---- PANTALLA edicion_nivel_entidad
	//--------------------------------------------------------------------------

	function conf__edicion_nivel_entidad(guarani_ei_pantalla $pantalla)
	{
		$datos = $this->controlador()->get_datos_ev_info();
		$this->ev_info()->set_datos($datos);

		// Configuración de la pantalla
		if ($pantalla->existe_dependencia('filtro_resp_acad')) {
			$pantalla->eliminar_dep('filtro_resp_acad');
		}
		if ($pantalla->existe_dependencia('cuadro_resp_acad')) {
			$pantalla->eliminar_dep('cuadro_resp_acad');
		}

		if ($pantalla->existe_dependencia('filtro_tipos_propuestas')) {
			$pantalla->eliminar_dep('filtro_tipos_propuestas');
		}
		if ($pantalla->existe_dependencia('cuadro_tipos_propuestas')) {
			$pantalla->eliminar_dep('cuadro_tipos_propuestas');
		}

		if ($pantalla->existe_dependencia('filtro_propuestas')) {
			$pantalla->eliminar_dep('filtro_propuestas');
		}
		if ($pantalla->existe_dependencia('cuadro_propuestas')) {
			$pantalla->eliminar_dep('cuadro_propuestas');
		}

		if ($pantalla->existe_dependencia('filtro_planes')) {
			$pantalla->eliminar_dep('filtro_planes');
		}
		if ($pantalla->existe_dependencia('cuadro_planes')) {
			$pantalla->eliminar_dep('cuadro_planes');
		}

		if (isset($this->s__nodo_editar)) {
			$tipo = $this->s__nodo_editar;
		} else {
			$tipo = $this->s__sist_valores['nivel'] ?? null;
		}

		$tipo_descr = '';
		if ($tipo) {
			// Se desactiva el cambio de valor mientras se seleccionan entidades
			$this->dep('form_sist_valores')->ef($this->s__campo_valor)->set_solo_lectura();
			
			$tipo_explode = explode(',', $tipo);
			$tipo = $tipo_explode[0] . ',' . $tipo_explode[1];

			switch ($tipo) {
				case 'E,5':
					// Responsables Académicas
					$pantalla->agregar_dep('filtro_resp_acad');
					$pantalla->agregar_dep('cuadro_resp_acad');
					$tipo_descr = 'Responsables Académicas';
					break;
				case 'S,6':
					// Tipos de Propuestas
					$pantalla->agregar_dep('filtro_tipos_propuestas');
					$pantalla->agregar_dep('cuadro_tipos_propuestas');
					$tipo_descr = 'Tipos de propuestas';
					break;
				case 'E,6':
					// Propuestas Curriculares
					$pantalla->agregar_dep('filtro_propuestas');
					$pantalla->agregar_dep('cuadro_propuestas');
					$tipo_descr = 'Propuestas';
					break;
				case 'E,7':
					// Planes
					$pantalla->agregar_dep('filtro_planes');
					$pantalla->agregar_dep('cuadro_planes');
					$tipo_descr = 'Planes';
					break;
			}
			
			$datos = $this->dep('form_sist_valores')->get_datos();
			$this->cn()->entidad()->seleccionar_param_sist_valores($tipo_explode[0], $tipo_explode[1], $datos[$this->s__campo_valor]);
		}

		// Si se trata de una edición, se cargan las selecciones.
		if ($this->cn()->entidad()->hay_cursor()) {
			$datos = $this->cn()->entidad()->get_param_sist_valores_seleccionado();
			if ($datos['tipo'] == parametro::nivel_tipo_subtipo) {
				$this->s__seleccion = $this->cn()->entidad()->get_param_sist_valores_subtipo();
			} elseif ($datos['tipo'] == parametro::nivel_tipo_entidad) {
				$this->s__seleccion = $this->cn()->entidad()->get_param_sist_valores_entidad();
			}
		} else {
			// es un alta -> elimino el evento borrar
			$pantalla->eliminar_evento('borrar');
		}

		// Se desactivan los botones de la botonera principal
		$this->controlador()->controlador()->evento('cancelar')->desactivar();
		$this->controlador()->controlador()->evento('guardar')->desactivar();
		
		if (!empty($this->s__entidades_no_seleccionables) && !empty($tipo_descr)) {
			$pantalla->agregar_notificacion("<strong>Sólo pueden seleccionarse $tipo_descr que no tengan otra configuración de valor</strong>");
		}
	}

	//--------------------------------------------------------------------------
	//---- form_sist_valores ---------------------------------------------------
	//--------------------------------------------------------------------------

	function evt__form_sist_valores__modificacion($datos)
	{
		$this->s__sist_valores = $datos;
	}

	function conf__form_sist_valores(toba_ei_formulario $formulario)
	{
		$parametro = $this->cn()->entidad()->get_datos();
		$tipo_de_parametro = $parametro['tipo_de_parametro'];
		$formulario->set_datos(array('parametro' => $parametro['parametro']));

		if (isset($this->valor_parametro_temp)) {
			$formulario->set_datos($this->valor_parametro_temp);
		} else {
			// Se define el ef valor a mostrar de acuerdo al tipo de parámetro y tipo de dato			
			if ($tipo_de_parametro == parametro::tipo_param_enumerado) {
				$this->s__campo_valor = 'valor_enumerado';
				$formulario->desactivar_efs(array('valor_fecha', 'valor_cadena', 'valor_entero', 'valor_hora', 'valor_punto_flotante'));
			} else {
				switch ($parametro['tipo_de_dato']) {
					case parametro::tipo_dato_fecha:
					case parametro::tipo_dato_fecha_y_hr:
						$this->s__campo_valor = 'valor_fecha';
						$formulario->desactivar_efs(array('valor_cadena', 'valor_entero', 'valor_hora', 'valor_punto_flotante', 'valor_enumerado'));
						break;
					case parametro::tipo_dato_hora:
						$this->s__campo_valor = 'valor_hora';
						$formulario->desactivar_efs(array('valor_cadena', 'valor_entero', 'valor_fecha', 'valor_punto_flotante', 'valor_enumerado'));
						break;
					case parametro::tipo_dato_entero:
						$this->s__campo_valor = 'valor_entero';
						$formulario->desactivar_efs(array('valor_cadena', 'valor_fecha', 'valor_hora', 'valor_punto_flotante', 'valor_enumerado'));
						break;
					case parametro::tipo_dato_punto_flot:
						$this->s__campo_valor = 'valor_punto_flotante';
						$formulario->desactivar_efs(array('valor_cadena', 'valor_fecha', 'valor_hora', 'valor_entero', 'valor_enumerado'));
						break;
					case parametro::tipo_dato_cadena:
						$this->s__campo_valor = 'valor_cadena';
						$formulario->desactivar_efs(array('valor_fecha', 'valor_entero', 'valor_hora', 'valor_punto_flotante', 'valor_enumerado'));
						break;
				}
			}
		}

		// Se ocultan los ef's correspondientes al Valor Desde y Valor Hasta en caso de que el Tipo de parámetro no sea Rango
		if ($tipo_de_parametro != parametro::tipo_param_rango) {
			$formulario->desactivar_efs(array('valor_desde', 'valor_hasta'));
		} else {
			$desde = $parametro['valor_desde'];
			$hasta = $parametro['valor_hasta'];
			if ($parametro['tipo_de_dato'] == parametro::tipo_dato_hora) {
				$desde = substr($desde, 0, 2) . ':' . substr($desde, 2, 2) . ':' . substr($desde, 4, 2);
				$hasta = substr($hasta, 0, 2) . ':' . substr($hasta, 2, 2) . ':' . substr($hasta, 4, 2);
			}
			$formulario->set_datos(array('valor_desde' => $desde,
				'valor_hasta' => $hasta
			));
			$formulario->set_solo_lectura(array('valor_desde', 'valor_hasta'));
		}

		// Se setea la obligatoriedad del campo valor y el resto como de solo lectura
		$formulario->ef($this->s__campo_valor)->set_obligatorio(true);

		if (isset($this->s__sist_valores)) {
			$formulario->set_datos($this->s__sist_valores);
		}
		if (isset($this->s__nodo_editar)) {
			$valor = explode(',', $this->s__nodo_editar);
			$str_nivel = $valor[0] . ',' . $valor[1];
			$formulario->set_datos(array('nivel' => $str_nivel));
			$formulario->set_solo_lectura(array('nivel'));
		}

		$param_sist = $this->cn()->entidad()->get_param_sist_valores_seleccionado();
		if (isset($param_sist['valor'])) {
			$formulario->set_datos(array($this->s__campo_valor => $param_sist['valor']));
		}
		
		// Se marcan las entidades que no pueden ser seleccionadas, por tener una
		// configuración de valor distinta para el mismo nivel
		$datos = $formulario->get_datos();
		
		if (!empty($datos['nivel'])) {
			$datos_nivel = explode(',', $datos['nivel']);
			$tipo = $datos_nivel[0];
			$entidad_tipo = $datos_nivel[1];
			$valor = $datos[$this->s__campo_valor];

			$this->s__entidades_no_seleccionables = $this->cn()->entidad()->get_entidades_no_seleccionables($tipo, $entidad_tipo, $valor);
		} else {
			unset($this->s__entidades_no_seleccionables);
		}
	}

	function get_niveles_aplicacion()
	{
		$parametro = $this->cn()->entidad()->get_datos();
		return toba::consulta_php('co_parametros')->get_niveles_aplicacion($parametro['nivel']);
	}

	//--------------------------------------------------------------------------
	//---- EI's DE CARGA DINÁMICA
	//--------------------------------------------------------------------------

	//--------------------------------------------------------------------------
	//---- filtro_resp_acad ----------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__filtro_resp_acad(guarani_ei_filtro $filtro)
	{
		$this->filtro_configurar('filtro_resp_acad', false);
	}

	function evt__filtro_resp_acad__filtrar($datos)
	{
		$this->filtro_filtrar('filtro_resp_acad', $datos);
	}

	function evt__filtro_resp_acad__cancelar()
	{
		$this->filtro_limpiar('filtro_resp_acad');
	}

	//--------------------------------------------------------------------------
	//---- cuadro_resp_acad ----------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro_resp_acad(toba_ei_cuadro $cuadro)
	{
		if ($this->filtro_esta_cargado('filtro_resp_acad') || $this->dep('filtro_resp_acad')->con_filtrado_inicial()) {
			$filtro = $this->get_filtro('filtro_resp_acad');
			$filtro = empty($filtro) ? 'true' : $filtro;
			$institucion_instalacion = toba::db()->quote(guarani_configuracion::instancia()->get_valor_config('institucion_instalacion'));
			$filtro .= " AND sga_responsables_academicas.institucion = $institucion_instalacion";
			$datos = guarani::co('co_responsables_academicas')->get_listado($filtro);
			$cuadro->set_datos($datos);
		}

		$msj_eof = $this->get_filtro_msg_eof('filtro_resp_acad', 'Responsable Académica');
		if ($msj_eof) {
			$cuadro->set_eof_mensaje($msj_eof);
		}
	}

	function evt__cuadro_resp_acad__seleccion($seleccion)
	{
		$this->s__seleccion = $seleccion;
	}

	function conf_evt__cuadro_resp_acad__seleccion(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro_resp_acad')->get_datos();
		if (in_array($datos[$fila]['entidad'], $this->s__entidades_no_seleccionables)) {
			$evento->anular();
		}
		if (isset($this->s__seleccion)) {
			$evento->set_check_activo($this->es_fila_seleccionada('cuadro_resp_acad', $fila, 'entidad'));
		}
	}

	//--------------------------------------------------------------------------
	//---- filtro_tipos_propuestas ---------------------------------------------
	//--------------------------------------------------------------------------

	function conf__filtro_tipos_propuestas(guarani_ei_filtro $filtro)
	{
		$this->filtro_configurar('filtro_tipos_propuestas', false);
	}

	function evt__filtro_tipos_propuestas__filtrar($datos)
	{
		$this->filtro_filtrar('filtro_tipos_propuestas', $datos);
	}

	function evt__filtro_tipos_propuestas__cancelar()
	{
		$this->filtro_limpiar('filtro_tipos_propuestas');
	}

	//--------------------------------------------------------------------------
	//---- cuadro_tipos_propuestas ---------------------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro_tipos_propuestas(toba_ei_cuadro $cuadro)
	{
		if ($this->filtro_esta_cargado('filtro_tipos_propuestas') || $this->dep('filtro_tipos_propuestas')->con_filtrado_inicial()) {
			$datos = guarani::co('co_propuestas')->get_propuestas_tipos_reporte($this->get_filtro('filtro_tipos_propuestas'));
			$cuadro->set_datos($datos);
		}
		$msj_eof = $this->get_filtro_msg_eof('filtro_tipos_propuestas', 'Propuestas');
		if ($msj_eof) {
			$cuadro->set_eof_mensaje($msj_eof);
		}
	}

	function evt__cuadro_tipos_propuestas__seleccion($seleccion)
	{
		$this->s__seleccion = $seleccion;
	}

	function conf_evt__cuadro_tipos_propuestas__seleccion(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro_tipos_propuestas')->get_datos();
		if (in_array($datos[$fila]['entidad_subtipo'], $this->s__entidades_no_seleccionables)) {
			$evento->anular();
		}
		if (isset($this->s__seleccion)) {
			$evento->set_check_activo($this->es_fila_seleccionada('cuadro_tipos_propuestas', $fila, 'entidad_subtipo'));
		}
	}

	//--------------------------------------------------------------------------
	//---- filtro_propuestas ---------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__filtro_propuestas(guarani_ei_filtro $filtro)
	{
		$this->filtro_configurar('filtro_propuestas', false);
		$filtro->columna('nombre')->set_condicion_fija('contiene');
	}

	function evt__filtro_propuestas__filtrar($datos)
	{
		$this->filtro_filtrar('filtro_propuestas', $datos);
	}

	function evt__filtro_propuestas__cancelar()
	{
		$this->filtro_limpiar('filtro_propuestas');
	}

	//--------------------------------------------------------------------------
	//---- cuadro_propuestas ---------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro_propuestas(toba_ei_cuadro $cuadro)
	{
		if ($this->filtro_esta_cargado('filtro_propuestas') || $this->dep('filtro_propuestas')->con_filtrado_inicial()) {
			$datos = guarani::co('co_propuestas')->get_listado($this->get_filtro('filtro_propuestas'), 'propuesta_nombre');
			$cuadro->set_datos($datos);
		}

		$msj_eof = $this->get_filtro_msg_eof('filtro_propuestas', 'Propuestas');
		if ($msj_eof) {
			$cuadro->set_eof_mensaje($msj_eof);
		}
	}

	function evt__cuadro_propuestas__seleccion($seleccion)
	{
		$this->s__seleccion = $seleccion;
	}

	function conf_evt__cuadro_propuestas__seleccion(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro_propuestas')->get_datos();
		if (in_array($datos[$fila]['entidad'], $this->s__entidades_no_seleccionables)) {
			$evento->anular();
		}
		if (isset($this->s__seleccion)) {
			$evento->set_check_activo($this->es_fila_seleccionada('cuadro_propuestas', $fila, 'entidad'));
		}
	}

	//--------------------------------------------------------------------------
	//---- filtro_planes -------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__filtro_planes(guarani_ei_filtro $filtro)
	{
		$this->filtro_configurar('filtro_planes', false);
	}

	function evt__filtro_planes__filtrar($datos)
	{
		$this->filtro_filtrar('filtro_planes', $datos);
	}

	function evt__filtro_planes__cancelar()
	{
		$this->filtro_limpiar('filtro_planes');
	}

	//--------------------------------------------------------------------------
	//---- cuadro_planes -------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro_planes(toba_ei_cuadro $cuadro)
	{
		if ($this->filtro_esta_cargado('filtro_planes') || $this->dep('filtro_planes')->con_filtrado_inicial()) {
			$where = "sga_planes.estado = '" . planes::estado_activo_vigente . "'";
			$filtro = $this->get_filtro('filtro_planes');
			if (!empty($filtro)) {
				$where .= ' AND ' . $filtro;
			}
			$datos = guarani::co('co_planes')->get_listado($where);
			$cuadro->set_datos($datos);
		}

		$msj_eof = $this->get_filtro_msg_eof('filtro_planes', 'Responsable Académica');
		if ($msj_eof) {
			$cuadro->set_eof_mensaje($msj_eof);
		}
	}

	function evt__cuadro_planes__seleccion($seleccion)
	{
		$this->s__seleccion = $seleccion;
	}

	function conf_evt__cuadro_planes__seleccion(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro_planes')->get_datos();
		if (in_array($datos[$fila]['entidad'], $this->s__entidades_no_seleccionables)) {
			$evento->anular();
		}
		if (isset($this->s__seleccion)) {
			$evento->set_check_activo($this->es_fila_seleccionada('cuadro_planes', $fila, 'entidad'));
		}
	}

	//--------------------------------------------------------------------------

	function es_fila_seleccionada($dep_cuadro, $fila, $campo_clave)
	{
		$clave = $this->dep($dep_cuadro)->get_clave_fila_array($fila);
		$clave_fila = $clave[$campo_clave];
		foreach ($this->s__seleccion as $seleccion) {
			if (isset($seleccion[$campo_clave])) {
				if ($seleccion[$campo_clave] == $clave_fila) {
					return true;
				}
			}
		}
		return false;
	}

	//--------------------------------------------------------------------------
	//---- EVENTOS -------------------------------------------------------------
	//--------------------------------------------------------------------------

	function evt__agregar()
	{
		$this->set_pantalla('edicion_nivel_entidad');
	}

	function evt__borrar()
	{
		$this->cn()->entidad()->eliminar_param_sist_valores_seleccionado();
		$this->cn()->entidad()->unset_cursor();
		$this->evt__cancelar();
	}

	function evt__cancelar()
	{
		$this->unset_vars();
		$this->set_pantalla('seleccion_nivel_entidad');
	}

	function evt__confirmar()
	{
		// Se valida que haya al menos una selección
		if (empty($this->s__seleccion)) {
			$tipo = $this->s__sist_valores['nivel'];
			switch ($tipo) {
				case 'E,5':
					$concepto = 'una Responsable Académica';
					break;
				case 'S,6':
					$concepto = 'un Tipo de Propuesta';
					break;
				case 'E,6':
					$concepto = 'una Propuesta Curricular';
					break;
				case 'E,7':
					$concepto = 'un Plan';
					break;
			}
			if ($concepto) {
				$mje = guarani_mensajes::get('800_no_seleccion', array($concepto));
				throw guarani::error($mje);
			}
		}

		// Seteo de valores en par_param_sist_valores.
		$datos = array();
		$datos_nivel = explode(',', $this->s__sist_valores['nivel']);

		$datos['tipo'] = $datos_nivel[0];
		$datos['entidad_tipo'] = $datos_nivel[1];
		$datos['valor'] = $this->s__sist_valores[$this->s__campo_valor];

		if (!$this->cn()->entidad()->hay_cursor()) {
			// Alta			
			$datos['apex_ei_analisis_fila'] = 'A';
			$datos['x_dbr_clave'] = 0;
			// Creo el nodo padre y seteo el cursor en él
			$id_interno = $this->cn()->entidad()->agregar_param_sist_valores($datos);
			$this->cn()->entidad()->set_cursor($id_interno);
		} else {
			// Modificación: se actualizan los datos en el nodo seleccionado
			$datos['apex_ei_analisis_fila'] = 'M';
			$this->cn()->entidad()->set_param_sist_valores_seleccionado($datos);
		}

		// Seteo de valores en par_param_sist_valores_entidad o par_param_sist_valores_subtipos según corresponda.		
		if ($datos_nivel[0] == parametro::tipo_entidad) {
			$this->cn()->entidad()->set_param_sist_valores_entidad($this->s__seleccion);
		} elseif ($datos_nivel[0] == parametro::tipo_subtipo) {
			$this->cn()->entidad()->set_param_sist_valores_subtipo($this->s__seleccion);
		}

		$this->cn()->entidad()->unset_cursor();
		$this->evt__cancelar();
	}

}