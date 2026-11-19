<?php
class pant_previsualizacion_acta_alumnos extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$datos = $this->controlador()->controlador()->ev_info()->get_datos(false);
		$this->controlador()->controlador()->ev_info()->generar_html();

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

		$this->dep('form_libro')->generar_html();
		echo '<hr />';
		if ($this->existe_dependencia('form_acta')) {
			echo '<hr />';
			$this->dep('form_acta')->generar_html();
		}
		$this->dep('alumnos')->generar_html();
	}
}
?>