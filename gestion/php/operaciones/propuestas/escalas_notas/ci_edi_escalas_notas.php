<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage EscalaDeNotas
 */
class ci_edi_escalas_notas extends ci_edicion_simple
{
	protected $datos_temp_cab;
	protected $datos_temp_det;
	protected $datos_ra_temp;
	protected $datos_temp_resultados;
	protected $datos_temp_conceptos;

	function ini()
	{
		$this->dep('responsables_academicas')->set_columna_ordenamiento('nombre');
	}

	function post_configurar()
	{
		if (!$this->cn()->entidad()->es_nueva() && $this->cn()->entidad()->esta_en_uso()) {
			// La edición de instancias, responsables académicas y estado se permite en todo momento, sin importar si la escala está en uso.
			// Se inhabilita la edición de notas.
			$this->dep('notas')->set_solo_lectura();
			$this->dep('notas')->desactivar_agregado_filas();
			$this->dep('notas')->eliminar_evento('modificacion');
			// Se inhabilita la edición de conceptos.
			$this->dep('conceptos')->set_solo_lectura();
			$this->dep('conceptos')->desactivar_agregado_filas();
			$this->dep('conceptos')->eliminar_evento('modificacion');
			// Se inhabilita la edición de resultados.
			$this->dep('resultados')->set_solo_lectura();
			$this->dep('resultados')->desactivar_agregado_filas();
			$this->dep('resultados')->eliminar_evento('modificacion');

			$this->dep('escala_notas')->set_solo_lectura();

			$this->dep('escala_notas')->ef('instancias')->set_solo_lectura(false);
			$this->dep('escala_notas')->ef('estado')->set_solo_lectura(false);
			$this->inhabilitar_eventos_controlador(array('eliminar'));
			$this->pantalla()->set_descripcion(toba::mensajes()->get('800_entidad_no_editable'));
		}
	}

	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		if (!$this->cn()->entidad()->es_nueva()) {
			$efs_continua = array('cantidad_decimales','separador_decimal','nota_inicial','nota_final');
			if ($this->cn()->entidad()->es_escala_continua()) {
				$pantalla->eliminar_dep('notas');
				//$this->dep('escala_notas')->set_solo_lectura($efs_continua);
			} else {
				$this->pantalla()->eliminar_tab('tab_resultados');
				$this->pantalla()->eliminar_tab('tab_conceptos');
				$this->dep('escala_notas')->desactivar_efs($efs_continua);
				$pantalla->eliminar_dep('notas_continuas');
			}
			$this->dep('escala_notas')->set_solo_lectura('es_numerica');
			$this->dep('escala_notas')->set_solo_lectura('tipo');
		}
	}
	function conf__pant_ras(toba_ei_pantalla $pantalla)
	{
			if (!$this->cn()->entidad()->es_escala_continua()) {
				$this->pantalla()->eliminar_tab('tab_resultados');
				$this->pantalla()->eliminar_tab('tab_conceptos');
			}
	}

	function conf__tab_resultados(toba_ei_pantalla $pantalla)
	{
		if (!$this->cn()->entidad()->es_nueva()) {
			if ($this->cn()->entidad()->esta_en_uso()) {
				if ($this->cn()->entidad()->es_escala_continua()) {
					$this->dep('resultados')->set_solo_lectura();
					$this->dep('resultados')->desactivar_agregado_filas();
					$this->dep('resultados')->eliminar_evento('modificacion');
				}
			}
		}
	}

	function conf__tab_conceptos(toba_ei_pantalla $pantalla)
	{
		if (!$this->cn()->entidad()->es_nueva()) {
			if ($this->cn()->entidad()->esta_en_uso()) {
				if ($this->cn()->entidad()->es_escala_continua()) {
					$this->dep('conceptos')->set_solo_lectura();
					$this->dep('conceptos')->desactivar_agregado_filas();
					$this->dep('conceptos')->eliminar_evento('modificacion');
				}
			}
		}
	}

	//---- Form Escalas Notas -------------
	function evt__escala_notas__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_escalas_notas($datos);
		} catch ( guarani_error $e ) {
			$this->datos_temp_cab = $datos;
			throw $e;
		}
		
	}

	function conf__escala_notas(toba_ei_formulario $form)
	{
		$form->ef('nota_inicial')->set_expreg(comunes::er_nota);
		$form->ef('nota_final')->set_expreg(comunes::er_nota);

		if(isset($this->datos_temp_cab)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp_cab);
		} else {
			$form->set_datos($this->cn()->entidad()->get_datos_escalas_notas());
		}
		
	}
	
	
	function ajax__eliminar_datos_tipo_continua($params, toba_ajax_respuesta $respuesta) 
	{
		$this->cn()->entidad()->eliminar_datos_tipo_continua();
	}
	

	//---- Form Notas -------------

	function evt__notas__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_notas($datos);
		} catch ( guarani_error $e ) {
			$this->datos_temp_det = $datos;
			throw $e;
		}
	}

	function conf__notas(toba_ei_formulario_ml $form_ml)
	{
		if(isset($this->datos_temp_det)) {
			$this->controlador->set_hubo_cambios();
			$form_ml->set_datos($this->datos_temp_det);
		} else {
			$form_ml->set_datos($this->cn()->entidad()->get_datos_notas());
		}
	}

	//---- Cuadro Escalas de Notas contínuas

	function conf__notas_continuas(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_datos($this->cn()->entidad()->get_datos_notas());
	}

	//---- Form Resultados -------------

	function evt__resultados__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_resultados($datos);
		} catch ( guarani_error $e ) {
			$this->datos_temp_resultados = $datos;
			throw $e;
		}
	}

	function conf__resultados(toba_ei_formulario_ml $form_ml)
	{
		if(isset($this->datos_temp_resultados)) {
			$this->controlador->set_hubo_cambios();
			$form_ml->set_datos($this->datos_temp_resultados);
		} else {
			$form_ml->set_datos($this->cn()->entidad()->get_datos_resultados());
		}
	}

	//---- Form Conceptos -------------

	function evt__conceptos__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_conceptos($datos);
		} catch ( guarani_error $e ) {
			$this->datos_temp_conceptos = $datos;
			throw $e;
		}
	}

	function conf__conceptos(toba_ei_formulario_ml $form_ml)
	{
		if(isset($this->datos_temp_conceptos)) {
			$this->controlador->set_hubo_cambios();
			$form_ml->set_datos($this->datos_temp_conceptos);
		} else {
			$form_ml->set_datos($this->cn()->entidad()->get_datos_conceptos());
		}
	}
	
	
	/**
	 * Retorna las fs js para validar las notas de la escala de notas nueva
	 *
	 * @param <type> $escalas
	 * @return <type>
	 *
	 */
	function get_js_validar_nota_escala_nueva()
	{
		$info = $this->cn()->entidad()->get_datos_escalas_notas();
		$validador = escalas_notas::validador_escalas_notas();
		return $validador->get_js_validar_nota_escala_nueva($info['cantidad_decimales'], $info['nota_inicial'], $info['nota_final'], $info['separador_decimal']);
	}

	//-----------------------------------------------------------------------------------
	//---- responsables_academicas ------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__responsables_academicas(form_ml_requerido $form_ml)
	{
		if (isset($this->datos_ra_temp)) {
			$this->controlador->set_hubo_cambios();
			$form_ml->set_datos($this->datos_ra_temp);
		} else {
			$datos = $this->cn()->entidad()->get_datos_ra();
		}

		$filtro = guarani_sql::armar_condicion_in($datos, 'sga_responsables_academicas', 'responsable_academica', 'responsable_academica', true);
		$institucion_instalacion = toba::db()->quote(guarani_configuracion::instancia()->get_valor_config('institucion_instalacion'));
		$filtro .= " AND sga_responsables_academicas.institucion = $institucion_instalacion";
		$datos_posibles = toba::consulta_php('co_responsables_academicas')->get_listado($filtro);

		$form_ml->set_datos($datos);
		$form_ml->set_datos_posibles($datos_posibles);
	}

	function conf_fila__responsables_academicas($fila)
	{
		$datos_ml = $this->dep('responsables_academicas')->get_datos();
		if (!empty($datos_ml[$fila]['requerido']) && $datos_ml[$fila]['requerido'] == 1) {
			$datos_responsable_academica = toba::consulta_php('co_responsables_academicas')->get_datos_responsable_academica($datos_ml[$fila]['responsable_academica']);
			if (empty($datos_responsable_academica)) {
				$this->dep('responsables_academicas')->ef('requerido')->set_solo_lectura(true);
			} else {
				$this->dep('responsables_academicas')->ef('requerido')->set_solo_lectura(false);
			}
		} else {
			$this->dep('responsables_academicas')->ef('requerido')->set_solo_lectura(false);
		}
	}

	function evt__responsables_academicas__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_ra($datos);
		} catch (guarani_error $e) {
			$this->datos_ra_temp = $datos;
			throw $e;
		}
	}
}
?>