<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos de Ingreso
 * 
 */
class form_ml_requisitos extends guarani_pers_ei_formulario_ml
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$formateo = new toba_formateo('html');
		$fecha = $formateo->formato_fecha(guarani_fecha::get_hoy());
		$date = guarani_fecha::get_hoy_js();
		$msj_fecha = guarani_mensajes::get('800_control_fechas', array('de presentación', 'mayor', 'actual'));

		echo "
		
		{$this->objeto_js}.evt__presentado__procesar = function(es_inicial, fila)
		{
			var existe_boton_doc_digital = this.existe_boton_doc_digital(fila);
			var fecha = '$fecha';

			if ({$this->objeto_js}.ef('presentado').ir_a_fila(fila).chequeado()) {
				if (!es_inicial) {
					{$this->objeto_js}.ef('fecha_presentacion').ir_a_fila(fila).set_estado(fecha);
				}
				{$this->objeto_js}.ef('fecha_presentacion').ir_a_fila(fila).activar();
				{$this->objeto_js}.ef('fecha_vencimiento').ir_a_fila(fila).activar();
				if (existe_boton_doc_digital) {
					{$this->objeto_js}.activar_boton_fila(fila, 'doc_digital');
				}
			} else {
				{$this->objeto_js}.ef('fecha_presentacion').ir_a_fila(fila).resetear_estado();
				{$this->objeto_js}.ef('fecha_presentacion').ir_a_fila(fila).desactivar();
				{$this->objeto_js}.ef('fecha_vencimiento').ir_a_fila(fila).resetear_estado();
				{$this->objeto_js}.ef('fecha_vencimiento').ir_a_fila(fila).desactivar();
				if (existe_boton_doc_digital) {
					{$this->objeto_js}.desactivar_boton_fila(fila, 'doc_digital');
				}
			}
		}
		
		/**
		 * Función para chequear si un botón existe a nivel fila para el presente formulario ML
		 * (no existe una función ad hoc en la API JS de Toba)
		 */
		{$this->objeto_js}.existe_boton_doc_digital = function(fila)
		{
			nombre = '#form_41001020_requisitos' + fila + '_doc_digital';
			return $(nombre)[0];
		}
		
		//---- Validacion de EFs -----------------------------------
		
		{$this->objeto_js}.evt__validar_datos = function()
		{
			var ok = true;
			var filas = this.filas();
			for (id_fila in filas) {
				if (this.ef('presentado').ir_a_fila(filas[id_fila]).chequeado()) {
					req = this.ef('nombre').ir_a_fila(filas[id_fila]).get_estado();
					if (!this.ef('fecha_presentacion').ir_a_fila(filas[id_fila]).tiene_estado()) {
						notificacion.agregar('Fecha Presentación es obligatoria para ' + req);
						ok = false;
					}
				}
			}
			return ok;
		}
		
		//---- Validacion de EFs -----------------------------------
		
		{$this->objeto_js}.evt__fecha_presentacion__validar = function(fila)
		{			
			// Validación sobre el campo fecha.
			var hoy = new $date;
			var fecha = this.ef('fecha_presentacion').ir_a_fila(fila).fecha();			
			if (this.ef('fecha_presentacion').ir_a_fila(fila).tiene_estado() && fecha > hoy) {				
					this.ef('fecha_presentacion').ir_a_fila(fila).set_error('$msj_fecha');					
					return false;						
			}
			return true;
		}
		";
	}

}
?>