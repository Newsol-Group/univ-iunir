<?php
class form_correl extends guarani_pers_ei_formulario
{
/*	function generar_layout()
	{
		//$this->generar_html_ef('nombre');
	}*/

	function ajax_generar_layout()
	{
		$this->_carga_opciones_ef->cargar();
		parent::generar_layout();
		if ($this->botonera_abajo()) {
				$this->generar_botones();
		}	
	}
}

?>