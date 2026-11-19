<?php
/**
 *
 *
 * @package		Exámenes
 * @subpackage	Inscripciones automáticas
 * 
 */
class ci_listado_insc_automaticas extends ci_impresion_popup
{
	function ini()
	{
		parent::ini();
		$this->s__titulo = 'Resúmen de Inscripciones Automáticas para el Año Académico ' . $this->s__datos_conf['anio_academico'];
		$this->s__datos = guarani_datos_impresion::instancia()->get_datos('comisiones_inscripciones');
	}

	function cuadro()
	{
		return $this->dep('cuadro_insc_automaticas');
	}

	//--------------------------------------------------------------------------
	//---- cuadro --------------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro_insc_automaticas(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_usar_ordenamiento_con_cortes(true);
		$cuadro->set_datos($this->s__datos);
	}

	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		$pantalla->set_titulo($this->s__titulo);
	}

}
?>