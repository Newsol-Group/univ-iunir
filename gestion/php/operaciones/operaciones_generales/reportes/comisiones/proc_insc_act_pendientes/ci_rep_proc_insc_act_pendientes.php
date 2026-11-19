<?php
class ci_rep_proc_insc_act_pendientes extends ci_impresion_popup
{
	function ini()
	{
		parent::ini();

		$this->s__titulo = guarani_datos_impresion::instancia()->get_datos('titulo');
		$this->s__datos = rs_ordenar_por_columnas(guarani_datos_impresion::instancia()->get_datos('inscripciones'), array('elemento', 'id_comision'));

		$reporte = guarani_datos_impresion::instancia()->get_datos('reporte');
		if ($reporte != pro_proc_insc_act_pendientes::reporte_con_observaciones) {
			$this->dep('cuadro')->eliminar_columnas(array('observaciones'));
		}
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
		$this->xml_set_orientacion('landscape');
		
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