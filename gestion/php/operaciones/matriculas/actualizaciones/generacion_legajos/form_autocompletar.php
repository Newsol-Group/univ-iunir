<?php
class form_autocompletar extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '	<table width="100%">
					<tr>
						<td>';
							$this->generar_html_ef('comp_para');
		echo '			</td>
						<td>';
							$this->generar_html_ef('comp_valor');
		echo '			</td>
						<td>';
							$this->generar_botones();
		echo '			</td>
					</tr>
				</table>';

		$this->evento('completar')->set_en_botonera(false);
	}
}
?>