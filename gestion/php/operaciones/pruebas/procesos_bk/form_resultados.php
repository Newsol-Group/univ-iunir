<?php
class form_resultados extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left">';
			echo'<tbody>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('procesados');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('com_copiadas');
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rep_com_copiadas', false);
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('com_sin_copia');
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rep_sin_copia', false);
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('errores');
					echo '</td>';
					echo '<td>';
					$this->generar_boton('rep_errores', false);
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
	}

	function extender_objeto_js () {

		echo "

		{$this->objeto_js}.evt__rep_com_copiadas = function()
		{
			var params = [];
			params['evento'] = 'rep_com_copiadas';
			this.controlador.ajax('reporte', params, this, this.respuesta_reporte);
			return false;
		}

		{$this->objeto_js}.evt__rep_sin_copia = function()
		{
			var params = [];
			params['evento'] = 'rep_sin_copia';
			this.controlador.ajax('reporte', params, this, this.respuesta_reporte);
			return false;
		}

		{$this->objeto_js}.evt__rep_errores = function()
		{
			var params = [];
			params['evento'] = 'rep_errores';
			this.controlador.ajax('reporte', params, this, this.respuesta_reporte);
			return false;
		}

		{$this->objeto_js}.respuesta_reporte = function(respuesta)
		{
			abrir_popup('reporte',respuesta);
		}

		";
		
	}

	

}
?>