<?php
class form_colacion extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
			echo'<tbody>';
				echo '<tr>';
					echo '<td >';
					$this->generar_html_ef('nombre');
					echo '</td>';
					echo '<td >';
					$this->generar_html_ef('fecha');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('calle');
					echo '</td>';
					echo '<td >';
					$this->generar_html_ef('numero');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td >';
					$this->generar_html_ef('localidad');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';    
					echo '<td colspan=2>';
					$this->generar_html_ef('descripcion');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
			echo'<tbody>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('fecha_inicio_inscripcion');
					echo '</td>';
					echo '<td colspan=2>';
					$this->generar_html_ef('hora_inicio_inscripcion', '135px');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('fecha_fin_inscripcion');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
			echo'<tbody>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('cupo');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
	}    
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$mensaje_numero = guarani_mensajes::get('800_dato_invalido', array('Número','debe ser un entero entre 0 y 999.999'));
		
		echo "
		//---- Validacion de EFs -----------------------------------
		
		{$this->objeto_js}.evt__numero__validar = function()
		{
			var es_numero_entero = this.ef('numero').get_estado() == Math.round(this.ef('numero').get_estado());
			var esta_en_rango = (this.ef('numero').get_estado() >= 0) && (this.ef('numero').get_estado() <= 999999);
			if (!es_numero_entero || !esta_en_rango) {
				this.ef('numero').set_error('$mensaje_numero');
				return false;
			}
			return true;
		}
		";
	}

}
?>