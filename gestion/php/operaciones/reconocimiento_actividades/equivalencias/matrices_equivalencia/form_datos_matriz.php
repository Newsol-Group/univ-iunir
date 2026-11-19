<?php
/**
 *
 *
 * @package Reconocimiento de Actividades
 * @subpackage Generar Matriz de Equivalencia
 */
class form_datos_matriz extends guarani_pers_ei_formulario  
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
			echo'<tbody>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('nombre', '130px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('nro_resolucion', '130px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=2>';
					$this->generar_html_ef('descripcion', '130px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('alcance', '130px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('propuesta_origen', '130px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('plan_origen', '130px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('plan_version_origen', '130px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan>';
					$this->generar_html_ef('aplicacion_automatica', '130px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan>';
					$this->generar_html_ef('activo', '130px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('nro_resolucion_baja', '130px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('fecha_baja', '130px');
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
		$alcance_general	= equivalencia::matriz_alcance_general;
		$alcance_propuesta	= equivalencia::matriz_alcance_entre_propuestas;
		$alcance_prop_plan	= equivalencia::matriz_alcance_entre_planes_otra_prop; 
		$alcance_plan		= equivalencia::matriz_alcance_entre_planes_misma_prop;
		$alcance_version	= equivalencia::matriz_alcance_entre_versiones;
		$no					= comunes::no;
		
		echo "

		//---- Procesamiento de EFs -----------------------------------
		
		/**
		 * Se determina qué campos serán accesibles para editar la Propuesta-Plan y Versión origen de la matriz, 
		 * según el alcance seleccionado para la misma.
		 */
		{$this->objeto_js}.evt__alcance__procesar = function(es_inicial)
		{
			var alcance;
			var mostrar_propuesta	= false;
			var mostrar_plan 		= false;
			var mostrar_version 	= false;	
		
			if (this.ef('alcance').tiene_estado()) {
				alcance = this.ef('alcance').get_estado();
				if (alcance == '$alcance_propuesta') {
					mostrar_propuesta = true;
				} else if (alcance == '$alcance_prop_plan' || alcance == '$alcance_plan') {
					mostrar_propuesta	= true;
					mostrar_plan		= true;
				} else if (alcance == '$alcance_version') {
					mostrar_propuesta	= true;
					mostrar_plan		= true;
					mostrar_version 	= true;
				}
			}
			
			// A los campos que se ocultan también se les resetea el estado.
			this.ef('propuesta_origen').mostrar(mostrar_propuesta, !mostrar_propuesta);
			this.ef('plan_origen').mostrar(mostrar_plan, !mostrar_plan);
			this.ef('plan_version_origen').mostrar(mostrar_version, !mostrar_version);
		}

		/**
		 * Cuando se setea la propuesta origen se verifica si el alcance no es General y el nombre de la matriz no
		 * está seteado, entonces se setea el nombre de la misma por defecto para la matriz.
		 */
		{$this->objeto_js}.evt__propuesta_origen__procesar = function(es_inicial)
		{
			if (!es_inicial) {
				var alcance = this.ef('alcance').get_estado();
				if (alcance != '$alcance_general' && this.ef('propuesta_origen').tiene_estado()) {
					if (!this.ef('nombre').tiene_estado()) {
						var prop_origen = this.ef('propuesta_origen').get_estado();
						var params = [prop_origen];
						this.controlador.ajax('nombre_matriz_defecto', params, this, this.respuesta_nombre_matriz_defecto);
					}
				} 
			}
		}
		
		{$this->objeto_js}.respuesta_nombre_matriz_defecto = function(respuesta) 
		{
			if (typeof respuesta == 'object') {
				var nombre_prop = respuesta['nombre'];
				this.ef('nombre').set_estado(nombre_prop);
			}
		}
		
		/**
		 * Se determina si se muestran y son obligatorios los campos de nro de resolución y fecha baja, según si la
		 * matriz está o no activa.
		 */
		{$this->objeto_js}.evt__activo__procesar = function(es_inicial)
		{
			var mostrar_baja = false;
			if (this.ef('activo').tiene_estado()) {
				activo = this.ef('activo').get_estado();
				if (activo == '$no') {
					mostrar_baja = true;
				}
			}
			// Se ocultan o muestran los campos según lo indique el flag. Los campos que se ocultan se resetean.
			this.ef('nro_resolucion_baja').mostrar(mostrar_baja, !mostrar_baja);
			this.ef('fecha_baja').mostrar(mostrar_baja, !mostrar_baja);
		}
		";
	}
}

?>