<?php
class form_actividad extends guarani_pers_ei_formulario
{
	/**
	* Permite modificar la forma en que se grafica el formulario, por defecto un ef sobre el otro
	*/
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="center" width="100%">';
			echo '<tbody>';
				echo '<tr>';
					echo '<td colspan=3>';
					$this->generar_html_ef('elemento');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('instancia');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('fecha');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('nota');
					echo '</td>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('escala_nota');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('resultado');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('fecha_vigencia');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=3>';
					$this->generar_html_ef('temas_a_rendir');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
    }
	
	/**
	 * Retorna el código JS necesario para validar las notas de las distintas escalas.
	 */
	function get_js_validar_nota_escalas()
	{
		$escalas = toba::consulta_php('co_escalas_notas')->get_listado();

		$validador = escalas_notas::validador_escalas_notas();
		return $validador->get_js_validar_nota_escalas($escalas);
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Validacion general ----------------------------------
		
		/**
		 * Validación general del formulario
		 * Retornar true/false para controlar la validación
		 * Usar notificacion.agregar() para mostrar mensajes al usuario
		 */
		{$this->objeto_js}.evt__validar_datos = function()
		{
		
			if (this.ef('nota').tiene_estado()) {
				var nota = this.ef('nota').get_estado();
				var escala = this.ef('escala_nota').get_estado();
				if (! eval('es_nota_valida_para_escala_'+escala+'(nota)')) {
					notificacion.agregar('Nota invalida para la escala de notas seleccionada');
					return false;
				} else {
					var resultado = eval('get_resultado_para_escala_'+escala+'(nota)');
					this.ef('resultado').set_estado(resultado);
					var nota_formateada = eval('formatear_nota_escala_'+escala+'(nota)');
					this.ef('nota').set_estado(nota_formateada);
				}
			}
		
			return true;
		}
		
		//---- Procesamiento de EFs --------------------------------
		
		/**
		 * Método que se invoca al cambiar el valor del ef en el cliente
		 * Se dispara inicialmente al graficar la pantalla, enviando en true el primer parámetro
		 */
		{$this->objeto_js}.evt__instancia__procesar = function(es_inicial)
		{ 
			var estado = this.ef('instancia').get_estado();
			if (!es_inicial) {
				if (estado == '" . equivalencia::equiv_instancia_parcial . "') {
					this.ef('temas_a_rendir').activar();
					//CONTROL: Si es de tipo parcial, el resultado debe ser 'APROBADO' si o si
					this.ef('resultado').set_estado('A');
					this.ef('resultado').desactivar();
				} else {
					this.ef('resultado').resetear_estado();
					this.ef('resultado').activar();
					this.ef('temas_a_rendir').resetear_estado();
					this.ef('temas_a_rendir').desactivar();
				}
				if (estado != '" . equivalencia::equiv_instancia_total . "') {
					this.ef('fecha_vigencia').activar();
				} else {
					this.ef('fecha_vigencia').resetear_estado();			
					this.ef('fecha_vigencia').desactivar();
				}
			} else {
				this.ef('temas_a_rendir').resetear_estado();
				this.ef('temas_a_rendir').desactivar();			
			}
		}
		
		/**
		 * Método que se invoca al cambiar el valor del ef en el cliente
		 * Se dispara inicialmente al graficar la pantalla, enviando en true el primer parámetro
		 */
		{$this->objeto_js}.evt__escala_nota__procesar = function(es_inicial)
		{
			if (!es_inicial) {
				this.ef('nota').resetear_estado();
				this.ef('resultado').resetear_estado();				
			}
		}
				
		/**
		 * Método que se invoca al cambiar el valor del ef en el cliente
		 * Se dispara inicialmente al graficar la pantalla, enviando en true el primer parámetro
		 */
		{$this->objeto_js}.evt__vencimiento__procesar = function(es_inicial)
		{
		}
		
		/**
		 * Método que se invoca al cambiar el valor del ef en el cliente
		 * Se dispara inicialmente al graficar la pantalla, enviando en true el primer parámetro
		 */
		{$this->objeto_js}.evt__temas_a_rendir__procesar = function(es_inicial)
		{
		}
		";

		$date = guarani_fecha::get_hoy_js();
		$msj_fecha = guarani_mensajes::get('800_control_fechas', array('', 'mayor', 'actual'));
		$msj_fecha_venc = guarani_mensajes::get('800_control_fechas', array('de Vencimiento', 'menor', 'actual'));

		$js_validacion_nota = static::get_js_validar_nota_escalas();
		echo "
		$js_validacion_nota
		var seteo_resultado_defecto = true;
		var hoy = new $date;

		//---- Procesamiento de EFs --------------------------------

		{$this->objeto_js}.evt__nota__procesar = function(es_inicial)
		{
			if (! es_inicial) {
				if (this.ef('nota').tiene_estado()) {
					var nota = this.ef('nota').get_estado();
					var escala = this.ef('escala_nota').get_estado();
					if (! eval('es_nota_valida_para_escala_'+escala+'(nota)')) {
						notificacion.limpiar();
						notificacion.agregar('Nota invalida para la escala de notas seleccionada');
						notificacion.mostrar();
						this.ef('nota').resetear_estado();
						this.ef('resultado').resetear_estado();
					} else {
						seteo_resultado_defecto = true;
					
						var resultado = eval('get_resultado_para_escala_'+escala+'(nota)');
						this.ef('resultado').set_estado(resultado);
						var nota_formateada = eval('formatear_nota_escala_'+escala+'(nota)');
						this.ef('nota').set_estado(nota_formateada);
					}
				} else {
					this.ef('resultado').resetear_estado();
				}
			}
		}

		{$this->objeto_js}.evt__resultado__procesar = function(es_inicial)
		{
			if (! es_inicial) {
				if (! seteo_resultado_defecto) {
					this.ef('nota').resetear_estado();
				} else {
					seteo_resultado_defecto = false;
				}
			}
			
		}
		
		//---- Validacion de EFs -----------------------------------
		
		/**
		 * Validación puntual de un ef en el cliente
		 * Retornar true/false para controlar la validación
		 * Usar this.ef(id).set_error(mensaje) para mostrar un error contextual al campo
		 */
		{$this->objeto_js}.evt__fecha__validar = function()
		{
			var ok = true;
			
			if(this.ef('fecha').tiene_estado()) {
				var fecha = this.ef('fecha').fecha();
				if (fecha > hoy) {
					this.ef('fecha').set_error('$msj_fecha');
					ok = false;
				}
			}
			return ok;
		}

		{$this->objeto_js}.evt__fecha_vigencia__validar = function()
		{
			var ok = true;
			
			if(this.ef('fecha_vigencia').tiene_estado()) {
				var fecha = this.ef('fecha_vigencia').fecha();
				if (fecha < hoy) {
					this.ef('fecha_vigencia').set_error('$msj_fecha_venc');
					ok = false;
				}
			}
			return ok;
		}
		
		";

		if (!$this->controlador()->puede_registrar_desaprobadas()) {
			echo "
			{$this->objeto_js}.evt__resultado__validar = function()
			{ 
				var estado = this.ef('resultado').get_estado();
				if (estado == '" . escalas_notas::resultado_reprobado . "') {
					this.ef('resultado').set_error('No es posible registrar notas desaprodas en la equivalencia');
					return false;
				}
				return true;
			}			 
			";
		}
	}

}
?>