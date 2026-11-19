<?php
/* 
 * Esta clase es para manejar la navegación de operaciones
 * del tipo wizard. Mantiene los datos que se van seleccionando y
 * la información relativa a los mismos para mostrar en un formulario
 * descriptivo de la ya seleccionado al inicio de cada pantalla.
 */

class ci_navegacion_wizard extends ci_navegacion
{
	//Seleccion
	protected $s__pantalla_actual;
	protected $s__seleccion;
	protected $s__seleccion_info;

	function conf()
	{
		$this->s__pantalla_actual = $this->get_id_pantalla();
		parent::conf();
	}

	/*
	 * Se maneja la ida a la pantalla anterior.
	 */
	function evt__cambiar_tab__anterior()
	{
		//Se limpia la selección e información de la pantalla actual
		$this->eliminar_seleccion_previa($this->s__pantalla_actual);
		$this->eliminar_seleccion_info_previa($this->s__pantalla_actual);
	}

	/*
	 * Se maneja el avance de pantalla.
	 */
	function evt__cambiar_tab__siguiente()
	{
		$metodo_validar = 'validar_'.$this->s__pantalla_actual;
		//Validar antes de irse de la pantalla
		if (method_exists($this, $metodo_validar)) {
			$this->$metodo_validar();
		}
		$pantalla_siguiente = $this->get_id_pantalla_siguiente($this->s__pantalla_actual);
		if ($pantalla_siguiente && !$this->existe_info_pantalla($pantalla_siguiente)) {
			$this->cargar_info($pantalla_siguiente);
		}
	}

	/*
	 * Guarda la selección de lo seleccionado en la pantalla actual.
	 */
	function set_seleccion($seleccion, $pantalla_actual=null)
	{
		if (!$pantalla_actual) {
			$pantalla_actual = $this->s__pantalla_actual;
		}
		if ($seleccion) {
			//Si es selección múltiple
			if (is_array($seleccion[current(array_keys($seleccion))]) && is_numeric(current(array_keys($seleccion)))) {
				foreach ($seleccion as $indx => $sel) {
					foreach ($sel as $clave => $valor) {
						$selec[$clave][] = $valor;
					}
				}
				$seleccion = null;
				foreach ($selec as $clave => $valores) {
					$seleccion[$clave] = $valores;
				}
			}
			if (isset($this->s__seleccion[$pantalla_actual])) {
				$this->s__seleccion[$pantalla_actual] = array_merge($this->s__seleccion[$pantalla_actual], $seleccion);
			} else {
				$this->s__seleccion[$pantalla_actual] = $seleccion;
			}
		}
	}

	/*
	 * Elimina lo seleccionado y su información aledaña.
	 */
	function limpiar_seleccion()
	{
		unset($this->s__seleccion);
		unset($this->s__seleccion_info);
	}

	/**
	 * Se guarda la info que se necesita mostrar en la pantalla actual,
	 * que será la descripción de lo seleccionado en la pantalla anterior.
	 * Las claves deben coincidir con los ef correspondientes del formulario que informa lo seleccionado.
	 * @param <array asociativo> $info
	 */
	function set_seleccion_info($pantalla, $info)
	{
		if (isset($this->s__seleccion_info[$pantalla])) {
			$this->s__seleccion_info[$pantalla] = array_merge($this->s__seleccion_info[$pantalla], $info);
		} else {
			$this->s__seleccion_info[$pantalla] = $info;
		}
	}

	function cargar_info($pantalla_info)
	{
		//ei_arbol( $this->s__seleccion_info , 'sel_info');
	}

	/*
	 * Obtiene la información que se precisa mostrar en una pantalla,
	 * correspondiente a las selecciones realizadas en las pantallas anteriores.
	 */
	function get_seleccion_info($pantalla_actual=null)
	{
		$info = array();
		$pant_anteriores = $this->get_id_pantallas_anteriores($pantalla_actual, true);
		foreach ($pant_anteriores as $pantalla) {
			if (isset($this->s__seleccion_info[$pantalla])) {
				$info = array_merge($info, $this->s__seleccion_info[$pantalla]);
			}
		}
		return $info;
	}

	/*
	 * Devuelve el valor de la información pedida.
	 */
	function get_seleccion_info_valor($dato)
	{
		$info = $this->get_seleccion_info();
		return isset($info[$dato]) ? $info[$dato] : '';
	}

	/*
	 * Determina si ya existe cargada la información de una pantalla en particular.
	 */
	function existe_info_pantalla($pantalla)
	{
		return isset($this->s__seleccion_info[$pantalla]);
	}

	/*
	 * Elimina lo seleccionado previamente.
	 */
	function eliminar_seleccion_previa($pantalla=null, $campo=null)
	{
		if (!$pantalla) {
			$pantalla = $this->s__pantalla_actual;
		}
		if (!is_null($campo)) {
			if (isset($this->s__seleccion[$pantalla][$campo])) {
				unset($this->s__seleccion[$pantalla][$campo]);
			}
		} else {
			unset($this->s__seleccion[$pantalla]);
		}
	}

	/**
	 * Elimina la selección de todas las pantallas (utilizado en casos en que existe un botón Volver 
	 * en la última pantalla del wizard).
	 */
	function eliminar_seleccion_completa()
	{
		unset($this->s__seleccion);
	}
	
	/*
	 * Elimina la información de lo seleccionado previamente.
	 */
	function eliminar_seleccion_info_previa($pantalla=null)
	{
		if (!$pantalla) {
			$pantalla = $this->s__pantalla_actual;
		}
		unset($this->s__seleccion_info[$pantalla]);
	}

