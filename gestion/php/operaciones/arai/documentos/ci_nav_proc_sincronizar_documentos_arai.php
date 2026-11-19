<?php
/**
 *
 *
 * @package     Araí
 * @subpackage  Documentos
 */
class ci_nav_proc_sincronizar_documentos_arai extends ci_nav_procesos_bk
{

	public function set_nombre_proceso() {
		$this->s__nombre_proceso = pro_proc_sincronizar_documentos_arai::nombre;
	}

	//--------------------------------------------------------------------------
	//---- cuadro_actas -----------------------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		
		$datos = [];
		
		$actas = $this->cn()->get_actas_sincronizar();
		foreach ($actas as $acta) {
			$renglon['tipo_documento'] = $acta['tipo_documento'];
			$renglon['detalle'] = $acta['detalle'];
			$datos[] = $renglon;
		}
		
		$equivalencias = $this->cn()->get_tramites_equivalencias_sincronizar();
		foreach ($equivalencias as $equivalencia) {
			$renglon['tipo_documento'] = $equivalencia['tipo_documento'];
			$renglon['detalle'] = $equivalencia['detalle'];
			$datos[] = $renglon;
		}
		
		$datos = rs_ordenar_por_columnas($datos, array('tipo_documento'));
		
		$cuadro->set_datos($datos);

		$msj_eof = guarani_mensajes::get('800_eof_cuadro', array('documentos a sincronizar con Araí'));
		$cuadro->set_eof_mensaje($msj_eof);
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__procesar()
	{
		
		$actas = $this->cn()->get_actas_sincronizar();
		$tramites_equivalencias = $this->cn()->get_tramites_equivalencias_sincronizar();

		if (empty($actas) && empty($tramites_equivalencias)) {
			$msj_eof = guarani_mensajes::get('800_eof_cuadro', array('documentos a sincronizar con Araí'));
			throw guarani::error($msj_eof);
		} else {
			$this->cn()->ejecutar_proceso(['actas' => $actas, 'tramites_equivalencias' => $tramites_equivalencias]);
			$this->set_pantalla('procesar');
		}
	}

	function evt__volver()
	{
		$this->set_pantalla('seleccion');
	}

	//-----------------------------------------------------------------------------------
	//---- Procesamiento
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
		$contenido = $this->cn()->get_resultado_proceso(pro_proc_sincronizar_documentos_arai::reporte_resultados);
		$resultados = current($contenido['mensajes']);
		if (!empty($resultados)) {
			$respuesta->set($resultados);
		}
	}

	function ajax__reporte($params, toba_ajax_respuesta $respuesta)
	{
		switch ($params['evento']) {
			case 'rep_sincronizados':
				$reporte = pro_proc_sincronizar_documentos_arai::reporte_sincronizados;
				$operacion = '89000007';
				$titulo = 'Documentos Sincronizados';
				break;
			case 'rep_fallidos':
				$reporte = pro_proc_sincronizar_documentos_arai::reporte_fallidos;
				$operacion = '89000007';
				$titulo = 'Documentos Fallidos';
				break;
		}

		if ($this->modo_visualizacion()) {
			$datos = $this->get_resultado_reporte($reporte);
		} else {
			// Se obtiene el proceso para pedirle el log de resultados
			$datos = $this->cn()->get_resultado_proceso($reporte);
		}

		// Se almacenan los datos en la instancia de impresión para ser consumida luego del ci del popup
		guarani_datos_impresion::instancia()->set_datos(array(
			'documentos' => $datos['mensajes'],
			'reporte' => $reporte,
			'titulo' => $titulo
		));
		guarani_datos_impresion::instancia()->guardar_datos_imp();

		//Creo el vinculo para que se abra en el popup
		$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), $operacion, null, array('celda_memoria' => 'popup'));
		$respuesta->set($vinculo);
	}

}