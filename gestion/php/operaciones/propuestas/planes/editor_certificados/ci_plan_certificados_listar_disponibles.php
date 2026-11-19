<?php
class ci_plan_certificados_listar_disponibles extends ci_plan_correlativas_listar_disponibles
{
	function ini()
	{
		toba_ef_combo::set_maximo_descripcion(25);
	}
	
	//-- MODULOS -------------------------------------------------

	function conf__modulos(toba_ei_cuadro $cuadro)
	{
		$cuadro->desactivar_modo_clave_segura();
		$cuadro->evento('seleccion')->set_seleccion_multiple();
		$cuadro->deseleccionar();
		$plan_version = $this->cn()->entidad()->get_id_plan_version();
		$this->msj_eof = '800_eof_cuadro';
		if (!isset($this->s__filtro_modulos)) {
			$this->msj_eof .= '_sinfiltro';
		} else {
			$datos = $this->controlador->get_elementos_disponibles('P',$this->s__filtro_modulos);
			$cuadro->set_datos($datos);
			//Si el filtro no es vacío se agrega el mensaje de eof del cuadro con filtro.
			if (isset($this->s__filtro_modulos)) {
				$this->msj_eof .= '_filtrado';
			}
		}
		$etiquetas = $etiquetas = guarani_etiquetas::get_campo('modulo');
		$msj_cuadro = guarani_mensajes::get($this->msj_eof,array($etiquetas->get_enf('plural')));
		$cuadro->set_eof_mensaje($msj_cuadro);
	}

    function evt__p_modulos__entrada()
    {
    	$this->controlador->set_tipo_elementos_seleccionables('P');
    }	

}
?>