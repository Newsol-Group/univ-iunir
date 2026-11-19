<?php
class pant_informacion extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		echo '<br> <br>';
		echo 'Se ha anulado la siguiente Acta de Examen:';
		echo '<br> <br>';

		$this->controlador()->ev_info()->generar_html();

		echo '<br>';

		$params = array();
		$params['operacion_a_volver'] = guarani::get_id_operacion_toba();
		$params['celda_a_restaurar'] = toba::memoria()->get_celda_memoria_actual_id();
		$params['id_acta'] = $this->controlador()->get_acta_seleccionada();
		$params['llamado_mesa_entidad'] = $this->controlador()->get_llamado_mesa();
		$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), '38000085', $params);
		echo 'Para realizar la impresión del acta ';
		echo "<a href='$vinculo'>PRESIONE AQUÍ</a>";
		echo '<br> <br>';
	}

}
?>