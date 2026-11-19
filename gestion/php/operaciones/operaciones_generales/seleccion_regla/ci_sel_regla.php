<?php
class ci_sel_regla extends guarani_pers_ci
{
	protected $s__regla;
	protected $cant_max_parametros = 5;
	protected $s__regla_tipo;
	protected $s__regla_fija = false;
	protected $s__etiqueta='Forma de cumplimiento';
	protected $s__reducir_cabecera=false;
	
	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ini()
	{
		toba_ef_combo::set_maximo_descripcion(200);
	}
	
	function conf()
	{
		$this->set_etiqueta_regla($this->s__etiqueta);
		//$this->set_regla_tipo(regla::tipo_cumplimiento_modulos);
		$this->pantalla('pant_inicial')->set_no_mostrar_extras_cabecera();
	}
	
	function set_regla($regla,$parametros=null)
	{	
		if (! $regla || $regla == 'nopar') return;
		$this->resetear();
		$this->s__regla['regla'] = $regla;
		$info_regla = toba::consulta_php('co_reglas')->get_info_regla($regla);
		$this->s__regla['regla_tipo'] = $info_regla['regla_tipo'];
		if (isset($this->s__regla_tipo)) {
			if ($this->s__regla_tipo != $info_regla['regla_tipo']) {
				throw new guarani_error ("El tipo de la {$this->get_etiqueta_regla()} estipulada no coincide con el tipo manejado.");
			}	
		} else {
			$this->s__regla_tipo = $info_regla['regla_tipo'];
		}
		if (isset($parametros)) {
			$this->s__regla['parametros'] = $parametros;
		}
		//Get definicion de parametros de la regla
		$parms_def = toba::consulta_php('co_reglas')->get_regla_params_def($regla);
		$id = 1;
		foreach ($parms_def as $param => $def) {
			$this->s__regla['params'][$id] = $def;
			$tipo_ef = $this->get_tipo_ef($def['tipo_de_dato']);
			$this->s__regla['params'][$id]['tipo_ef'] = $tipo_ef;
			$this->s__regla['params'][$id]['ef'] = 'param_'.$id.'_'.$tipo_ef;
			$id++;
		}
		$this->s__regla['param_cant'] = $id - 1;
		if (isset($parametros)) {
			$this->set_params_valores($this->s__regla['parametros']);
		}
	}

	function ajax__limpiar_regla($regla, toba_ajax_respuesta $respuesta)
	{		
		$this->resetear();
	}

	function ajax__setear_regla($parametros, toba_ajax_respuesta $respuesta)
	{		
		$this->set_regla($parametros['regla']);
		$datos = array();
		if ($this->regla_tiene_parametros()) {
			$datos = array_merge(array('regla' => $this->s__regla['regla']), $this->s__regla['params']);
		}
		$respuesta->set($datos);
	}
		
	function ajax__cambiar_regla($parametros, toba_ajax_respuesta $respuesta)
	{        
		//Por seguridad, me aseguro que no me cambie la regla si ésta está definida como fija.
		if ($this->s__regla_fija && isset($this->s__regla['regla']) && $parametros['regla'] != $this->s__regla['regla']) return;
		if (isset($this->s__regla['parametros'])) {
			$this->set_regla($parametros['regla'],$this->s__regla['parametros']);
		} else {
			$this->set_regla($parametros['regla']);
		}	
		$datos = array();
		if ($this->regla_tiene_parametros()) {
			$datos = array_merge(array('regla' => $this->s__regla['regla']), $this->s__regla['params']);
		}
		$respuesta->set($datos);
	}

	function set_regla_tipo($tipo)
	{
		$this->s__regla_tipo = $tipo;
	}

	function set_etiqueta_regla($etiqueta)
	{
		$this->s__etiqueta = $etiqueta;
		$this->dep('formulario')->ef('regla')->set_etiqueta($etiqueta);
	}
	
	function get_etiqueta_regla() {
		return $this->s__etiqueta;
	}	
	