	/**
	 * Elimina la información de la selección de todas las pantallas (utilizado en casos en que existe un 
	 * botón Volver en la última pantalla del wizard).
	 */
	function eliminar_seleccion_info_completa()
	{
		unset($this->s__seleccion_info);
	}
	
	/**
	 * Devuelve en un array asociativo los valores correspondientes a la o las claves recibidas.
	 * @param <array/string> $claves Array de claves o una clave en particular
	 */

	function get_seleccion($claves=null)
	{
		$seleccion = array();
		if (isset($claves) && ! is_array($claves)) {
			$claves = array($claves);
		}
		if (!isset($this->s__seleccion)) return null;
		foreach ($this->s__seleccion as $pantalla => $datos) {
			foreach($datos as $dato => $valor) {
				if (!isset($claves) || in_array($dato, $claves, true)) {
					$seleccion[$dato] = $valor;
				}
				if (isset($claves) && guarani_arreglos::count($claves) == guarani_arreglos::count($seleccion)) break;
			}
		}
		return $seleccion;
	}

	/*
	 * Devuelve el valor de un dato seleccionado.
	 */
	function get_seleccion_valor($clave)
	{
		$dato = $this->get_seleccion($clave);
		return (isset($dato) && ! empty($dato)) ? $dato[$clave] : null;
	}

	/*
	 * Determina si falta seleccionar un dato en particular.
	 */
	function falta_seleccionar($dato)
	{
		$valor = $this->get_seleccion_valor($dato);
		return !isset($valor);
	}

	/*
	 * Determina si están seleccionados un o unos datos en particular.
	 */
	function existe_seleccion($datos)
	{
		if (! is_array($datos)) {
			$datos = array($datos);
		}
		foreach ($datos as $dato) {
			if ($this->falta_seleccionar($dato)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Este método determina si existe una selección previa del cuadro recibido
	 * y si es así la selecciona.
	 * @param toba_ei_cuadro $cuadro
	 */
	function marcar_seleccion_previa(toba_ei_cuadro $cuadro)
	{
		if (! $cuadro->datos_cargados()) return;
		if ($cuadro->hay_seleccion()) {
			$cuadro->seleccionar($cuadro->get_clave_seleccionada());
		} else {
			$clave[0] = $cuadro->get_clave_fila_array(0);
			$claves = array_keys($clave[0]);
			$seleccion_previa = $this->get_seleccion($claves);
			if ($seleccion_previa) {
				$cuadro->seleccionar($seleccion_previa);
			} else {//Si tiene una única fila la selecciono
				if (guarani_arreglos::count($cuadro->get_datos()) == 1) {
					$cuadro->seleccionar($clave[0]);
					$this->set_seleccion($clave[0], $this->get_id_pantalla());
				}
			}
		}
	}

	/*
	 * Configura y carga el formulario de cabecera con la información relativa
	 * a lo ya seleccionado. Oculta los campos que no han sido cargados aún.
	 */
	function configurar_formulario_info(toba_ei_formulario $formulario)
	{
		$datos = $this->get_seleccion_info($this->get_id_pantalla_siguiente());
		$formulario->set_datos($datos);
		//Ocultar efs no cargados
		$efs_sin_estado = array();
		foreach ($formulario->get_nombres_ef() as $ef) {
			if (! $formulario->ef($ef)->tiene_estado()) $efs_sin_estado[] = $ef;
		}
		$formulario->desactivar_efs($efs_sin_estado);
	}

	/**
	 * Retorna las claves del conjunto de datos manejados por un cuadro.
	 * @param toba_ei_cuadro $cuadro
	 */
	function get_claves(toba_ei_cuadro $cuadro)
	{
		$columnas_clave = array_keys($cuadro->get_clave_fila_array(0));
		$datos = $cuadro->get_datos();

		$rs = array();
		foreach ($datos as $d) {
			$clave = array();
			foreach ($columnas_clave as $col) {
				$clave[$col] = $d[$col];
			}
			$rs[] = $clave;
		}
		return $rs;
	}

	/**
	 * Retorna verdadero si la clave recibida está entre las claves guardadas como seleccionadas.
	 *
	 * @param string $clave_fila Clave serializada de una fila del cuadro
	 * @param string $dato Sección de datos
	 * @return boolean
	 */
	function es_clave_seleccionada($clave_fila, $dato)
	{
		$claves = array();
		if ($this->existe_seleccion($dato)) {
			$seleccion = current($this->get_seleccion($dato));
			foreach ($seleccion as $valor) {
				$claves[] = implode(apex_qs_separador, $valor);
			}
		}
		return in_array($clave_fila, $claves);
	}

	function es_fila_seleccionada($dep_cuadro, $fila)
	{
		$clave = $this->dep($dep_cuadro)->get_clave_fila_array($fila);
		$claves = array_keys($clave);
		if ($clave && $this->existe_seleccion($claves)) {
			$seleccion = $this->get_seleccion($claves);
			//Si es una selección múltiple
			if (is_array($seleccion[$claves[0]])) {
				foreach ($seleccion[$claves[0]] as $indx => $valor) {
					$clave_a_comparar = array();
					foreach ($claves as $clave_id) {
						$clave_a_comparar[$clave_id] = $seleccion[$clave_id][$indx];
					}
					if ($clave == $clave_a_comparar) {
						return true;
					}
				}
				return false;
			} else {
				return ($clave == $seleccion);
			}
		}
		return false;
	}
}

?>