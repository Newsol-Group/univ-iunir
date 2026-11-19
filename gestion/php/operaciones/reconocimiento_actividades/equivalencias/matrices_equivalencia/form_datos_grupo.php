<?php
/**
 *
 *
 * @package Reconocimiento de Actividades
 * @subpackage Generar Matriz de Equivalencia
 */
class form_datos_grupo extends guarani_pers_ei_formulario  
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
			echo'<tbody>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('nombre', '110px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('nro_resolucion_alta', '110px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('traslada_nota', '110px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('activo', '110px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('nro_resolucion_baja', '110px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('fecha_baja', '110px');
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
		$no = comunes::no;
		$id_form_ml_activ_origen	= $this->controlador()->dependencia('actividades_origen')->get_id_objeto_js();
		$id_form_ml_activ_destino	= $this->controlador()->dependencia('actividades_destino')->get_id_objeto_js();
		
		echo "
		
		//---- Procesamiento de EFs -----------------------------------
		
		/**
		 * Se determina si se muestran y son obligatorios los campos de nro de resolución y fecha baja, según si el
		 * grupo está o no activo.
		 */
		{$this->objeto_js}.evt__activo__procesar = function(es_inicial)
		{
			var mostrar_baja = false;
			if (this.ef('activo').tiene_estado()) {
				activo = this.ef('activo').get_estado() 
				if (activo == '$no') {
					mostrar_baja = true;
				}
			}
			// Se ocultan o muestran los campos según lo indique el flag. Los campos que se muestran se setean obligatorios.
			// Los campos que se ocultan se resetean.
			this.ef('nro_resolucion_baja').mostrar(mostrar_baja, !mostrar_baja);
			this.ef('nro_resolucion_baja').set_obligatorio(mostrar_baja);
			this.ef('fecha_baja').mostrar(mostrar_baja, !mostrar_baja);
			this.ef('fecha_baja').set_obligatorio(mostrar_baja);		
		}
		
		{$this->objeto_js}.evt__traslada_nota__procesar = function(es_inicial)
		{
			var activar = false;
			if (this.ef('traslada_nota').chequeado()) {
				activar = true;
			}
			// Se activan los check de los campos notas de ambos forms ml de actividades origen y destino, o se desactivan
			// y resetean según se corresponda con el valor de traslada nota del grupo.
			var filas_origen = $id_form_ml_activ_origen.filas();
			var filas_destino = $id_form_ml_activ_destino.filas();
			if (activar) {
				for (id_fila in filas_origen) {
      				$id_form_ml_activ_origen.ef('nota').ir_a_fila(filas_origen[id_fila]).activar();
 				}
 				for (id_fila in filas_destino) {
      				$id_form_ml_activ_destino.ef('nota').ir_a_fila(filas_destino[id_fila]).activar();
 				}
			} else {
				 for (id_fila in filas_origen) {
					$id_form_ml_activ_origen.ef('nota').ir_a_fila(filas_origen[id_fila]).resetear_estado();
      				$id_form_ml_activ_origen.ef('nota').ir_a_fila(filas_origen[id_fila]).desactivar();
				}
				for (id_fila in filas_destino) {
					$id_form_ml_activ_destino.ef('nota').ir_a_fila(filas_destino[id_fila]).resetear_estado();
      				$id_form_ml_activ_destino.ef('nota').ir_a_fila(filas_destino[id_fila]).desactivar();
				}
			}
		}
				
		";
	}
}

?>