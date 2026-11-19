<?php
class cuadro_hist_academica extends guarani_pers_ei_cuadro
{
	protected function ordenar()
	{
		/*
		 * Si el ordenamiento es solo por la columna nombre actividad se cambia 
		 * para que se haga por la otra columna que luego se elimina
		 */
		if ($this->hay_ordenamiento() && $this->_orden_columna == 'actividad_codigo_y_nombre') {
			$this->_orden_columna = 'actividad_nombre';
		} elseif ($this->hay_ordenamiento_multiple() && in_array('actividad_codigo_y_nombre', $this->_columnas_orden_mul)) {
			$clave = array_search('actividad_codigo_y_nombre', $this->_columnas_orden_mul);
			$this->_columnas_orden_mul[$clave] = 'actividad_nombre';
			$this->_sentido_orden_mul['actividad_nombre'] = $this->_sentido_orden_mul['actividad_codigo_y_nombre'];
		}
		parent::ordenar();		
	}
}

?>