<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos
 * 
 */
class ci_edi_requisitos extends guarani_pers_ci
{
	
	protected $datos_princ_temp;
	protected $datos_reglas_temp;
	protected $datos_parametros;
	protected $s__filtro_acciones_disp;
	protected $s__datos_acciones_disp;
	protected $s__filtro_ptos_control_disp;
	protected $s__datos_ptos_control_disp;
	
	protected $acciones_disponibles;
	protected $ptos_control_disponibles;
	
	// Componente visual de información.
	protected $ev_info;
	
	/** Parámetro que determina si se utiliza repositorio digital integrado para almacenar los
	 *	archivos asociados a requisitos documentales.
	 */
	function usa_repositorio_digital()
	{
		return guarani::parametros()->get_valor('usa_repositorio_digital') == comunes::si;
	}
	
	//---- PANTALLA DE DATOS PRINCIPALES -------------------------------------------------------------------------------------
	
	//---- Form datos principales -------------------------------------
	
	function evt__form_datos_princ__modificacion($datos)
	{
		try {
                    
                        // Si 'Aplica a Becas' esta marcado y 'Tipo' es distinto a 'Documentales'
                        if ($datos['aplica_a_becas'] == comunes::si && $datos['requisito_tipo'] != requisito::tipo_documentales) {
                            throw new guarani_error("Si el requisito <b>Aplica a Becas</b> entonces debería ser de tipo <b>Documentales</b>.");
                        }

			$this->cn()->entidad()->set_datos($datos);
		} catch ( guarani_error $e ) {
			$this->datos_princ_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	function conf__form_datos_princ(toba_ei_formulario $form)
	{
		//proviene_de_abm: Parámetro booleano a setear para saber si la operacion de procedencia es el ABM de Requisitos o no
		$proviene_de_abm = toba::memoria()->get_dato('proviene_de_abm');
		if (!$this->usa_repositorio_digital()) {
			$form->desactivar_efs(array('tipo_documento_digital'));
		} else {
			$form->ef('tipo_documento_digital')->set_descripcion(guarani_mensajes::get('800_rdi_tipo_documento'));
		}
		if (isset($this->datos_princ_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_princ_temp);
		} else {
			$form->ef('activo')->set_estado_defecto(comunes::si);
			if (!$proviene_de_abm) {
				$form->ef('requisito_tipo')->set_estado(requisito::tipo_documentales);
			}
			$form->set_datos($this->cn()->entidad()->get_datos());
		}
		if ((!$this->cn()->entidad_es_nueva()) || (!$proviene_de_abm)) {
			$form->set_solo_lectura(array('nombre', 'requisito_tipo'));
		}
		$form->ef('requisito_subordinado_de')->vinculo()->set_parametros(array('requisito_tipo' => requisito::tipo_documentales));
	}

	function evt__form_datos_princ__cambio_tipo_requisito($datos)
	{
		// Eliminar las acciones y puntos de control asociados.
		$this->cn()->entidad()->eliminar_req_validos();
		$this->cn()->entidad()->eliminar_ptos_control_req();

		// Si el nuevo tipo de requisito es "Documentales", resetear el valor de la regla.
		if ($datos['requisito_tipo'] == requisito::tipo_documentales) {
			$this->cn()->entidad()->set_columna_valor('regla', null);
		}

		$this->evt__form_datos_princ__modificacion($datos);
	}
	
	//---- PANTALLA DE REGLAS DE EVALUACION - PARAMETROS ---------------------------------------------------------------------
	
	function conf__pant_reglas(toba_ei_pantalla $pantalla)
	{
		$datos_requisito = $this->cn()->entidad()->get_datos();
		if ($datos_requisito['requisito_tipo'] == requisito::tipo_correlativas_especiales) {
			$pantalla->tab('pant_acciones')->desactivar();
			$pantalla->tab('pant_puntos_control')->desactivar();
		}
	}
	
	//---- Form Reglas de evaluación - Parámetros ---------------------
	
	function evt__form_regla__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_columna_valor('regla', $datos['regla']);
		} catch ( guarani_error $e ) {
			$this->datos_reglas_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}
	
	function conf__form_regla(toba_ei_formulario $form)
	{
		if (isset($this->datos_reglas_temp)) {
			$this->controlador->set_hubo_cambios();
			$datos = $this->datos_reglas_temp;
		} else {
			$datos = $this->cn()->entidad()->get_datos();
			
			if ((!is_null($datos)) && (isset($datos['regla']))) {
				$info_regla = toba::consulta_php('co_reglas')->get_info_regla($datos['regla']);
				$this->datos_parametros = toba::consulta_php('co_reglas')->get_regla_params_def($datos['regla']);
			}
		}
		
		$form->ef('regla')->set_maximo_descripcion(100);
		$form->set_datos($datos);
	}
	
	function evt__form_regla__cargar_parametros($datos)
	{
		if ($datos) {
			$this->datos_parametros = toba::consulta_php('co_reglas')->get_regla_params_def($datos['regla']);
			$this->datos_reglas_temp = $datos;
		}
	}
	
	//---- cuadro_parametros ------------------------------------------------------------
	
	function conf__cuadro_parametros(toba_ei_cuadro $cuadro)
	{
		if (isset($this->datos_parametros)) {
			$cuadro->set_datos($this->datos_parametros);
		}
	}
	
	//---- PANTALLA DE ACCIONES ----------------------------------------------------------------------------------------------
	
	function conf__pant_acciones(toba_ei_pantalla $pantalla)
	{
		// Seteo de datos en ev_info
		$datos = $this->cn()->entidad()->get_datos();
		$this->ev_info()->set_datos($datos);
	}
	
	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('nombre'));
			
