<?php
class ci_pro_cierre_masivo_equivalencia extends ci_nav_procesos_bk_sin_listado_inicial
{
	protected $s__tramites;
	
	public function get_tramite_equiv_seleccionado()
	{
		return current($this->s__seleccion);
	}
	
	public function set_nombre_proceso()
	{
		$this->s__nombre_proceso = 'pro_cierre_masivo_equivalencia';
	}

	public function evt__cuadro__seleccionar($seleccion)
	{
		$this->s__tramites = $seleccion;
	}
	
	public function evt__cuadro__seleccion($seleccion)
	{
		$this->s__seleccion = $seleccion;
		$this->set_pantalla('detalle_tramite');
		$this->cn()->cargar_atributos_parametros($seleccion['propuesta_alumno_id']);
	}
	
	
	public function evt__filtro__filtrar($datos)
	{
		if (isset($datos) && !empty($datos)) {
            $this->s__datos = $datos;
            $filtro = '';
            if ((isset($datos['responsable_academica']))) {
                $responsable = $datos['responsable_academica']['valor'];
                if ($datos['responsable_academica']['condicion'] == 'es_igual_a') {
                    $filtro .= " AND sga_propuestas.propuesta IN (SELECT propuesta FROM sga_propuestas_ra WHERE sga_propuestas_ra.responsable_academica = $responsable)";
                } else {
                    $filtro .= " AND sga_propuestas.propuesta NOT IN (SELECT propuesta FROM sga_propuestas_ra WHERE sga_propuestas_ra.responsable_academica = $responsable)";
                }
                $this->dep('filtro')->columna('responsable_academica')->resetear_estado();
            }
            $this->s__filtro = $this->dep('filtro')->get_sql_where().$filtro;
        } else {
            unset($this->s__datos);
            $this->s__filtro = '';
        }
	}
	
	
	public function evt__procesar()
	{
		if (guarani_arreglos::count($this->s__tramites) == 0) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_equiv_otorgada');
			$mje = guarani_mensajes::get('800_no_seleccion', array($etiqueta->get('una')));
			throw guarani::error($mje);
		}
		
		$this->procesar();
		$this->set_pantalla('procesar');
	}
		
	public function procesar()
	{		
		$parametros = array('tramites' => $this->s__tramites);
		$this->cn()->ejecutar_proceso($parametros);
		$this->set_pantalla('procesar');
	}
	
	public function evt__volver_detalle()
	{
		unset($this->s__seleccion);
		$this->set_pantalla('seleccion');
	}
	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__detalle_tramite(toba_ei_pantalla $pantalla)
	{
		$this->dep('ci_detalle_equivalencia')->evento('pdf')->anular();
	}
	
	
	
	function ajax__estado_proceso($parametros, toba_ajax_respuesta $respuesta)
	{	
		$contenido = $this->cn()->get_estado_proceso();
		if (!empty($contenido)) {
			$respuesta->set($contenido);
		}
	}
	
	function ajax__resultados_proceso($parametros, toba_ajax_respuesta $respuesta)
	{
		$contenido = $this->cn()->get_resultado_proceso(pro_cierre_masivo_equivalencia::reporte_resultados);
		$resultados = current($contenido['mensajes']);
		if (!empty($resultados)) {
			$respuesta->set($resultados);
		}
	}
	
	function ajax__reporte($params, toba_ajax_respuesta $respuesta)
	{
		switch ($params['evento']) {
			case 'reporte_cerrados':
				$reporte = pro_cierre_masivo_equivalencia::reporte_cerrados;
				$operacion = '14000021';
				$titulo = 'Equivalencias Otorgadas';
				break;
			case 'reporte_no_pasaron':
				$reporte = pro_cierre_masivo_equivalencia::reporte_no_pasaron;
				$operacion = '14000021';
				$titulo = 'Equivalencias que no pasaron controles';
				break;
		}

		if ($this->modo_visualizacion()) {
			$datos = $this->get_resultado_reporte($reporte);
		} else {
			// Obtengo el Proceso para pedirle el log de resultados
			$datos = $this->cn()->get_resultado_proceso($reporte);
		}
		
		//Almaceno los datos en la instasncia de impresion para ser consumida luego del ci del popup
		guarani_datos_impresion::instancia()->set_datos(array(	'datos' => $datos['mensajes'],
																'reporte' => $reporte,
																'titulo' => $titulo));
		guarani_datos_impresion::instancia()->guardar_datos_imp();

		//Creo el vinculo para que se abra en el popup
		$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), $operacion, null, array('celda_memoria' => 'popup'));
		$respuesta->set($vinculo);		
	}
	
	function conf__resultados(toba_ei_formulario $form)
	{
		$form->set_solo_lectura();		
		if (isset($this->s__datos_resultados)) {
			$form->set_datos($this->s__datos_resultados);
			if ($this->modo_visualizacion()) {				
				foreach (array_keys($this->s__datos_resultados) as $ef) {
					if ($form->existe_ef($ef) && $form->existe_evento($ef) && $form->ef($ef)->get_estado() == 0)
						$form->evento($ef)->desactivar();
				}
			}
		}
	}

}
?>