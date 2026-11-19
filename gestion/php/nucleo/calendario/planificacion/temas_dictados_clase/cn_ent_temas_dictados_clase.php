<?php
/**
 * 
 * 
 * @package 	Calendario Académico
 * @subpackage 	Planificación
 *
 */
class cn_ent_temas_dictados_clase extends cn_entidad
{
	function cargar($id)
	{
		$this->tabla('docentes')->persistidor()->activar_perfil_de_datos();
		parent::cargar($id);
	}

	//-----------------------------------------------------------------------------------
	//---- sga_clases -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function get_datos($tabla=null)
	{
		return $this->tabla('clases')->get();
	}
		
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->tabla('clases')->set($datos);	
	}
	
	//-----------------------------------------------------------------------------------
	//---- sga_clases_docentes ----------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function get_datos_docentes_asociados()
	{
		return $this->tabla('docentes')->get_filas();
	}
	
	function set_datos_docentes_asociados($datos)
	{
		$this->tabla('docentes')->procesar_filas($datos);
	}
	
	function eliminar_docente_asociado($id_fila_docente)
	{
		$this->tabla('docentes')->eliminar_fila($id_fila_docente);
	}

	function agregar_docente_asociado($docente)
	{
		$this->tabla('docentes')->nueva_fila($docente);
	}
	
	//-----------------------------------------------------------------------------------
	//---- Validaciones
	//-----------------------------------------------------------------------------------
	
	function pre_sincronizar()
	{
		// Si fueron seleccionados docentes, se debe elegir UN responsable.
		$datos = $this->get_datos_docentes_asociados();
		if (!empty($datos)) {
			$this->validar_datos_docentes($datos);
		}
	}
	
	function validar_datos_docentes($datos)
	{
		$cant = 0;
		foreach ($datos as $dato) {
			if ($dato['responsable']) {
				$cant++;
			}
		}
		if ($cant != 1) {
			$this->set_error('800_unica_seleccion', array('un único responsable'));
			throw guarani::error($this->errores);
		}
	}
}
?>