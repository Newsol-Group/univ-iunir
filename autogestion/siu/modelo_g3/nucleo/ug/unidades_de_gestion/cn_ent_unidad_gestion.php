<?php
/**
 *
 *
 * @package 	Unidades de Gestión
 * @subpackage 	Unidades de Gestión
 * 
 */
class cn_ent_unidad_gestion extends cn_entidad
{
	function pre_sincronizar()
	{
		$datos = $this->get_datos_unidades_gestion();
		// Se valida que se haya seleccionado un dominio.
		if (empty($datos['dominio'])) {
			$mje = guarani_mensajes::get('800_no_seleccion_unica', array('el dominio de aplicación'));
			throw guarani::error($mje);
		}
		
		// Se valida, en función del dominio elegido, que haya al menos una selección.
		switch ($datos['dominio']) {
			case unidades_gestion::dominio_ra :
				$datos = $this->get_datos_responsables_academicas_ug();
				if (empty($datos)) {
					$etiqueta = guarani_etiquetas::get_tabla('sga_responsables_academicas');
					$mje = guarani_mensajes::get('800_no_seleccion', array($etiqueta->get('una')));
					throw guarani::error($mje);
				}
				break;
			case unidades_gestion::dominio_tipo_prop :
				$datos = $this->get_datos_tipos_propuestas();
				if (empty($datos)) {
					$etiqueta = guarani_etiquetas::get_tabla('sga_propuestas_tipos');
					$mje = guarani_mensajes::get('800_no_seleccion', array($etiqueta->get('un')));
					throw guarani::error($mje);
				}
				break;
			case unidades_gestion::dominio_prop :
				$datos = $this->get_datos_propuestas();
				if (empty($datos)) {
					$etiqueta = guarani_etiquetas::get_tabla('sga_propuestas');
					$mje = guarani_mensajes::get('800_no_seleccion', array($etiqueta->get('una')));
					throw guarani::error($mje);
				}
				break;
		}
	}
	
	function resetear_relacion()
	{
		$this->tabla('ug_resp_academicas')->eliminar_filas();
		$this->tabla('ug_tipos_propuestas')->eliminar_filas();
		$this->tabla('ug_propuestas')->eliminar_filas();
	}
	
	//-----------------------------------------------------------------------------------
	//---- sga_unidades_gestion
	//-----------------------------------------------------------------------------------
	
	function set_datos_unidades_gestion($datos, $validar_nombre=true)
	{
		if ($validar_nombre) {
			$this->validar_datos_unidades_gestion($datos);
		}
		$this->tabla('unidades_gestion')->set($datos);
	}
	
	function get_datos_unidades_gestion()
	{
		return $this->tabla('unidades_gestion')->get();
	}
	
	//-----------------------------------------------------------------------------------
	//---- sga_ug_espacios
	//-----------------------------------------------------------------------------------
	
	function get_datos_dependencias()
	{
		return $this->tabla('ug_espacios')->get_filas();
	}
	
	function agregar_dependencia_unidad_gestion($datos)
	{
		$this->tabla('ug_espacios')->nueva_fila($datos);
	}
	
	function eliminar_dependencia($id_fila)
	{
		$this->tabla('ug_espacios')->eliminar_fila($id_fila);	
	}

	function ug_tiene_espacios()
	{
		return ($this->tabla('ug_espacios')->get_cantidad_filas() > 0);
	}
	
	//-----------------------------------------------------------------------------------
	//---- sga_ug_responsables_academicas
	//-----------------------------------------------------------------------------------
	
	function get_datos_responsables_academicas_ug()
	{
		return $this->tabla('ug_resp_academicas')->get_filas();
	}

	function agregar_responsables_academica($datos)
	{
		$this->tabla('ug_resp_academicas')->eliminar_filas();
		if (!empty($datos)) {
			foreach ($datos as $seleccion) {
				$this->tabla('ug_resp_academicas')->nueva_fila($seleccion);	
			}
		}
	}

	//-----------------------------------------------------------------------------------
	//---- sga_ug_tipo_propuestas
	//-----------------------------------------------------------------------------------
	
	function get_datos_tipos_propuestas()
	{
		return $this->tabla('ug_tipos_propuestas')->get_filas();
	}

	function agregar_tipos_propuestas($datos)
	{
		$this->tabla('ug_tipos_propuestas')->eliminar_filas();
		if (!empty($datos)) {
			foreach ($datos as $seleccion) {
				$this->tabla('ug_tipos_propuestas')->nueva_fila($seleccion);	
			}
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- sga_ug_propuestas
	//-----------------------------------------------------------------------------------
	
	function get_datos_propuestas()
	{
		return $this->tabla('ug_propuestas')->get_filas();
	}

	function agregar_propuestas($datos)
	{
		$this->tabla('ug_propuestas')->eliminar_filas();
		if (!empty($datos)) {
			foreach ($datos as $seleccion) {
				$this->tabla('ug_propuestas')->nueva_fila($seleccion);	
			}
		}
	}	

	//-----------------------------------------------------------------------------------
	//---- Validaciones
	//-----------------------------------------------------------------------------------
	
	function validar_datos_unidades_gestion($datos)
	{
		$datos['unidad_gestion'] = $this->tabla('unidades_gestion')->get_columna('unidad_gestion');
		$this->validar_nombre_unidad_gestion($datos['nombre'], $datos['unidad_gestion']);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	/**
 	  *  Determinar si el nombre de la Unidad de Gestion ya existe.
      *	 @param string 	nombre	es el nombre que debe verificarse si ya existe.
      *	 @param integer ug		es el id de la Ug que se excluye del control (para el caso de la edición de la entidad).
    */
	function validar_nombre_unidad_gestion($nombre, $ug) 
	{
		if (!toba::consulta_php('co_unidades_gestion')->validar_nombre_unidad_gestion($nombre, $ug)) {
			$this->set_error('dato_existente', array('nombre','unidad de gestión'));
		}
	}

}

?>