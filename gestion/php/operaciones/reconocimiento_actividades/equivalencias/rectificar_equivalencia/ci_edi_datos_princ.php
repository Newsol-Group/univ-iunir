<?php
class ci_edi_datos_princ extends guarani_pers_ci
{
	protected $s__datos_principales;
	protected $s__datos_temp;
	protected $seleccionados;
	protected $s__list_activs_nuevas = array();
	
	/**
	 * Elemento visual para mostrar los datos del plan versión-propuesta destino seleccionados en la pantalla inicial.
	 * @var ev_info_datos
	 */
	protected $ev_info_datos_princ;

	function ini()
	{
		$this->s__datos_principales = $this->controlador()->get_datos_basicos();
	}

	function conf()
	{
		// Se setean los datos principales para mostrar
		$datos = $this->s__datos_principales;

		if ($datos['equivalencia_tramite'] == $datos['rectifica_a']) {
			unset($datos['rectifica_a']);
		}

		$this->ev_info_datos_princ()->set_datos($datos);
	}

	function get_activ_nuevas_arectificar()
	{
		return $this->s__list_activs_nuevas;
	}

	//--------------------------------------------------------------------------
	//---- Componente visual para mostrar información --------------------------
	//--------------------------------------------------------------------------

	function ev_info_datos_princ()
	{
		if (!isset($this->ev_info_datos_princ)) {
			$this->ev_info_datos_princ = guarani::ev_info_datos();
			$this->ev_info_datos_princ->set_controlador($this);
			$this->ev_info_datos_princ->set_style('border-style: solid; border-width: 1px; border-color: #DDD;');
			$this->ev_info_datos_princ->set_columnas_a_mostrar(array('equivalencia_tramite', 'rectifica_a', 'alumno_nombre_completo', 'alumno_identificacion', 'legajo', 'propuesta_alumno', 'plan_alumno', 'version_alumno', 'origen_descr'));
			$this->ev_info_datos_princ->set_max_columnas_por_fila(4);
			
			$this->ev_info_datos_princ->set_td_etiqueta_width('100px');
			$this->ev_info_datos_princ->set_td_valores_width('200px');

			$this->ev_info_datos_princ->set_columnas_fila(array('equivalencia_tramite', 'rectifica_a', 'origen_descr'), 1);
			$this->ev_info_datos_princ->set_columnas_fila(array('institucion_nombre'), 2);
			$this->ev_info_datos_princ->set_columnas_fila(array('alumno_nombre_completo'), 3);
			$this->ev_info_datos_princ->set_columnas_fila(array('alumno_identificacion', 'legajo'), 4);
			$this->ev_info_datos_princ->set_columnas_fila(array('propuesta_alumno'), 5);
			$this->ev_info_datos_princ->set_columnas_fila(array('plan_alumno'), 6);
			$this->ev_info_datos_princ->set_columnas_fila(array('version_alumno'), 7);


			$this->ev_info_datos_princ->set_etiqueta('equivalencia_tramite', 'Nº Trámite:');
			$this->ev_info_datos_princ->set_etiqueta('rectifica_a', 'Nº Rectificativa:');
			$this->ev_info_datos_princ->set_etiqueta('alumno_nombre_completo', 'Apellido y Nombre:');
			$this->ev_info_datos_princ->set_etiqueta('alumno_identificacion', 'Identificación:');
			$this->ev_info_datos_princ->set_etiqueta('legajo', 'Legajo:');
			$this->ev_info_datos_princ->set_etiqueta('propuesta_alumno', 'Propuesta:');
			$this->ev_info_datos_princ->set_etiqueta('plan_alumno', 'Plan:');
			$this->ev_info_datos_princ->set_etiqueta('version_alumno', 'Versión:');
			$this->ev_info_datos_princ->set_etiqueta('origen_descr', 'Tipo:');
			$this->ev_info_datos_princ->set_etiqueta('institucion_nombre', 'Institución:');
		}
		return $this->ev_info_datos_princ;
	}

	//--------------------------------------------------------------------------
	//---- form_datos_princ ----------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__form_datos_princ(toba_ei_formulario $form)
	{
		$datos_temp = $this->cn()->entidad()->get_datos();
		$form->set_datos($datos_temp);
	}

	function evt__form_datos_princ__modificacion($datos)
	{
		if ($datos) {
			try {
				$this->cn()->entidad()->set_datos($datos);
			} catch (guarani_error $e) {
				$this->s__datos_temp = $datos;
				throw $e;
			}
		}
	}

	//--------------------------------------------------------------------------
	//---- cuadro_activ_asociadas ----------------------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro_activ_asociadas(toba_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->entidad()->get_datos_equiv_otorgada();
		$cuadro->set_datos($datos);
		$cuadro->set_eof_mensaje(guarani_mensajes::get('800_sin_registros'));
	}

	function evt__cuadro_activ_asociadas__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_equiv_otorgada($seleccion);
	}

	//--------------------------------------------------------------------------
	//---- cuadro_activ_disp ---------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro_activ_disp(toba_ei_cuadro $cuadro)
	{
		$equivs = $this->cn()->get_datos_tramite_equiv_otorgado($this->s__datos_principales['rectifica_a']);
		// Se guardan y formatean en datos_temp los datos del cuadro para trabajarlos en memoria
		$this->s__datos_temp = array();
		foreach ($equivs as $equiv) {
			$cod_equiv = $equiv['equivalencia'];
			$this->s__datos_temp[$cod_equiv] = $equiv;
		}

		// Se desasocian los que ya se encuentran en el cuadro superior
		$asoc = aplanar_matriz($this->cn()->entidad()->get_datos_equiv_otorgada(), 'equivalencia');
		foreach ($asoc as $a) {
			unset($this->s__datos_temp[$a]);
		}
		$cuadro->set_datos($this->s__datos_temp);
	}

	function evt__cuadro_activ_disp__agregar()
	{
		if (!empty($this->seleccionados)) {
			foreach ($this->seleccionados as $sel) {
				$dato = $this->s__datos_temp[$sel['equivalencia']];
				$dato['rectifica_a'] = $dato['equivalencia'];
				$equiv_otorgada = $this->cn()->entidad()->agregar_equiv_otorgada($dato);
				$this->cn()->entidad()->set_cursor_equiv_otorgada($equiv_otorgada);

				// Se copian también las equivalencias externas e internas.
				$equiv_externas = $this->cn()->get_datos_equiv_externas($dato['equivalencia']);
				foreach ($equiv_externas as $equiv_externa) {
					$this->cn()->entidad()->agregar_externa($equiv_externa);
				}
				$equiv_internas = $this->cn()->get_datos_equiv_internas($dato['equivalencia']);
				foreach ($equiv_internas as $equiv_interna) {
					$this->cn()->entidad()->agregar_interna($equiv_interna);
				}

				$this->s__list_activs_nuevas[] = $dato['equivalencia'];
			}
		}
	}

	function conf_evt__cuadro_activ_disp__seleccion(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro_activ_disp')->get_datos();
		if ($datos[$fila]['invalidado'] == comunes::si_desc) {
			$evento->anular();
		}
	}

	function evt__cuadro_activ_disp__seleccion($datos)
	{
		$this->seleccionados = $datos;
	}

}
?>