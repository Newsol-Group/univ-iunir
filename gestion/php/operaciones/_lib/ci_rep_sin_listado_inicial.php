<?php 
abstract class ci_rep_sin_listado_inicial extends ci_reporte
{
	//---- cuadro -----------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_filtro_asociado($this->dep('filtro'));
		$cuadro->set_usar_ordenamiento_con_cortes();
		/**
 		 *	Si no está seteado el filtro entonces es la carga inicial del cuadro (el usuario no presionó filtrar).
		 *	No se traen datos (no se ejecuta la consulta) y se pone un mensaje en el cuadro que indica al usuario
		 *	que debe filtrar para obtener datos.
		*/
		$this->msj_eof = '800_eof_cuadro';
		if (!isset($this->s__filtro)) {
			$this->indx_msg_eof .= '_sinfiltro';
		} else {
			//El usuario presiono filtrar (ya sea con datos o con el filtro vacío).
			$datos = $this->get_datos($this->s__filtro);
			$cuadro->set_datos($datos);
		}
		$cuadro->set_eof_mensaje($this->get_msg_eof());
	}

	//---- filtro -----------------------------------------------------------------------

	function evt__filtro__cancelar()
	{
		unset($this->s__datos);
		unset($this->s__filtro);
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$etiquetas = guarani_etiquetas::get_tabla($this->get_tabla_principal());
		$msj_confirm_consulta = guarani_mensajes::get('800_conf_filtrovacio', array($etiquetas->get_plural()));
		$id_js_filtro = $this->dependencia('filtro')->get_id_objeto_js();
		
		parent::extender_objeto_js();
		
		//La confirmación de la consulta se realiza solo si se está en la pantalla de selección (ya que ni el filtro ni su evento
		//Filtrar existen en la pantalla de edición).
		if ($this->get_id_pantalla() == 'pant_inicial') {

			echo "
				//Chequea si ninguno de los campos (columnas) del filtro tienen estado, es decir el filtro es vacío, el usuario
				//debe confirmar la ejecución de la consulta.
				{$id_js_filtro}.evt__filtrar = function() {
					//Obtiene las filas y los campos de la variable interna que se mantiene en el filtro de columnas y valores (ei_filtro.js).
					var filas = this._filas;
					var campos = this._efs;
					var confirmar_cons = true;
					for (id_fila in filas) {
						var id_ef = this._filas[id_fila];
						if (campos[id_ef].tiene_estado()) {
							confirmar_cons = false;
							break;
						}
					}
					if (confirmar_cons) {
						return confirmar_consulta();
					}
				}

				function confirmar_consulta(proyecto, operacion, url, es_popup) {
					if (! es_popup || typeof(es_popup) == 'undefined') {
						resp = confirm('$msj_confirm_consulta');
						if (resp) {
							return true;
						} else {
							return false;
						}
					}
					return true;
				}
				
			";
		}
	}
	
}