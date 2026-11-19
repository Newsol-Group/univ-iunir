<?php
class pantalla_resumen extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$datos = $this->controlador()->get_datos_resumen();

		echo "Se ha generado la siguiente Rectificativa para el Acta de Promocionales Nº {$datos[6]['valor']}";
		echo '<br> <br>';

		// Datos del acta.
		echo "
				<table style='border-style: solid; border-width: 1px; border-color: #DDD;' width='100%'>
					<tbody>
						<tr>
							<td width=150px>
								<label style='margin-left: 10px; font-weight: bold;'>
									{$datos[0]['etiq']}: 
								</label>
							</td>
							<td>
								{$datos[0]['valor']}
							</td>
						</tr>
						<tr>
							<td width=150px>
								<label style='margin-left: 10px; font-weight: bold;'>
									{$datos[1]['etiq']}: 
								</label>
							</td>
							<td>
								{$datos[1]['valor']}
							</td>
							<td width=150px>
								<label style='margin-left: 10px; font-weight: bold;'>
									{$datos[5]['etiq']}: 
								</label>
							</td>
							<td>
								{$datos[5]['valor']}
							</td>
							<td width=150px>
								<label style='margin-left: 10px; font-weight: bold;'>
									{$datos[3]['etiq']}: 
								</label>
							</td>
							<td>
								{$datos[3]['valor']}
							</td>
						</tr>
						<tr>
							<td width=150px>
								<label style='margin-left: 10px; font-weight: bold;'>
									{$datos[2]['etiq']}: 
								</label>
							</td>
							<td>
								{$datos[2]['valor']}
							</td>
							<td width=150px>
								<label style='margin-left: 10px; font-weight: bold;'>
									{$datos[4]['etiq']}: 
								</label>
							</td>
							<td colspan=3>
								{$datos[4]['valor']}
							</td>
						</tr>
					</tbody>
				</table>
			";
		echo '<br>';

		$params = array();
		$params['operacion_a_volver'] = guarani::get_id_operacion_toba();
		$params['celda_a_restaurar'] = toba::memoria()->get_celda_memoria_actual_id();
		$params['id_acta'] = $this->controlador()->get_id_acta_rectificativa();
		$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), '41000066', $params);
		echo 'Para realizar la carga de notas al acta ';
		echo "<a href='$vinculo'>PRESIONE AQUÍ</a>";
		echo '<br> <br>';
	}

}
?>