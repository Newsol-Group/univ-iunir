<?php
/**
 *
 *
 * @package		Matriculas
 * @subpackage	Personas
 * 
 */
class form_documentos extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$date = guarani_fecha::get_hoy_js();
		$CUIL = persona::doc_CUIL;
		$DNI_temporal = persona::doc_dni_temporal;
		$visa_temporal = persona::visa_temporal;
		$visa_permanente = persona::visa_permanente;
		$mensaje_fechas_dni_temp = guarani_mensajes::get('800_dato_dep_obligatorio', array('fecha de otorgamiento y fecha de vencimiento del DNI', 'tipo de documento'));
		$mensaje_otorg = guarani_mensajes::get('800_control_fechas', array(' de otorgamiento', 'mayor o igual', ' actual'));
		$mensaje_venc = guarani_mensajes::get('800_control_fechas', array(' de vencimiento', 'menor', ' de otorgamiento ni a la fecha actual'));

		echo "
		
		//---- Validacion de Datos --------------------------------
		
		{$this->objeto_js}.evt__validar_datos = function()
		{
			var ok = true;
			var hoy = new $date;
		
			if (this.ef('fecha_vencimiento').tiene_estado() && !this.ef('fecha_vencimiento').es_oculto()) {
				var fecha_venc = this.ef('fecha_vencimiento').fecha();
				var fecha_otorg = this.ef('fecha_otorgamiento').fecha();
				if ((fecha_venc <= hoy) || (fecha_venc <= fecha_otorg)) {
					this.ef('fecha_vencimiento').set_error('$mensaje_venc');
					ok = false;
				}
			}
		
			return ok;
		}
		
		//---- Validacion de EFs -----------------------------------
		
		{$this->objeto_js}.evt__fecha_otorgamiento__validar = function()
		{
			var ok = true;
			var hoy = new $date;
			
			if (this.ef('fecha_otorgamiento').tiene_estado() && !this.ef('fecha_otorgamiento').es_oculto()) {
				if (this.ef('fecha_otorgamiento').fecha() > hoy) {
					this.ef('fecha_otorgamiento').set_error('$mensaje_otorg');
					ok = false;		
				}
			}
			
			return ok;
		}

		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__tipo_documento__procesar = function(es_inicial)
		{
			var tipo = this.ef('tipo_documento').get_estado();
			var mostrar_campos_fechas = false;
			var mostrar_cuit = false;
			
			if (tipo == $DNI_temporal) {
				mostrar_campos_fechas = true;
			} 
			
			// Los campos que no se muestran, es decir se ocultan, se resetean.
			this.ef('fecha_otorgamiento').mostrar(mostrar_campos_fechas,!mostrar_campos_fechas);
			this.ef('fecha_vencimiento').mostrar(mostrar_campos_fechas,!mostrar_campos_fechas);
			
			//Según el tipo de documento seleccionado se muestra el campo nro_documento o el campo cuit, para completar el nro. 
			if (tipo == $CUIL) {
				mostrar_cuit = true;
			}
			
			this.ef('cuit').mostrar(mostrar_cuit, !mostrar_cuit);
			this.ef('nro_documento').mostrar(!mostrar_cuit, mostrar_cuit)
		}
		
		";
	}

}
?>
