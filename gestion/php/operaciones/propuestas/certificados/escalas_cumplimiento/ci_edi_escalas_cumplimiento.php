<?php
/**
 *
 * 
 * @package 	Propuestas
 * @subpackage 	Certificados
 */
class ci_edi_escalas_cumplimiento extends ci_edicion_simple
{
	//-----------------------------------------------------------------------------------
	//---- PANTALLA NIVELES
	//-----------------------------------------------------------------------------------

	function evt__niveles__salida()
	{
		$this->dep('editor_nivel')->evt__cancelar();
	}
}
?>