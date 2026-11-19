<?php
/**
 *
 *
 * @package		Cursadas
 * @subpackage	Comisiones
 * 
 */
class form_datos_grales_subcomision extends guarani_pers_ei_formulario  
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
			echo'<tbody>';
				echo '<tr>';
					echo '<td colspan=2>';
					$this->generar_html_ef('nombre');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=2>';
					$this->generar_html_ef('tipo_clase');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('letra_desde');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('letra_hasta');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('cupo');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('inscripcion_habilitada', '145px');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function extender_objeto_js()
	{
		$msj_rango_letras = guarani_mensajes::get('800_dato_dep_invalido', array('Letra Hasta', 'Letra Desde'));
		$msj_completar_rango = guarani_mensajes::get('800CUR_completar_letras', array('Subcomisión'));
	
		echo "
		
		//---- Validación general de datos -----------------------------
		
		{$this->objeto_js}.evt__validar_datos = function()
		{
			var ok = true;
			
			// Las letras desde y hasta deben completarse ambas o ninguna.
			if ((this.ef('letra_desde').tiene_estado() && !this.ef('letra_hasta').tiene_estado()) ||
				!this.ef('letra_desde').tiene_estado() && this.ef('letra_hasta').tiene_estado()) {
				notificacion.agregar('$msj_completar_rango');
				ok = false;
			}	
			//Chequeo que el campo letra desde sea menor en orden alfabético al campo letra hasta.		
			if (this.ef('letra_desde').tiene_estado() && this.ef('letra_hasta').tiene_estado()) {
				var desde = this.ef('letra_desde').get_estado();
				var hasta = this.ef('letra_hasta').get_estado();
				letra_desde = desde.toLowerCase();
				letra_hasta = hasta.toLowerCase();
				if (letra_desde.charAt(0) > letra_hasta.charAt(0)) {
					notificacion.agregar('$msj_rango_letras');
					ok = false;		
				} 
			}
			
			return ok;
		}
		
		";
	}
}
?>