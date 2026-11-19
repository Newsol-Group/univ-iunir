<?php
class ci_plan_elementos extends guarani_pers_ci
{
	protected $separador_accion = '%-%';
	protected $separador_accion_param = '%_%';
	protected $s__nodo_seleccionado = null;
	protected $s__nodo_padre_seleccionado = null;
	protected $s__modo_asociar = false;
	protected $s__apertura;

	function cn_elementos()
	{
		return $this->cn()->entidad()->elementos();
	}

	//--------------------------------------------------------------------------
	//---- Estado de la pantalla -----------------------------------------------
	//--------------------------------------------------------------------------

	function es_editable()
	{
		return $this->cn()->entidad()->esta_activo() && !$this->cn()->entidad()->es_nueva_version();
	}

	function set_estado_inicial()
	{
		$this->desactivar_asociacion();
		$this->deseleccionar_elemento();
	}

	function conf()
	{
		if ($this->s__modo_asociar) {
			$this->pantalla()->eliminar_dep('propiedades');
		} else {
			$this->pantalla()->eliminar_dep('disponibles');
			if (!isset($this->s__nodo_seleccionado)) {
				$this->pantalla()->eliminar_dep('propiedades');
			}
		}
	}

	//--------------------------------------------------------------------------
	//---- Generacion gráfica del árbol ----------------------------------------
	//--------------------------------------------------------------------------

	function evt__arbol__cargar_nodo($id)
	{
		return array($this->cn_elementos()->elemento($id));
	}

	function conf__arbol(arbol_plan_elementos $arbol)
	{
		$arbol->set_nivel_apertura(7);
		$arbol->set_ancho_nombres(45);
		$plan = $this->cn_elementos()->arbol_edicion();
		$this->disparar_configuracion_nodos($plan);
		$arbol->set_datos(array($plan));
		if (isset($this->s__apertura)) {
			$arbol->set_apertura_nodos($this->s__apertura);
		}
		$arbol->set_mostrar_ayuda(false);
		if ($this->cn()->entidad()->edicion_version_bloqueada()) {
			$arbol->mostrar_barra(false);
		} else {
			$arbol->mostrar_barra(true);
			if ($this->s__modo_asociar) {
				$arbol->mostrar_boton_editar(false);
				if (!$this->cn()->entidad()->edicion_plan_bloqueada()) {
					// Se muestra siempre el botón para agregar orientaciones. Ticket #2499
					// Pueden cargarse diferentes orientaciones para titulo final y titulos intermedios.
					$arbol->mostrar_boton_orientaciones(true);
				} else {
					$arbol->mostrar_boton_orientaciones(false);
				}
			} else {
				$arbol->mostrar_boton_editar(true);
			}
		}
	}

	function evt__arbol__cambio_apertura($datos)
	{
		$this->s__apertura = $datos;
	}

	protected function disparar_configuracion_nodos($nodo)
	{
		$this->configurar_nodo($nodo);
		if (!$nodo->es_hoja()) {
			foreach ($nodo->get_hijos() as $hijo) {
				$this->disparar_configuracion_nodos($hijo);
			}
		}
	}