			$this->ev_info->set_columnas_fila(array('nombre'), 1);
			
			$this->ev_info->set_max_columnas_por_fila(1);
			
			$this->ev_info->set_etiqueta('nombre', 'Requisito');
			
			$this->ev_info->set_td_etiqueta_width('70px');
		}
		return $this->ev_info;
	}

	//---- cuadro_acciones_asociadas ----------------------------------------------------
	
	function conf__cuadro_acciones_asociadas(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_datos($this->cn()->entidad()->get_datos_req_validos());
	}

	function evt__cuadro_acciones_asociadas__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_fila_req_validos($seleccion);
	}

	//---- filtro_acciones_disp ---------------------------------------------------------
	
	function conf__filtro_acciones_disp(guarani_ei_filtro $filtro)
	{
		if (isset($this->s__datos_acciones_disp)) {
			$filtro->set_datos($this->s__datos_acciones_disp);
		}
	}

	function evt__filtro_acciones_disp__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_acciones_disp = $datos;
			$this->s__filtro_acciones_disp = $this->dep('filtro_acciones_disp')->get_sql_where();
		} else {
			unset($this->s__datos_acciones_disp);
			$this->s__filtro_acciones_disp = '';
		}
	}

	function evt__filtro_acciones_disp__cancelar()
	{
		unset($this->s__datos_acciones_disp);
		$this->s__filtro_acciones_disp = '';
	}
	
	//---- cuadro_acciones_disponibles --------------------------------------------------
	
	function conf__cuadro_acciones_disponibles(toba_ei_cuadro $cuadro)
	{
		//Se obtiene la lista de todas las acciones existentes, salvo aquellas que se encuentran asociadas al requisito.
		$datos_acciones = $this->cn()->entidad()->get_datos_req_validos();
		$condicion = '';
		if (!empty($datos_acciones)) {
			$acciones_req = guarani_sql::armar_condicion_not_in($datos_acciones, 'sga_acciones', 'accion', 'accion');
			$condicion = empty($this->s__filtro_acciones_disp) ? $acciones_req : ' AND '.$acciones_req;
		}
		$datos_cuadro_acciones = toba::consulta_php('co_acciones')->get_listado($this->s__filtro_acciones_disp . $condicion);
		$cuadro->set_datos($datos_cuadro_acciones);
	}

	function evt__cuadro_acciones_disponibles__agregar()
	{
		if (!empty($this->acciones_disponibles)) {
			foreach ($this->acciones_disponibles as $accion) {
				$this->cn()->entidad()->nueva_fila_req_validos($accion);
			}
		}
	}
	
	function evt__cuadro_acciones_disponibles__seleccion($seleccion)
	{
		$this->acciones_disponibles = $seleccion;
	}
		
	//---- PANTALLA DE PUNTOS DE CONTROL -------------------------------------------------------------------------------------

	function conf__pant_puntos_control(toba_ei_pantalla $pantalla)
	{
		// Seteo de datos en ev_info
		$datos = $this->cn()->entidad()->get_datos();
		$this->ev_info()->set_datos($datos);
	}
	
	//---- ptos_control_asoc ------------------------------------------------------------
	
	function conf__ptos_control_asoc(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_datos($this->cn()->entidad()->get_datos_ptos_control_req());
	}

	function evt__ptos_control_asoc__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_fila_ptos_control_req($seleccion);
	}
	
	//---- filtro_ptos_control ----------------------------------------------------------
	
	function conf__filtro_ptos_control(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos_ptos_control_disp)) {
			$filtro->set_datos($this->s__datos_ptos_control_disp);
		}
	}

	function evt__filtro_ptos_control__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_ptos_control_disp = $datos;
			$this->s__filtro_ptos_control_disp = $this->dep('filtro_ptos_control')->get_sql_where();
		} else {
			unset($this->s__datos_ptos_control_disp);
			$this->s__filtro_ptos_control_disp = '';
		}
	}

	function evt__filtro_ptos_control__cancelar()
	{
		unset($this->s__datos_ptos_control_disp);
		$this->s__filtro_ptos_control_disp = '';
	}

	//---- ptos_control_disp ------------------------------------------------------------
	
	function conf__ptos_control_disp(toba_ei_cuadro $cuadro)
	{
		//Se obtiene la lista de todos los puntos de control existentes, salvo aquellos que se encuentran asociados al requisito.
		$datos_ptos_control = $this->cn()->entidad()->get_datos_ptos_control_req();
		$condicion = '';
		if (!empty($datos_ptos_control)) {
			$ptos_control_req = guarani_sql::armar_condicion_not_in($datos_ptos_control, 'par_puntos_control', 'pto_control', 'pto_control');
			$condicion .= $ptos_control_req;
		}		
		$condicion .= empty($this->s__filtro_ptos_control_disp) ? '' : ' AND '.$this->s__filtro_ptos_control_disp;
		$datos_cuadro_ptos_control = toba::consulta_php('co_puntos_de_control')->get_listado($condicion); 
		$cuadro->set_datos($datos_cuadro_ptos_control);
	}

	function evt__ptos_control_disp__agregar()
	{
		if (!empty($this->ptos_control_disponibles)) {		
			foreach ($this->ptos_control_disponibles as $pto_control) {
	    		$this->cn()->entidad()->nueva_fila_ptos_control_req($pto_control);
			}
		}
	}	
	
	function evt__ptos_control_disp__seleccion($seleccion)
	{
		$this->ptos_control_disponibles = $seleccion;
	}

	//-----------------------------------------------------------------------------------
	//---- AJAX -------------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function ajax__validar_cambio_tipo($params, toba_ajax_respuesta $respuesta) 
	{
		$requito_tipo = current($params);

		$acciones = $this->cn()->entidad()->get_datos_req_validos();
		$puntos_control = $this->cn()->entidad()->get_datos_ptos_control_req();
		$regla = $this->cn()->entidad()->get_columna('regla');

		$salida = false;
		// Si el nuevo tipo de requisito no elige Reglas, Acciones o Puntos de Control, solicitar al usuario una confirmación.
		if ($requito_tipo == requisito::tipo_correlativas_especiales && (! empty($acciones) || ! empty($puntos_control)) ||
			$requito_tipo == requisito::tipo_documentales && (! empty($regla) || ! empty($acciones) || ! empty($puntos_control))) {
			$salida = array('mensaje' => guarani_mensajes::get('800REQ_confirmar_cambio_tipo'));
		}
		$respuesta->set($salida);
	}
	
}