	function set_solo_lectura()
	{
		$this->dep('formulario')->set_solo_lectura();
		$this->dep('formulario')->eliminar_evento('modificacion');
	}
	
	function set_regla_solo_lectura($solo_lectura = true)
	{
		$this->s__regla_fija = $solo_lectura;
		$this->dep('formulario')->ef('regla')->set_solo_lectura($solo_lectura);
	}

	function get_regla()
	{
		return $this->s__regla['regla'];
	}
	
	function get_datos()
	{
		return $this->s__regla;
	}

	function get_datos_regla()
	{
		$datos ['regla'] = $this->s__regla['regla'];
		if (isset($this->s__regla['parametros'])) {
			$datos ['parametros'] = $this->s__regla['parametros'];
		}
		return $datos;
	}

	function get_regla_tipo()
	{
		return $this->s__regla_tipo;
	}
	
	function set_datos($datos)
	{	
		$regla_nueva = false;
		//Actualizo los datos de sesion de la regla: regla, parametros (cadena) y params
		if ($datos) {
			if (!isset($this->s__regla['regla']) || ($datos['regla'] != $this->s__regla['regla'])) {
				$this->set_regla($datos['regla']);
				$regla_nueva = true;
			}
			if ($this->regla_tiene_parametros()) {
				foreach($this->s__regla['params'] as $id => $parm) {
					$valor = null;
					if (isset($datos[$parm['ef']])) {
						$valor = $datos[$parm['ef']];
					} 
					if (isset($valor)) {
						if ($this->validar_parametro($parm,$valor)) { 
							$this->s__regla['params'][$id]['valor'] = $valor;
						}	
					} elseif (! $regla_nueva) {
						throw new guarani_error('El parámetro <B>'.$parm['etiqueta'].'</B> es obligatorio.');	
					}
					unset($valor);
				}
			}	
		}
		$this->set_parametros();
	}
	
	function set_params_valores($parametros=null)
	{
		if (!isset($parametros)) return;
		$params = regla::separar_parametros($parametros);
		if (guarani_arreglos::count($params) <> ($this->get_cantidad_parametros())) {
			throw new guarani_error("No coinciden la cantidad de parámetros recibidos (".guarani_arreglos::count($params).") con la definición de la regla (".$this->get_cantidad_parametros().").");
		}
		//$this->limpiar_params_anteriores();
		$i = 1;
		foreach ($this->s__regla['params'] as $param => $def) {
			$valor = $params[$i - 1];
			//$this->regla::validar_parametro($def,$valor);
			$this->s__regla['params'][$i]['valor'] = $valor;
			$i++;
		}
	}
	
	function set_parametros() 
	{
		$parametros = '';
		$id = 1;
		if ($this->regla_tiene_parametros()) {
			foreach ($this->s__regla['params'] as $param => $def) {
				if($id == 2) $parametros .= regla::separador_parametros;
				$valor = '';
				if (isset($this->s__regla['params'][$id]['valor'])) {
					$valor = $this->s__regla['params'][$id]['valor'];
				}
				$parametros .= $valor;
				$id++;
			}
		}
		if ($parametros != '') 	{
			$this->s__regla['parametros'] = $parametros;
		}
	}
	
	function limpiar_params_anteriores() 
	{
		for($i=1;$i > $this->cant_max_parametros;$i++) {
			unset($this->s__regla['params'][$i]);
		}
	}
	
	function resetear()
	{
		unset($this->s__regla);
	}
	
	function get_tipo_ef($tipo_de_dato) 
	{
		return $tipo_de_dato;
	}
	
	function get_valores_para_efs() 
	{
		$datos['regla'] = $this->s__regla['regla'];
		if ($this->regla_tiene_parametros()) {
			foreach ($this->s__regla['params'] as $param => $def) {
				if (isset($def['valor'])) {
					$datos[$def['ef']] = $def['valor'];
				}	
			}
		}	
		return $datos;
	}
	
