<?php
class ci_rep_reprocesar_inscripciones_actividades extends ci_impresion_popup
{
	function ini()
	{
		parent::ini();

		$this->s__titulo = guarani_datos_impresion::instancia()->get_datos('titulo');
		$this->s__datos = rs_ordenar_por_columnas(guarani_datos_impresion::instancia()->get_datos('inscripciones'), array('elemento', 'id_comision'));
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_titulo($this->s__titulo);
		$cuadro->set_datos($this->s__datos);
	}

	function vista_xml($inicial = false, $xmlns = null)
	{
		// ENCABEZADO
		$this->xml_set_titulo($this->s__titulo);
		$path_logo = guarani::get_path_logo_impresion_chico();
		$this->xml_set_logo($path_logo);

		// CUERPO
		$xml = $this->xml_get_tag_inicio();
		$xml .= $this->dep('cuadro')->vista_xml(false, $xmlns);
		$xml .= $this->xml_get_tag_fin();
		return $xml;
	}
}
?>