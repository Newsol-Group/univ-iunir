<?php
class form_sit_laboral extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" width="100%">';
			echo '<tbody>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('trabajo_existe', '320px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('trabajo_hace', '240px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('trabajo_con_desc_jubilatorios', '360px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('trabajo_familiar', '360px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('trabajo_recibe_pago', '360px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('trabajo_ocupacion', '240px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('trabajo_hora_sem', '240px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('trabajo_carrera', '240px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('trabajo_tarea_descripcion', '240px');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
	}
	
	function extender_objeto_js()
	{
		$con_trabajo = persona::trabajo;
		$msj_campos_trabajo = guarani_mensajes::get('800_dato_dep_obligatorio', array('campo relacionado al trabajo de la persona', 'Condición de Actividad durante la semana pasada'));
		
		echo "

		//---- Validacion general ----------------------------------------------
		
		{$this->objeto_js}.evt__validar_datos = function()
		{
			var ok = true;
				
			// Chequea que los campos referentes al trabajo esten seteados si el campo de trabajo existe tiene el valor de que la persona trabaja.
		
			if (this.ef('trabajo_existe').tiene_estado()) {	
				var estado_laboral = this.ef('trabajo_existe').get_estado();
	
				if ((estado_laboral == $con_trabajo) && 
					(!this.ef('trabajo_hace').tiene_estado() || !this.ef('trabajo_familiar').tiene_estado() || 
					 !this.ef('trabajo_con_desc_jubilatorios').tiene_estado || !this.ef('trabajo_recibe_pago').tiene_estado() ||
					 !this.ef('trabajo_ocupacion').tiene_estado() || !this.ef('trabajo_hora_sem').tiene_estado() ||
					 !this.ef('trabajo_carrera').tiene_estado() || !this.ef('trabajo_tarea_descripcion').tiene_estado())) {
					ok = false;
					notificacion.agregar('$msj_campos_trabajo');
				}
			}			
			
			return ok;
		}

		{$this->objeto_js}.evt__trabajo_existe__procesar = function(es_inicial)
		{
			var mostrar_campos_trabajo = false;	
			var estado_laboral = this.ef('trabajo_existe').get_estado();
	
			if (estado_laboral == $con_trabajo){
				mostrar_campos_trabajo = true;
			}

			// Los campos que no se muestran, es decir se ocultan, se resetean.

			this.ef('trabajo_hace').mostrar(mostrar_campos_trabajo,!mostrar_campos_trabajo);
			this.ef('trabajo_familiar').mostrar(mostrar_campos_trabajo,!mostrar_campos_trabajo);
			this.ef('trabajo_con_desc_jubilatorios').mostrar(mostrar_campos_trabajo,!mostrar_campos_trabajo);
			this.ef('trabajo_recibe_pago').mostrar(mostrar_campos_trabajo,!mostrar_campos_trabajo);
			this.ef('trabajo_ocupacion').mostrar(mostrar_campos_trabajo,!mostrar_campos_trabajo);			
			this.ef('trabajo_hora_sem').mostrar(mostrar_campos_trabajo,!mostrar_campos_trabajo);
			this.ef('trabajo_carrera').mostrar(mostrar_campos_trabajo,!mostrar_campos_trabajo);
			this.ef('trabajo_tarea_descripcion').mostrar(mostrar_campos_trabajo,!mostrar_campos_trabajo);
		} 
		";
	}

}
?>