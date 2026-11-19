<?php
class pant_previsualizacion extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$datos = $this->controlador()->ev_info_acta()->get_datos(false);
		$this->controlador()->ev_info_acta()->generar_html();

		echo "	<hr />
				<div class='ci-simple-cont' style='margin-left: 2px'>
					<table>
						<tr class='ei-form-fila'>
							<td align='left' width='10%'>
								<strong><span ei-form-etiq-oblig>Cantidad de Alumnos: </span></strong>
							</td>
							<td align='left'>
								<span ei-form-etiq>{$datos['cant_alumnos']}</span>
							</td>
						</tr>
					</table> 
				</div>
		";

		if (acta::solicitar_libro_cierre($this->controlador()->get_acta_seleccionada())) {
			$this->dep('form_libro')->generar_html();
			echo '<hr/>';
		} else {
			$this->eliminar_dep('form_libro');
		}

		if ($this->existe_dependencia('cuadro_alumnos')) {
			$this->dep('cuadro_alumnos')->generar_html();
		}
	}
}
?>