<?php
class form_tecnologia extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left">';
			echo'<tbody>';
				echo '<tr>';
					echo '<td style="padding-right: 10px;">';
						echo '<fieldset title="Dispone de PC"><legend>Dispone de PC</legend>';
								$this->generar_html_ef('tecnologia_pc_casa');
								$this->generar_html_ef('tecnologia_pc_trabajo');
								$this->generar_html_ef('tecnologia_pc_universidad');
								$this->generar_html_ef('tecnologia_pc_otro_lugar');
						echo '</fieldset>';
					echo '</td>';
					echo '<td>';
						echo '<fieldset title="Accede a Internet"><legend>Accede a Internet</legend>';
							$this->generar_html_ef('tecnologia_int_casa');
							$this->generar_html_ef('tecnologia_int_trabajo');
							$this->generar_html_ef('tecnologia_int_universidad');
							$this->generar_html_ef('tecnologia_int_movil');
							$this->generar_html_ef('tecnologia_int_otro_lugar');
						echo '</fieldset>';
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
	}

}
?>