<?php
/**
 *	Representa un tipo de período lectivo
 *
 * @package Calendario
 * @subpackage Períodos Lectivos
 */
class cn_ent_periodo_lectivo_tipo extends cn_entidad
{

	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('periodo_lec_tipos')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('periodo_lec_tipos')->get();
	}
	
	//-------------------------------------------------------------------
	
	function validar_datos($datos)
	{
		$this->validar_periodo_lectivo_tipo($datos['periodo_lectivo_tipo']);
		$this->validar_periodo_lectivo_nombre($datos['nombre'], $datos['periodo_lectivo_tipo']);
        if (isset($datos['duracion_en_meses'])) {
            $this->validar_duracion_meses($datos['duracion_en_meses']);
        }
        if (isset($datos['duracion_en_semanas'])) {
            $this->validar_duracion_semanas($datos['duracion_en_semanas']);
        }
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}
	
	function validar_periodo_lectivo_tipo($periodo_lectivo_tipo)
	{
		if (! toba::consulta_php('co_periodos_lectivos')->validar_periodo_lectivo_tipo($periodo_lectivo_tipo, $this->controlador->entidad_es_nueva())) {
			$this->set_error('dato_duplicado', array('tipo', 'periodo lectivo'));
		}
	}
	
	function validar_periodo_lectivo_nombre($nombre, $periodo_lectivo_tipo)
	{
		if (! toba::consulta_php('co_periodos_lectivos')->validar_periodo_lectivo_nombre($nombre, $periodo_lectivo_tipo, $this->controlador->entidad_es_nueva())) {
			$this->set_error('dato_duplicado', array('nombre', 'periodo lectivo'));
		}
	}
	
	function validar_duracion_meses($duracion_en_meses)
	{
        if (! (($duracion_en_meses >= 1) && ($duracion_en_meses <= 24))) {
            $this->set_error('800_dato_invalido', array('duracion en meses', 'debe estar en el rango [1..24]'));
        }
	}

	function validar_duracion_semanas($duracion_en_semanas)
	{
        if (! (($duracion_en_semanas >= 1) && ($duracion_en_semanas <= 100))) {
            $this->set_error('800_dato_invalido', array('duracion en semanas', 'debe estar en el rango [1..100]'));
        }
	}
	
}

?>