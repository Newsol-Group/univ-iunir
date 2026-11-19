<?php
/**
 *
 *
 * @package 	Propuestas
 * @subpackage 	Certificados
 * 
 */
class cn_ent_escala_cumplimiento extends cn_entidad
{
	//------------------------------------------------------------------------
	//-- Escala de Cumplimiento
	//------------------------------------------------------------------------

	function get_datos($tabla=null)
	{
		$datos = $this->tabla('escala_cumplimiento')->get();
		$campos = $this->get_campos_disciplinares();
		$datos['campo_disciplinar'] = aplanar_matriz($campos, 'campo_disciplinar');
		return $datos;
	}

	function set_datos($datos, $tabla = null, $validar=true)
	{
		$campos_disciplinares = $datos['campo_disciplinar'];
		unset($datos['campo_disciplinar']);

		$clave = $this->tabla('escala_cumplimiento')->get_columna('escala_cumplimiento');
		$this->validar_nombre($datos['nombre'], $clave);
		
		$this->tabla('escala_cumplimiento')->set($datos);
		$this->set_campos_disciplinares($campos_disciplinares);
	}

	function validar_nombre($nombre, $clave)
	{
		if (! toba::consulta_php('co_certificados')->validar_nombre_escala_cumplimiento($nombre, $clave)) {
			$msj = guarani_mensajes::get('dato_duplicado', array('nombre', 'Escala de Cumplimiento'));
			throw guarani::error($msj);
		}
	}
	
	//------------------------------------------------------------------------
	//-- Campos de Escala de Cumplimiento
	//------------------------------------------------------------------------
	
	function get_campos_disciplinares()
	{
		return $this->tabla('escalas_cumplimiento_campos')->get_filas();
	}
	
	function set_campos_disciplinares($datos)
	{
		$campos_existentes = $this->get_campos_disciplinares();
		$campos_aplanados = aplanar_matriz($campos_existentes, 'campo_disciplinar');
		$campos_a_borrar = array_diff($campos_aplanados, $datos);
		$campos_a_insertar = array_diff($datos, $campos_aplanados);
		
		$campos_asociativos = rs_convertir_asociativo($campos_existentes, array('campo_disciplinar'), apex_datos_clave_fila);
		foreach ($campos_a_borrar as $campo) {
			$this->tabla('escalas_cumplimiento_campos')->eliminar_fila($campos_asociativos[$campo]);
		}
		
		foreach ($campos_a_insertar as $campo) {
			$this->tabla('escalas_cumplimiento_campos')->nueva_fila(array('campo_disciplinar' => $campo));
		}		
	}

	//------------------------------------------------------------------------
	//-- Niveles de Escala de Cumplimiento
	//------------------------------------------------------------------------

	function get_datos_niveles()
	{
		$datos = $this->tabla('escalas_cumplimiento_det')->get_filas();
		return rs_ordenar_por_columna($datos, 'orden');
	}

	function set_datos_nivel($datos)
	{
		if ($this->hay_nivel_seleccionado()) {
			$this->validar_datos_nivel($datos);
			$this->tabla('escalas_cumplimiento_det')->set($datos);
		}
	}

	function seleccionar_nivel($id_fila)
	{
		$this->tabla('escalas_cumplimiento_det')->set_cursor($id_fila);
	}

	function deseleccionar_nivel()
	{
		$this->tabla('escalas_cumplimiento_det')->resetear_cursor();
	}

	function get_datos_nivel_seleccionado()
	{
		if ($this->hay_nivel_seleccionado()) {
			return $this->tabla('escalas_cumplimiento_det')->get();
		}
	}

	function hay_nivel_seleccionado()
	{
		return $this->tabla('escalas_cumplimiento_det')->hay_cursor();
	}

	function eliminar_nivel_seleccionado()
	{
		if ($this->hay_nivel_seleccionado()) {
			$id_fila = $this->tabla('escalas_cumplimiento_det')->get_cursor();
			$this->tabla('escalas_cumplimiento_det')->eliminar_fila($id_fila);
		}
	}

	function nuevo_nivel($datos)
	{
		$this->validar_datos_nivel($datos);
		$id = $this->tabla('escalas_cumplimiento_det')->nueva_fila($datos);
		$this->seleccionar_nivel($id);
	}

	function validar_datos_nivel($datos)
	{
		$this->validar_orden_repetido($datos);
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	/**
	 * Valida que no se ingrese más de una vez el mismo orden.
	 *
	 * @param array $datos Datos del nivel a validar.
	 */
	function validar_orden_repetido($datos)
	{
		$id_fila = $this->tabla('escalas_cumplimiento_det')->get_cursor();
		$niveles = $this->tabla('escalas_cumplimiento_det')->get_filas();
		foreach ($niveles as $nivel) {
			if ($nivel['orden'] == $datos['orden'] && (is_null($id_fila) || $id_fila != $nivel[apex_datos_clave_fila])) {
				$this->set_error('800PRO_orden_repetido');
			}
		}
	}
}
?>