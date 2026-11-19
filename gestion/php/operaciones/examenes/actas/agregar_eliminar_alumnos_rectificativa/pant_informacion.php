<?php
class pant_informacion extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		echo '<br> <br>';
		echo 'Se ha modificado la siguiente Acta Rectificativa:';
		echo '<br> <br>';
	
		$this->controlador()->ev_info()->generar_html();

		echo '<br> <br>';
		
		$params = array();
		$params['operacion_a_volver'] = guarani::get_id_operacion_toba();
		$params['celda_a_restaurar'] = toba::memoria()->get_celda_memoria_actual_id();
		$params['id_acta'] = $this->controlador()->get_acta_seleccionada();
		$params['llamado_mesa_entidad'] = $this->controlador()->get_llamado_mesa();
		$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), '37000059', $params);
		echo 'Para realizar la carga de notas al acta ';
		echo "<a href='$vinculo'>PRESIONE AQUÍ</a>";
		echo '<br> <br>';
	}

}
?>