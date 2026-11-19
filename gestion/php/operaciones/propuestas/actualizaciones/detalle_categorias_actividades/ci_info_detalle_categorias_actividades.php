<?php
/**
 *
 * @package 	Propuestas 
 * @subpackage 	Actualizaciones
 * 
 */
class ci_info_detalle_categorias_actividades extends guarani_pers_ci
{
	protected $s__categoria;

	function ini()
	{
		$parametro_categoria = toba::memoria()->get_parametro('categoria');
		if (!empty($parametro_categoria)) {
			$this->s__categoria = $parametro_categoria;
		}
	}
	
	function get_datos_detalle()
	{
		$categoria = toba::db()->quote($this->s__categoria);
		$where = "sga_categorias_actividades.categoria = $categoria";
		$datos = toba::consulta_php('co_categorias_actividades')->get_listado($where);

		if (guarani_arreglos::count($datos) > 0) {
			return $datos[0];
		}
	}
	
	
	//-------------------------------------------------------------
	//---- cuadro -------------------------------------------------
	//-------------------------------------------------------------
	
	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_usar_ordenamiento_con_cortes();
		if (!empty($this->s__categoria) && $this->s__categoria != apex_ef_no_seteado) {
			$detalle_categoria = toba::consulta_php('co_categorias_actividades')->get_valores_categoria($this->s__categoria);
			$cuadro->set_datos($detalle_categoria);
		}
	}

	function extender_objeto_js()
	{
		//Se recupera el id en javascript del cuadro para atender su evento Cerrar, y provocar que se cierre la ventana popup.
		$id_js = $this->dep('cuadro')->get_id_objeto_js();
		echo "
			{$id_js}.evt__cerrar = function() {
				window.close();
			}
		";
	}
}
?>