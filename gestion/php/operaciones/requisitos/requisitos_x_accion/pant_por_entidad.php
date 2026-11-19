<?php 
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos por Acción
 * 
 */
class pant_por_entidad extends guarani_pers_ei_pantalla
{
	
	function generar_layout()
	{
		$this->dependencia('form_nombre_accion')->generar_html();
		$this->dependencia('form_seleccion_ent')->generar_html();
		$this->dependencia('cuadro_req_x_entidad')->generar_html();
		echo '<hr />';
		$params = array();
		$id_accion = $this->controlador()->get_accion_seleccionada();
		$params['accion'] = $id_accion;
        $vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), '41000061', $params);
		echo 'Para configurar estos requisitos en cada operación de esta acción ';
		echo "<a href='$vinculo'>PRESIONE AQUÍ</a>";
		
	}
	
}
?>