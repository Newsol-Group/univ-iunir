<?php
class form_escala_notas extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo "<table border='0'>";
		echo "<tr>";
		echo "<td>";
		$this->generar_html_ef('es_numerica');
		echo "</td><td>";
		$this->generar_html_ef('tipo');
		echo "</td><td>";
		$this->generar_html_ef('estado');
		echo "</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td colspan='3'>";
		$this->generar_html_ef('nombre');
		echo "</td></tr><tr><td colspan='3'>";
		$this->generar_html_ef('descripcion');
		echo "</td></tr><tr><td>";
		echo "</td></tr><tr><td colspan='3'>";
		$this->generar_html_ef('instancias');
		echo "</td></tr><tr><td>";
		$this->generar_html_ef('cantidad_decimales');
		echo "</td><td colspan='2'>";
		$this->generar_html_ef('separador_decimal');
		echo "</td></tr><tr><td>";
		$this->generar_html_ef('nota_inicial');
		echo "</td><td colspan='2'>";
		$this->generar_html_ef('nota_final');
		echo "</td></tr>";
		echo "</table>";
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		if($this->controlador()->cn()->entidad()->es_nueva())
		{
			//Si se trata de un registro nuevo, selecciona todos los valores de instancias por defecto.
			echo 
			"var es_nueva = true;";
		} else {
			echo "var es_nueva = false";
		}
		echo "
		//---- Validacion general ----------------------------------
		
		{$this->objeto_js}.evt__validar_datos__ = function()
		{
			//return true;
		}
		
		{$this->objeto_js}.evt__es_numerica__procesar = function(es_inicial)
		{
			if (this.ef('es_numerica').get_estado() == '" . comunes::no . "') {
				if (!es_inicial) { 	// Fix para bug en control de cambios
					this.ef('tipo').set_estado('" . escalas_notas::tipo_discreta . "');
				}
				this.ef('tipo').set_solo_lectura(true);
				this.controlador.dep('notas').ef('valor_numerico').set_obligatorio(false);
			} else {
				this.ef('tipo').set_solo_lectura(false);
                                if (this.ef('tipo').get_estado() == '" . escalas_notas::tipo_discreta . "') {
					this.controlador.dep('notas').ef('valor_numerico').set_obligatorio(true);
                                }
			}
		}
		
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__tipo__procesar = function(es_inicial)
		{
			var efs_continua = ['cantidad_decimales','separador_decimal','nota_inicial','nota_final'];
			if (this.ef('tipo').get_estado() == '".escalas_notas::tipo_discreta."') {
				//this.controlador.dep('notas_continuas').ocultar();
				if (this.controlador.dep('notas') != undefined) {
					this.controlador.dep('notas').mostrar();
					this.controlador.ocultar_tab('tab_resultados');
					this.controlador.ocultar_tab('tab_conceptos');
					if (this.ef(efs_continua[0]) != undefined) {
						for(efs in efs_continua) {
							this.ef(efs_continua[efs]).resetear_estado();
							this.ef(efs_continua[efs]).ocultar();
							this.ef(efs_continua[efs]).set_obligatorio(false);
						}
					}
				var params= [];
				this.controlador.ajax('eliminar_datos_tipo_continua', params, this, this.respuesta_eliminacion_datos_continua);
				}
				
			} else {
				if (this.controlador.dep('notas') != undefined) {
					this.controlador.dep('notas').eliminar_filas();
					this.controlador.dep('notas').ocultar();
					this.controlador.mostrar_tab('tab_resultados');
					this.controlador.mostrar_tab('tab_conceptos');
				}
				//this.controlador.dep('notas_continuas').mostrar();
				for(efs in efs_continua) {
					this.ef(efs_continua[efs]).mostrar();
					this.ef(efs_continua[efs]).set_obligatorio(true);
				}
				this.evt__cantidad_decimales__procesar();
			}
			
			//Si se trata de un registro nuevo, selecciona todos los valores de instancias por defecto.
			if (es_nueva) {
				this.ef('instancias').seleccionar_todo(true);
			}
		}
				
		{$this->objeto_js}.respuesta_eliminacion_datos_continua = function(respuesta) 
		{
			
		}		
		
		{$this->objeto_js}.evt__nota_final__validar = function()
		{
			var ok = true;
			if (this.ef('tipo') == '".escalas_notas::tipo_continua."') {
				if (! this.ef('nota_final').tiene_estado() || ! this.ef('nota_inicial').tiene_estado()) {
					return false;
				}
			}
			return ok;
		}
		
		{$this->objeto_js}.evt__cantidad_decimales__procesar = function(es_inicial)
		{
			if (this.ef('cantidad_decimales').get_estado() == 0) {
				this.ef('separador_decimal').ocultar();
			} else {
				this.ef('separador_decimal').mostrar();
			}
			
			this.evt__nota_inicial__procesar(false);
			this.evt__nota_final__procesar(false);
		}
		
		{$this->objeto_js}.evt__nota_inicial__procesar = function(es_inicial)
		{
			if (!es_inicial) {
				if (this.ef('nota_inicial').tiene_estado()) {
					var nota = this.ef('nota_inicial').get_estado();
					var nota_formateada = this.formatear_nota(nota);
					this.ef('nota_inicial').set_estado(nota_formateada);
				}
			}
		}
		
		{$this->objeto_js}.evt__nota_final__procesar = function(es_inicial)
		{
			if (!es_inicial) {
				if (this.ef('nota_final').tiene_estado()) {
					var nota = this.ef('nota_final').get_estado();
					var nota_formateada = this.formatear_nota(nota);
					this.ef('nota_final').set_estado(nota_formateada);
				}
			}
		}
		
		{$this->objeto_js}.formatear_nota = function(nota)
		{
			var cant_decimales = this.ef('cantidad_decimales').get_estado();
			var separador = this.ef('separador_decimal').get_estado();
		
			nota = nota.replace(',',separador);
			nota = nota.replace('.',separador);
		
			if (nota.indexOf(separador) < 0 && cant_decimales > 0) {
				nota = nota + separador;
			}
		
			pos = nota.indexOf(separador);
			
			// Caso: cant_decimales = 0 
			if (pos < 0) {
				return nota;
			}
			
			var parte_entera = nota.substring(0,pos);
			var parte_decimal = nota.substring(pos+1,nota.length);
		
			// parte Entera
			if (parte_entera == ''){
				parte_entera = '0';
			}
			parte_entera = parseInt(parte_entera,10).toString();
			
			// Caso: parte entera no numérico.
			if (parte_entera == 'NaN') {
				notificacion.limpiar();
				notificacion.agregar('Formato inválido.');
				notificacion.mostrar();
				return '';
			}
			
			if (parte_entera > 99999) {
				notificacion.limpiar();
				notificacion.agregar('Formato inválido. La nota no puede tener más de 5 dígitos en su parte entera.');
				notificacion.mostrar();
				return '';
			}
			
			// Caso: cant_decimales = 0 y agrega decimales en la nota
			if (cant_decimales == 0) {
				return parte_entera;
			}
		
			// parte Decimal
			var cantidad = cant_decimales - parte_decimal.length;
			if (cantidad < 0){
				return parte_entera+separador+parte_decimal.substring(0,parte_decimal.length+cantidad)
			}
			for (x = 0; x < cantidad; x++) {
				parte_decimal = parte_decimal + '0';
			}
		
			return parte_entera+separador+parte_decimal;
		}
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__separador_decimal__procesar = function(es_inicial)
		{
			this.evt__nota_inicial__procesar(false);
			this.evt__nota_final__procesar(false);
		}
		";
	}



}
?>