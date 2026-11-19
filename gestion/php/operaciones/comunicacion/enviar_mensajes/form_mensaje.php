<?php
/**
 *
 *
 * @package 	Unidades de Gestión
 * @subpackage 	Documentos
 * 
 */
class form_mensaje extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		$this->generar_html_ef('titulo');
		$this->generar_html_ef('cuerpo');
		$this->generar_html_ef('cuerpo_fijo');
		
		echo "<div style='margin-left:112px; margin-right:10px;'>
			<fieldset>
	    			<legend>
	    				Publicar...
	    			</legend>
	    			<table width='100%'>
						<tr>
							<td>";
								$this->generar_html_ef('tipo_mensaje');
		echo '				</td>
							<td>';
								$this->generar_html_ef('enviar_por_mail');
		echo '				</td>
						</tr>
	    				<tr>
	    					<td>';
								$this->generar_html_ef('responder_a');
		echo '				</td>
						</tr>
	    				<tr>
	    					<td>';
								$this->generar_html_ef('fecha_desde');
		echo '				</td>
							<td>';
								$this->generar_html_ef('hasta');
		echo '				</td>
							<td>';
								$this->generar_html_ef('fecha_hasta', '20px');
		echo '				</td>
						</tr>
						<tr>
							<td>';
								$this->generar_html_ef('enviar_por_twitter');
		echo '				</td>
							<td>';
								$this->generar_html_ef('enviar_por_facebook');
		echo "				</td>
						</tr>
						<tr>
							<td colspan='3'>";
								$this->generar_html_ef('mensaje_twitter');
		echo "				</td>
						</tr>
						<tr>
							<td colspan='3'>";
								$this->generar_html_ef('activo');
		echo '				</td>
						</tr>
					</table>
				</fieldset>
			</div>';
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$opcion_fecha = 1;
		
		echo "
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__hasta__procesar = function(es_inicial)
		{
			if (!es_inicial) {
				if (this.ef('hasta').get_estado() == '$opcion_fecha') {
					this.ef('fecha_hasta').mostrar();
				} else {
					this.ef('fecha_hasta').ocultar();
					this.ef('fecha_hasta').resetear_estado();
				}
			}
		}
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__enviar_por_twitter__procesar = function(es_inicial)
		{
			if (this.ef('enviar_por_twitter').get_estado() == 1) {
				this.ef('mensaje_twitter').mostrar();
			} else {
				this.ef('mensaje_twitter').ocultar();
				this.ef('mensaje_twitter').resetear_estado();
			}
		}
		
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__fecha_desde__procesar = function(es_inicial)
		{
			if (!es_inicial) {
				if (this.ef('hasta').get_estado() == '$opcion_fecha') {
					var fecha = this.ef('fecha_desde').get_estado();
					this.ef('fecha_hasta').set_estado(fecha)
				}
			} else {
				// Si es inicial y no está cargado -> Valor por defecto: Fecha actual
				var fecha = this.ef('fecha_desde').get_estado();
				if (!fecha) {
					var fechaActual = new Date();
					this.ef('fecha_desde').set_fecha(fechaActual);
				}
			}
		}
		
		{$this->objeto_js}.evt__enviar_por_mail__procesar = function(es_inicial)
		{
			if (this.ef('enviar_por_mail').chequeado()) {
				this.ef('responder_a').mostrar();
			} else {
				this.ef('responder_a').ocultar();
			}
		}
		";
	}

}
?>