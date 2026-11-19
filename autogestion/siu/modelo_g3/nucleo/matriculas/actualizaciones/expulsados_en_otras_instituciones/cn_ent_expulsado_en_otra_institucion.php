<?php
/**
 *	Representa un expulsado en otra institución
 *
 * @package Matricula
 * @subpackage Actualizaciones
 */

class cn_ent_expulsado_en_otra_institucion extends cn_entidad
{
	
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('expulsados_otra_inst')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('expulsados_otra_inst')->get();
	}
	
	//-------------------------------------------------------------------
	
	function validar_datos($datos)
	{
		$datos['nro_expulsion'] = $this->tabla('expulsados_otra_inst')->get_columna('nro_expulsion');
		$this->validar_combinacion_pais_tipo_numero($datos['pais'], $datos['tipo_documento'], $datos['nro_documento'], $datos['nro_expulsion']);
		$this->validar_fecha_desde($datos['fecha_desde']);
		$this->validar_fecha_hasta($datos['fecha_hasta']);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	// Determinar si el la sancion ya existe (combinacion de los campos pais + tipo + numero documento).
	function validar_combinacion_pais_tipo_numero($pais, $tipo_documento, $nro_documento, $nro_expulsion) 
	{
		if (! toba::consulta_php('co_documentos')->validar_combinacion_pais_tipo_numero($pais, $tipo_documento, $nro_documento, $nro_expulsion)) {
			$this->set_error('dato_duplicado', array('combinación país + tipo + numero documento'));			
		}
	}

	// Determinar si la Fecha Desde es valida.
	function validar_fecha_desde($fecha_desde)
	{
		if (! toba::consulta_php('co_documentos')->validar_fecha_desde($fecha_desde)) {
			$this->set_error('800_fecha_desde_erronea');
		}
	}

	// Determinar si la Fecha Hasta es valida.
	function validar_fecha_hasta($fecha_hasta)
	{
		if (!empty($fecha_hasta) && !toba::consulta_php('co_documentos')->validar_fecha_hasta($fecha_hasta)) {
			$this->set_error('800_fecha_hasta_erronea');
		}
	}
}

?>