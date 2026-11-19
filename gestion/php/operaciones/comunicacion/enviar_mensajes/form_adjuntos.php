<?php
/**
 *
 *
 * @package 	Comunicación
 * @subpackage 	-
 * 
 */
class form_adjuntos extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '	<table>
					<tr>
						<td>';
							$this->generar_html_ef('archivo');
		echo '			</td>
						<td>';
							$this->controlador()->generar_boton('adjuntar');
							$this->controlador()->evento('adjuntar')->set_en_botonera(false);
		echo '			</td>
					</tr>
				</table>';
	}

}
?>