<?php
/**
 *	Representa una cátedra
 *
 * @package Docentes
 * @subpackage Actualizaciones
 */
class cn_ent_catedra extends cn_entidad
{
	function cargar($id)
	{
		$this->tabla('catedras_actividades')->persistidor()->activar_perfil_de_datos();
		$this->tabla('catedras_docentes')->persistidor()->activar_perfil_de_datos();
		parent::cargar($id);
	}

	//------Cátedras------//
	
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('catedras')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('catedras')->get();
	}
	
	function tiene_catedra_equivalente($catedra)
	{
		return toba::consulta_php('co_catedras_equivalentes')->tiene_catedra_equivalente($catedra);
	}
	
	function nombre_repetido()
	{
		$nombre = $this->tabla('catedras')->get_columna('nombre');
		$catedra = $this->tabla('catedras')->get_columna('catedra');
		return toba::consulta_php('co_catedras')->nombre_repetido($nombre, $catedra);
	}
	
	//------Docentes de Cátedras------//
	
	function set_datos_docentes($datos)
	{
		$this->tabla('catedras_docentes')->procesar_filas($datos);
	}
	
	function get_datos_docentes_disp($clave=null)
	{
		if (is_null($clave)) {
			$datos = $this->tabla('catedras_docentes')->get_filas();
		} else {
			$datos = $this->tabla('catedras_docentes')->get_fila($clave);			
		}
		return $datos;
	}
	
	function get_datos_docentes_asoc()
	{
		return $this->tabla('catedras_docentes')->get_filas();
	}
	
	function agregar_fila_docente_asociado($seleccion)
	{		
		$this->tabla('catedras_docentes')->nueva_fila($seleccion);
	}
	
	function eliminar_fila_docente_asociado($seleccion)
	{
		$this->tabla('catedras_docentes')->eliminar_fila($seleccion);
	}
	
	function eliminar_datos_docentes($clave)
	{
		return $this->tabla('catedras_docentes')->eliminar_fila($clave);		
	}	
	
	//------Actividades de Cátedras------//
	
	function set_datos_actividades($datos)
	{
		$this->tabla('catedras_actividades')->procesar_filas($datos);
	}
	
	function get_datos_actividades_asoc()
	{
		return rs_ordenar_por_columna($this->tabla('catedras_actividades')->get_filas(), 'nombre_actividad');
	}
	
	function agregar_fila_actividad_asociada($seleccion)
	{
		$this->tabla('catedras_actividades')->nueva_fila($seleccion);
	}
	
	function eliminar_fila_actividad_asociada($seleccion)
	{
		// Validación de eliminación
		$datos_catedra = $this->get_datos();		
		if (isset($datos_catedra['catedra'])) {
			$actividades = $this->get_datos_actividades_asoc();			
			$i = 0;		
			while (($i < guarani_arreglos::count($actividades)) && ($actividades[$i]['x_dbr_clave'] != $seleccion)) {
				$i++;
			}
			$actividad = $actividades[$i];				
			$this->validar_eliminar_actividad($datos_catedra['catedra'], $actividad['elemento']);
			if ($this->hay_errores()) {
			 	throw guarani::error($this->errores);
			} 	
		}
		
		// Si superó la validación, se elimina
		$this->tabla('catedras_actividades')->eliminar_fila($seleccion);	

	}
	
	function set_datos_activ($datos)
	{		
		$this->tabla('catedras_actividades')->nueva_fila($datos);
	}
	
	function get_datos_activ()
	{
		return $this->tabla('catedras_actividades')->get_filas();
	}	

	function eliminar_datos_activ($clave)
	{
		return $this->tabla('catedras_actividades')->eliminar_fila($clave);
	}

	//------Validaciones------//
	
	function validar_datos($datos)
	{
		$datos['catedra'] = $this->tabla('catedras')->get_columna('catedra');		
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}		
	}	
	
	/**
	 * Determina si una actividad se puede desasociar de una cátedra.
	 *
	 */
	function validar_eliminar_actividad($catedra, $elemento) 
	{
		if (toba::consulta_php('co_comisiones')->existe_comision_asociada_catedra($catedra, $elemento)) {
			$this->set_error('800_dato_no_eliminable', array('Actividad', 'ya existen Comisiones para esta cátedra y actividad'));
		}
		
		if (toba::consulta_php('co_mesas_de_examen')->existe_mesa_asociada_catedra($catedra, $elemento)) {
			$this->set_error('800_dato_no_eliminable', array('Actividad', 'ya existen Mesas de Examen para esta cátedra y actividad'));
		}
	}
	
	/**
	 * Determina si se puede eliminar una cátedra
	 * Para ello, no tiene que estar definida para una Comisión o Mesa de Examen.
	 */
	function validar_eliminacion()
	{
		$datos_catedra = $this->get_datos();		
		if (toba::consulta_php('co_comisiones')->existe_comision_asociada_catedra($datos_catedra['catedra']) || toba::consulta_php('co_mesas_de_examen')->existe_mesa_asociada_catedra($datos_catedra['catedra'])) {
			return false;
		} else {
			return true;
		}
	}
	
}
?>