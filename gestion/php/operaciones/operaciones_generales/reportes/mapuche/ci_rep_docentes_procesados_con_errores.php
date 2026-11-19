<?php
class ci_rep_docentes_procesados_con_errores extends ci_impresion_popup
{
	function ini() 
	{
		parent::ini();
		$this->s__titulo = guarani_datos_impresion::instancia()->get_datos('titulo');
		$this->s__datos = rs_ordenar_por_columna(guarani_datos_impresion::instancia()->get_datos('docentes'), 'fila'); 
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
?>