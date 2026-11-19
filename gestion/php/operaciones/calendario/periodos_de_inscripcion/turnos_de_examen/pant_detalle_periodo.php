<?php
/**
 * 
 *
 * @package 	Calendario
 * @subpackage 	Turnos de Examen
 *
 */
class pant_detalle_periodo extends guarani_pers_ei_pantalla
{
	
	function generar_layout()
	{
		echo "	<table width='100%'>";
		echo '	<tr>
					<td>';
						$this->controlador()->ev_info_llamado_turno()->generar_html();
		echo '		</td>
				</tr>';
		echo '  </table>';
		echo '  <hr/>';
		echo "	<table width='100%'>";
		echo '	<tr>
					<td>
						<p style="font-weight:bold; font-size:11px; text-decoration: underline">PERÍODO INSCRIPCIÓN</p>';
						$this->controlador()->ev_info_per_inscripcion()->generar_html();
		echo '		</td>
				</tr>
			  	</table>';
	}

}
?>