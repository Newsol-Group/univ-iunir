<?php
class pant_comprobante extends guarani_pers_ei_pantalla
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
			foreach ($datos['datos'] as $key => $dato) {
				if (isset($dato['etiq']) && $dato['valor']) {
					echo "
								<div style='' class='ei-cuerpo ei-form-cuerpo'>
									<div style='' class='ei-form-fila'>
										<label class='ei-form-etiq-oblig' style=''>
											{$dato['etiq']} :
										</label>
									<div style='margin-left: 150px;'>
										<div class='ef-fijo'>
											{$dato['valor']}
										</div>
									</div>
								</div>
					";
				}
			}
			echo "
							</td>
						</tr>
					</tbody>
				</table>
				";
		}

	}

}
?>