<?php 
/**
 *
 *
 * @package 	Unidades de Gestión
 * @subpackage 	Documentos
 * 
 */
class cn_ent_documento extends cn_entidad
{
	
	//-----------------------------------------------------------------------------------
	//---- documentos -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('documentos')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('documentos')->get();
	}
	
	function validar_datos($datos)
	{
		$this->validar_fechas($datos);
		$this->validar_docs($datos);
		
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}
	
	function validar_fechas($datos)
	{
		$fecha = $datos['fecha'];
		if (guarani_fecha::es_mayor($fecha, guarani_fecha::get_hoy())) {
			$this->set_error('800_fecha_desde_erronea');
		}
	}
	
	function validar_docs($datos)
	{
		$nro = $datos['documento_numero'];
		$tipo = $datos['documento_tipo'];
		$fecha = $datos['fecha'];
		$datos_doc = $this->tabla('documentos')->get_columna('documento');
		
		$val = toba::consulta_php('co_documentos')->validar_combinacion_nrodoc_tipo_numero_fecha($nro, $tipo, $datos_doc,$fecha);

		if (!$val) {
			$this->set_error('800_dato_seteado', array('documento','nro, tipo y fecha elegidos'));				
		}
	}
	
	function validar_docs_rel()
	{
		$datos_doc = $this->tabla('documentos')->get_columna('documento');
		
		//Si es un alta, todo ok
		if (is_null($datos_doc)) {
			return true;	
		}
		//Valido que no este relacionado a otra tabla
		$val = toba::consulta_php('co_documentos')->validar_docs_rel($datos_doc);
		if (!$val) {
			//$this->set_error('800_dato_no_modif_elim', array('documento',"esta siendo referenciado desde otra operación"));
			return false;
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- documentos_digital -----------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function set_datos_doc($datos)
	{
		if (isset($datos['nombre_archivo'])) {
			$handle = fopen($datos['nombre_archivo']['tmp_name'], 'rb');

			$archivo = array();
			$archivo['nombre_archivo'] = $datos['nombre_archivo']['name'];
			$archivo['fecha_upload'] = 'now()';
			
			$this->tabla('documentos_digital')->set($archivo);
			$this->tabla('documentos_digital')->set_blob('archivo', $handle);
		}
	}
 	
	function get_datos_doc()
	{
		$datos = $this->tabla('documentos_digital')->get();
		if (!empty ($datos)) {
			$datos['blob'] = $this->tabla('documentos_digital')->get_blob('archivo');
		}
		return $datos;
	}

	function eliminar_datos_doc()
	{
		// La tabla tiene una única fila.
		$this->tabla('documentos_digital')->eliminar_filas();	
	}

	//------------------------------------------------------------------------
	//-- Responsables Académicas
	//------------------------------------------------------------------------

	function get_datos_responsables_academicas()
	{
        return $this->tabla('documentos_ra')->get_filas();
	}

	function set_datos_responsables_academicas($datos)
	{
		$ras = $this->tabla('documentos_ra')->get_filas();
		$ras_aplanadas = aplanar_matriz($ras, 'responsable_academica');
		$ras_a_borrar = array_diff($ras_aplanadas, $datos);
		$ras_a_insertar = array_diff($datos, $ras_aplanadas);

		$ras_asociativas = rs_convertir_asociativo($ras, array('responsable_academica'), apex_datos_clave_fila);
		foreach ($ras_a_borrar as $ra) {
			$this->tabla('documentos_ra')->eliminar_fila($ras_asociativas[$ra]);
		}

		foreach ($ras_a_insertar as $ra) {
			$this->tabla('documentos_ra')->nueva_fila(array('responsable_academica' => $ra));
		}
	}
}
?>