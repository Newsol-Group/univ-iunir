<?php
class pant_inicial extends toba_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('filtro')->generar_html();
		if ($this->existe_dependencia('cuadro_persona')) {			
			echo '<fieldset><legend>Datos Personales</legend>';
				$this->controlador()->ev_info()->generar_html();
			echo '</fieldset>';
			echo '<fieldset><legend>Datos Académicos</legend><br>';
				echo "<div style='border:none;background-color: #7ea2bc;color: #ffffff;padding: 5px;font-weight: bold;font-size:13px;'>Actividades Aprobadas</div>";
				$this->dep('cuadro_persona')->generar_html();
				echo '<br><br>';
				echo "<div style='border:none;background-color: #7ea2bc;color: #ffffff;padding: 5px;font-weight: bold;font-size:13px;'> Sistema Nacional de Reconocimiento Académico: Trayectos Formativos </div>";
				$this->dep('cuadro_rtf')->generar_html();
			echo '</fieldset>';
		}
	}

}
?>