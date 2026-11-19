<?php
/**
 *
 *
 * @package		Unidades de Gestión
 * @subpackage	Espacios
 *
 */
class form_filtro_asignaciones_espacios extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<fieldset><legend>Seleccionar fecha, tipo de actividad y edificio:</legend>';
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
		echo '    <tbody>';
		echo '    <tr>';
		echo '        <td width="30%">';
		$this->generar_html_ef('fecha', '70px');
		echo '        </td>';
		echo '        <td>';
		$this->generar_html_ef('ubicacion', '70px');
		echo '        </td>';
		echo '    </tr>';
		echo '    <tr>';
		echo '        <td width="30%">';
		$this->generar_html_ef('asignar', '70px');
		echo '        </td>';
		echo '        <td>';
		$this->generar_html_ef('edificio', '70px');
		echo '        </td>';
		echo '    </tr>';
		echo '    </tbody>';
		echo '</table>';
		echo '</fieldset>';
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		var fecha_anterior;

		//---- Procesamiento de EFs --------------------------------
		{$this->objeto_js}.evt__fecha__procesar = function(es_inicial)
		{
			if (es_inicial) {
				this.ef('fecha').cuando_cambia_valor('check_fechas();');
			}
		}
		
		{$this->objeto_js}.evt__ubicacion__procesar = function(es_inicial)
		{
			if (es_inicial) {
				this.ef('ubicacion').cuando_cambia_valor('check_filtros();');
			}
		}
		
		{$this->objeto_js}.evt__edificio__procesar = function(es_inicial)
		{
			if (es_inicial) {
				this.ef('edificio').cuando_cambia_valor('check_filtros();');
			}
		}
		
		{$this->objeto_js}.evt__asignar__procesar = function(es_inicial)
		{
			if (es_inicial) {
				this.ef('asignar').cuando_cambia_valor('check_filtros();');
			}
		}
		
		function check_fechas()
		{	
			if (typeof fecha_anterior == 'undefined') {
				fecha_anterior = {$this->objeto_js}.ef('fecha').get_estado();
			}
			
			check_filtros();
		}
		
		function check_filtros()
		{
			let filtros_ok = 
				{$this->objeto_js}.ef('fecha').tiene_estado() &&
				(fecha_anterior !== {$this->objeto_js}.ef('fecha').get_estado()) &&
				{$this->objeto_js}.ef('ubicacion').tiene_estado() &&
				{$this->objeto_js}.ef('edificio').tiene_estado() &&
				({$this->objeto_js}.ef('asignar').tiene_estado() != 0);
			
			fecha_anterior = {$this->objeto_js}.ef('fecha').get_estado();
				
			if( filtros_ok )
				{$this->objeto_js}.fitrar();
		}
		
		{$this->objeto_js}.fitrar = function()
		{
			this.set_evento(new evento_ei('modificacion', true, false, [], true));
		}
		";
	}
}
?>