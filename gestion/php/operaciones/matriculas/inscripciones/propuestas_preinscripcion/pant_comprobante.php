<?php
/**
 * 
 * 
 * @package 	Matrícula
 * @subpackage 	Inscripciones
 *
 */
class pant_comprobante extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$persona = $this->controlador()->get_persona();
		$pantalla_retorno = $this->controlador()->get_pantalla_retorno();

		$this->controlador()->ev_info()->generar_html();

		$params = array();
		$params['operacion_a_volver'] = guarani::get_id_operacion_toba();
		$params['celda_a_restaurar'] = toba::memoria()->get_celda_memoria_actual_id();
		$params['pantalla_retorno'] = $pantalla_retorno;
		$params['persona'] = $persona;

		$opciones = array('celda_memoria' => sha1(guarani_fecha::get_ahora() . rand(1, 999999)));
		$vinculo = toba::vinculador()->get_url(toba_proyecto::get_id(), '37000023', $params, $opciones);

		echo '<br/>';
		$mensaje_confirm = 'Está por salir de la operación. Tenga presente que al regresar perderá las selecciones efectuadas en esta pantalla. ¿Desea continuar?';
		echo "Para editar los datos de la persona <a href='$vinculo' onclick='return confirm(\"$mensaje_confirm\");'>presione aquí</a>.";
		echo '<hr/>';
		
		echo "<div style='padding: 10px;'>";
		echo "<h2>Inscripciones procesadas</h2>";
		$inscripciones = $this->controlador()->cn()->get_inscripciones_procesadas();
		foreach ($inscripciones as $inscripcion) {
			echo "<h4>" . $inscripcion['propuesta_descr'] . "</h4>";
			echo "<ul>";
			echo "<li><strong>Plan</strong>:" . $inscripcion['plan_descr'] . "</li>";
			echo "<li><strong>Modalidad</strong>:" . $inscripcion['modalidad_descr'] . "</li>";
			echo "<li><strong>Ubicación</strong>:" . $inscripcion['ubicacion_descr'] . "</li>";
			echo "<li><strong>Estado</strong>:" . $inscripcion['estado_insc_descr'] . "</li>";
			echo "</ul>";
		}
		echo "</div>";
		
		echo '<hr/>';

		parent::generar_layout();

		//---- Datos de resultado de envío de mail -------------------------------------------

		if ($this->controlador()->mail_enviado()) {
			$color = $this->controlador()->envio_ok() ? '#dff0d8' : '#f2dede';
			echo '<hr />';
			echo "<div style='font-size: small; padding: 10px; border: 1px solid; background-color: $color;'>";

			echo "<p style='font-size: small; text-align: center;'>";
			if ($this->controlador()->envio_ok()) {
				echo 'Se ha realizado el envío del mail al siguiente destinatario:';
			} else {
				echo 'No se ha podido realizar el envío del mail a:';
			}
			echo '</p>';

			$datos = $this->controlador()->get_datos_mail();

			echo "<p style='font-size: medium; font-weight: bold; text-align: center;'>
						{$datos['nombre_completo']}";
			if (!is_null($datos['email'])) {
				echo '<br><br>' . $datos['email'];
			} else {
				echo '</p>';
				echo "<p style='font-size: small; text-align: center;'>";
				echo 'El destinatario no tiene una dirección de mail configurada';
			}
			echo '</p>';
			
			echo '</div>';
		}
	}
}
?>