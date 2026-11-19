<?php
class pant_finalizar extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		if ($this->controlador()->cn()->es_fuera_de_termino()) {
			$this->dep('form_autorizacion')->generar_html();
		}
		echo '<hr />';
		$this->dep('cuadro_comisiones_elegidas')->generar_html();
		if ($this->existe_dependencia('cuadro_subcomisiones_elegidas')) {
			echo '<hr />';
			$this->dep('cuadro_subcomisiones_elegidas')->generar_html();
		}

		$datos = $this->controlador()->get_datos_mail();

		if (!is_null($datos)) {
			$color = $this->controlador()->envio_ok() ? '#dff0d8' : '#f2dede';
			echo '<hr />';
			echo "<div style='font-size: small; padding: 10px; border: 1px solid; background-color: $color;'>";
			if ($this->controlador()->envio_ok()) {
				echo 'El Comprobante se ha enviado por correo electrónico a ';
			} else {
				echo 'Hubo un problema al intentar enviar el Comprobante por correo electrónico a ';
			}

			echo "<strong>{$datos['nombre_completo']}</strong>";
			
			if (isset($datos['email'])) {
				$email = trim($datos['email']);
			} else {
				$email = '';
			}
			
			if (!empty($email)) {
				echo ' - <strong>' . $email . '</strong>';
			} else {
				echo '. El destinatario no tiene una dirección de mail configurada.';
			}
			echo "</div>";	
		}
	}

}
?>