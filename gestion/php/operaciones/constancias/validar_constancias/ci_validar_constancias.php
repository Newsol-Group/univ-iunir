<?php
class ci_validar_constancias extends guarani_pers_ci
{
	protected $s__codigo_verificacion;
	protected $s__dni;
	protected $s__captcha_ok;
	protected $s__solicitud;
        protected $s__datos_solicitud;
	
	function ini() 
	{
		$this->s__captcha_ok = false;

		$parametro_codigo_verificacion = toba::memoria()->get_parametro('codigo_verificacion');
		if (!empty($parametro_codigo_verificacion)) {
			$this->s__codigo_verificacion = $parametro_codigo_verificacion;
		}
		$parametro_dni = toba::memoria()->get_parametro('dni');
		if (!empty($parametro_dni)) {
			$this->s__dni = $parametro_dni;
		}
	}
	
	function conf__formulario(toba_ei_formulario $form)
	{
		if (isset($this->s__codigo_verificacion)){
			$datos['codigo_verificacion'] = $this->s__codigo_verificacion;
		}
		if (isset($this->s__dni)){
			$datos['dni'] = $this->s__dni;
		}
		if (isset($datos)){
			$form->set_datos($datos);
		}
		$form->ef('captcha')->set_parametros_captcha(array('use_gd_font'=>true,'gd_font_file'=> toba_dir() . '/php/3ros/securimage/gdfonts/automatic.gdf'));
	}
	
	function evt__formulario__modificacion($datos)
	{
		$this->s__codigo_verificacion = $datos['codigo_verificacion'];
		$this->s__dni = $datos['dni'];
		
		if (isset($datos['captcha']) && !$datos['captcha']){
			$this->s__captcha_ok = false;
			toba::notificacion()->agregar('El valor ingresado de confirmación no es correcto',comunes::error_critico);
		}else{
			$this->s__captcha_ok = true;
		}
	}
	
	function evt__validar()
	{
		if ($this->s__captcha_ok) {
			$this->s__solicitud =  toba::consulta_php('co_constancias')->get_solicitud_vigente($this->s__dni,$this->s__codigo_verificacion);

			if ($this->s__solicitud == null){
				$msje = guarani_mensajes::get('800CON_constancia_invalida');
				toba::notificacion()->agregar($msje, comunes::error_critico);
			}else{
				$msje = guarani_mensajes::get('800CON_constancia_valida');
				toba::notificacion()->agregar($msje, comunes::error_informacion);
			}
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- IMPRESION JASPER -------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function vista_jasperreports(toba_vista_jasperreports $report) 
	{		
		$plantilla = toba::consulta_php('co_constancias')->get_constancia_archivo_plantilla($this->s__datos_solicitud['constancia']);
		$path = guarani::get_path_reporte_jasper($plantilla);
		$report->set_path_reporte($path);
		
		//Seteo el logo de la Institución correspondiente, si no existe seteo el logo de Guarani
		$path_logo = guarani::get_path_logo_impresion();
		$report->set_parametro('logo', 'S', $path_logo);
		$report->set_parametro('solicitud', 'E', $this->s__solicitud);
		$report->set_parametro('ocultar_firma', 'B', true);
                
                $param_nombre_persona_doc_oficiales = persona::get_param_nombre_persona_doc_oficiales();		
		$report->set_parametro('apellido_y_nombre', 'S', $param_nombre_persona_doc_oficiales);
		
		$parametros = toba::consulta_php('co_constancias')->get_solicitud_params($this->s__solicitud);        
		foreach ($parametros as $param) {
			$report->set_parametro($param['campo'], $param['tipo_de_dato'], $param['valor']);            
		}
		$this->s__solicitud = null;
                $this->s__datos_solicitud = null;
	}

        function vista_araireportes(toba_vista_araireportes $report)
        {
                // Recupero información del reporte
                $informacion_reporte = arai_reportes::getInformacionReporteById($this->s__datos_solicitud['constancia']);
                // Recupero la URI del reporte
                $uri_reporte    = toba::consulta_php('co_arai_reportes')->get_uri_reporte($informacion_reporte['id']);
                $report->set_path_reporte($uri_reporte);

                // Recupero la URI del logo
                $uri_logo    = arai_reportes::getUriLogoImpresion();
                $report->set_parametro('uri_logo', 'S', $uri_logo);
                $report->set_parametro('ocultar_firma', 'B', true);

                $parametros = toba::consulta_php('co_constancias')->get_solicitud_params($this->s__solicitud);
		foreach ($parametros as $param) {
			$report->set_parametro($param['campo'], $param['tipo_de_dato'], $param['valor']);
		}

                // Seteo el título del reporte
                $report->set_parametro('titulo', 'S', $informacion_reporte['titulo']);

                // Recupero datos para el detalle del reporte si corresponde
                if (isset($informacion_reporte['clase_consulta']) && isset($informacion_reporte['metodo_consulta'])) {
                    $clase_consulta = $informacion_reporte['clase_consulta'];
                    $metodo_consulta = $informacion_reporte['metodo_consulta'];
                    $datos  = toba::consulta_php($clase_consulta)->$metodo_consulta($this->s__solicitud);

                    // Seteo datos para el reporte
                    $report->set_json(rest_encode($datos));
                }

                $this->s__solicitud = null;
                $this->s__datos_solicitud = null;
        }
        
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------


	function extender_objeto_js()
	{
		// Abre la ventana que muestra el reporte Jasper para la impresión.
		if ($this->s__solicitud != null) {

                        $this->s__datos_solicitud = toba::consulta_php('co_constancias')->get_datos_solicitud($this->s__solicitud);

                        $funcion = 'vista_jasperreports';
                        // Me fijo si esta habilitado para Araí Reportes
                        if (arai_reportes::esReporteHabilitado($this->s__datos_solicitud['constancia'])) {
                            $funcion = 'vista_araireportes';
                        }
                        
			echo "
				window.open(vinculador.get_url(null, null, '{$funcion}', null, [{$this->objeto_js}._id]));
			";
		} 
	}
}