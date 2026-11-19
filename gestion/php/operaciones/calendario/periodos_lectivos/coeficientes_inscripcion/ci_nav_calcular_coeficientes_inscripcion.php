<?php
/**
 *
 * @package Calendario
 * @subpackage Períodos Lectivos
 */
class ci_nav_calcular_coeficientes_inscripcion extends ci_nav_procesos_bk_wizzard
{
	protected $s__propuestas_ubicaciones;
	protected $s__alumnos;

	function conf()
	{
		parent::conf();
		$this->set_sin_listado_inicial();
	}

	function evt__cambiar_tab__siguiente()
	{
		parent::evt__cambiar_tab__siguiente();

		if ($this->s__pantalla_actual == 'propuestas') {
			$this->cn()->ejecutar_proceso(array('alumnos' => $this->s__alumnos));
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PROPUESTAS -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__propuestas(toba_ei_pantalla $pantalla)
	{
		$this->evento('cambiar_tab__siguiente')->set_etiqueta('Procesar');
		$this->evento('cambiar_tab__siguiente')->set_imagen('/generar.png');
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__propuestas_ubicaciones = $seleccion;
	}

	function conf_evt__cuadro__seleccion(toba_evento_usuario $evento, $fila)
	{
		$evento->set_check_activo($this->es_clave_seleccionada($this->dep('cuadro')->get_clave_fila($fila, true), 'propuesta'));
	}
	
	function es_clave_seleccionada($clave_fila, $dato)
	{
		$claves = array();
		if (isset($this->s__propuestas_ubicaciones)) {
			foreach ($this->s__propuestas_ubicaciones as $valor) {
				$claves[] = implode(apex_qs_separador, $valor);
			}
		}
		return in_array($clave_fila, $claves);
	}

	function validar_propuestas()
	{
		if (empty($this->s__propuestas_ubicaciones)) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_propuestas');
			throw guarani::error(guarani_mensajes::get('800_no_seleccion', array($etiqueta->get('una'))));
		} else {
			$this->s__alumnos = toba::consulta_php('co_alumnos')->get_alumnos_activos_propuestas_ubicaciones($this->s__propuestas_ubicaciones);
			if (guarani_arreglos::count($this->s__alumnos) == 0) {
				throw guarani::error(guarani_mensajes::get('800CAL_sin_alumnos_activos'));
			}
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PROCESAMIENTO ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__procesar(toba_ei_pantalla $pantalla)
	{
		parent::conf__procesar($pantalla);
		$this->evento('cambiar_tab__anterior')->ocultar();
		$this->dependencia('form_totales')->set_solo_lectura();
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__finalizar()
	{
		$this->limpiar_memoria();
		$this->set_pantalla('propuestas');
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares el manejo del avance del proceso ----------------------------------
	//-----------------------------------------------------------------------------------

	function ajax__estado_proceso($parametros, toba_ajax_respuesta $respuesta)
	{
		$contenido = $this->cn()->get_estado_proceso();
		if (!empty($contenido)) {
			$respuesta->set($contenido);
		}
	}

	function ajax__resultados_proceso($parametros, toba_ajax_respuesta $respuesta)
	{
		$resultados = $this->cn()->get_resultado_proceso(pro_calcular_coeficientes_inscripcion::reporte_resultados);
		if (!empty($resultados['mensajes'])) {
			$respuesta->set(current($resultados['mensajes']));
		}
	}
}
?>