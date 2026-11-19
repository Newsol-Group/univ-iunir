<?php
/**
 * 
 * @package Encuestas
 */
class ci_edi_vincular_encuestas extends ci_edicion_simple
{

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		if (!$this->cn()->entidad_es_nueva() && $this->cn()->entidad()->encuesta_asociada_a_grupo()) {
			$this->dep('formulario')->set_solo_lectura(array('kolla_unidad_gestion'));
			$this->dep('formulario')->set_solo_lectura(array('kolla_id_encuesta'));
			$this->controlador()->evento('eliminar')->desactivar();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__formulario(toba_ei_formulario $form)
	{
		parent::conf__formulario($form);
		if (!encuesta::kolla_utiliza_ug()) {
			$form->desactivar_efs(array('kolla_unidad_gestion'));
		}
	}

	function evt__formulario__modificacion($datos)
	{
		if (!encuesta::kolla_utiliza_ug()) {
			$datos['kolla_unidad_gestion'] = encuesta::get_ug_default_kolla();
		}
		parent::evt__formulario__modificacion($datos);
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_encuestas_kolla($ug = null)
	{
		return $this->controlador()->get_encuestas_kolla($ug);
	}

	//-----------------------------------------------------------------------------------
	//---- AJAX -------------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ajax__nombre_default($params, toba_ajax_respuesta $respuesta)
	{
		$kolla_id_encuesta = current($params);
		$encuestas_kolla = rs_convertir_asociativo($this->get_encuestas_kolla(), array('encuesta'), 'nombre');
		if (isset($encuestas_kolla[$kolla_id_encuesta])) {
			$salida = array('nombre' => $encuestas_kolla[$kolla_id_encuesta]);
			$respuesta->set($salida);
		} else {
			$respuesta->set(false);
		}
	}

}
?>