<?php
/**
 *	Representa una Cátedra Equivalente
 *
 * @package Docentes
 * @subpackage Actualizaciones
 */
class cn_ent_catedra_equivalente extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->tabla('catedra')->set($datos);
	}

	function get_datos($tabla=null)
	{
		return $this->tabla('catedra')->get();
	}
	
	function set_datos_catedras_equivalentes($datos)
	{
		$this->validar_datos($datos);
		$this->set_datos(array('estado' => 'B')); // Doy de baja la Cátedra Original
		$this->tabla('catedras_eq')->eliminar_filas(); // Elimina la asociación actual para ingresar la nueva
		$this->tabla('catedras_eq')->set($datos);
	}
	
	function get_datos_catedras_equivalentes()
	{
		return $this->tabla('catedras_eq')->get_filas();
	}
	
	//-------------------------------------------------------------------
	
	function eliminar()
	{
		$this->tabla('catedra')->sincronizar();
		$this->tabla('catedras_eq')->eliminar_todo();
	}	
	
	//-------------------------------------------------------------------
	
	/**
	 * Valida si los datos ingresados son correctos
    */
	function validar_datos($datos)
	{
		$this->validar_ambas_catedras_distintas($datos);
		$this->validar_equivalencia_existente($datos);
		$this->validar_catedra_equivalente_no_baja($datos);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}
	
	/**
	 * Valida si las cátedras Original y Equivalente son distintas
    */
	function validar_ambas_catedras_distintas($datos)
	{
		if ($datos['catedra'] == $datos['catedra_equivalente']) {
			$this->set_error('800_dato_invalido', array('cátedra equivalente', 'la cátedra equivalente debe ser distinta a la original'));
		}
	}
	
	/**
	 * Valida si no existen dos Cátedras Originales distintas con la misma Cátedras Equivalente
    */
	function validar_equivalencia_existente($datos)
	{
		$catedras_equivalentes = toba::consulta_php('co_catedras_equivalentes')->get_catedras_equivalentes();
		if (in_array($datos['catedra_equivalente'], aplanar_matriz($catedras_equivalentes, 'catedra_equivalente'))) {
			$this->set_error('dato_existente', array('Cátedra Equivalente', 'relación Cátedra Original - Cátedra Equivalente'));
		}
	}
	
	/**
	 * Valida si la Cátedra Equivalente no esta dada de baja
    */
	function validar_catedra_equivalente_no_baja($datos)
	{
		$cat_eq_estado = current(toba::consulta_php('co_catedras_equivalentes')->get_catedra_estado($datos['catedra_equivalente']));
		if ($cat_eq_estado['catedra_estado'] == comunes::estado_baja_desc) {
			$this->set_error('800_dato_invalido', array('Cátedra Equivalente', 'la Cátedra Equivalente no puede estar dada de baja'));
		}
	}
	
}
?>