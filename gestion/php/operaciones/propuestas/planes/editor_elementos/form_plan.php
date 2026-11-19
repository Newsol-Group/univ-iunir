<?php
class form_plan extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table border="0">';

		echo '<tr>';
		echo '<td colspan="3">';
		$this->generar_html_ef('administra_cursos', '250px');
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td colspan="3">';
		$this->generar_html_ef('propuesta');
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td colspan="3">';
		$this->generar_html_ef('nombre');
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td width="250px">';
		$this->generar_html_ef('codigo');
		echo '</td>';
		echo '<td>';
		$this->generar_html_ef('estado');
		$this->generar_html_ef('plan_estado_descripcion');
		echo '</td>';
		echo '<td>';
		echo ' ';
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td colspan="1">';
		$this->generar_html_ef('version');
		echo '</td>';
		echo '<td colspan="2">';
		$this->generar_html_ef('inscripcion_habilitada');
		echo '</td>';
		echo '</tr>';
		
		echo '<tr>';
		echo '<td colspan="3">';
		$this->generar_html_ef('cobrable');
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td colspan="3">';
		$this->generar_html_ef('modalidad');
		echo '</td>';
		echo '</tr>';


		echo '<tr>';
		echo '<td>';
		$this->generar_html_ef('documento_alta');
		echo '</td>';
		echo '<td colspan="2">';
		$this->generar_html_ef('fecha_entrada_vigencia');
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td>';
		$this->generar_html_ef('documento_baja');
		echo '</td>';
		echo '<td colspan="2">';
		$this->generar_html_ef('fecha_baja');
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td colspan="1">';
		$this->generar_html_ef('vigencia_minima');
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td colspan="3">';
		$this->generar_html_ef('creditos_tit');
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td>';
		$this->generar_html_ef('tope_creditos_cursado');
		echo '</td>';
		echo '<td colspan="2">';
		$this->generar_html_ef('tope_creditos_reg_vigentes');
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td colspan="3">';
		$this->generar_html_ef('duracion_tit');
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td>';
		$this->generar_html_ef('duracion_en_anios');
		echo '</td>';
		echo '<td>';
		$this->generar_html_ef('duracion_en_meses');
		echo '</td>';
		echo '<tr>';

		echo '</tr>';
		echo '<td>';
		$this->generar_html_ef('duracion_teorica');
		echo '</td>';
		echo '</tr>';

		echo '</table>';
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$id_js_form_version = $this->controlador()->dependencia('form_plan_version')->get_id_objeto_js();

		echo "
			
		//---- Validación de EFs -----------------------------------
		{$this->objeto_js}.validar = function()
		{   
			if (!this.controlador.get_validar_todo()) {
				return true;
			}
			var ok = true;
			var validacion_particular = 'evt__validar_datos';
			if(this._evento && this._evento.validar) {
					if (existe_funcion(this, validacion_particular)) {
						ok = this[validacion_particular]();        
					}
					for (id_ef in this._efs) {
						ok = this.validar_ef(id_ef) && ok;
					}
			} else {
				this.resetear_errores();
			}

			if (!ok) {
					this.reset_evento();
			}
			return ok;
			};
		
		//---- Procesamiento de EFs -----------------------------------
		/**
			* Si el plan estructurado es para Administrar Cursos, se ocultan los datos correspondientes.
			*/
		{$this->objeto_js}.evt__administra_cursos__procesar = function(es_inicial)
		{    
			var campos_estruct = true;
			if (this.ef('administra_cursos').chequeado()) {        
				campos_estruct = false;
			}
			// Para los Planes Estructurados que Administran Cursos se permiten editar menos campos.
			this.ef('version').mostrar(campos_estruct, !campos_estruct);
			this.ef('documento_baja').mostrar(campos_estruct, !campos_estruct);
			this.ef('fecha_baja').mostrar(campos_estruct, !campos_estruct);
			this.ef('vigencia_minima').mostrar(campos_estruct, !campos_estruct);
			this.ef('creditos_tit').mostrar(campos_estruct, !campos_estruct);
			this.ef('tope_creditos_cursado').mostrar(campos_estruct, !campos_estruct);
			this.ef('tope_creditos_reg_vigentes').mostrar(campos_estruct, !campos_estruct);
			this.ef('duracion_teorica').mostrar(campos_estruct, !campos_estruct);
			this.ef('duracion_en_anios').mostrar(campos_estruct, !campos_estruct);
			this.ef('duracion_en_meses').mostrar(campos_estruct, !campos_estruct);
			this.ef('duracion_tit').mostrar(campos_estruct, !campos_estruct);
			this.ef('cobrable').mostrar(campos_estruct);
			if (!campos_estruct && this.ef('cobrable').activo()) {
				this.ef('cobrable').resetear_estado();
			}
			
			/**    Los campos documento y fecha entrada vigencia se muestran actualmente como no 
				*    editables para los planes estructurados comunes. Se debe permitir su edición 
				*    para los planes que administran Cursos.
				*/
			this.ef('documento_alta').set_solo_lectura(campos_estruct);
			this.ef('fecha_entrada_vigencia').set_solo_lectura(campos_estruct);
			this.ef('fecha_entrada_vigencia').set_obligatorio(!campos_estruct);
			
			// Excluye del control de cambios los ef's de tipo barra divisora.
			this._cambios_excluir_efs = ['creditos_tit', 'duracion_tit'];
			
			/** El form plan_version se oculta ya que si el Plan Administra Cursos, 
				*    no se podrá versionar y se creará con una versión única con datos default.
				*    La dependencia siempre existe, ya que se agrega/elimina en conjunto con el 
				*    form_plan, para planes de tipo estructurados.
				*/ 
			if (!campos_estruct) { 
				var efs = {$id_js_form_version}.efs();
				for (id_ef in efs) {
					{$id_js_form_version}.ef(id_ef).resetear_estado();    
					{$id_js_form_version}.ef(id_ef).set_obligatorio(false);
				}
				{$id_js_form_version}.ocultar();
				// Se excluyen los efs del form oculto del control de cambios.
				{$id_js_form_version}._cambios_excluir_efs = efs;
			} else {
				{$id_js_form_version}.mostrar();
				{$id_js_form_version}.ef('version').set_obligatorio(true);
				{$id_js_form_version}.ef('nombre').set_obligatorio(true);
			}
		}
		";
	}

}
?>