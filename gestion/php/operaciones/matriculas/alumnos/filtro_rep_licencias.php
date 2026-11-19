<?php
/**
 *
 * 
 * @package 	Matrícula
 * @subpackage 	Reportes
 * 
 */
class filtro_rep_licencias extends guarani_pers_ei_filtro 
{
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$rango_fechas = licencia::alcance_rango_fechas;
		$periodos = licencia::alcance_periodos_lectivos;

		echo "
		{$this->objeto_js}.refrescar_botonera = function () {
			var tipo = {$this->objeto_js}.get_tipo();
			var combo = $$(this._instancia + '_nuevo');
			var hay_faltantes = false;
			
			for (var i=0; i<combo.length; i++) {
				var id = combo.options[i].value;
				if (id != 'nopar') {
					if (this.dependencia_prohibida(combo.options[i].value, tipo)) {
						combo.options[i].disabled = true;
					} else {
						//-- Esta seleccionada la fila?
						if (in_array(id, this._filas)) {
							combo.options[i].disabled = true;
						} else {
							hay_faltantes = true;
							combo.options[i].disabled = false;
						}
					}
				}
			}
			var display = (! hay_faltantes) ? 'none' : '';
			$$('botonera_' + this._instancia).style.display = display;
		};

		{$this->objeto_js}.dependencia_prohibida = function(fila, tipo)
		{
			var prohibida = false;
			if (tipo == 'nopar') {
				if ((fila == 'fecha_desde') || (fila == 'fecha_hasta') || (fila == 'anio_academico') || (fila == 'periodo_lectivo')) {
					prohibida = true;
				}
			}
			if (tipo == '$rango_fechas') {
				if ((fila == 'anio_academico') || (fila == 'periodo_lectivo')) {
					prohibida = true;
				}
			}
			if (tipo == '$periodos') {
				if ((fila == 'fecha_desde') || (fila == 'fecha_hasta')) {
					prohibida = true;
				}
				if ((fila == 'periodo_lectivo') && (!this.ef('anio_academico').tiene_estado())) {
					prohibida = true;
				}
			}
			return prohibida;
		}

		{$this->objeto_js}.get_tipo = function(es_inicial)
		{
			return {$this->objeto_js}.ef('tipo').get_estado();
		}
		
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__tipo__procesar = function(es_inicial)
		{
			var tipo = this.ef('tipo').get_estado();
			if (tipo == 'nopar') {
				if (this.esta_activa('anio_academico')) {
					this.eliminar_fila('anio_academico');
				}
				if (this.esta_activa('fecha_desde')) {
					this.eliminar_fila('fecha_desde');
				}
				if (this.esta_activa('fecha_hasta')) {
					this.eliminar_fila('fecha_hasta');
				}
				if (this.esta_activa('periodo_lectivo')) {
					this.eliminar_fila('periodo_lectivo');
				}
			}
			if (tipo == '$rango_fechas') {
				if (this.esta_activa('anio_academico')) {
					this.eliminar_fila('anio_academico');
				}
				if (this.esta_activa('periodo_lectivo')) {
					this.eliminar_fila('periodo_lectivo');
				}
			}
			if (tipo == '$periodos') {
				if (this.esta_activa('fecha_desde')) {
					this.eliminar_fila('fecha_desde');
				}
				if (this.esta_activa('fecha_hasta')) {
					this.eliminar_fila('fecha_hasta');
				}
			}
			this.refrescar_botonera();
		}
		";
	}
}
?>