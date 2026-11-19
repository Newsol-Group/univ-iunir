<?php
class cuadro_estadistica extends guarani_pers_ei_cuadro
{
	//---- Configuracion de cortes de control -------------------------------------------	
	/**
		<b>Totales por actividad</b>
	*/
	function sumarizar_cc__actividad__propia($filas)
	{
		$datos = array();
		foreach ($filas as $fila) {
			if (!isset($datos[$this->datos[$fila]['cond_regularidad_desc']]))
				$datos[$this->datos[$fila]['cond_regularidad_desc']] = $this->datos[$fila]['cantidad'];
			else
				$datos[$this->datos[$fila]['cond_regularidad_desc']] += $this->datos[$fila]['cantidad'];
		}
		
		//Armo el string para mostrar el total
		$string = '';
		foreach (array_keys($datos) as $key) {
			$string .= $key. ': ' .$datos[$key].' ';
		}
		return $string;
	}

}
?>