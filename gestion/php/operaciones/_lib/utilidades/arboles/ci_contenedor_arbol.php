<?php 
class ci_contenedor_arbol extends guarani_pers_ci
{
	protected $separador_accion = '%-%';
	protected $separador_accion_param = '%_%';
	protected $s__nodos_seleccionados = array();
	protected $s__modo_asociar = false;
	protected $s__nivel_apertura = 7;
	protected $s__eventos_enviados=array();

	function conf()
	{
	}

	//-------------------------------------------------------------------------------
	//-- Generacion grafica del arbol
	//-------------------------------------------------------------------------------

	function conf__arbol(guarani_ei_arbol $arbol)
	{
		$arbol->set_ancho_nombres(25);
		$arbol->set_nivel_apertura($this->s__nivel_apertura);
		$constructor = $this->controlador->construir_arbol();
		$constructor->set_objeto_js($this->get_objeto_js());
		$nodos = $constructor->get_nodos();
		$this->disparar_configuracion_nodos($nodos);
		$this->s__eventos_enviados = $constructor->get_eventos_activos();
		$arbol->set_datos($nodos);
		$arbol->mostrar_barra(true);
		$arbol->mostrar_boton_editar(false);
		//$arbol->set_mostrar_filtro_rapido(true);
	}
	
	protected function disparar_configuracion_nodos($nodos)
	{
		foreach ($nodos as $nodo) {
			$this->configurar_nodo($nodo);
			if( ! $nodo->es_hoja() ) {
				$this->disparar_configuracion_nodos($nodo->get_hijos());
			}
		}
	}
	
	function configurar_nodo($nodo)
	{
		if ($this->nodo_esta_seleccionado($nodo->get_nivel()->get_id(),$nodo->get_id())) {
			$nodo->set_clase_css_li('ei-arbol-nodo-seleccionado');
		}
	}

	function validar($nivel, $evento, $id_nodo)
	{
		return isset($this->s__eventos_enviados[$nivel][$id_nodo][$evento]);
	}

	//-------------------------------------------------------------------------------
	//-- Utilidades sobre NODOS
	//-------------------------------------------------------------------------------
	
	/**
	*	Disparo de eventos
	*/
	function extender_objeto_js()
	{
		//if (confirmar_cambios() == false) return;
		//alert(id_nodo);
		echo "
		{$this->objeto_js}.accion_nodo = function (nivel, evento, id_nodo, confirmacion)
		{
			var parametros = 	nivel + '{$this->separador_accion}' +
								evento + '{$this->separador_accion}' +
								id_nodo;
			this.set_evento(new evento_ei('accion_nodo', true, confirmacion, [parametros] ));
		}
		";
	}

	//-------------------------------------------------------------------------------
	//-- Ejecución de eventos de los nodos disparados desde la interfase por js
	//-------------------------------------------------------------------------------

	/**
	*	Decodificacion de eventos
	*/
	function evt__accion_nodo($parametros)
	{
		//-- 1) Desempaqueto los parametros de la ACCION
		$parametros = explode($this->separador_accion, $parametros);
		//ei_arbol($parametros);
		if(guarani_arreglos::count($parametros) != 3) {
			throw guarani::error('La cantidad de parametros de la accion solicitada son incorrectos');
		}
		$nivel = $parametros[0];
		$evento = $parametros[1];
		$nodo_id = $parametros[2];

		//-- 2) Invoco la accion seleccionada
		if ($this->validar($nivel, $evento, $nodo_id)) {//$this->constructor->validar($nivel, $evento, $nodo_id)) {
			$metodo = 'nodo__' . $nivel . '__' . $evento;
			if (method_exists($this->controlador(),$metodo)) {
				$this->disparar_eventos_cis_hermanos();
				$this->controlador->$metodo($nodo_id);
			} else {
				throw new guarani_error('El controlador del CI contenedor del arbol no posee el metodo: ' . $metodo);
			}
			$this->deseleccionar_nodos();
			$this->seleccionar_nodo($nivel,$nodo_id);
		}
	}

	//-------------------------------------------------------------------------------
	//-- Primitivas
	//-------------------------------------------------------------------------------		
	/*
	protected function agregar_nodo($nodo_padre, $subtipo, $nombre)
	{
		//$nuevo_modulo = $this->cn_elementos()->modulo_crear($nodo_padre, $subtipo, $nombre);
		//$this->seleccionar_elemento($nuevo_modulo->get_id(), $nodo_padre);
		//return $nuevo_modulo;
	}
	*/
	function seleccionar_nodo($nivel,$nodo_id)
	{
		if (! $this->nodo_esta_seleccionado($nivel,$nodo_id)) {
			$this->s__nodos_seleccionados[] = $nivel.'__'.$nodo_id;
		}
	}

	function deseleccionar_nodo($nivel,$nodo_id)
	{
		if ($this->nodo_esta_seleccionado($nivel,$nodo_id)) {
			unset($this->s__nodos_seleccionados[$nivel.'__'.$nodo_id]);
		}
	}

	function deseleccionar_nodos()
	{
		$this->s__nodos_seleccionados = array();
	}

	function nodo_esta_seleccionado($nivel,$nodo_id)
	{
		return in_array($nivel.'__'.$nodo_id,$this->s__nodos_seleccionados);
	}

	function disparar_eventos_cis_hermanos()
	{
		foreach ($this->controlador->get_dependencias_clase('toba_ci') as $dep_id) {
			if ($this->controlador->dep($dep_id)->get_id() != $this->get_id()) {
				if ($this->controlador->dependencia_cargada($dep_id)) {
					$this->controlador->dep($dep_id)->disparar_eventos();
					$this->controlador->dep($dep_id)->borrar_memoria();
				}
			}
		}
	}

}
?>