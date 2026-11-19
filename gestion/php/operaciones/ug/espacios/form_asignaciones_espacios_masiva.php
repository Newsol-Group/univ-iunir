<?php
/**
 *
 *
 * @package		Unidades de Gestión
 * @subpackage   Espacios
 * 
 */
class form_asignaciones_espacios_masiva extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		$this->generar_html_ef('banda_horaria');
		$this->generar_html_ef('detalle');

		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
		echo '    <tbody>';
		echo '    <tr>';
		echo '        <td colspan=2>';
		$this->generar_html_ef('espacio', '110px');
		echo '        </td>';
		echo '        <td>';
		$this->generar_html_ef('estado', '80px');
		echo '        </td>';
		echo '    </tr>';
		echo '    <tr>';
		echo '        <td style="width: 110px"><label class="ei-form-etiq" style="padding-left: 8px">Horario</label></td>';
		echo '        <td>';
		$this->generar_html_ef('hora_inicio', '90px');
		echo '        </td>';
		echo '        <td>';
		$this->generar_html_ef('hora_fin', '80px');
		echo '        </td>';
		echo '    </tr>';
		echo '    <tr>';
		echo '        <td style="width: 110px"><label class="ei-form-etiq" style="padding-left: 8px">Rango de fechas</label></td>';
		echo '        <td>';
		$this->generar_html_ef('fecha_desde', '90px');
		echo '        </td>';
		echo '        <td>';
		$this->generar_html_ef('fecha_hasta', '80px');
		echo '        </td>';
		echo '    </tr>';
		echo '    </tbody>';
		echo '</table>';
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Procesamiento de EFs --------------------------------
		{$this->objeto_js}.evt__cascadas_fin = function() { setup(); }
		
		function setup() 
		{
			{$this->objeto_js}.ef('detalle').desactivar();
		}
		";
	}
}
?>