<?php
class pantalla_resumen extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		echo 'Se ha invalidado la siguiente Acta:';
		echo '<br> <br>';

		// Datos del acta
		$datos = $this->controlador()->get_datos_resumen();
		// Id del acta
		$id_acta = $this->controlador()->get_acta_seleccionada();

		// Se muestra el libro si ya existe la asignación.
		if (!empty($datos[3]['valor'])) {
			$libro = "	<td width=110px>
							<label style='margin-left: 10px; font-weight: bold;'>
								{$datos[3]['etiq']}: 
							</label>
						</td>
						<td>
							{$datos[3]['valor']}
						</td>";
			$colspan = '';
		} else {
			$libro = '';
			$colspan = "colspan='3'";
		}

		echo "
				<table style='border-style: solid; border-width: 1px; border-color: #DDD;' width='100%'>
					<tbody>
						<tr>
							<td width=110px>
								<label style='margin-left: 10px; font-weight: bold;'>
									{$datos[0]['etiq']}: 
								</label>
							</td>
							<td>
								{$datos[0]['valor']}
							</td>
							$libro
							<td width=110px>
								<label style='margin-left: 10px; font-weight: bold;'>
									{$datos[6]['etiq']}: 
								</label>
							</td>
							<td $colspan>
								{$datos[6]['valor']}
							</td>
						</tr>
						<tr>
							<td width=110px>
								<label style='margin-left: 10px; font-weight: bold;'>
									{$datos[1]['etiq']}: 
								</label>
							</td>
							<td>
								{$datos[1]['valor']}
							</td>
							<td width=110px>
								<label style='margin-left: 10px; font-weight: bold;'>
									{$datos[4]['etiq']}: 
								</label>
							</td>
							<td colspan=3>
								{$datos[4]['valor']}
							</td>							
						</tr>
						<tr>
							<td width=110px>
								<label style='margin-left: 10px; font-weight: bold;'>
									{$datos[2]['etiq']}: 
								</label>
							</td>
							<td>
								{$datos[2]['valor']}
							</td>
							<td width=110px>
								<label style='margin-left: 10px; font-weight: bold;'>
									{$datos[5]['etiq']}: 
								</label>
							</td>
							<td colspan=3>
								{$datos[5]['valor']}
							</td>							
						</tr>
					</tbody>
				</table>
			";

		echo '<br>';
		$params = array();
		$params['operacion_a_volver'] = guarani::get_id_operacion_toba();
		$params['celda_a_restaurar'] = toba::memoria()->get_celda_memoria_actual_id();
		$params['id_acta'] = $id_acta;
		$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), '34000021', $params);
		echo 'Para imprimir el acta ';
		echo "<a href='$vinculo'>PRESIONE AQUÍ</a>";
	}

}
?>