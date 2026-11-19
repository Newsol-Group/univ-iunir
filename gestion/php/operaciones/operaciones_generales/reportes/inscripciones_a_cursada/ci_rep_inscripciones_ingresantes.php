<?php
class ci_rep_inscripciones_ingresantes extends ci_impresion_popup
{
	protected $indx_msg_eof = '800_eof_cuadro';
	
	function ini()
	{
		parent::ini();
		toba::solicitud()->set_datos_item(array('item_nombre' => $this->s__titulo));
	}
	
	//---- cuadro -----------------------------------------------------------------------
	
	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		if (!empty($this->s__datos)) {
			$cuadro->set_datos($this->s__datos);
		}
		if ($this->s__titulo != 'Reporte de Inscripciones Rechazadas') {
			$cuadro->eliminar_columnas(array('error'));
		}
	}
	
	function vista_xml($inicial = false, $xmlns = null)
	{
		// ----------------------------------------------------------
		// 	ENCABEZADO
		// ----------------------------------------------------------
		
		$this->xml_set_alto_cabecera('4cm');
		$this->xml_set_titulo($this->s__titulo);
		$path_logo = guarani::get_path_logo_impresion_chico();
		$this->xml_set_logo($path_logo);

		// ----------------------------------------------------------
		//	CUERPO
		// ----------------------------------------------------------

		$xml = $this->xml_get_tag_inicio();
		if ($this->dep('cuadro')->get_datos()) {
			$xml .= $this->dep('cuadro')->vista_xml(false, $xmlns);
		}
		$xml .= $this->xml_get_tag_fin();
		return $xml;
	}
}
?>