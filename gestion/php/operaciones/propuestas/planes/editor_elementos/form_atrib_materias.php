<?php
class form_atrib_materias extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		$encabezado = array(
			'nombre',
			'nombre_abreviado',
			'codigo'
		);
		$periodo = array(
			'anio_de_cursada',
			'periodo_de_cursada'
		);
		$creditos = array(
			'creditos',
			'creditos_min',
			'creditos_max'
		);

		$coeficiente_cobrable = array('coeficiente', 'cobrable');
		$escala = array('escala_nota_cursada_regular', 'escala_nota_cursada_promocion', 'escala_nota_examen', 'escala_nota_equivalencia');
		$horas = array('horas_semanales', 'horas_totales');
		$prom = array('permite_promocion', 'promediable', 'aprobacion_x_resolucion', 'permite_rendir_libre');
		$req = array('requiere_cursada', 'requiere_examen');
		$var = array('sale_listado', 'vigencia', 'plazo', 'cantidad_de_aplazos');

		echo $this->get_html_alineado($encabezado, 1, '150px');
		echo '<hr/>';
		echo $this->get_html_alineado($periodo, 2, '110px');
		echo '<hr/>';
		echo $this->get_html_alineado($creditos, 3, '50px');
		echo '<hr/>';
		echo $this->get_html_alineado($coeficiente_cobrable, 2, '70px');
		echo '<hr/>';
		echo $this->get_html_alineado($horas, 2);
		echo '<hr/>';
		echo $this->get_html_alineado($prom, 2, '150px');
		echo '<hr/>';
		echo $this->get_html_alineado($req, 2);
		echo '<hr/>';
		echo $this->get_html_alineado($var, 2, '120px');
		echo '<hr/>';
		$this->generar_html_ef('modalidad', '150px');
		echo '<hr/>';
		echo $this->generar_html_ef('escala_nota');
		echo $this->get_html_alineado($escala, 1);
	}

	function extender_objeto_js()
	{
		$no = comunes::no;

		echo "
		//---- Validacion general ----------------------------------
		
		{$this->objeto_js}.evt__validar_datos = function()
		{
			// Creditos
			var creditos	= this.ef('creditos').get_estado();
			var min			= this.ef('creditos_min').get_estado();
			var max			= this.ef('creditos_max').get_estado();;
		
		    if (this.ef('creditos').tiene_estado() && creditos < 0 ) {
					notificacion.agregar('Créditos - Debe ingresar Créditos Mayores o Iguales a Cero');
					return false
			}
			if ( this.ef('creditos_min').tiene_estado() != this.ef('creditos_max').tiene_estado() ) {
				if ( this.ef('creditos_min').tiene_estado() ) {
					notificacion.agregar('Créditos - Debe ingresar también el Máximo');
					return false
				} else {
					notificacion.agregar('Créditos - Debe ingresar también el Minimo');
					return false
				}
			} else {
				if (this.ef('creditos_min').tiene_estado()) {
					if (min >= max) {
						notificacion.agregar('Créditos - El Máximo debe ser mayor que el Mínimo');
						return false
					} else {
						if (this.ef('creditos').tiene_estado()) {
							if (! validacion.esta_entre(creditos,min,max)) {
								notificacion.agregar('Créditos - Los Créditos deben estar entre el Mínimo y el Máximo');
								return false
							}
						}
						if (min < 0 ) {
							notificacion.agregar('Créditos - Los Valores de Créditos , Máximo y Mínimo deben ser Mayores ó Igual a 0');
							return false
						}
					}
				}
			}
		
		    // Coeficiente
		    var coeficiente = this.ef('coeficiente').get_estado();
			if (isset(coeficiente) && coeficiente < 0) {
				notificacion.agregar('Debe ingresar un Coeficiente Mayor o Igual a Cero');
				return false;
			}
		
			// Horas Semanales
			var horas_semanales = this.ef('horas_semanales').get_estado();
			if (horas_semanales > 168 || horas_semanales < 0) {
				notificacion.agregar('Debe ingresar una cantidad de Horas semanales Válida.');
				return false;
			}
		
			// Horas Totales
			var horas_totales = this.ef('horas_totales').get_estado();
			

			if (this.ef('horas_totales').tiene_estado() == this.ef('horas_semanales').tiene_estado()) {
				if (horas_totales < horas_semanales) {
					notificacion.agregar('Debe ingresar una cantidad 9999999999de Horas semanales menor que las Horas Totales.');
					return false;
				}
			}
			// Vigencia
			if (this.ef('vigencia').tiene_estado()) {
				var vigencia = this.ef('vigencia').get_estado();
				if (! validacion.es_entero(vigencia) || vigencia <= 0) {
					notificacion.agregar('Debe ingresar en Vigencia un valor Entero mayor a Cero.');
					return false;
				}
			}
			// Plazo
			if (this.ef('plazo').tiene_estado()) {
				var plazo = this.ef('plazo').get_estado();
				if (! validacion.es_entero(plazo) || plazo <= 0) {
					notificacion.agregar('Debe ingresar en Plazo un valor Entero mayor a Cero.');
					return false;
				}
			}
		
			// Cantidad de aplazos
			if (this.ef('cantidad_de_aplazos').tiene_estado()) {
				var cantidad_aplazos = this.ef('cantidad_de_aplazos').get_estado();
				if (! validacion.es_entero(cantidad_aplazos) || cantidad_aplazos < 0) {
					notificacion.agregar('Debe ingresar en Canitdad de Aplazos un valor Entero mayor o igual a Cero.');
					return false;
				}
			}
		
		
		return true
		}
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__permite_promocion__procesar = function(es_inicial)
		{
			var valor = this.ef('permite_promocion').get_estado();
			if (valor == '$no') {
				this.ef('escala_nota_cursada_promocion').resetear_estado();
				this.ef('escala_nota_cursada_promocion').ocultar();
			} else {
				this.ef('escala_nota_cursada_promocion').mostrar();
			}
		}
		
		{$this->objeto_js}.evt__requiere_cursada__procesar = function(es_inicial)
		{
			var valor = this.ef('requiere_cursada').get_estado();
			if (valor == '$no') {
				this.ef('escala_nota_cursada_regular').resetear_estado();
				this.ef('escala_nota_cursada_regular').ocultar();
			} else {
				this.ef('escala_nota_cursada_regular').mostrar();
			}
		}
		
		{$this->objeto_js}.evt__requiere_examen__procesar = function(es_inicial)
		{
			var valor = this.ef('requiere_examen').get_estado();
			if (valor == '$no') {
				this.ef('escala_nota_examen').resetear_estado();
				this.ef('escala_nota_examen').ocultar();
			} else {
				this.ef('escala_nota_examen').mostrar();
			}
		}
		";
	}

}
?>