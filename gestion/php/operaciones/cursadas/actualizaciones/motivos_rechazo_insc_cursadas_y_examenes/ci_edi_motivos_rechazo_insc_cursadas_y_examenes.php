<?php
/**
 *
 * @package 	Propuestas
 * @subpackage 	Actualizaciones
 * 
 */
class ci_edi_motivos_rechazo_insc_cursadas_y_examenes extends ci_edicion_simple 
{
	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__formulario(toba_ei_formulario $form)
	{
		parent::conf__formulario($form);
		if (!$this->cn()->entidad()->es_nueva()) {
			if ($this->cn()->entidad()->motivo_en_uso()) {
				$form->set_solo_lectura(array('nombre', 'descripcion', 'alcance'));
			}
		}
	}

}

?>