<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos de Ingreso
 * 
 */
class ci_edi_requisitos_ingreso extends guarani_pers_ci
{
    protected $datos_temp;
	protected $nivel;
	protected $s__datos_filtro;
	protected $s__filtro;
	protected $filtrando = false;

	function conf()
	{
		$requisito = $this->cn()->entidad()->get_datos('requisito');

		// Parámetros de la regla asociada al requisito.
		$parametros = toba::consulta_php('co_requisitos')->get_requisito_regla_params_def($requisito);
		if (empty($parametros)) {
			$this->pantalla()->eliminar_dep('parametros');
		}

		if ($this->cn()->entidad()->dep('datos')->esta_cargada()) {
			// Si el requisito de ingreso está asociado a un período de inscripción vigente y tiene asociada una fecha tope particular,
			// se modifica el mensaje de confirmación de eliminación.
			if ($this->cn()->entidad()->tiene_periodo_asociado($requisito) || $this->cn()->entidad()->tiene_aspirante_asociado($requisito)) {
				$req_nom = $this->dep('requisito_info')->ef('nombre')->get_estado();
				$mensaje = guarani_mensajes::get('800REQ_conf_eliminacion_ri', array($req_nom));
				$this->controlador->evento('eliminar')->set_msg_confirmacion($mensaje);
			}
		}

		$tipo = $this->dep('nivel')->ef('entidad_tipo')->get_estado();

		if ($this->pantalla()->existe_dependencia('subtipos')) {
			$this->pantalla()->eliminar_dep('subtipos');
		}
		if ($this->pantalla()->existe_dependencia('filtro_entidades')) {
			$this->pantalla()->eliminar_dep('filtro_entidades');
		}
		if ($this->pantalla()->existe_dependencia('entidades')) {
			$this->pantalla()->eliminar_dep('entidades');
		}
		if ($tipo) {
			$tipo_datos = explode(',', $tipo);
			$this->nivel['tipo'] = $tipo_datos[0];
			$this->nivel['entidad_tipo'] = $tipo_datos[1];
			$this->nivel['para_todo'] = false;

			if ($this->nivel['tipo'] == g3entidad::nivel_aplicacion_tipo_total) {
				$this->nivel['para_todo'] = true;
			}

			//Habilito dependencias necesarias
			switch ($this->get_tipo()) {
				case g3entidad::nivel_aplicacion_tipo_entidad:
					$this->pantalla()->agregar_dep('filtro_entidades');
					$this->pantalla()->agregar_dep('entidades');
					break;
				case g3entidad::nivel_aplicacion_tipo_subtipo:
					$this->pantalla()->agregar_dep('subtipos');
					break;
			}
			
			//Si es para toda la institución, entonces seteo a nivel entidad 
			//para la entidad de la institucion de la instalacion
			if ($this->nivel['tipo'] == g3entidad::nivel_aplicacion_tipo_total) {
				$this->nivel['tipo'] = g3entidad::nivel_aplicacion_tipo_entidad;
				$this->nivel['entidad_tipo'] = g3entidad::tipo_institucion;
			}
			
			$this->nivel['entidad_tipo_datos'] = g3entidad::get_datos_tipo($this->nivel['entidad_tipo']);
		}
	}
	
	function hay_tipo_definido() 
	{
		return isset($this->nivel['tipo']);
	}
	
	function get_tipo()
	{
		return $this->nivel['tipo'];
	}

	function get_entidad_tipo()
	{
		return $this->nivel['entidad_tipo'];
	}
	
	function get_entidad_tipo_dato($dato)
	{
		return $this->nivel['entidad_tipo_datos'][$dato];
	}

