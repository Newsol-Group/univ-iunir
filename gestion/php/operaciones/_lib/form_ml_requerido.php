<?php
/**
 * El análisis de cambios se realiza en base al bit 'requerido'.
 * Se agrega un método para setear la columna por la cual se quieren ordenar las filas.
 * Se agrega un método para setear los datos posibles, que pueden ser seleccionados.
 */

class form_ml_requerido extends guarani_pers_ei_formulario_ml
{
	protected $columna_ordenamiento;
	protected $datos_posibles = array();

	function set_columna_ordenamiento($columna)
	{
		$this->columna_ordenamiento = $columna;
	}

	function set_datos_posibles($datos)
	{
		$this->datos_posibles = $datos;
	}

	function get_datos_posibles()
	{
		return $this->datos_posibles;
	}
	
	function analizar_diferencias($datos)
	{
		foreach ($datos as $id_fila => $valor) {
			if ($valor['requerido'] && !in_array($id_fila, $this->_filas_recibidas)) {
				$datos[$id_fila][apex_ei_analisis_fila] = 'A';
			} elseif (!$valor['requerido'] && in_array($id_fila, $this->_filas_recibidas)) {
				$datos[$id_fila][apex_ei_analisis_fila] = 'B';
			} elseif ($valor['requerido'] && in_array($id_fila, $this->_filas_recibidas)) {
				$datos[$id_fila][apex_ei_analisis_fila] = 'M';
			} else {
				unset($datos[$id_fila]);
			}
		}
		return $datos;
	}

	function set_datos($datos, $set_cargado=true)
	{
		// Los datos que se setean al ML son sólo aquellos marcados como "requeridos".
		foreach ($datos as $clave => $valor) {
			/**
			 * Datos que vienen directo del Dt, sin el campo requerido, se setea dicho campo en 1. 
			 * Datos temporales, si viene con el campo requerido seteado en 0, no se considera esa fila,
			 * si viene con requerido en 1, sí (se dejan los valores iguales).
			 */
			if (!isset($datos[$clave]['requerido'])) {
				$datos[$clave]['requerido'] = 1; 
			}
		}
		parent::set_datos($datos, $set_cargado);
	}

	function generar_formulario_cuerpo()
	{
		// Unificación de los datos "actuales" y "posibles".
		$datos = $this->_datos;
		foreach ($this->datos_posibles as $dato) {
			$datos[] = $dato;
		}

		// Ordenamiento por una columna específica.
		$ordenes = array();
		$maximo = 0;
		foreach ($datos as $id => $dato) {
			$orden = strtoupper($dato[$this->columna_ordenamiento]);
			if ($orden > $maximo) {
				$maximo = $orden;
			}
			$ordenes[$id] = $orden;
		}
		asort($ordenes);
		$ordenes = array_keys($ordenes);

		// HTML de las filas.
		echo "<tbody>";
		$this->_filas_enviadas = array();
		foreach ($ordenes as $fila) {
			$dato = $datos[$fila];

			$this->_filas_enviadas[] = $fila;

			$this->cargar_registro_a_ef($fila, $dato);
			$this->_carga_opciones_ef->cargar();

			$callback_configurar_fila_contenedor = 'conf_fila__' . $this->_parametros['id'];
			if (method_exists($this->controlador, $callback_configurar_fila_contenedor)) {
				$this->controlador->$callback_configurar_fila_contenedor($fila);
			}

			echo "\n<!-- FILA $fila -->\n\n";
			$this->generar_layout_fila($fila);
			echo "</tr>\n";
		}
		echo "</tbody>\n";
	}
}
?>