<?php
class ci_rep_proc_sincronizar_documentos_arai extends ci_impresion_popup
{
	
	// Determina la orientacion del reporte. 
	// Por defecto no esta definida y se determina de acuerdo a la cantidad de columnas a imprimir
	protected $reporte_vertical = false;
	
	function ini()
	{
		parent::ini();

		$this->s__titulo = guarani_datos_impresion::instancia()->get_datos('titulo');
		$this->s__datos = rs_ordenar_por_columnas(guarani_datos_impresion::instancia()->get_datos('documentos'), array('tipo_documento'));
		
		$reporte = guarani_datos_impresion::instancia()->get_datos('reporte');
		if ($reporte != pro_proc_sincronizar_documentos_arai::reporte_fallidos) {
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
	
}