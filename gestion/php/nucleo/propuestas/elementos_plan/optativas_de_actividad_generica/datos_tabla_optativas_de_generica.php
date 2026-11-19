<?php
/**
 * Manipulación del componente de persistencia Datos Tabla.
 * @package Componentes
 * @subpackage Persistencia
 */

class datos_tabla_optativas_de_generica extends guarani_pers_datos_tabla
{
	// Se redefine el eliminar fila de Toba, para que no elimine las filas hijas en todos los casos.
	// Sólo se modifica la llamada al evt__eliminacion_fila_padre de la relación con las tablas hijas (optativas).
	function eliminar_fila($id)
	{
		$id = $this->normalizar_id($id);
		if (!$this->existe_fila($id)) {
			$mensaje = $this->get_txt() . " ELIMINAR. No existe un registro con el INDICE indicado ($id)";
			toba::logger()->error($mensaje);
			throw new toba_error_def($mensaje);
		}
		if ( $this->get_cursor() == $id ) { 
 			$this->resetear_cursor();        
		}
 		
		//Se notifica la eliminación a las relaciones
		$this->eliminacion_fila_padre($id);
		
		foreach ( $this->_relaciones_con_padres as $rel) {
			$rel->evt__eliminacion_fila_hijo($id);			
		}
		if($this->_cambios[$id]['estado']=="i"){
			unset($this->_cambios[$id]);
			unset($this->_datos[$id]);
		}else{
			$this->registrar_cambio($id,"d");
		}
		return $id;
	}

	/**
	 * Para esta tabla se redefine el método que elimina las filas hijas en cascada con la eliminación de una fila padre, 
	 * ya que en las tablas temporales de atributos de materias, se definió que las materias genéricas compartan los 
	 * atributos de las optativas que las componen. Por lo tanto, al eliminar la asociación de una optativa a una genérica, 
	 * la misma debe ser eliminada de sga_elementos_comp_aux, pero no siempre de los registros sga_elementos_plan_aux que
	 * contienen sus atributos para cada plan versión. Los registros en esta última tabla se eliminan sólo en caso de no 
	 * estar asociados actualmente a ninguna otra materia genérica.
	 * Las modalidades asociadas a cada elemento plan correspondiente a la optativa, sí se eliminaran de la forma usual, 
	 * es decir, si su padre se elimina.
	 */
	function eliminacion_fila_padre($id)
	{
		foreach ($this->_relaciones_con_hijos as $rel) {
			//¿Hay filas hijos?
			$mapeo_filas = $rel->get_mapeo_filas();
			if (isset($mapeo_filas[$id]) && !empty($mapeo_filas[$id])) {
				//Borra las filas en cascada
				foreach ($mapeo_filas[$id] as $hijo) {
					// Se verifica si debe eliminarse la fila hija de optativas elementos plan en consecuencia
					if (!$this->controlador()->controlador()->optativa_en_otra_generica($hijo)) {
						$tabla_hijo = $rel->tabla_hijo();
						$tabla_hijo->eliminar_fila($hijo);
					}
				}
				//Borra el mapeo
				unset($mapeo_filas[$id]);			
			}
		}
	}
}
?>