	protected function configurar_nodo($nodo)
	{
		if (isset($this->s__nodo_seleccionado) && ($nodo->get_id() == $this->s__nodo_seleccionado)) {
			$nodo->set_estilo_css_li('font-weight: bold; background: rgb(158, 187, 217)');
		}
		if ($this->cn()->entidad()->edicion_version_bloqueada()) {
			$nodo->mostrar_utilerias_edicion(false);
		} else {
			if ($this->s__modo_asociar) {
				$nodo->mostrar_utilerias_edicion(true);
				if ($this->cn_elementos()->elemento($nodo->get_id())->puede_eliminar()) {
					$nodo->mostrar_utileria_eliminacion(true);
				} else {
					$nodo->mostrar_utileria_eliminacion(false);
				}
				if ($nodo->get_controlador()->get_tipo() == elemento::tipo_modulo) {
					if ($nodo->get_controlador()->es_compartible()) {
						$nodo->mostrar_utileria_eliminacion(true);
					} else {
						$nodo->mostrar_utileria_asociacion();
					}
				} else {
					if ($nodo->get_padre() instanceof plan_elemento_gui_modulo_mg) {
						$nodo->mostrar_utileria_eliminacion(false);
					}
				}
				if ($this->cn()->entidad()->edicion_plan_bloqueada() && $nodo instanceof plan_elemento_gui_modulo_contenedor_orientaciones) {
					$nodo->mostrar_utilerias_edicion(false);
				}
				if ($this->cn()->entidad()->edicion_plan_bloqueada() && $nodo->get_padre() instanceof plan_elemento_gui_modulo_contenedor_orientaciones) {
					$nodo->mostrar_utileria_eliminacion(false);
				}
			} else {
				$nodo->mostrar_utilerias_edicion(false);
			}
		}
	}

	//--------------------------------------------------------------------------
	//---- MODO asociar elementos ----------------------------------------------
	//--------------------------------------------------------------------------

	function evt__asociar()
	{
		if (!$this->s__modo_asociar) {
			$this->activar_asociacion(false);
		} else {
			$this->desactivar_asociacion(true);
		}
	}

	function activar_asociacion()
	{
		$this->s__modo_asociar = true;
		$this->deseleccionar_elemento();
	}

	function desactivar_asociacion()
	{
		$this->s__modo_asociar = false;
	}

	//--------------------------------------------------------------------------
	//---- Utilidades sobre ELEMENTOS ------------------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Disparo de eventos
	 */
	function extender_objeto_js()
	{
		echo "
		{$this->objeto_js}.accion_nodo = function (accion, nodo, nodo_padre, parametros)
		{
			if( typeof parametros == 'undefined') {
				parametros = '';
			} else {
				if(! (parametros.constructor.toString().indexOf('Array') == -1) ) {
					parametros = parametros.join('{$this->separador_accion_param}');
				}
			}
			var paquete = 	accion + '{$this->separador_accion}' + 
							nodo + '{$this->separador_accion}' + 
							nodo_padre + '{$this->separador_accion}' + 
							parametros;							
			this.set_evento(new evento_ei('accion_nodo', true, '', [paquete] ));
		}
		";
	}

	/**
	 * Decodificación de eventos
	 */
	function evt__accion_nodo($parametros)
	{
		//-- 1) Se desempaquetan los parámetros de la acción
		$parametros = explode($this->separador_accion, $parametros);
		if (guarani_arreglos::count($parametros) != 4) {
			throw new guarani_error('Edicion de planes: La cantidad de parametros de la accion solicitada son incorrectos');
		}
		if ($parametros[3]) {
			$argumentos = explode($this->separador_accion_param, $parametros[3]);
		} else {
			$argumentos = null;
		}
		//@ToDo: Controlar parámetros
		$accion = $parametros[0];
		$nodo = $parametros[1];
		$nodo_padre = $parametros[2];

		//-- 2) Se invoca la acción seleccionada
		$this->disparar_accion_nodo($accion, $nodo, $nodo_padre, $argumentos);
	}

