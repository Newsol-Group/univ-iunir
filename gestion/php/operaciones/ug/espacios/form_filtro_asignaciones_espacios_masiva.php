<?php
/**
 *
 *
 * @package		Unidades de Gestión
 * @subpackage   Espacios
 * 
 */
class form_filtro_asignaciones_espacios_masiva extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<fieldset><legend>Seleccionar ubicacion y edificio, período lectivo y nombre de la actividad:</legend>';
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
		echo '    <tbody>';
		echo '    <tr>';
		echo '        <td width="50%">';
		$this->generar_html_ef('ubicacion', '70px');
		echo '        </td>';
		echo '        <td>';
		$this->generar_html_ef('anio_academico', '100px');
		echo '        </td>';
		echo '    </tr>';
		echo '    <tr>';
		echo '        <td width="50%">';
		$this->generar_html_ef('edificio', '70px');
		echo '        </td>';
		echo '        <td>';
		$this->generar_html_ef('periodo_lectivo', '100px');
		echo '        </td>';
		echo '    </tr>';
		echo '    <tr>';
		echo '        <td colspan=2>';
		$this->generar_html_ef('elemento', '70px');
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
		//---- Procesamiento de EFs --------------------------------
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
		
		{$this->objeto_js}.evt__anio_academico__procesar = function(es_inicial)
		{
			if (es_inicial) {
				this.ef('anio_academico').cuando_cambia_valor('check_filtros();');
			}
		}
		
		{$this->objeto_js}.evt__periodo_lectivo__procesar = function(es_inicial)
		{
			if (es_inicial) {
				this.ef('periodo_lectivo').cuando_cambia_valor('check_filtros();');
			}
		}
		
		{$this->objeto_js}.evt__elemento__procesar = function(es_inicial)
		{
			if (es_inicial) {
				this.ef('elemento').cuando_cambia_valor('check_filtros();');
			}
		}
		
		function check_filtros()
		{
			let filtros_ok = 
				{$this->objeto_js}.ef('ubicacion').tiene_estado() &&
				{$this->objeto_js}.ef('edificio').tiene_estado() &&
				{$this->objeto_js}.ef('anio_academico').tiene_estado() &&
				{$this->objeto_js}.ef('periodo_lectivo').tiene_estado() &&
				{$this->objeto_js}.ef('elemento').tiene_estado();
			
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