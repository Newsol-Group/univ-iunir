<?php
/**
 *
 *
 * @package 	Constancias y Certificados
 * @subpackage 	Administrar Solicitudes
 * 
 */
class pant_procesar_multiples extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<hr/>';
		// $datos_usuario = $this->controlador()->get_datos_usuario();
		// // Se setea la etiqueta del ef de fecha de entrega con el nombre del usuario actualmente loggueado.
		// $usuario = 'Se registrar� en el sistema que la Constancia ser� entregada por ' . $datos_usuario['nombre'] . ' el d�a y hora:';
		// echo "<b> $usuario </b>";
		// echo '<br/>';
		// echo '<br/>';
		parent::generar_layout();
	}
}
?>