	function get_cantidad_parametros() 
	{
		if (! isset($this->s__regla['param_cant'])) return 0;
		return $this->s__regla['param_cant'];
	}
	
	function regla_tiene_parametros() 
	{
		return ($this->get_cantidad_parametros() > 0);
	}	

	function validar_parametro($param, $valor)
	{	
		return regla::validar_parametro($param, $valor);
	}
	
	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_lista_reglas()
	{
		$filtro = '';
		if ($this->get_regla_tipo()) {
			$filtro = 'sga_reglas.regla_tipo = '.$this->get_regla_tipo();
		}
		return toba::consulta_php('co_reglas')->get_lista_reglas($filtro);
	}


	function conf__formulario(toba_ei_formulario $form)
	{
		$form->ef('param_1_T')->set_expreg(comunes::er_hora);
		$form->ef('param_2_T')->set_expreg(comunes::er_hora);
		$form->ef('param_3_T')->set_expreg(comunes::er_hora);
		$form->ef('param_4_T')->set_expreg(comunes::er_hora);
		$form->ef('param_5_T')->set_expreg(comunes::er_hora);
		
		$param_efs = $form->get_nombres_ef();
		unset($param_efs[0]);
		$datos = $this->controlador->get_datos_regla();
		//Seteo el combo de reglas segun el tipo de regla
		if (isset($this->s__regla['regla'])) {
			//Encender los ef que corresponden segun la definicion de parametros de la regla en cuestion
			//Apagar el resto
			//Seteo los efs segun la definicion de la regla
			if ( isset($this->s__regla['params']) ) {
				foreach ($this->s__regla['params'] as $param => $def) {
					$id = $param + 1;
					$efs_activos[] = $def['ef'];
					$form->ef($def['ef'])->set_etiqueta($def['etiqueta']);
					if ($def['descripcion']) {
						$form->ef($def['ef'])->set_descripcion($def['descripcion']);
					}
					$form->ef($def['ef'])->set_obligatorio();
					//Si tiene valor definido lo seteo ? o lo hago con un set_datos poniendo a cada parametro la clave segun el campo donde iría ?
				
				}
				foreach ($param_efs as $id_ => $ef) {
					if (! in_array($ef,$efs_activos)) {
						$efs_inactivos[] = $ef;
					}	
				}
			} else {
				$efs_inactivos = $param_efs;
			}
			$datos = $this->get_valores_para_efs();
		}
		if ($this->s__regla_fija) {
			$form->ef('regla')->set_solo_lectura();
		}
		$form->set_datos($datos);
	}

	function evt__formulario__modificacion($datos)
	{
		$this->set_datos($datos);
		$this->controlador->evt__form_regla__modificacion($this->get_datos_regla());
	}

	function reducir_cabecera()
	{
		$this->s__reducir_cabecera = true;
		$this->dep('formulario')->set_ancho_etiqueta('120px');
	}

	function usar_cabecera_reducida()
	{
		return $this->s__reducir_cabecera;
	}

    function extender_objeto_js()
    {
        echo "            
            ci.prototype.ajax = function(metodo, parametros, clase_js, funcion_js, contexto_js) {
                var respuesta = new ajax_respuesta('D');
                respuesta.set_callback(clase_js, funcion_js);
                respuesta.set_contexto(contexto_js);
                var callback_real = {
                    success: respuesta.recibir_respuesta,
                    failure: toba.error_comunicacion,
                    scope: respuesta
                };
                var pasa_zona = typeof parametros['toba_qs_zona'] != 'undefined';
                var param = {'ajax-metodo': metodo, 'ajax-modo': 'D', 'ajax-param': serializar(parametros)};
                var vinculo = vinculador.get_url(null, null, 'ajax', param, [this._id], '', pasa_zona);
                var con = conexion.asyncRequest('GET', vinculo, callback_real, null);	
            };
        ";
    }
    
}

?>