	/**
	 * Ruteo de eventos
	 */
	function disparar_accion_nodo($accion, $nodo, $nodo_padre, $argumentos)
	{
		switch ($accion) {
			//-- SELECCIONAR un ELEMENTO
			case 'seleccionar_elemento':
				$this->seleccionar_elemento($nodo, $nodo_padre);
				break;
			//-- ELIMINAR un ELEMENTO
			case 'eliminar_elemento':
				$this->cn_elementos()->elemento_eliminar($nodo, $nodo_padre);
				break;
			//-- Asociar ELEMENTOS a un MODULO
			case 'modulo_asociar_elementos':
				$elementos = $this->dep('disponibles')->get_elementos_seleccionados();
				$this->validar_elementos_disponibles($elementos);
				/**
				 * Se consulta cuál fue el tipo de elementos que se seleccionaron para agregar, dado que los
				 * módulos RTF en realidad no se asocian como actividades o módulos comunes, ya que son sólo
				 * cáscaras, por lo cual al "asociarlos" deben crearse desde cero los elementos como se hace
				 * al agregar un módulo nuevo.
				 */
				$tipo = $this->dep('disponibles')->get_tipo_seleccionados();
				if (isset($tipo) && $tipo == 'RTF') {
					$this->cn_elementos()->agregar_modulos_rtf($nodo, $elementos);
				} else {
					$this->cn_elementos()->elementos_asociar($nodo, $elementos);
				}
				$this->deseleccionar_elemento();
				break;
			case 'modulo_agregar':
				$this->agregar_modulo($nodo, elemento::subtipo_agrupador_modulos, $argumentos[0]);
				break;
			case 'modulo_orientacion_agregar':
				$this->agregar_modulo($nodo, elemento::subtipo_orientacion, $argumentos[0]);
				break;
			default:
				throw new guarani_error('Edicion de planes: La ACCION solicitada no existe');
		}
	}

	//--------------------------------------------------------------------------
	//---- Utilerias -----------------------------------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Creación de orientaciones
	 */
	function evt__orientaciones($parametros)
	{
		//Desempaqueto los parámetros
		$parametros = explode($this->separador_accion_param, $parametros);
		//Si la cantidad representa un numero entero positivo y el valor está entre 1 y 99
		if((preg_match("/^\d+$/", $parametros[0])) && ($parametros[0] > 0) && ($parametros[0] < 100)) {
			//Si el nombre ingresado tiene entre 1 y 255 caracteres de longitud
			if( (strlen($parametros[1]) > 0) && (strlen($parametros[1]) < 256) ) {
				$cantidad = $parametros[0];
				$nombre = $parametros[1];
				try {
					$this->cn_elementos()->validar_orientacion($cantidad, $nombre);
					$raiz = $this->cn_elementos()->elemento_raiz();
					$modulo = $this->agregar_modulo($raiz, elemento::subtipo_agrupador_orientaciones, $nombre);
					$base_nombre = 'Orientacion ';
					for ($a = 1; $a <= $cantidad; $a++) {
						$this->agregar_modulo($modulo->get_id(), elemento::subtipo_orientacion, $base_nombre . $a);
					}
				} catch (guarani_error $e) {
					throw $e;
				}
			} else {
				toba::notificacion()->agregar($this->get_mensaje('800PRO_nombre_orientacion'), 'error');	
			}
		} else {
			toba::notificacion()->agregar($this->get_mensaje('800PRO_cantidad_orientaciones'), 'error');	
		}		
	}

	//--------------------------------------------------------------------------
	//---- Primitivas ----------------------------------------------------------
	//--------------------------------------------------------------------------

	protected function agregar_modulo($nodo_padre, $subtipo, $nombre)
	{
		$nuevo_modulo = $this->cn_elementos()->modulo_crear($nodo_padre, $subtipo, $nombre);
		return $nuevo_modulo;
	}

	protected function seleccionar_elemento($nodo, $nodo_padre)
	{
		if ($this->cn_elementos()->existe_elemento($nodo)) {
			if ($nodo_padre == 'null') {
				$nodo_padre = null;
			}
			$this->desactivar_asociacion();
			$this->s__nodo_seleccionado = $nodo;
			$this->s__nodo_padre_seleccionado = $nodo_padre;
			$this->dep('propiedades')->set_edicion_nodo($nodo, $nodo_padre);
		} else {
			throw new guarani_error('Error, nodo inexistente: ' . $nodo);
		}
	}

