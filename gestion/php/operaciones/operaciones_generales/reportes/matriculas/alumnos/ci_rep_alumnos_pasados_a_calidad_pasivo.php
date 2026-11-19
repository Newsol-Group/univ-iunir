<?php
class ci_rep_alumnos_pasados_a_calidad_pasivo extends ci_impresion_popup
{
	
	function ini()
	{
		parent::ini();
		$this->s__titulo = 'Listado de Alumnos Activos pasados a calidad Pasivo';
	}
	
	//---- cuadro -----------------------------------------------------------------------
	
	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$datos = current($this->s__datos);
		$cuadro->set_datos($datos);
	}
	
	function vista_xml($inicial = false, $xmlns = null)
	{
		//ENCABEZADO
		$this->xml_set_alto_cabecera('1.5cm');
		$this->xml_set_titulo($this->s__titulo);
		$path_logo = guarani::get_path_logo_impresion_chico();
		$this->xml_set_logo($path_logo);

		//CUERPO
		$xml = $this->xml_get_tag_inicio();
		if ($this->dep('cuadro')->get_datos()) {
			$xml .= $this->dep('cuadro')->vista_xml(false, $xmlns);
		}
		$xml .= $this->xml_get_tag_fin();
		return $xml;
	}

}
?>