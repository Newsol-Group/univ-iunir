<?php
/**
 *
 *
 * @package		Unidades de Gestión
 * @subpackage   Espacios
 * 
 */
class form_asignaciones_espacios extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		$this->generar_html_ef('actividad');
		$this->generar_html_ef('detalle');
		$this->generar_html_ef('espacio');

		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
		echo '    <tbody>';
		echo '    <tr>';
		echo '        <td style="width: 100px"><label class="ei-form-etiq" style="padding-left: 6px">Horario</label></td>';
		echo '        <td>';
		$this->generar_html_ef('hora_inicio', '80px');
		echo '        </td>';
		echo '        <td>';
		$this->generar_html_ef('hora_fin', '65px');
		echo '        </td>';
		echo '        <td>';
		$this->generar_html_ef('aplicar_clases_restantes', '155px');
		echo '        </td>';
		echo '        <td>';
		$this->generar_html_ef('fecha_hasta', '80px');
		echo '        </td>';
		echo '    </tr>';
		echo '    </tbody>';
		echo '</table>';

		$this->generar_html_ef('estado');
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$si = comunes::si;
		$mensaje_asignar_restantes = guarani_mensajes::get('800UNG_asignar_restantes');
		$mensaje_modificar_restantes = guarani_mensajes::get('800UNG_modificar_restantes');
		$mensaje_eliminar_restantes = guarani_mensajes::get('800UNG_eliminar_restantes');
		
		echo "
			
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.ini = function()
		{
			{$this->objeto_js}.ef('aplicar_clases_restantes').set_solo_lectura(true);
			{$this->objeto_js}.ef('detalle').set_solo_lectura(true);
		}

		{$this->objeto_js}.evt__aplicar_clases_restantes__procesar = function(es_inicial)
		{
			if (!es_inicial) {
				setup();
			}
		}
		
		{$this->objeto_js}.evt__cascadas_fin = function()
		{
			setup();
		}
		
		function setup() 
		{
			if({$this->objeto_js}.ef('actividad').get_estado().charAt(0) === '". asignacion_espacio::tipo_comision ."') {
				{$this->objeto_js}.ef('aplicar_clases_restantes').set_solo_lectura(false);
				if({$this->objeto_js}.ef('aplicar_clases_restantes').get_estado() === '$si') {
					{$this->objeto_js}.ef('fecha_hasta').set_solo_lectura(false);
				} else {
					{$this->objeto_js}.ef('fecha_hasta').set_solo_lectura(true);
				}
			} else {
				{$this->objeto_js}.ef('aplicar_clases_restantes').set_solo_lectura(true);
				{$this->objeto_js}.ef('fecha_hasta').set_solo_lectura(true);
			}

			{$this->objeto_js}.ef('detalle').set_solo_lectura(true);
		}
		
		{$this->objeto_js}.set_evento = function(evento, hacer_submit, input)
		{
			if ({$this->objeto_js}.ef('aplicar_clases_restantes').get_estado() === '$si') {
				if(evento.id == 'asignar') {
					evento.confirmar = '$mensaje_asignar_restantes';
				} else if(evento.id == 'modificar') { 
					evento.confirmar = '$mensaje_modificar_restantes';
				} else { //es evento eliminar
					evento.confirmar = '$mensaje_eliminar_restantes';
				}
			} 
			ei.prototype.set_evento.call(this, evento, hacer_submit);
		}
		";
	}
}
?>