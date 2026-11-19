<?php
/**
 *
 *
 * @package 	Comunicación
 * @subpackage
 * 
 */
class ci_edi_grupos_destinatarios extends ci_edicion_simple
{
	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCIÓN PERSONAS --------------------------------------------------
	//-----------------------------------------------------------------------------------

	//---- personas_asociadas -----------------------------------------------------------

	function conf__personas_asociadas(toba_ei_cuadro $cuadro)
	{
		$etiqueta = guarani_etiquetas::get_tabla('men_grupos_personas');
		$indx_msj = guarani_mensajes::get('800_eof_cuadro', array($etiqueta->get_enf('plural')));
		$cuadro->set_eof_mensaje($indx_msj);

		$cuadro->set_datos($this->cn()->entidad()->get_personas_asociadas());
	}

	function evt__personas_asociadas__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_asociado($seleccion);
	}
}
?>