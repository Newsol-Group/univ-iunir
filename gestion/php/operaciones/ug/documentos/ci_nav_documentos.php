<?php
class ci_nav_documentos extends ci_navegacion
{
	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__eliminar()
	{
		parent::evt__eliminar();
		$this->dep('editor')->eliminar_doc_digital();
	}

	function evt__guardar()
	{
		parent::evt__guardar();
		$this->dep('editor')->eliminar_doc_digital();
	}

	function evt__cancelar()
	{
		parent::evt__cancelar();
		$this->dep('editor')->eliminar_doc_digital();
	}
}
?>