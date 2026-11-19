<?php
/**
 *
 *
 * @package Cursadas
 * @subpackage Comisiones
 */
class ci_detalle_escalas_notas extends guarani_pers_ci
{
	protected $s__comision;

	function ini()
	{
		$parametro_comision = toba::memoria()->get_parametro('comision');
		if (!empty($parametro_comision)) {
			$this->s__comision = $parametro_comision;
		}
	}

	//---- cuadro_detalle_escala --------------------------------------------------------
	
	function conf__cuadro_detalle_escala(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_usar_ordenamiento_con_cortes();
		if (!empty($this->s__comision) && $this->s__comision != apex_ef_no_seteado) {
			$detalles_escalas = toba::consulta_php('co_escalas_notas')->get_detalle_escala_notas_por_comision($this->s__comision);
			$cuadro->set_datos($detalles_escalas);
		}
	}

	function extender_objeto_js()
	{
		$id_js_cuadro = $this->dependencia('cuadro_detalle_escala')->get_id_objeto_js();
		echo "
			{$id_js_cuadro}.evt__cerrar = function() {
				window.close();
			}
		";
	}
}
?>