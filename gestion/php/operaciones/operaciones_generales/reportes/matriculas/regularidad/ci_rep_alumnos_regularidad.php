<?php
/**
 *
 *
 * @package 	Matrícula
 * @subpackage 	Regularidad
 */
class ci_rep_alumnos_regularidad extends ci_impresion_popup
{
	//-----------------------------------------------------------------------------------
	//---- PANTALLA INICIAL -------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	//---- cuadro -----------------------------------------------------------------------
	
	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		if ($this->s__datos['ocultar_columnas']) {
			$cuadro->eliminar_columnas(array('causa_perdida', 'readmision_auto'));
		}
		$cuadro->set_datos($this->s__datos['alumnos']);
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