	protected function deseleccionar_elemento()
	{
		if (isset($this->s__nodo_seleccionado)) {
			unset($this->s__nodo_seleccionado);
		}
		$this->dep('propiedades')->desactivar_edicion_nodo();
	}

	//--------------------------------------------------------------------------
	//---- Listado de elementos disponibles para agregar -----------------------
	//--------------------------------------------------------------------------

	function get_elementos_disponibles($tipo, $filtro)
	{
		if ($tipo != 'RTF') {
			// Las actividades, materias o módulos disponibles deben estar en estado Activo.
			$where = "sga_elementos.estado = '" . elemento::estado_activo . "'";
			$filtro = (!empty($filtro)) ? $filtro . ' AND ' . $where : $where;
		}

		if ($tipo == 'A') {
			$datos = toba::consulta_php('co_elementos')->get_actividades_y_materias_genericas($filtro);
		} elseif ($tipo == 'M') {
			// Tener en cuenta que en los módulos se incluyen los módulos RTF ya asociados al plan
			$datos = toba::consulta_php('co_elementos')->get_modulos($filtro, false, true);
		} elseif ($tipo == 'MG') {
			$datos = toba::consulta_php('co_elementos')->get_materias_genericas($filtro);
		} elseif ($tipo == 'RTF') {
			$datos = toba::consulta_php('co_elementos')->get_modulos_rtf($filtro);
		}
		$mem = array();
		foreach (array_keys($datos) as $id) {
			$mem[] = $datos[$id]['id'];
			$url = toba_recurso::url_proyecto() . "/img/" . $datos[$id]['icono'];
			$ayuda = 'Tipo: ' . $datos[$id]['subtipo_nombre'];
			$img = "<img title='$ayuda' src='$url'/>";
			$datos[$id]['icono'] = $img;
		}
		toba::memoria()->set_array_operacion('elementos_disponibles', $mem);
		return $datos;
	}

	function validar_elementos_disponibles($elementos)
	{
		foreach ($elementos as $elemento) {
			toba::memoria()->en_array_operacion('elementos_disponibles', $elemento);
		}
	}

	function ajax__es_correlativa_de_o_asociada_a_certificado($parametros, toba_ajax_respuesta $respuesta)
	{
		$mensaje_correlativas = '';
		$mensaje_certificados = '';
		if ($this->cn()->entidad()->es_recien_creado()) {
			/** Chequea si el elemento que se está por eliminar es correlativa de otros para alertar
			 *  al usario antes de confirmar la eliminación.
			 */
			$elementos = $this->cn_elementos()->tienen_como_correlativa_a($parametros['id_elemento']);
			foreach ($elementos as $elemento) {
				$mensaje_correlativas .= "(" . $elemento['codigo'] . ") " . $elemento['nombre'] . ",\n";
			}
			if ($mensaje_correlativas <> '') {
				$mensaje_correlativas = substr($mensaje_correlativas, 0, strlen($mensaje_correlativas) - 2);
			}
			/** Chequea si el elemento que se está por eliminar está asociado a Certificados del Plan Versión 
			 *  para alertar al usario antes de confirmar la eliminación.
			 */
			$certificados = $this->cn_elementos()->certificados_asociados_a($parametros['id_elemento']);
			foreach ($certificados as $certificado) {
				$mensaje_certificados .= "(" . $certificado['codigo'] . ") " . $certificado['nombre'] . ",\n";
			}
			if ($mensaje_certificados <> '') {
				$mensaje_certificados = substr($mensaje_certificados, 0, strlen($mensaje_certificados) - 2);
			}
		}
		$salida = array('id_elemento' => $parametros['id_elemento'],
						'id_elemento_padre' => $parametros['id_elemento_padre'],
						'nombre' => $parametros['nombre'],
						'mensaje_correlativas' => $mensaje_correlativas,
						'mensaje_certificados' => $mensaje_certificados);
		$respuesta->set($salida);
	}

}
?>