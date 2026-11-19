<?php 
class pantalla_info extends guarani_pers_ei_pantalla
{
	
	function generar_layout()
	{

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
	
	}
	
}

?>