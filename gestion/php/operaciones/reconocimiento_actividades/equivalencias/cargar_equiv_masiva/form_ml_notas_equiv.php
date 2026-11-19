<?php
/**
 *
 * @package 	Reconocimiento de Actividades
 * @subpackage 	Equivalencias
 * 
 */
class form_ml_notas_equiv extends guarani_pers_ei_formulario_ml
{

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$date = guarani_fecha::get_hoy_js();
		$msj_fecha = guarani_mensajes::get('800_control_fechas', array('', 'mayor', 'actual'));
		$msj_nota_invalida = guarani_mensajes::get('800_nota_invalida');
		$js_validacion_nota	= $this->controlador()->get_js_validar_nota_escalas();
		
		$datos = $this->controlador()->get_datos();
		$escalas = "var escalas = new Array();";
		$i=0;
		foreach($datos as $dato) {
			$escalas .= "escalas[$i] = " . $dato['escala_nota'] . ";";
			$i++;
		}
		
		echo "
		$js_validacion_nota

		//---- Procesamiento de EFs --------------------------------

		$escalas
		
		{$this->objeto_js}.evt__nota__procesar = function(es_inicial, fila)
		{
			if (! es_inicial) {
				if (this.ef('nota').ir_a_fila(fila).tiene_estado()) {
					var nota = this.ef('nota').ir_a_fila(fila).get_estado();
					if (! eval('es_nota_valida_para_escala_'+escalas[fila]+'(nota)')) {
						var alumno = this.ef('apellido_y_nombre').ir_a_fila(fila).get_estado();
						notificacion.limpiar();
						notificacion.agregar('$msj_nota_invalida' + alumno + '.');
						notificacion.mostrar();
						this.ef('nota').ir_a_fila(fila).resetear_estado();
						this.ef('resultado').ir_a_fila(fila).resetear_estado();
					} else {
						var resultado = eval('get_resultado_para_escala_'+escalas[fila]+'(nota)');
						this.ef('resultado').ir_a_fila(fila).set_estado(resultado);
						var nota_formateada = eval('formatear_nota_escala_'+escalas[fila]+'(nota)');
						this.ef('nota').ir_a_fila(fila).set_estado(nota_formateada);
					}
				} else {
					this.ef('resultado').ir_a_fila(fila).resetear_estado();
				}
			}
		}

		{$this->objeto_js}.evt__resultado__procesar = function(es_inicial, fila)
		{
			this.ef('resultado').ir_a_fila(fila).set_solo_lectura(true);
		}

		{$this->objeto_js}.evt__fecha__procesar = function(es_inicial, fila)
		{
			if (! es_inicial) {
				if (this.ef('fecha').ir_a_fila(fila).tiene_estado()) {
					var fecha = this.ef('fecha').ir_a_fila(fila).fecha();
					var hoy = new $date;
					if (fecha > hoy) {
						notificacion.limpiar();
						notificacion.agregar('$msj_fecha');
						notificacion.mostrar();
						this.ef('fecha').ir_a_fila(fila).resetear_estado();
					} 
				}
			}
		}

		";
	}
}
?>