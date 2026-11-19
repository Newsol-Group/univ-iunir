<?php
class form_financiamiento extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<fieldset title="¿Cómo costea sus estudios?"><legend>¿Cómo costea sus estudios?</legend>';
		echo '<table cellspacing="1" cellpadding="1" border="0" width="100%">';
			echo '<tbody>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('costeo_estudios_familiar', '150px');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('costeo_estudios_plan_social');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('costeo_estudios_trabajo');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('costeo_estudios_beca', '50px');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('costeo_estudios_otro', '30px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan="5">';
					$this->generar_html_ef('costeo_estudios_descripcion');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
		echo '</fieldset> <fieldset id="fuente_beca" title="Fuente de la beca"><legend>Fuente de la beca</legend>';
		echo '<table cellspacing="1" cellpadding="1" border="0" align="center" width="100%">';
			echo '<tbody>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('beca_fuente_universidad', '60px');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('beca_fuente_nacional', '50px');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('beca_fuente_provincial', '50px');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('beca_fuente_municipal', '50px');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('beca_fuente_internacional', '70px');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('beca_fuente_otra', '30px');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
		echo '</fieldset> <fieldset id="tipo_beca" title="Tipo de beca"><legend>Tipo de beca</legend>';
		echo '<table cellspacing="1" cellpadding="1" border="0" align="center" width="100%">';
			echo '<tbody>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('beca_tipo_economica', '110px');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('beca_tipo_servicios', '180px');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('beca_tipo_investigacion', '90px');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
		echo '</fieldset> <fieldset id="becas_economicas" title="S&oacute;lo si es beca de tipo " ayuda="" económica=""><legend>S&oacute;lo si es beca de tipo &quot;ayuda econ&oacute;mica&quot;</legend>';
		echo '<table cellspacing="1" cellpadding="1" border="0" align="center" width="100%">';
			echo '<tbody>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('beca_economica_transporte', '60px');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('beca_economica_efectivo', '50px');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('beca_economica_fotocopias', '60px');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('beca_economica_habitacional', '70px');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('beca_economica_comedor', '50px');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
		echo '</fieldset>';
	}
	
	//--------------------------------------------------------------------------
	//---- JAVASCRIPT ----------------------------------------------------------
	//--------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Procesamiento de EFs --------------------------------------------
		
		{$this->objeto_js}.evt__costeo_estudios_beca__procesar = function(es_inicial)
		{
			var beca = this.ef('costeo_estudios_beca').chequeado();
	
			// Los campos que no se muestran, es decir se ocultan, se resetean.

			this.ef('beca_fuente_universidad').mostrar(beca,!beca);
			this.ef('beca_fuente_nacional').mostrar(beca,!beca);
			this.ef('beca_fuente_provincial').mostrar(beca,!beca);
			this.ef('beca_fuente_municipal').mostrar(beca,!beca);
			this.ef('beca_fuente_internacional').mostrar(beca,!beca);
			this.ef('beca_fuente_otra').mostrar(beca,!beca);
			this.ef('beca_tipo_economica').mostrar(beca,!beca);
			this.ef('beca_tipo_servicios').mostrar(beca,!beca);
			this.ef('beca_tipo_investigacion').mostrar(beca,!beca);
			
			if (beca) {
				$('#fuente_beca').show();
				$('#tipo_beca').show();
			} else {
				$('#fuente_beca').hide();
				$('#tipo_beca').hide();
			}
		} 
		
		{$this->objeto_js}.evt__costeo_estudios_otro__procesar = function(es_inicial)
		{
			var otro = this.ef('costeo_estudios_otro').chequeado();
	
			// Si el campo no se muestra, es decir se oculta, se resetea.
			this.ef('costeo_estudios_descripcion').mostrar(otro,!otro);
		} 
		
		{$this->objeto_js}.evt__beca_tipo_economica__procesar = function(es_inicial)
		{
			var beca_economica = this.ef('beca_tipo_economica').chequeado();
	
			// Los campos que no se muestran, es decir se ocultan, se resetean.

			this.ef('beca_economica_transporte').mostrar(beca_economica,!beca_economica);
			this.ef('beca_economica_efectivo').mostrar(beca_economica,!beca_economica);
			this.ef('beca_economica_fotocopias').mostrar(beca_economica,!beca_economica);
			this.ef('beca_economica_habitacional').mostrar(beca_economica,!beca_economica);
			this.ef('beca_economica_comedor').mostrar(beca_economica,!beca_economica);
			
			if (beca_economica) {
				$('#becas_economicas').show();
			} else {
				$('#becas_economicas').hide();
			}
		}
		";
	}
}
?>