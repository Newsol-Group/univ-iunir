<?php
/**
 *
 *
 * @package 	Propuestas
 * @subpackage 	Actualizaciones
 * 
 */
class cn_ent_categoria_actividades extends cn_entidad
{
	//-------------------------------------------------
	//---- sga_categorias_actividades
	//-------------------------------------------------
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_nombre($datos);				
		$this->tabla('categoria')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('categoria')->get();
	}	
	
	//-------------------------------------------------
	//---- sga_categorias_actividades_det
	//-------------------------------------------------

	function set_datos_detalle($datos)
	{
		$this->validar_detalles($datos);
		$this->tabla('detalle')->procesar_filas($datos);
		
	}
	
	function get_datos_detalle()
	{
		$datos = $this->tabla('detalle')->get_filas();
		return rs_ordenar_por_columna($datos, 'valor');
	}
	
	//-------------------------------------------------
	//---- Validaciones 
	//-------------------------------------------------
	
	function validar_nombre($datos)
	{
		$nombre = $datos['nombre'];
		$categoria = $this->tabla('categoria')->get_columna('categoria');
		
		if (! toba::consulta_php('co_categorias_actividades')->validar_nombre_categoria($nombre, $categoria)) {
			$this->set_error('dato_duplicado', array('nombre', 'Categoría de Actividades'));
		}		
		
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}
	
	function validar_detalles($datos)
	{
		$this->validar_al_menos_uno($datos);
		$this->validar_duplicados($datos, 'valor');
		
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}
	
	/**
	 * Valida que se haya ingresado al menos un detalle
	 */
	function validar_al_menos_uno($datos)
	{
		if (guarani_arreglos::count($datos) == 0) {
			$this->set_error('dato_faltante', array('Cargar <strong>Categorías</strong>'));
		}
	}
	
	function validar_duplicados($datos, $campo)
	{
		$elementos = array();
		foreach ($datos as $clave => $valor) {
			if ($valor['apex_ei_analisis_fila'] != 'B') {
				if (in_array(strtolower($valor[$campo]), $elementos)) {
					$this->set_error('dato_duplicado', array($campo, 'Categoría de Actividades'));
					return;
				} else {
					$elementos[] = strtolower($valor[$campo]);
				}
			}
		}
	}
}

?>