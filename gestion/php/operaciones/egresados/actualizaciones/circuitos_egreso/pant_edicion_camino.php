<?php
class pant_edicion_camino extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		echo "<table>
				<tr>
					<td colspan='2'>";
						$this->dep('form_camino')->generar_html();
		echo "		<td>
				</tr>
				<tr>
					<td>";
						$this->dep('ml_forms')->generar_html();			
		echo "		</td>
					<td>";
						$this->dep('ml_campos')->generar_html();		
		echo "		</td>
				</tr>
			  </table>
				";
	}

}

?>