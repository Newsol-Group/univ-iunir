<?php
class pant_informacion extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		echo '<br> <br>';
		$acta_original = $this->controlador()->get_datos_acta();
		$nro_acta = $acta_original['nro_acta'];
		echo "Se ha generado la siguiente Rectificativa del Acta de Exámen Nº $nro_acta";
		echo '<br> <br>';

		$this->controlador()->ev_info()->generar_html();

		echo '<br> <br>';

		$params = array();
		$params['operacion_a_volver'] = guarani::get_id_operacion_toba();
		$params['celda_a_restaurar'] = toba::memoria()->get_celda_memoria_actual_id();
		$params['id_acta'] = $this->controlador()->get_acta_generada();
		$params['llamado_mesa_entidad'] = $this->controlador()->get_llamado_mesa();
		$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), '37000059', $params);
		echo 'Para realizar la carga de notas al acta ';
		echo "<a href='$vinculo'>PRESIONE AQUÍ</a>";
		echo '<br> <br>';
	}

}
?>