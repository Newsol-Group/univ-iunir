<?php
/**
 *
 * @package		Administración
 * @subpackage 	Administrar Configuración del Sistema
 * 
 */
class ci_edi_configuracion_sistema extends guarani_pers_ci
{

	function conf__pant_principales(guarani_ei_pantalla $pantalla)
	{
		$datos = $this->cn()->entidad()->get_datos_plataformas();
		
		// Si completo los datos para conectarse a Moodle
		if (isset($datos['host']) && isset($datos['url']) && isset($datos['token'])) {
			
			$moodle_obj = new moodle($datos['host'], $datos['url'], $datos['token']);
			// Si falla la conexión a Moodle
			if (!$moodle_obj->verificarConexion()) {
				// Desactivo las solapas roles y categorias
				$pantalla->tab('pant_roles')->desactivar();
				$pantalla->tab('pant_categorias')->desactivar();
			}
			
		} else {// sino completo los datos
			// Desactivo las solapas roles y categorias
			$pantalla->tab('pant_roles')->desactivar();
			$pantalla->tab('pant_categorias')->desactivar();
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- form_principales -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_principales(guarani_pers_ei_formulario $form)
	{
		$form->ef('host')->set_expreg(comunes::er_host);
		$form->ef('token')->set_expreg(comunes::er_token);
		$datos = $this->cn()->entidad()->get_datos_plataformas();
		$datos['resp_acad'] = aplanar_matriz($this->cn()->entidad()->get_datos_responsables_academicas(), 'responsable_academica');
		$form->set_datos($datos);
	}

	function evt__form_principales__modificacion($datos)
	{
		$this->cn()->entidad()->set_datos_plataformas($datos);
		$this->cn()->entidad()->set_datos_responsables_academicas($datos['resp_acad']);
	}
	
	//-----------------------------------------------------------------------------------
	//---- form_roles -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_roles(form_roles $form)
	{
		$form->set_datos($this->cn()->entidad()->get_datos_plataformas());
	}

	function evt__form_roles__modificacion($datos)
	{
		$this->cn()->entidad()->set_datos_plataformas($datos);
	}
	
	function get_categorias_cursos_moodle()
	{
		$plataforma = $this->cn()->entidad()->get_datos_plataformas();
		$moodle_obj = new moodle($plataforma['host'], $plataforma['url'], $plataforma['token']);
		
		return $moodle_obj->getCategoriasCursos();
	}
	
	function get_roles_moodle()
	{
		$plataforma = $this->cn()->entidad()->get_datos_plataformas();
		$moodle_obj = new moodle($plataforma['host'], $plataforma['url'], $plataforma['token']);
		
		return $moodle_obj->getRoles();
	}
	
	function get_ras_disponibles()
	{
		$plataforma = 0;
		// Si se trata de una edición
		if (!$this->cn()->entidad_es_nueva()) {
			$datos = $this->cn()->entidad()->get_datos_plataformas();
			$plataforma = $datos['plataforma'];
		}
		
		return toba::consulta_php('co_configuracion')->get_ras_disponibles_by_plataforma($plataforma);
	}
	
	/**
	 * Metodo invocado desde JS para probar configuración
	 */
	function ajax__probar_configuracion($parametros, toba_ajax_respuesta $respuesta)
	{
		$estado = false;
		$moodle_obj = new moodle($parametros['host'], $parametros['url'], $parametros['token']);
			
		if ($moodle_obj->verificarConexion()) {
			$estado = true;
		}
		
		//-- Paso la información a JS
		$respuesta->set(array($estado));
	}
	
	function extender_objeto_js()
	{
		$this->js_probar_configuracion();
	}

	/**
	 * Javascript necesario para el caso de preguntar/responder datos
	 */
	function js_probar_configuracion()
	{
		$id_js = toba::escaper()->escapeJs($this->objeto_js);
		echo "		
			/**
			 * Acción del Probar Configuración
			 */
			{$id_js}.evt__form_principales__probar_conf = function() {
				//--- Construyo los parametros de la configuración, en este caso son los valores del form
				var parametros = this.dep('form_principales').get_datos();
				
				//--- Hago la peticion de datos al server, la respuesta vendra en el método this.actualizar_datos
				this.ajax('probar_configuracion', parametros, this, this.mostrar_mensaje);
				
				//--- Evito que el mecanismo 'normal' de comunicacion cliente-servidor se ejecute
				return false;
			}
			
			/**
			 * Acción cuando vuelve la respuesta desde PHP
			 */
			{$id_js}.mostrar_mensaje = function(datos)
			{				
				if (datos[0]) {
				
					{$id_js}.activar_tab('pant_roles');
					{$id_js}.activar_tab('pant_categorias');
						
					notificacion.agregar('".guarani_mensajes::get('800_pev_exito_conexion')."', 'info');
					notificacion.mostrar();
					notificacion.limpiar();
					
				} else {
				
					{$id_js}.desactivar_tab('pant_roles');
					{$id_js}.desactivar_tab('pant_categorias');
						
					notificacion.agregar('".guarani_mensajes::get('800_pev_error_conexion')."');
					notificacion.mostrar();
					notificacion.limpiar();
					
				}
			}
		";
	}
	
	//-----------------------------------------------------------------------------------
	//---- form_resp_docentes -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_resp_docentes(guarani_pers_ei_formulario_ml $form_ml)
	{
		$datos = $this->cn()->entidad()->get_datos_roles_docentes();
		foreach ($datos as $clave => $valor) {
			if (!isset($valor['responsabilidad'])) {
				unset($datos[$clave]);
			}
		}
		$form_ml->set_datos($datos);
	}

	function evt__form_resp_docentes__modificacion($datos)
	{
		$this->cn()->entidad()->set_datos_roles_docentes($datos);
	}

	//-----------------------------------------------------------------------------------
	//---- form_roles_docentes -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_roles_docentes(guarani_pers_ei_formulario_ml $form_ml)
	{
		$datos = $this->cn()->entidad()->get_datos_roles_docentes();
		foreach ($datos as $clave => $valor) {
			if (!isset($valor['rol'])) {
				unset($datos[$clave]);
			}
		}
		$form_ml->set_datos($datos);
	}

	function evt__form_roles_docentes__modificacion($datos)
	{
		$this->cn()->entidad()->set_datos_roles_docentes($datos);
	}

	//-----------------------------------------------------------------------------------
	//---- form_categorias --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_categorias(guarani_pers_ei_formulario $form)
	{
		$form->set_datos($this->cn()->entidad()->get_datos_plataformas());
	}

	function evt__form_categorias__modificacion($datos)
	{
		$this->cn()->entidad()->set_datos_plataformas($datos);
	}

}
?>