	function conf__requisito_info(toba_ei_formulario $form)
	{
		if ($this->cn()->modo_egreso()) {
			$form->desactivar_efs(array('restrictivo', 'visible_en_preinscripcion', 'doc_digital_obligatorio_preinscripcion'));
		}
		if (isset($this->datos_temp['requisito_info'])) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp['requisito_info']);
		} else {
			$datos = $this->cn()->entidad()->get_datos();
			$form->set_datos($datos);
		}
	}

	function evt__requisito_info__modificacion($datos)
	{
		try {
		    $this->cn()->entidad()->set_datos($datos);
		} catch (guarani_error $e) {
		    $this->datos_temp['requisito_info'] = $datos;
		    throw $e;
		}
	}

	function conf__parametros(toba_ei_formulario_ml $form_ml)
	{
		if (isset($datos_temp['parametros'])) {
			$form_ml->set_datos($datos_temp['parametros']);
		} else {
			$datos = $this->cn()->entidad()->get_datos();
			$parametros_formales = toba::consulta_php('co_requisitos')->get_requisito_regla_params_def($datos['requisito']);
			if (isset($datos['parametros'])) {
				$parametros_actuales = explode('|', $datos['parametros']);
				if (guarani_arreglos::count($parametros_formales) == guarani_arreglos::count($parametros_actuales)) {
					$index = 0;
					foreach ($parametros_formales as $clave => $valor) {
						$parametros_formales[$clave]['parametro_valor'] = $parametros_actuales[$index];
						$index++;
					}
				}
			}
			$form_ml->set_datos($parametros_formales);
		}
	}

	function evt__parametros__modificacion($datos)
	{
		try {
			$valor_params = guarani_arreglos::aplanar_matriz_sin_nulos($datos, 'parametro_valor');
			$this->cn()->entidad()->validar_parametros($valor_params);
			$lista_params = implode('|', $valor_params);
			$this->cn()->entidad()->set_datos(array('parametros' => $lista_params));
		} catch (guarani_error $e) {
			$this->datos_temp['parametros'] = $datos;	
			throw new guarani_error($e->getMessage());
		}
	}

	function conf__nivel(toba_ei_formulario $form)
	{
		if (isset($this->datos_temp['nivel'])) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp['nivel']);
		} else {
			$datos = $this->cn()->entidad()->get_datos();
			if (isset($datos['tipo']) && $datos['tipo']) {
				if ((isset($this->nivel['para_todo']) && $this->nivel['para_todo']) || $this->cn()->entidad()->es_para_institucion_gestionante()) {
					$datos['entidad_tipo'] = g3entidad::nivel_aplicacion_tipo_total.','.$datos['entidad_tipo'];
					$this->nivel['entidad_tipo'] = g3entidad::nivel_aplicacion_tipo_total;
					$this->nivel['para_todo'] = true;
				} else {
					$datos['entidad_tipo'] = $datos['tipo'].','.$datos['entidad_tipo'];
				}	
			}
			$form->set_datos($datos);
		}
	}

	function evt__nivel__modificacion($datos)
	{
		$_datos = $datos;
		$_datos['tipo'] = substr($datos['entidad_tipo'], 0, 1);
		$_datos['entidad_tipo'] = substr($datos['entidad_tipo'], 2);
		$_datos['para_todo'] = false;
		
		//Si es para toda la institución, entonces seteo a nivel entidad 
		//para la entidad de la institucion de la instalacion
		if ($_datos['tipo'] == g3entidad::nivel_aplicacion_tipo_total) {
			$_datos['tipo'] = g3entidad::nivel_aplicacion_tipo_entidad;
			$_datos['entidad_tipo'] = g3entidad::tipo_institucion;
			$_datos['para_todo'] = true;
			$this->nivel['para_todo'] = true;
		} else {
			$this->nivel['para_todo'] = false;
		}
			
		try {
		    $this->cn()->entidad()->set_datos($_datos);
		} catch (guarani_error $e) {
		    $this->datos_temp['nivel'] = $datos;
		    throw new guarani_error($e->getMessage());
		}
	}

	function conf__subtipos(toba_ei_formulario_ml $form)
	{
		if (isset($this->datos_temp['subtipos'])) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp['subtipos']);
		} else {
			if ($this->hay_tipo_definido()) {
				//Cambio el nombre de la columna
				$form->ef('entidad_subtipo_nombre')->set_etiqueta('Tipos de '.$this->get_entidad_tipo_dato('tipo_nombre'));
				
				// El único tipo de entidad posible es "Propuestas". Los tipos de propuesta se obtienen de la tabla 'sga_propuestas_tipos' (filtrada con perfil de datos).
				$subtipos = guarani::co('co_propuestas')->get_propuestas_tipos_reporte();

				//Determino si fueron cargados como requeridos o no
				foreach ($subtipos as $fila => $subtipo) {
					if ($this->cn()->entidad()->es_subtipo_requerido($subtipos[$fila]['entidad_subtipo'])) {
						$subtipos[$fila]['requerido'] = '1';
					} else {
						$subtipos[$fila]['requerido'] = '0';
					}
				}
				$form->set_datos($subtipos);
			}
		}
	}

	function evt__subtipos__modificacion($datos)
	{
		try {
		    $this->cn()->entidad()->set_datos_subtipos($datos);
		} catch ( guarani_error $e ) {
		    $this->datos_temp['subtipos'] = $datos;
		    throw new guarani_error($e->getMessage());
		}
	}

	function conf__entidades(toba_ei_formulario_ml $form)
	{
		if ($this->filtrando) {
			$this->filtrando = false;
			$this->controlador()->set_hubo_cambios();
			$this->cn()->entidad()->marcar_eliminacion_entidades();
		}
		if (isset($this->datos_temp['entidades'])) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp['entidades']);
		} else {
			if ($this->hay_tipo_definido()) {
				//Cambio la expresion de la columna del filtro
				$this->dep('filtro_entidades')->columna('nombre')->set_expresion($this->get_entidad_tipo_dato('tabla').'.nombre');
				//Cambio el nombre de la columna
				$form->ef('entidad_descripcion')->set_etiqueta($this->get_entidad_tipo_dato('tipo_nombre'));
			}
			$this->s__filtro = $this->dep('filtro_entidades')->get_sql_where();
			if ($this->hay_tipo_definido()) {
				$entidades = guarani::co('co_g3entidades')->get_listado_tipo_detallado($this->get_entidad_tipo(), $this->s__filtro);

				//Determino si fueron cargadas como requeridas o no
				foreach ($entidades as $fila => $entidad) {
					if ($this->cn()->entidad()->es_entidad_requerida($entidades[$fila]['entidad'])) {
						$entidades[$fila]['requerido'] = '1';
					} else {
						$entidades[$fila]['requerido'] = '0';
					}
				}
				$form->set_datos($entidades);
			}
		}
	}

	function evt__entidades__modificacion($datos)
	{
		try {
		    $this->cn()->entidad()->set_datos_entidades($datos);
		} catch ( guarani_error $e ) {
		    $this->datos_temp['entidades'] = $datos;
		    throw new guarani_error($e->getMessage());
		}
	}
	
	function evt__entidades__todos($datos)
	{
		$this->entidades_seleccionar($datos);
	}
	
	function evt__entidades__ninguno($datos)
	{
		$this->entidades_seleccionar($datos, false);
	}
	
	function entidades_seleccionar($entidades, $todas=true)
	{
		foreach ($entidades as $key => $entidad) {
			$entidades[$key]['requerido'] = $todas;
		}
		$this->evt__entidades__modificacion($entidades);
	}
	
	//------ Filtro ----------------------

	function evt__filtro_entidades__filtrar($datos_filtro)
	{
		$this->filtrando = true;
		if (isset($datos_filtro)) {
			$this->s__datos_filtro = $datos_filtro;
			$this->s__filtro = $this->dep('filtro_entidades')->get_sql_where();
		} else {
			$this->evt__filtro__cancelar();
		}
	}

	function evt__filtro_entidades__cancelar()
	{
		unset($this->s__datos_filtro);
		unset($this->s__filtro);
	}

	function conf__filtro_entidades(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos_filtro)) {
			$filtro->set_datos($this->s__datos_filtro);
		}		
	}
	
	function extender_objeto_js()
	{
		parent::extender_objeto_js();
		
		if (! $this->pantalla()->dependencia_cargada('filtro_entidades')) {
			return;
		}	

		$msj_confirm_consulta = "Al filtrar se perderán los cambios realizados. ¿Desea continuar?";
        $id_js_filtro = $this->dependencia('filtro_entidades')->get_id_objeto_js();
        $id_js_form_entidades = $this->dependencia('entidades')->get_id_objeto_js();
        
		echo "
			//Chequea si seleccionó algo en el cuadro de entidades o hizo alguna modificación
			//Si es así, pido confirmación porque perderá los datos
			{$id_js_filtro}.evt__filtrar = function() {
				//Obtiene las filas y los campos de la variable interna que se mantiene en el filtro de columnas y valores (ei_filtro.js).
				var filas = {$id_js_form_entidades}.filas();
				var req = 'requerido';
				var confirmar_filtrar = {$id_js_form_entidades}.hay_cambios();
				if (confirmar_filtrar) {
					return confirmar_filtrado();
				}
				for (id_fila in filas) {
					var id_ef = {$id_js_form_entidades}.ef(req).ir_a_fila(filas[id_fila]);
					if (id_ef.chequeado()) {
						confirmar_filtrar = true;
						break;
					}
				}
				if (confirmar_filtrar) {
					return confirmar_filtrado();
				}
			}
			
			function confirmar_filtrado(proyecto, operacion, url, es_popup) {
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
	
	function usa_repositorio_digital()
	{
		return $this->cn()->entidad()->usa_repositorio_digital();
	}
	
	function es_requisito_documental()
	{
		return $this->cn()->entidad()->es_requisito_documental();
	}
}