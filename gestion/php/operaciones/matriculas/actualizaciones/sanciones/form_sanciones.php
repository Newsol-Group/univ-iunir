<?php
class form_sanciones extends guarani_pers_ei_formulario
{

	function generar_layout()
	{
		echo '<table border=0>';
    	echo '<tr>';
    		echo '<td colspan=2>';
    		$this->generar_html_ef('persona');
    		echo '</td>';
    	echo '</tr>';
    	echo '<tr>';
    		echo '<td colspan=2>';
    		$this->generar_html_ef('propuesta');
    		echo '</td>';
    	echo '</tr>';
    	echo '<tr>';
    		echo '<td>';
			$this->generar_html_ef('tipo_sancion');
			echo '</td>';
			echo '<td>';
			$this->generar_html_ef('fecha_desde');
			echo '</td>';
		echo '</tr>';
		echo '<tr>';
			echo '<td>';
			$this->generar_html_ef('documento_alta');
			echo '</td>';
			echo '<td>';
			$this->generar_html_ef('fecha_hasta');
			echo '</td>';
		echo '</tr>';
		echo '<tr>';
			echo '<td colspan=2>';
			$this->generar_html_ef('observaciones');
			echo '</td>';
		echo '</tr>';
		echo '</table>';
		echo "<fieldset title='Datos de Cancelación' style='padding-left: 0px;padding-right: 0px;'><legend>Datos de Cancelación</legend>";
			echo "<table border=0 width='100%'>";
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('documento_cancelacion');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('fecha_cancelacion');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=2>';
					$this->generar_html_ef('observaciones_cancelacion');
					echo '</td>';
				echo '</tr>';
			echo '</table>';
		echo '</fieldset>';
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$mensaje_fecha_hasta = guarani_mensajes::get('800_control_fechas', array('Desde', 'mayor', 'Hasta.'));
		$mensaje_fecha_cancelacion = guarani_mensajes::get('800_control_fecha_entre', array('Cancelación', 'Desde', 'Hasta'));
		
		echo "
		
		//---- Validacion de EFs -----------------------------------
		
		{$this->objeto_js}.evt__fecha_hasta__validar = function()
		{
			var ok = true;
			if (this.ef('fecha_desde').tiene_estado() && this.ef('fecha_hasta').tiene_estado()) {
			
				var fecha_desde = this.ef('fecha_desde').fecha();
				var fecha_hasta = this.ef('fecha_hasta').fecha();
				
				if (fecha_desde > fecha_hasta) {
					this.ef('fecha_hasta').set_error('$mensaje_fecha_hasta');
					ok = false;		
				}
			}
			return ok;
		}
		
		{$this->objeto_js}.evt__fecha_cancelacion__validar = function()
		{
			var ok = true;
			if (this.ef('fecha_cancelacion').tiene_estado() && 
				this.ef('fecha_desde').tiene_estado() && 
				this.ef('fecha_hasta').tiene_estado()) {
				
				var fecha_desde = this.ef('fecha_desde').fecha();
				var fecha_hasta = this.ef('fecha_hasta').fecha();
				var fecha_cancelacion = this.ef('fecha_cancelacion').fecha();				
				
				var cancelacion_menor_que_desde = (fecha_cancelacion < fecha_desde);
				var cancelacion_mayor_que_hasta = ((this.ef('fecha_hasta').tiene_estado() && !(fecha_desde > fecha_hasta)) && (fecha_cancelacion > fecha_hasta));
				
				if (cancelacion_menor_que_desde || cancelacion_mayor_que_hasta) {
					this.ef('fecha_cancelacion').set_error('$mensaje_fecha_cancelacion');
					ok = false;
				}
			}
			return ok;
		}
		";
	}


}
?>