<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage Detalle Instancias
 */
class ci_info_instancias extends guarani_pers_ci
{
	protected $s__instancias;
	
	function ini()
	{
		$parametro_instancias = toba::memoria()->get_parametro('instancias');
		if (!empty($parametro_instancias)) {
			$this->s__instancias = $parametro_instancias;
		}
	}

	//---- cuadro_datos_instancias ------------------------------------------------------
	
	function conf__cuadro_datos_instancias(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_usar_ordenamiento_con_cortes();
		if (!empty($this->s__instancias) && $this->s__instancias != apex_ef_no_seteado) {
			$array_claves_instancias = explode(',', $this->s__instancias);
			$detalles_instancias = toba::consulta_php('co_mesas_de_examen')->get_instancias_datos($array_claves_instancias);
			$cuadro->set_datos($detalles_instancias);
		}
	}

	function extender_objeto_js()
	{
		//Se recupera el id en javascript del cuadro para atender su evento Cerrar, y provocar que se cierre la ventana popup.
		$id_js_cuadro = $this->dependencia('cuadro_datos_instancias')->get_id_objeto_js();
		echo "
			{$id_js_cuadro}.evt__cerrar = function() {
				window.close();
			}
		";
	}
	
}
?>