<?php

class guarani_logs_resultados extends guarani_logs
{

	protected $fila;
	protected $reporte_pedido;
	protected $reportes = array();

	function obtener_resultados()
	{
		$nuevo = array();
		foreach ($this->array_resultado as $dato) {
			$nuevo[] = $dato;
		}
		$resultado['mensajes'] = $nuevo;
		return $resultado;
	}


	function get($reporte=null, $archivo=null)
	{
		$this->reporte_pedido = $reporte;
		return parent::get(null, $archivo);
	}


	/**
	* handlerDatos
	* Esta funcion se invoca al encontrar una etiqueta
	*/
	function handlerInicial($parser, $nombre, $atributos)
	{
		$this->etiqueta_actual = $nombre;
		if ($this->etiqueta_actual == self::etiqueta_fila) {
			$this->reportes = explode(',', $atributos['REPORTES']);
		}
		/*foreach ($atributos as $clave => $valor) {
			// Manejo de Atributos
		}*/
	}

	/**
	* handlerDatos
	* Esta funcion se invoca al encontrar datos "el contenido que hay entre etiquetas"
	* <etiqueta>contenido</etiqueta>
	*/
	function handlerDatos($parser, $dato)
	{
                // Si etiqueta_actual es null salgo
                if (is_null($this->etiqueta_actual)) {
                    return false;
                }

		if ($this->etiqueta_actual != guarani_logs_estados::etiqueta_estado && (in_array($this->reporte_pedido, $this->reportes))) {
			if (!empty(trim($dato))) {
				// Esto se hace porque cuando el XML parser encuentra el primer caracter especial (como acento) hace dos llamadas.
				// Por lo tanto se deben concatenar los dos valores.
				if (!isset($this->array_resultado[$this->fila][strtolower($this->etiqueta_actual)])) {
					$this->array_resultado[$this->fila][strtolower($this->etiqueta_actual)] = '';
				}
				$this->array_resultado[$this->fila][strtolower($this->etiqueta_actual)] = $this->array_resultado[$this->fila][strtolower($this->etiqueta_actual)].$dato;
			} else {
				// Si no encuentra dato setea en vacío.
				if (!isset($this->array_resultado[$this->fila][strtolower($this->etiqueta_actual)])) {
					$this->array_resultado[$this->fila][strtolower($this->etiqueta_actual)] = '';
				}
			}
		}
	}

	/**
	* handlerFinal
	* Esta funcion se invoca al cierre de una etiqueta
	*/
	function handlerFinal($parser, $nombre)
	{
		if ($nombre == guarani_logs::etiqueta_fila) {
			$this->fila = $this->fila + 1;
			$this->reportes = array();
		}
		
	}

	///==============================================

	/**
	 * Acumula en $this->mensajes, el $mensaje que se utilizaran en los reportes $reportes
	 * 
	 * @param <type> $mensaje
	 * @param <type> $reportes 
	 */


	function set($mensaje, $reportes = null)
	{
		$array['mensaje'] = $mensaje;
		$array['reportes'] = $reportes;
		$this->mensajes[] = $array;
	}

	function guardar()
	{
		if (isset($this->mensajes) && is_array($this->mensajes))
		{
			foreach ($this->mensajes as $mensaje) {
				// Se abre el archivo XML
				$etiqueta = '';
				if (!file_exists($this->archivo)) {
					toba::logger()->info('Guarani LOGS: No se puede abrir el archivo ' .$this->archivo);
					return false;
				}
				$fich = fopen($this->archivo, 'a');

				$etiqueta .= "\t<". guarani_logs::etiqueta_fila ." reportes='" . implode(',', $mensaje['reportes']) ."'>\n";
				//fwrite($fich, $etiqueta);
				foreach ($mensaje['mensaje'] as $tag => $valor) {
					$valor_cod = guarani_texto::parsear_caracteres_xml(trim($valor));
					$etiqueta .= "\t\t<$tag>$valor_cod</$tag>\n";
					//fwrite($fich, $etiqueta);
				}
				$etiqueta .= "\t</". guarani_logs::etiqueta_fila .">\n";
				fwrite($fich, $etiqueta);
				fclose($fich);
			}
			unset($this->mensajes);
		}
	}
}