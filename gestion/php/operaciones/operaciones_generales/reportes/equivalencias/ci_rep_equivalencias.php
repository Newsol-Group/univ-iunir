<?php
class ci_rep_equivalencias extends ci_impresion_popup
{
	protected $s__datos;
			
	
	function ini()
	{
		parent::ini();
		// El reporte muestra las comisiones creadas con observaciones
		$this->s__datos = guarani_datos_impresion::instancia()->get_datos();
		$this->set_titulo($this->s__datos['titulo']);
	}
	
	
	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(guarani_pers_ei_cuadro $cuadro)
	{
		$datos = $this->s__datos['datos'];		
		$cuadro->set_datos($datos);
		
		if ($this->s__datos['reporte'] == pro_cierre_masivo_equivalencia::reporte_cerrados) {
			$cuadro->eliminar_columnas(array('error'));
		}
	}

}
?>