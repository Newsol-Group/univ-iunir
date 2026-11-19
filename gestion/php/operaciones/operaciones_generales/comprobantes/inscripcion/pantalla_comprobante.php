<?php 
class pantalla_comprobante extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$datos = $this->controlador()->get_datos_imp();

		if (isset($datos['datos'])) {
			echo "
					<table class='ei-base ei-form-base'>
						<tbody>
							<tr>
								<td style='padding: 0pt;'>
				";
			
			foreach ($datos['datos'] as $dato) {
				echo "
									<div class='ei-cuerpo ei-form-cuerpo'>
										<div class='ei-form-fila' style=''>
											<label class='ei-form-etiq-oblig' style=''>";
											if (!empty($dato['etiq'])) {
												echo $dato['etiq'] . ": ";
											} else {
												echo "&nbsp;";											
											}
				echo "				
											</label>
										</div>
										<div style='margin-left: 150px;'>
											<div>
												{$dato['valor']}
											</div>
										</div>
									</div>
					";			
			}
	
			echo '
								</td>
							</tr>
						</tbody>
					</table>
				';
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
				echo ' - <strong>' . $datos['email'] . '</strong>';
			} else {
				echo '. El destinatario no tiene una dirección de mail configurada.';
			}
			echo "</div>";
		}
	}
}
?>