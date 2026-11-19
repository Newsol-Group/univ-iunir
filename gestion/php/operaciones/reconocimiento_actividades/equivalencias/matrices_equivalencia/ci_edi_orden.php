<?php
class ci_edi_orden extends guarani_pers_ci
{
	
	//-----------------------------------------------------------------------------------
	//---- ml_orden ---------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__ml_orden(guarani_pers_ei_formulario_ml $form_ml)
	{
		$this->cn()->entidad()->cargar_grupos();
		$datos = $this->cn()->entidad()->get_filas_grupos();
		$form_ml->set_datos($datos);
	}

	function evt__ml_orden__modificacion($datos)
	{
		$this->cn()->entidad()->procesar_grupos($datos);
		$this->cn()->entidad()->sincronizar_grupos();
		$this->cn()->entidad()->resetear_grupos();
	}

}
?>