<?php
class ci_edi_proceso_bk extends guarani_pers_ci
{
	protected $s__valor;
	protected $s__ultimo_elemento = 0;
	protected $s__reporte;

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__procesar()
	{
		$datos_proceso = $this->s__valor;
		$this->cn()->ejecutar_proceso($datos_proceso);
		$this->set_pantalla('proceso');
	}

	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__formulario(toba_ei_formulario $form)
	{
		if (isset($this->s__valor)) {
			$form->set_datos($this->s__valor);
		}
	}

	function conf__resultados(toba_ei_formulario $form)
	{
		$form->set_solo_lectura();
	}

	function evt__formulario__modificacion($datos)
	{
		$this->s__valor = $datos;
	}

	function ajax__estado_proceso($parametros, toba_ajax_respuesta $respuesta)
	{
		$contenido = $this->cn()->get_estado_proceso();
		if (!empty($contenido)) {
			$respuesta->set($contenido);
		}
	}

	function ajax__reporte($params, toba_ajax_respuesta $respuesta)
	{
		switch ($params['evento']){
			case 'rep_com_copiadas':
				$reporte			= pro_prueba::reporte_copiadas;
				$operacion			= '38000079';
				$parametro_popup	= 'comisiones';
			break;
			case 'rep_sin_copia':
				$reporte			= pro_prueba::reporte_no_copiadas;
				$operacion			= '38000079';
				$parametro_popup	= 'comisiones';
			break;
			case 'rep_errores':
				$reporte			= pro_prueba::reporte_errores;
				$operacion			= '38000082';
				$parametro_popup	= 'comisiones_con_obs';
			break;
		}

		// Obtengo el Proceso para pedirle el log de resultados
		$datos = $this->cn()->get_resultado_proceso($reporte);
		//Almaceno los datos en la instasncia de impresion para ser consumida luego del ci del popup
		guarani_datos_impresion::instancia()->set_datos(array($parametro_popup => $datos['mensajes']));
		guarani_datos_impresion::instancia()->guardar_datos_imp();

		//Creo el vinculo para que se abra en el popup
		$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(),$operacion, null, array('celda_memoria'=>'popup'));
		$respuesta->set($vinculo);
	}

	function ajax__resultados_proceso($parametros, toba_ajax_respuesta $respuesta)
	{
		$contenido = $this->cn()->get_resultado_proceso(pro_prueba::reporte_resultados);
		$resultados = current($contenido['mensajes']);
		if (!empty($resultados)) {
			$respuesta->set($resultados);
		}
	}


	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_informe(toba_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->get_resultado_proceso($this->s__reporte);
		if (isset($datos['mensajes'])) {
			$cuadro->set_datos($datos['mensajes']);
		}
		
	}

	function evt__refresh()
	{
		$this->s__reporte = pro_prueba::reporte_copiadas;
	}

	function evt__reporte1()
	{
		$this->s__reporte = pro_prueba::reporte_copiadas;
		$this->set_pantalla('resultado');
	}
	
	function evt__reporte2()
	{
		$this->s__reporte = pro_prueba::reporte_no_copiadas;
		$this->set_pantalla('resultado');
	}



	function evt__volver()
	{
		$this->set_pantalla('pant_inicial');
	}

}
?>