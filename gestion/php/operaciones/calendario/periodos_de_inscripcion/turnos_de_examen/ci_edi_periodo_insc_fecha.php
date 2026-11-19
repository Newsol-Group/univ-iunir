<?php
class ci_edi_periodo_insc_fecha extends ci_edicion_entidad
{
    protected $datos_temp;
    protected $seleccion_modalidades_temp;
    protected $seleccion_ubicaciones_temp;
	protected $nivel;
	protected $s__datos_filtro;
	protected $s__filtro;
	protected $filtrando = false;
	
	protected $s__filtro_subtipos = '';
	protected $s__datos_filtro_subtipos;

	function ini()
	{
		$this->dep('seleccion_modalidades')->set_columna_ordenamiento('modalidad_nombre');
		$this->dep('seleccion_ubicaciones')->set_columna_ordenamiento('ubicacion_nombre');
	}
	
	function conf()
	{
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
		if ($this->pantalla()->existe_dependencia('filtro_subtipos')) {
			$this->pantalla()->eliminar_dep('filtro_subtipos');
		}
		
		if ($tipo) {
			$tipo_datos = explode(',',$tipo);
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
					$this->pantalla()->agregar_dep('filtro_subtipos');
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

		$llamado_periodo = $this->cn()->get_llamado_periodo_id();
		$condicion = "sga_llamados_turno.periodo = $llamado_periodo";
		$datos_turno_llamado = $this->cn()->co()->get_turnos_llamados_periodos($condicion);
		$this->controlador()->ev_info()->set_datos($datos_turno_llamado[0]);
	}

	function post_configurar()
	{
		$this->componentes_eventos_a_inhabilitar['formulario'] = array('modificacion','todos','ninguno');
		parent::post_configurar();
	}
	
	function post_eventos()
	{
		try {
			if (isset($this->datos_temp)) {
				$this->cn()->entidad()->set_datos($this->datos_temp);
				unset($this->datos_temp);
			}
			if (isset($this->seleccion_modalidades_temp)) {
				$this->cn()->entidad()->set_datos_modalidades($this->seleccion_modalidades_temp);
				unset($this->seleccion_modalidades_temp);
			}
			if (isset($this->seleccion_ubicaciones_temp)) {
				$this->cn()->entidad()->set_datos_ubicaciones($this->seleccion_ubicaciones_temp);
				unset($this->seleccion_ubicaciones_temp);
			}
		} catch (guarani_error $e) {
			$this->resetear_cambio_pantalla();
			throw $e;
		}
	}

	function reiniciar()
	{
		$this->nivel=null;
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

	function conf__periodo_insc_fechas(toba_ei_formulario $form)
	{
		$datos['habilitado'] = comunes::si;
		$datos['habilitado_interfaz'] = comunes::interfaz_todas;
		$datos['tipo_alumno'] = periodo_de_inscripcion::tipo_alumno_todos;
		$form->set_datos_defecto($datos);

		if(isset($this->datos_temp)) {
			$this->controlador->set_hubo_cambios();
			$datos = $this->datos_temp;
		} else {            
			$datos = $this->cn()->entidad()->get_datos();
		}

		//Se formatea la fecha y hora para los ef_editable_fecha_hora
		$datos['fecha_inicio'] = guarani_fecha::separar_fecha_hora($datos['fecha_inicio']);
		if (!empty($datos['fecha_fin'])) {
			$datos['fecha_fin'] = guarani_fecha::separar_fecha_hora($datos['fecha_fin']);
		}
		if (!empty($datos['fecha_tope_bajas'])) {
			$datos['fecha_tope_bajas'] = guarani_fecha::separar_fecha_hora($datos['fecha_tope_bajas']);
		}		

		$form->set_datos($datos);
	}

	function evt__periodo_insc_fechas__modificacion($datos)
	{
		$datos['fecha_inicio'] = "{$datos['fecha_inicio'][0]} {$datos['fecha_inicio'][1]}";
		if (isset($datos['fecha_fin'])) {
			$datos['fecha_fin'] = "{$datos['fecha_fin'][0]} {$datos['fecha_fin'][1]}";
		}
		if (isset($datos['fecha_tope_bajas'])) {
			$datos['fecha_tope_bajas'] = "{$datos['fecha_tope_bajas'][0]} {$datos['fecha_tope_bajas'][1]}";
		}
		if ($datos['habilitado'] == comunes::no) {
			$datos['habilitado_interfaz'] = comunes::interfaz_todas;
			$datos['tipo_alumno'] = periodo_de_inscripcion::tipo_alumno_todos;
		}
		$this->datos_temp = $datos;
	}

    function conf__turno_info(toba_ei_formulario $form)
	{
		$llamado_periodo = $this->cn()->get_llamado_periodo_id();
		$condicion = "sga_llamados_turno.periodo = $llamado_periodo";
		$datos_turno_llamado = $this->cn()->co()->get_turnos_llamados_periodos($condicion);
		$form->set_datos($datos_turno_llamado[0]);
	}

	function conf__nivel(toba_ei_formulario $form)
	{
		if(isset($this->datos_temp['nivel'])) {
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
		$_datos['tipo'] = substr($datos['entidad_tipo'],0,1);
		$_datos['entidad_tipo'] = substr($datos['entidad_tipo'],2);
		$_datos['para_todo'] = false;

		//Si es para toda la institución, entonces seteo a nivel entidad
		//para la entidad de la institucion de la instalacion
		if ($_datos['tipo'] == g3entidad::nivel_aplicacion_tipo_total) {
			$_datos['tipo'] = g3entidad::nivel_aplicacion_tipo_entidad;
			$_datos['entidad_tipo'] = g3entidad::tipo_institucion;
			$_datos['para_todo'] = $this->nivel['para_todo'] = true;
		} else {
			$this->nivel['para_todo'] = false;
		}

	    try {
			$this->cn()->entidad()->set_datos($_datos,null,false);
	    } catch ( guarani_error $e ) {
		    $this->datos_temp['nivel'] = $datos;
		    throw $e;
	    }
	}

	//-----------------------------------------------------------------------------------
	//---- filtro_subtipos --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_subtipos(guarani_ei_filtro $filtro)
	{
		if (isset($this->s__datos_filtro_subtipos)) {
			$filtro->set_datos($this->s__datos_filtro_subtipos);
		}
	}

	function evt__filtro_subtipos__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_filtro_subtipos = $datos;
			$this->s__filtro_subtipos = $this->dep('filtro_subtipos')->get_sql_where();
		} else {
			$this->evt__filtro_subtipos__cancelar();
		}
	}

	function evt__filtro_subtipos__cancelar()
	{
		$this->s__filtro_subtipos = '';
		unset($this->s__datos_filtro_subtipos);
	}
	
	function conf__subtipos(toba_ei_formulario_ml $form)
	{
		if(isset($this->datos_temp['subtipos'])) {
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
		    throw $e;
	    }
	}

	function conf__entidades(toba_ei_formulario_ml $form)
	{
		if ($this->filtrando) {
			$this->filtrando = false;
			$this->controlador()->set_hubo_cambios();
			$this->cn()->entidad()->marcar_eliminacion_entidades();
		}
		if(isset($this->datos_temp['entidades'])) {
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
			if($this->hay_tipo_definido()) {
				$entidades = guarani::co('co_g3entidades')->get_listado_tipo_detallado($this->get_entidad_tipo(),$this->s__filtro);

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
		    throw $e;
	    }
	}

	function evt__entidades__todos($datos)
	{
		$this->entidades_seleccionar($datos);
	}

	function evt__entidades__ninguno($datos)
	{
		$this->entidades_seleccionar($datos,false);
	}

	function entidades_seleccionar($entidades,$todas=true)
	{
		foreach ($entidades as $key => $entidad) {
			$entidades[$key]['requerido'] = $todas;
		}
		$this->evt__entidades__modificacion($entidades);
	}

	//-----------------------------------------------------------------------------------
	//---- seleccion_modalidades --------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__seleccion_modalidades(form_ml_requerido $form_ml)
	{
		if (isset($this->seleccion_modalidades_temp)) {
			$this->controlador->set_hubo_cambios();
			$datos = $this->seleccion_modalidades_temp;
		} else {
			$datos = $this->cn()->entidad()->get_datos_modalidades();
		}

		$form_ml->set_datos($datos);
		
		$filtro = empty($datos) ? '' : guarani_sql::armar_condicion_in($datos, 'sga_modalidad_cursada', 'modalidad', 'modalidad', true);
		$datos_posibles = toba::consulta_php('co_alumnos')->get_modalidades_cursadas_alumnos($filtro);
		$form_ml->set_datos_posibles($datos_posibles);
	}

	function evt__seleccion_modalidades__modificacion($datos)
	{
		$this->seleccion_modalidades_temp = $datos;
	}
	
	//-----------------------------------------------------------------------------------
	//---- seleccion_ubicaciones --------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__seleccion_ubicaciones(form_ml_requerido $form_ml)
	{
		if (isset($this->seleccion_ubicaciones_temp)) {
			$this->controlador->set_hubo_cambios();
			$datos = $this->seleccion_ubicaciones_temp;
		} else {
			$datos = $this->cn()->entidad()->get_datos_ubicaciones();
		}

		$form_ml->set_datos($datos);
		
		$filtro = empty($datos) ? '' : guarani_sql::armar_condicion_in($datos, 'sga_ubicaciones', 'ubicacion', 'ubicacion', true);
		$datos_posibles = toba::consulta_php('co_ubicaciones')->get_listado($filtro);
		
		if ($this->cn()->entidad()->es_nueva() && toba::consulta_php('co_ubicaciones')->get_cantidad_ubicaciones() == 1 && isset($datos_posibles[0])) {
			$datos_posibles[0]['requerido'] = 1;
		}
		
		$form_ml->set_datos_posibles($datos_posibles);
	}

	function evt__seleccion_ubicaciones__modificacion($datos)
	{
		$this->seleccion_ubicaciones_temp = $datos;
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

}