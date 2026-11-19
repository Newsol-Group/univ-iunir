<?php
/**
 *
 * 
 * @package 	Reconocimiento de Actividades
 * @subpackage 	Aprobación por Resolución
 * 
 */
class form_ayudantia extends guarani_pers_ei_formulario 
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" width="100%">';
			echo '<tbody>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('comision');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('actividad_nombre', '60px');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('anio_academico', '90px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('periodo_lectivo_nombre');
					echo '</td>';
					echo '<td colspan=2>';
					$this->generar_html_ef('docente_responsable', '130px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=3>';
					$this->generar_html_ef('descripcion');
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
		parent::extender_objeto_js();
		
		echo "
		
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__comision__procesar = function(es_inicial)
		{
			var mostrar_campos_comision = false;
			if (this.ef('comision').tiene_estado()) {
				mostrar_campos_comision = true;
			}
			// Los campos que no se muestran se resetean.
			this.ef('actividad_nombre').mostrar(mostrar_campos_comision, !mostrar_campos_comision);
			this.ef('anio_academico').mostrar(mostrar_campos_comision, !mostrar_campos_comision);
			this.ef('periodo_lectivo_nombre').mostrar(mostrar_campos_comision, !mostrar_campos_comision);
			this.ef('docente_responsable').mostrar(mostrar_campos_comision, !mostrar_campos_comision);
			this.ef('descripcion').mostrar(mostrar_campos_comision, !mostrar_campos_comision);
		}
		
		/**
		 * Los campos con datos de la comisión se deben setear de sólo lectura de manera dinámica, ya que al 
		 * estar en cáscada con el ef comisión, se les asigna el valor correspondiente al maestro y se vuelven 
		 * a habilitar.
		 */ 
		{$this->objeto_js}.evt__actividad_nombre__procesar = function(es_inicial)
		{
			this.ef('actividad_nombre').set_solo_lectura(true);
		}
		
		{$this->objeto_js}.evt__periodo_lectivo_nombre__procesar = function(es_inicial)
		{
			this.ef('periodo_lectivo_nombre').set_solo_lectura(true);
		}
		
		{$this->objeto_js}.evt__anio_academico__procesar = function(es_inicial)
		{
			this.ef('anio_academico').set_solo_lectura(true);
		}
		
		";
	}
}
?>