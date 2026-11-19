<?php
/**
 * 
 *
 * @package		Matrícula
 * @subpackage 	Alumnos
 */
class form_parametros extends toba_ei_formulario
{
	function generar_layout()
	{
		$this->generar_html_ef('documento');
		echo '<table cellspacing="1" cellpadding="1" border="0" width="70%">';
			echo '<tr>';
				echo '<td>';
				$this->generar_html_ef('aplicar_cohorte');
				echo '</td>';
				echo '<td>';
				$this->generar_html_ef('periodo', '0px');
				echo '</td>';
				echo '<td>';
				$this->generar_html_ef('cohorte_desde', '0px');
				echo '</td>';
				echo '<td>';
				$this->generar_html_ef('cohorte_hasta', '0px');
				echo '</td>';
			echo '</tr>';
		echo '</table>';
		echo '<table cellspacing="1" cellpadding="1" border="0" width="100%">';
			echo '<tr>';
				echo '<td colspan="2">';
				$this->generar_html_ef('observaciones');
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<td>';
				$this->generar_html_ef('clasificacion');
				echo '</td>';
				echo '<td>';
				$this->generar_html_ef('hacia', '70px');
				echo '</td>';
			echo '</tr>';
		echo '</table>';
		$oculto_ult_vers = $this->_elemento_formulario['ultima_version_nombre']->get_descripcion_estado('html');
		if (trim($oculto_ult_vers) == '') {
			echo '<fieldset><legend>Versión a la cual se pasarán los Alumnos:</legend>';
			echo '<table cellspacing="1" cellpadding="1" border="0" width="100%">';
				echo '<tr>';
				echo '<td colspan="2">';
				$this->generar_html_ef('ultima_version_plan_nombre');
				echo '</td>';
				echo '<td colspan="2">';
				$this->generar_html_ef('ultima_version_nombre');
				echo '</td>';
				echo '</tr>';
			echo '</table></fieldset>';
		}	else {
			$this->generar_html_ef('ultima_version_plan_nombre');
			$this->generar_html_ef('ultima_version_nombre');
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$periodo_desde = 'D';
		$periodo_hasta = 'H';
		$periodo_entre = 'E';
		$categoria_version = alumno::cat_cambio_version;
		$destino_ult_version = alumno::dest_ultima_version;
		$msj_obligatorio = guarani_mensajes::get('800_dato_dep_obligatorio', array('Año académico', 'Aplicar cohorte'));
		
		echo "
			
		//---- Validación general de datos -----------------------------
		
		{$this->objeto_js}.evt__validar_datos = function()
		{
			var ok = true;
			
			// Si se completa aplicar cohorte y se selecciona un período, debe seleccionarse el año correspondiente.
			if (this.ef('aplicar_cohorte').chequeado() && this.ef('periodo').tiene_estado()) {
				var periodo = this.ef('periodo').get_estado();
				if ((periodo == '$periodo_desde' && !this.ef('cohorte_desde').tiene_estado()) || 
					(periodo == '$periodo_hasta' && !this.ef('cohorte_hasta').tiene_estado()) ||
					(periodo == '$periodo_entre' && (!this.ef('cohorte_desde').tiene_estado() || !this.ef('cohorte_hasta').tiene_estado())) ) {
					ok = false;
					notificacion.agregar('$msj_obligatorio');
				}	
			} 
			return ok;
		}
		
		//---- Procesamiento de EFs --------------------------------
		
		/**
		 * Cuando se procesa un cambio, no inicial, en el ef que indica el destino al cual se cambiarán los alumnos, 
		 * se realiza un submit para agregar o eliminar la dependencia 'cuadro_destinos_disponibles' que debe visualizarse 
		 * sólo para la opción de cambio hacia 'Planes Activos y Vigentes', y para completar los campos de datos sobre la 
		 * última Versión Vigente del Plan si corresponde.
		 * Además se ocultan o muestran según correspondan al Tipo de Cambio los campos de la descripción de la última
		 * versión vigente.
		**/
		{$this->objeto_js}.evt__hacia__procesar = function(es_inicial)
		{
			var mostrar = false;
			var estado = this.ef('hacia').get_estado();
			if (estado == '$destino_ult_version') {
				mostrar = true;
			} 
	
			this.ef('ultima_version_nombre').mostrar(mostrar, !mostrar);
			this.ef('ultima_version_plan_nombre').mostrar(mostrar, !mostrar);
			
			if (!es_inicial && this.ef('hacia').tiene_estado()) {
				this.submit();
			} 
		}
		
		// Habilita o deshabilita el campo de Período del que a su vez dependen de los filtros por años académicos, 
		// según el valor del checkbox Aplicar Cohorte.
		{$this->objeto_js}.evt__aplicar_cohorte__procesar = function(es_inicial)
		{
			var aplicar = false;
			if (this.ef('aplicar_cohorte').chequeado()) {
				aplicar = true;
			} 
			this.ef('periodo').set_solo_lectura(!aplicar);
			if (!aplicar) {
				this.ef('periodo').resetear_estado();
			}	
		}
		
		// Habilita o deshabilita y resetea los filtros por años académicos de alumnos, según el Período seleccionado.
		{$this->objeto_js}.evt__periodo__procesar = function(es_inicial)
		{
			var periodo = this.ef('periodo').get_estado();	
			var mostrar_desde = false;
			var mostrar_hasta = false;
			
			if (periodo == '$periodo_desde') {
				mostrar_desde = true;
			} else if (periodo == '$periodo_hasta'){
				mostrar_hasta = true;
			} else if (periodo == '$periodo_entre') {
				mostrar_desde = true;
				mostrar_hasta = true;
			} 
			
			this.ef('cohorte_desde').mostrar(mostrar_desde, !mostrar_desde);
			this.ef('cohorte_hasta').mostrar(mostrar_hasta, !mostrar_hasta);
		}
		";
	}
}