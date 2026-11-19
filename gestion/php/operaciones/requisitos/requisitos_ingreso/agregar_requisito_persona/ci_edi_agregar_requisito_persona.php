<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos de Ingreso
 * 
 */
class ci_edi_agregar_requisito_persona extends guarani_pers_ci
{
	
	protected $s__filtro_requisitos;
	protected $s__datos_filtro;
	
	protected $requisitos_seleccionados;
	
	protected $datos_asoc_temp;
	
	//Componente Visual de Información
	protected $ev_info;
	
	function get_seleccion_inscripcion()
	{
		return $this->controlador()->get_seleccion_inscripcion();
	}
	
	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__pant_requisitos()
	{
		$inscr	= current($this->get_seleccion_inscripcion());
		$filtro	= 'sga_propuestas_aspira.propuesta_aspira = '.$inscr;
		$datos 	= toba::consulta_php('co_inscripciones')->get_inscriptos($filtro);
		$this->ev_info()->set_datos(current($datos));
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_max_chars_por_columna(80);
			$this->ev_info->set_columnas_a_mostrar(array('documento_desc', 'persona_desc', 'propuesta_nombre'));
			$this->ev_info->set_etiqueta('documento_desc', 'Identificación');
			$this->ev_info->set_etiqueta('persona_desc', 'Apellido y Nombres');
			$this->ev_info->set_etiqueta('propuesta_nombre', 'Inscripción a Propuesta');
		}
		return $this->ev_info;
	}
	
	//-----------------------------------------------------------------------------------
	//---- asignados --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__asignados(toba_ei_formulario_ml $form_ml)
	{
		if (isset($this->datos_asoc_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_asoc_temp);		
		} else {
			$asignados = $this->cn()->entidad()->get_datos();
			$form_ml->set_datos($asignados);
		}
	}

	function evt__asignados__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_requisitos_asignados($datos);
		} catch ( guarani_error $e ) {
			$this->datos_asoc_temp = $datos;
			throw $e;
		}
	}

	function evt__asignados__parametros($seleccion)
	{
		$this->cn()->entidad()->seleccionar_requisito($seleccion);
		$this->set_pantalla('pant_parametros_requisito');
	}

	function conf_evt__asignados__parametros(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('asignados')->get_datos(true);
		$parametros = toba::consulta_php('co_requisitos')->get_requisito_regla_params_def($datos[$fila]['requisito']);
		if (empty($parametros)) {
			$evento->ocultar();
		} else {
			$evento->mostrar();			
		}
	}

	function evt__asignados__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_datos($seleccion);
	}
	
	function conf_evt__asignados__eliminar(toba_evento_usuario $evento, $fila)
	{
		$agregados 	= $this->dep('asignados')->get_datos();
		if (isset($agregados[$fila])) {
			$requisito 	= $agregados[$fila]['requisito'];
			$propuesta	= current($this->get_seleccion_inscripcion());
			$existe_req	= toba::consulta_php('co_requisitos')->existe_requisito_de_propuesta_aspira($propuesta, $requisito);
			
			//Si el Requisito ya estaba asociado a la Propuesta Aspira anulo el evento
			if ($existe_req) {
				$evento->anular();
			}
		}
	}

	//-----------------------------------------------------------------------------------
	//---- parametros -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__pant_parametros_requisito(toba_ei_pantalla $pantalla)
	{
		$ci_navegacion = $this->controlador()->controlador();
		$ci_navegacion->evento('guardar')->desactivar();
		$ci_navegacion->evento('cancelar')->desactivar();
	}

	function conf__parametros(toba_ei_formulario_ml $form_ml)
	{
		if (isset($datos_temp['parametros'])) {
			$form_ml->set_datos($datos_temp['parametros']);
		} else {
			$datos = $this->cn()->entidad()->get_datos_requisito_seleccionado();
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

	function evt__parametros__aceptar($datos)
	{
		try {
			$valor_params = guarani_arreglos::aplanar_matriz_sin_nulos($datos, 'parametro_valor');
			$this->cn()->entidad()->validar_parametros_requisito($valor_params);
			$lista_params = implode('|' , $valor_params);
			$this->cn()->entidad()->set_datos_requisito(array('parametros' => $lista_params));
			$this->evt__parametros__cancelar();
		} catch (guarani_error $e) {
			$this->datos_temp['parametros'] = $datos;	
			throw new guarani_error($e->getMessage());
		}
	}

	function evt__parametros__cancelar()
	{
		$this->cn()->entidad()->deseleccionar_requisito();
		$this->set_pantalla('pant_requisitos');
	}

	//-----------------------------------------------------------------------------------
	//---- filtro_requisitos ------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function evt__filtro_requisitos__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_filtro = $datos;
			$this->s__filtro_requisitos = $this->dep('filtro_requisitos')->get_sql_where();
		} else {
			unset($this->s__datos_filtro);
			$this->s__filtro_requisitos = '';
		}
	}

	function evt__filtro_requisitos__cancelar()
	{
		unset($this->s__datos_filtro);
		$this->s__filtro_requisitos = '';
	}

	function conf__filtro_requisitos(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos_filtro)) {
			$filtro->set_datos($this->s__datos_filtro);
		}		
	}

	//-----------------------------------------------------------------------------------
	//---- disponibles ------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__disponibles(toba_ei_cuadro $cuadro)
	{
		$requisitos = $this->cn()->entidad()->get_datos();
		$filtro = 'true';
		
		if (!empty($requisitos)) {
			$filtro .= ' AND '.guarani_sql::armar_condicion_in($requisitos, 'sga_requisitos', 'requisito', 'requisito', true);
		}
		
		if (!empty($this->s__filtro_requisitos)) {
			$filtro .= " AND $this->s__filtro_requisitos";
		}

		$filtro .= " AND sga_requisitos.requisito_tipo IN (".requisito::tipo_documentales.", ".requisito::tipo_procesos.") AND sga_requisitos.requisito_ingreso = '".comunes::si."'";
		// Sólo están disponibles los requisitos en estado Activo.
		$filtro .= " AND sga_requisitos.activo = '" . comunes::si . "'";
		$datos = toba::consulta_php('co_requisitos')->get_listado($filtro);
		$cuadro->set_datos($datos);
		
		if (empty($datos)) {
			// Mensaje EOF
			$indx_msg = '800_eof_cuadro';
			if (isset($this->s__filtro_requisitos) && !empty($this->s__filtro_requisitos)) {
				$indx_msg .= '_filtrado';
			}
			$msj = guarani_mensajes::get($indx_msg, array('Requisitos'));
			$cuadro->set_eof_mensaje($msj);
		}
	}

	function evt__disponibles__seleccion($seleccion)
	{
		$this->requisitos_seleccionados = $seleccion;
	}

	function evt__disponibles__agregar($sel)
	{
		if (!empty($this->requisitos_seleccionados)) {
			$inscr = current($this->get_seleccion_inscripcion());
			foreach ($this->requisitos_seleccionados as $requisito) {
				$this->cn()->entidad()->set_datos_requisito_persona($requisito, $inscr);
			}
		}
	}
	
}
?>