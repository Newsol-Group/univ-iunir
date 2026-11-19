<?php
class form_expulsados_en_otras_instituciones extends guarani_pers_ei_formulario
{

	function generar_layout()
	{
		echo "<fieldset title='Datos de la persona sancionada' style='padding-left: 0px;padding-right: 0px;'><legend>Datos de la persona sancionada</legend>";		
		echo "<table border=0>";
		echo '<tr>';
			echo '<td colspan=2>';
			$this->generar_html_ef('apellido');
			echo '</td>';
		echo '</tr>';
		echo '<tr>';
			echo '<td colspan=2>';
			$this->generar_html_ef('nombres');
			echo '</td>';
		echo '</tr>';
		echo '<tr>';
			echo '<td>';
			$this->generar_html_ef('pais');
			echo '</td>';
			echo '<td>';
			$this->generar_html_ef('tipo_documento', '130px');
			echo '</td>';
		echo '</tr>';
		echo '<tr>';
			echo '<td>';
			$this->generar_html_ef('nro_documento');
			echo '</td>';
			echo '<td>';
			$this->generar_html_ef('cuil', '130px');
			echo '</td>';
		echo '</tr>';
		echo '</table>';
		echo '</fieldset>';
		echo '<br/>';
		echo "<table border=0 style='padding-left: 0px;padding-right: 0px;'>";
		echo '<tr>';
			echo '<td>';
			$this->generar_html_ef('institucion');
			echo '</td>';
		echo '</tr>';
		echo '<tr>';
			echo '<td>';
			$this->generar_html_ef('tipo_sancion');
			echo '</td>';
		echo '</tr>';
		echo '<tr>';
			echo '<td>';
			$this->generar_html_ef('fecha_desde');
			echo '</td>';
		echo '</tr>';
		echo '<tr>';
			echo '<td>';
			$this->generar_html_ef('fecha_hasta');
			echo '</td>';
		echo '</tr>';
		echo '<tr>';
			echo '<td>';
			$this->generar_html_ef('resolucion');
			echo '</td>';
		echo '</tr>';		
		echo '<tr>';
			echo'<td>';
			$this->generar_html_ef('nro_expediente_notificacion');
			echo '</td>';
		echo '</tr>';
		echo '</table>';
	}

}
?>