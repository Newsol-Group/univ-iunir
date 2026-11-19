<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage Certificados
 */
class cn_ent_certificado extends cn_entidad
{
	function get_datos($tabla=null)
	{
		return $this->tabla('certificados')->get();
	}
	
	function set_datos($datos, $tabla = null, $validar=true) 
	{
		$this->validar_datos($datos);
		$this->tabla('certificados')->set($datos);
	}

	function validar_datos($datos) 
	{
		$certificado = $this->tabla('certificados')->get_columna('certificado');
		$this->validar_codigo_certificado($datos['codigo'], $certificado);
		if($this->hay_errores()){
			throw new guarani_error($this->get_mensaje_error());
		} 
	}
	
	function validar_codigo_certificado($codigo,$certificado=null) 
	{
		if (! toba::consulta_php('co_certificados')->validar_codigo_certificado($codigo,$certificado)){
			$this->set_error('dato_duplicado',array('código','Certificado'));
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- alcances ---------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_datos_alcances($condiciones=null)
	{
		return $this->tabla('alcances')->get_filas($condiciones);
	}

	function hay_cambios_alcances()
	{
		return $this->tabla('alcances')->hay_cambios();
	}
	
	function eliminar_fila_alcance_asignada($seleccion)
	{
		$fila = current($this->get_datos_alcances($seleccion));
		if (!empty($fila)) {
			$this->tabla('alcances')->eliminar_fila($fila['x_dbr_clave']);	
		}
	}
	
	function agregar_fila_alcance($fila)
	{
		$this->tabla('alcances')->nueva_fila($fila);
	}
	
	//-----------------------------------------------------------------------------------
	//---- responsables académicas ------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function get_datos_ra()
	{
        return $this->tabla('resp_academicas')->get_filas();
	}

	function set_datos_ra($datos)
	{
		$ras = $this->tabla('resp_academicas')->get_filas();
		$ras_aplanadas = aplanar_matriz($ras, 'responsable_academica');
		$ras_a_borrar = array_diff($ras_aplanadas, $datos);
		$ras_a_insertar = array_diff($datos, $ras_aplanadas);

		$ras_asociativas = rs_convertir_asociativo($ras, array('responsable_academica'), apex_datos_clave_fila);
		foreach ($ras_a_borrar as $ra) {
			$this->tabla('resp_academicas')->eliminar_fila($ras_asociativas[$ra]);
		}

		foreach ($ras_a_insertar as $ra) {
			$this->tabla('resp_academicas')->nueva_fila(array('responsable_academica' => $ra));
		}
	}
	
}
?>