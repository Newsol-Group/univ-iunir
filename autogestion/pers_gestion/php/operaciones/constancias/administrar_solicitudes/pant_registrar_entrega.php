<?php
/**
 *
 *
 * @package 	Constancias y Certificados
 * @subpackage 	Administrar Solicitudes
 * 
 */
class pant_registrar_entrega extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<hr/>';
		$datos_usuario = $this->controlador()->get_datos_usuario();
		// Se setea la etiqueta del ef de fecha de entrega con el nombre del usuario actualmente loggueado.
		$usuario = 'Se registrará en el sistema que la Constancia será entregada por ' . $datos_usuario['nombre'] . ' el día y hora:';
		echo "<b> $usuario </b>";
		echo '<br/>';
		echo '<br/>';
		parent::generar_layout();
	}
}
?>