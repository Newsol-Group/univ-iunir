<?php
class pantalla_resumen extends guarani_ei_pantalla
{
	function generar_layout()
	{
		echo 'Se ha generado la siguiente Acta de Regulares:';
		echo '<br> <br>';

		// Datos del acta
		$datos = $this->controlador()->get_datos_resumen();
		// Id del acta
		$id_acta = $this->controlador()->get_acta();

		echo "
				<table style='border-style: solid; border-width: 1px; border-color: #DDD;' width='100%'>
					<tbody>
						<tr>
							<td width=110px>
								<label style='margin-left: 10px; font-weight: bold;'>
									{$datos['nro_acta']['etiq']}: 
								</label>
							</td>
							<td width=360px>
								{$datos['nro_acta']['valor']}
							</td>
							<td width=110px>
								<label style='margin-left: 10px; font-weight: bold;'>
									{$datos['periodo_nombre']['etiq']}: 
								</label>
							</td>
							<td width=360px>
								{$datos['periodo_nombre']['valor']}
							</td>
						</tr>
						<tr>
							<td width=110px>
								<label style='margin-left: 10px; font-weight: bold;'>
									{$datos['anio_academico_periodo']['etiq']}: 
								</label>
							</td>
							<td width=360px>
								{$datos['anio_academico_periodo']['valor']}
							</td>
							<td width=110px>
								<label style='margin-left: 10px; font-weight: bold;'>
									{$datos['nombre_ubicacion']['etiq']}: 
								</label>
							</td>
							<td width=360px>
								{$datos['nombre_ubicacion']['valor']}
							</td>
						</tr>
						<tr>
							<td width=110px>
								<label style='margin-left: 10px; font-weight: bold;'>
									{$datos['comision_nombre']['etiq']}: 
								</label>
							</td>
							<td width=360px>
								{$datos['comision_nombre']['valor']}
							</td>
							<td width=110px>
								<label style='margin-left: 10px; font-weight: bold;'>
									{$datos['codigo_y_nombre_actividad']['etiq']}: 
								</label>
							</td>
							<td width=360px>
								{$datos['codigo_y_nombre_actividad']['valor']}
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
		$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), '41000066', $params);
		echo 'Para realizar la carga de notas al acta ';
		echo "<a href='$vinculo'>PRESIONE AQUÍ</a>";
		echo '<br> <br>';

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