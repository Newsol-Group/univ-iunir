<?php
class form_discapacidad extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		
		$ancho_etiqueta = '400px';
		
		echo '<div id="agrupador_condicion_discapacidad">';
		echo '<table cellspacing="1" cellpadding="1" border="0" align="center" width="100%">';
			echo '<tbody>';
				echo '<tr>';
					echo '<td>';
						echo '<fieldset title="Condición de Discapacidad"><legend>Condición de Discapacidad</legend>';
						echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
							echo '<tbody>';
								echo '<tr>';
									echo '<td>';
									$this->generar_input_ef('condicion_discapacidad');
									echo '</td>';
								echo '</tr>';
								echo '<tr>';
									echo '<td>';
									$this->generar_html_ef('tiene_cud', $ancho_etiqueta);
									echo '</td>';
								echo '</tr>';
							echo '</tbody>';
						echo '</table>';
						echo '</fieldset>';
				echo '</td>';
			echo '</tbody>';
		echo '</table>';
		echo '</div>';
			
		echo '<div id="agrupador_listado_discapacidad_tipos">';
		echo '<table cellspacing="1" cellpadding="1" border="0" align="center" width="100%">';
			echo '<tbody>';
				echo '<tr>';
					echo '<td>';
						echo '<fieldset title="Discapacidad"><legend>Discapacidad</legend>';
						echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
							echo '<tbody>';
								echo '<tr>';
									echo '<td>';
									$this->generar_html_ef('disc_auditiva', $ancho_etiqueta);
									echo '</td>';
								echo '</tr>';
								echo '<tr>';
									echo '<td>';
									$this->generar_html_ef('disc_visual', $ancho_etiqueta);
									echo '</td>';
								echo '</tr>';
								echo '<tr>';
									echo '<td>';
									$this->generar_html_ef('disc_motora', $ancho_etiqueta);
									echo '</td>';
								echo '</tr>';
								echo '<tr>';
									echo '<td>';
									$this->generar_html_ef('disc_cond_psicosocial', $ancho_etiqueta);
									echo '</td>';
								echo '</tr>';
								echo '<tr>';
									echo '<td>';
									$this->generar_html_ef('disc_otra', $ancho_etiqueta);
									echo '</td>';
								echo '</tr>';
							echo '</tbody>';
						echo '</table>';
						echo '</fieldset>';
					echo '</td>';
				echo '</tbody>';
		echo '</table>';
		echo '</div>';
			
		echo '<div id="agrupador_auditiva">';
		echo '<table cellspacing="1" cellpadding="1" border="0" align="center" width="100%">';
			echo '<tbody>';
				echo '<tr>';
					echo '<td>';
						echo '<fieldset title="Auditiva"><legend>Auditiva</legend>';
						echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
							echo '<tbody>';
								echo '<tr>';
									echo '<td>';
									$this->generar_html_ef('aud_tipo', $ancho_etiqueta);
									echo '</td>';
								echo '</tr>';
								echo '<tr>';
									echo '<td>';
									echo '<fieldset title="¿Cuál es su forma habitual de comunicación?"><legend>¿Cuál es su forma habitual de comunicación?</legend>';
									echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
										echo '<tbody>';
											echo '<tr>';
												echo '<td>';
												$this->generar_html_ef('aud_lenguaje_senias', $ancho_etiqueta);
												echo '</td>';
											echo '</tr>';
											echo '<tr>';
												echo '<td>';
												$this->generar_html_ef('aud_lenguaje_labial', $ancho_etiqueta);
												echo '</td>';
											echo '</tr>';
											echo '<tr>';
												echo '<td>';
												$this->generar_html_ef('aud_comunicacion_otra', $ancho_etiqueta);
												echo '</td>';
											echo '</tr>';
										echo '</tbody>';
									echo '</table>';
									echo '</fieldset>';
									echo '</td>';
								echo '</tr>';
								echo '<tr>';
									echo '<td>';
									echo '<fieldset title="Apoyos que requiere"><legend>Apoyos que requiere</legend>';
									echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
										echo '<tbody>';
											echo '<tr>';
												echo '<td>';
												$this->generar_html_ef('aud_apoyo_vida_diaria', $ancho_etiqueta);
												echo '</td>';
											echo '</tr>';
											echo '<tr>';
												echo '<td>';
												$this->generar_html_ef('aud_req_interprete_lengua_senias', $ancho_etiqueta);
												echo '</td>';
											echo '</tr>';
											echo '<tr>';
												echo '<td>';
												$this->generar_html_ef('aud_req_aro_magnetico', $ancho_etiqueta);
												echo '</td>';
											echo '</tr>';
											echo '<tr>';
												echo '<td>';
												$this->generar_html_ef('aud_req_otros_apoyos', $ancho_etiqueta);
												echo '</td>';
											echo '</tr>';
											echo '<tr>';
												echo '<td>';
												$this->generar_html_ef('aud_otros_apoyos', $ancho_etiqueta);
												echo '</td>';
											echo '</tr>';
										echo '</tbody>';
									echo '</table>';
									echo '</fieldset>';
									echo '</td>';
								echo '</tr>';
							echo '</tbody>';
						echo '</table>';
						echo '</fieldset>';
					echo '</td>';
				echo '</tbody>';
		echo '</table>';
		echo '</div>';
			
		echo '<div id="agrupador_visual">';
		echo '<table cellspacing="1" cellpadding="1" border="0" align="center" width="100%">';
			echo '<tbody>';
				echo '<tr>';
					echo '<td>';
						echo '<fieldset title="Visual"><legend>Visual</legend>';
						echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
							echo '<tbody>';
								echo '<tr>';
									echo '<td>';
									$this->generar_html_ef('vis_dificultad_para_ver', $ancho_etiqueta);
									echo '</td>';
								echo '</tr>';
								echo '<tr>';
									echo '<td>';
									echo '<fieldset title="Apoyos que requiere"><legend>Apoyos que requiere</legend>';
									echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
										echo '<tbody>';
											echo '<tr>';
												echo '<td>';
												$this->generar_html_ef('vis_apoyo_vida_diaria', $ancho_etiqueta);
												echo '</td>';
											echo '</tr>';
											echo '<tr>';
												echo '<td>';
												$this->generar_html_ef('vis_req_archivo_audio', $ancho_etiqueta);
												echo '</td>';
											echo '</tr>';
											echo '<tr>';
												echo '<td>';
												$this->generar_html_ef('vis_req_texto_digital', $ancho_etiqueta);
												echo '</td>';
											echo '</tr>';
											echo '<tr>';
												echo '<td>';
												$this->generar_html_ef('vis_req_texto_braile', $ancho_etiqueta);
												echo '</td>';
											echo '</tr>';
											echo '<tr>';
												echo '<td>';
												$this->generar_html_ef('vis_req_otros_apoyos', $ancho_etiqueta);
												echo '</td>';
											echo '</tr>';
											echo '<tr>';
												echo '<td>';
												$this->generar_html_ef('vis_otros_apoyos', $ancho_etiqueta);
												echo '</td>';
											echo '</tr>';
										echo '</tbody>';
									echo '</table>';
									echo '</fieldset>';
									echo '</td>';
								echo '</tr>';
							echo '</tbody>';
						echo '</table>';
						echo '</fieldset>';
					echo '</td>';
				echo '</tbody>';
		echo '</table>';
		echo '</div>';
			
		echo '<div id="agrupador_motora">';
		echo '<table cellspacing="1" cellpadding="1" border="0" align="center" width="100%">';
			echo '<tbody>';
				echo '<tr>';
					echo '<td>';
						echo '<fieldset title="Motora"><legend>Motora</legend>';
						echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
							echo '<tbody>';
								echo '<tr>';
									echo '<td>';
									echo '<fieldset title="Apoyos que requiere"><legend>Apoyos que requiere</legend>';
									echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
										echo '<tbody>';
											echo '<tr>';
												echo '<td>';
												$this->generar_html_ef('mot_apoyo_vida_diaria', $ancho_etiqueta);
												echo '</td>';
											echo '</tr>';
											echo '<tr>';
												echo '<td>';
												$this->generar_html_ef('mot_req_apoyo_aula', $ancho_etiqueta);
												echo '</td>';
											echo '</tr>';
											echo '<tr>';
												echo '<td>';
												$this->generar_html_ef('mot_apoyo_aula', $ancho_etiqueta);
												echo '</td>';
											echo '</tr>';
											echo '<tr>';
												echo '<td>';
												$this->generar_html_ef('mot_req_otros_apoyos', $ancho_etiqueta);
												echo '</td>';
											echo '</tr>';
											echo '<tr>';
												echo '<td>';
												$this->generar_html_ef('mot_otros_apoyos', $ancho_etiqueta);
												echo '</td>';
											echo '</tr>';
										echo '</tbody>';
									echo '</table>';
									echo '</fieldset>';
									echo '</td>';
								echo '</tr>';
							echo '</tbody>';
						echo '</table>';
						echo '</fieldset>';
					echo '</td>';
				echo '</tbody>';
		echo '</table>';
		echo '</div>';
			
		echo '<div id="agrupador_psicosocial">';
		echo '<table cellspacing="1" cellpadding="1" border="0" align="center" width="100%">';
			echo '<tbody>';
				echo '<tr>';
					echo '<td>';
						echo '<fieldset title="Condición Psicosocial"><legend>Condición Psicosocial</legend>';
						echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
							echo '<tbody>';
								echo '<tr>';
									echo '<td>';
									$this->generar_html_ef('psi_descripcion', $ancho_etiqueta);
									echo '</td>';
								echo '</tr>';
								echo '<tr>';
									echo '<td>';
									echo '<fieldset title="Apoyos que requiere"><legend>Apoyos que requiere</legend>';
									echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
										echo '<tbody>';
											echo '<tr>';
												echo '<td>';
												$this->generar_html_ef('psi_apoyo_vida_diaria', $ancho_etiqueta);
												echo '</td>';
											echo '</tr>';
											echo '<tr>';
												echo '<td>';
												$this->generar_html_ef('psi_req_otros_apoyos', $ancho_etiqueta);
												echo '</td>';
											echo '</tr>';
											echo '<tr>';
												echo '<td>';
												$this->generar_html_ef('psi_otros_apoyos', $ancho_etiqueta);
												echo '</td>';
											echo '</tr>';
										echo '</tbody>';
									echo '</table>';
									echo '</fieldset>';
									echo '</td>';
								echo '</tr>';
							echo '</tbody>';
						echo '</table>';
						echo '</fieldset>';
					echo '</td>';
				echo '</tbody>';
		echo '</table>';
		echo '</div>';
		
		echo '<div id="agrupador_otras">';
		echo '<table cellspacing="1" cellpadding="1" border="0" align="center" width="100%">';
			echo '<tbody>';
				echo '<tr>';
					echo '<td>';
						echo '<fieldset title="Otra situación de discapacidad"><legend>Otra situación de discapacidad</legend>';
						echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
							echo '<tbody>';
								echo '<tr>';
									echo '<td>';
									$this->generar_html_ef('otra_descripcion', $ancho_etiqueta);
									echo '</td>';
								echo '</tr>';
								echo '<tr>';
									echo '<td>';
									$this->generar_html_ef('otra_req_apoyo_vida_diaria', $ancho_etiqueta);
									echo '</td>';
								echo '</tr>';
								echo '<tr>';
									echo '<td>';
									$this->generar_html_ef('otra_apoyo_vida_diaria', $ancho_etiqueta);
									echo '</td>';
								echo '</tr>';
							echo '</tbody>';
						echo '</table>';
						echo '</fieldset>';
				echo '</td>';
			echo '</tbody>';
		echo '</table>';
		echo '</div>';
		
		echo '<div id="agrupador_importante">';
		echo '<table cellspacing="1" cellpadding="1" border="0" align="center" width="100%">';
			echo '<tbody>';
				echo '<tr>';
					echo '<td>';
						echo '<fieldset title="Alguna otra información que consideres importante comunicar"><legend>Alguna otra información que consideres importante comunicar</legend>';
						echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
							echo '<tbody>';
								echo '<tr>';
									echo '<td>';
									$this->generar_html_ef('informacion_importante', '0px');
									echo '</td>';
								echo '</tr>';
							echo '</tbody>';
						echo '</table>';
						echo '</fieldset>';
				echo '</td>';
			echo '</tbody>';
		echo '</table>';
		echo '</div>';
			
	}
	
	//--------------------------------------------------------------------------
	//---- JAVASCRIPT ----------------------------------------------------------
	//--------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$si = comunes::si;
		$msj_falta_discapacidad = guarani_mensajes::get('800MAT_falta_discapacidad');
		echo "
        function evt_condicion_discapacidad()
        {
            var condicion_discapacidad = this.ef('condicion_discapacidad').get_estado();
			if (condicion_discapacidad == '$si') {
				this.ef('tiene_cud').mostrar();
				$('#agrupador_listado_discapacidad_tipos').show();
				{$this->objeto_js}.evt__disc_auditiva__procesar();
				{$this->objeto_js}.evt__disc_visual__procesar();
				{$this->objeto_js}.evt__disc_motora__procesar();
				{$this->objeto_js}.evt__disc_cond_psicosocial__procesar();
				{$this->objeto_js}.evt__disc_otra__procesar();
				$('#agrupador_importante').show();
			} else {
				this.ef('tiene_cud').ocultar();
				$('#agrupador_listado_discapacidad_tipos').hide();
				$('#agrupador_auditiva').hide();
				$('#agrupador_visual').hide();
				$('#agrupador_motora').hide();
				$('#agrupador_psicosocial').hide();
				$('#agrupador_otras').hide();
				$('#agrupador_importante').hide();
			}
        }
		
		function evt_discapacidades_auditivas()
		{
			if (this.ef('disc_auditiva').chequeado()) {
				$('#agrupador_auditiva').show();
			} else {
				this.ef('aud_otros_apoyos').set_obligatorio(false);
				$('#agrupador_auditiva').hide();
			}
		}
		
		function evt_aud_otros_apoyos()
		{
			if (this.ef('aud_req_otros_apoyos').chequeado()) {
				this.ef('aud_otros_apoyos').mostrar();
				this.ef('aud_otros_apoyos').set_obligatorio(true);
			} else {
				this.ef('aud_otros_apoyos').set_obligatorio(false);
				this.ef('aud_otros_apoyos').ocultar();
			}
		}
		
		function evt_discapacidades_visuales()
		{
			if (this.ef('disc_visual').chequeado()) {
				$('#agrupador_visual').show();
			} else {
				this.ef('vis_otros_apoyos').set_obligatorio(false);
				$('#agrupador_visual').hide();
			}
		}
		
		function evt_vis_otros_apoyos()
		{
			if (this.ef('vis_req_otros_apoyos').chequeado()) {
				this.ef('vis_otros_apoyos').mostrar();
				this.ef('vis_otros_apoyos').set_obligatorio(true);
			} else {
				this.ef('vis_otros_apoyos').set_obligatorio(false);
				this.ef('vis_otros_apoyos').ocultar();
			}
		}
		
		function evt_discapacidades_motoras()
		{
			if (this.ef('disc_motora').chequeado()) {
				$('#agrupador_motora').show();
			} else {
				this.ef('mot_otros_apoyos').set_obligatorio(false);
				$('#agrupador_motora').hide();
			}
		}
		
		function evt_mot_apoyo_aula()
		{
			if (this.ef('mot_req_apoyo_aula').chequeado()) {
				this.ef('mot_apoyo_aula').mostrar();
			} else {
				this.ef('mot_apoyo_aula').ocultar();
			}
		}
		
		function evt_mot_otros_apoyos()
		{
			if (this.ef('mot_req_otros_apoyos').chequeado()) {
				this.ef('mot_otros_apoyos').mostrar();
				this.ef('mot_otros_apoyos').set_obligatorio(true);
			} else {
				this.ef('mot_otros_apoyos').set_obligatorio(false);
				this.ef('mot_otros_apoyos').ocultar();
			}
		}
			
		function evt_discapacidades_psicosociales()
		{
			if (this.ef('disc_cond_psicosocial').chequeado()) {
				$('#agrupador_psicosocial').show();
			} else {
				this.ef('psi_otros_apoyos').set_obligatorio(false);
				$('#agrupador_psicosocial').hide();
			}
		}
		
		function evt_psi_otros_apoyos()
		{
			if (this.ef('psi_req_otros_apoyos').chequeado()) {
				this.ef('psi_otros_apoyos').mostrar();
				this.ef('psi_otros_apoyos').set_obligatorio(true);
			} else {
				this.ef('psi_otros_apoyos').set_obligatorio(false);
				this.ef('psi_otros_apoyos').ocultar();
			}
		}
		
		function evt_discapacidades_otras()
		{
			if (this.ef('disc_otra').chequeado()) {
				$('#agrupador_otras').show();
				this.ef('otra_descripcion').set_obligatorio(true);
			} else {
				this.ef('otra_descripcion').set_obligatorio(false);
				this.ef('otra_apoyo_vida_diaria').set_obligatorio(false);
				$('#agrupador_otras').hide();
			}
		}
		
		function evt_otra_apoyo_vida_diaria()
		{
			if (this.ef('otra_req_apoyo_vida_diaria').chequeado()) {
				this.ef('otra_apoyo_vida_diaria').mostrar();
				this.ef('otra_apoyo_vida_diaria').set_obligatorio(true);
			} else {
				this.ef('otra_apoyo_vida_diaria').set_obligatorio(false);
				this.ef('otra_apoyo_vida_diaria').ocultar();
			}
		}
		
		//---- Procesamiento de EFs --------------------------------------------
		
		{$this->objeto_js}.evt__condicion_discapacidad__procesar = evt_condicion_discapacidad;
		{$this->objeto_js}.evt__disc_auditiva__procesar = evt_discapacidades_auditivas;
		{$this->objeto_js}.evt__aud_req_otros_apoyos__procesar = evt_aud_otros_apoyos;
		{$this->objeto_js}.evt__disc_visual__procesar = evt_discapacidades_visuales;
		{$this->objeto_js}.evt__vis_req_otros_apoyos__procesar = evt_vis_otros_apoyos;
		{$this->objeto_js}.evt__disc_motora__procesar = evt_discapacidades_motoras;
		{$this->objeto_js}.evt__mot_req_apoyo_aula__procesar = evt_mot_apoyo_aula;
		{$this->objeto_js}.evt__mot_req_otros_apoyos__procesar = evt_mot_otros_apoyos;
		{$this->objeto_js}.evt__disc_cond_psicosocial__procesar = evt_discapacidades_psicosociales;
		{$this->objeto_js}.evt__psi_req_otros_apoyos__procesar = evt_psi_otros_apoyos;
		{$this->objeto_js}.evt__disc_otra__procesar = evt_discapacidades_otras;
		{$this->objeto_js}.evt__otra_req_apoyo_vida_diaria__procesar = evt_otra_apoyo_vida_diaria;

		//---- Validacion general ----------------------------------

		{$this->objeto_js}.evt__validar_datos = function()
		{
			if (this.ef('condicion_discapacidad').get_estado() == '$si' &&
				!this.ef('disc_auditiva').chequeado() &&
				!this.ef('disc_visual').chequeado() &&
				!this.ef('disc_motora').chequeado() &&
				!this.ef('disc_cond_psicosocial').chequeado() &&
				!this.ef('disc_otra').chequeado()) {
				notificacion.agregar('$msj_falta_discapacidad');
				return false;
			}
			return true;
		}
		";
	}
}