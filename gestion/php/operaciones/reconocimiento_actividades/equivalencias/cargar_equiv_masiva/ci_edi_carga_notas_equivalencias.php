<?php
/**
 *
 * @package 	Reconocimiento de Actividades
 * @subpackage 	Equivalencias
 * 
 */
class ci_edi_carga_notas_equivalencias extends guarani_pers_ci
{
	// Datos temporales para el formulario multilínea de notas.
	protected $datos_notas_temp;
	protected $s__datos;
	protected $s__datos_elementos = array();

	// Componente Visual de Información de datos de la evaluación.
	protected $ev_info;

	function ini()
	{
		parent::ini();
		$datos = $this->controlador()->get_equivalencias();
		foreach ($datos as $elemento) {
			$dato = array();
			$dato['elemento'] = $elemento['elemento'];
			$dato['actividad_descr'] = $elemento['actividad'];
			$filtro = 'sga_escalas_notas.escala_nota = ' . $elemento['escala_nota'];
			$detalle_escala = current(toba::consulta_php('co_escalas_notas')->get_listado_reporte($filtro));
			$dato['escala_nota_nombre'] = $detalle_escala['nombre_escala'];
			$dato['escala_nota_desc'] = $detalle_escala['descripcion_escala'];			
			$this->s__datos_elementos[] = $dato;
		}
	}

	function conf__pant_inicial()
	{
		$sel = $this->controlador()->get_seleccion();
		$datos_ev['fecha_visual'] = $sel['fecha_visual'];
		$datos_ev['institucion_nombre'] = $sel['institucion_nombre'];
		$datos_ev['propuesta_nombre'] = $sel['propuesta_nombre'];
		$datos_ev['resp_acad_nombre'] = $sel['resp_acad_nombre'];
		$datos_ev['icono_info_escala_notas'] = $this->get_html_icono_info_escala_notas();
		$this->ev_info()->set_datos($datos_ev);
	}

	function ev_info()		
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_style('width: 70%;');
			$this->ev_info->set_columnas_a_mostrar(array('fecha_visual', 'institucion_nombre', 'propuesta_nombre', 'resp_acad_nombre', 'icono_info_escala_notas'));
			$this->ev_info->set_columnas_fila(array('fecha_visual', 'institucion_nombre'), 1);
			$this->ev_info->set_columnas_fila(array('propuesta_nombre', 'resp_acad_nombre'), 2);
			$this->ev_info->set_columnas_fila(array('icono_info_escala_notas'), 3);
			$this->ev_info->set_etiqueta('fecha_visual', 'Fecha de Otorgamiento');
			$this->ev_info->set_etiqueta('institucion_nombre', 'Institución Externa');
			$this->ev_info->set_etiqueta('propuesta_nombre', 'Propuesta');
			$this->ev_info->set_etiqueta('resp_acad_nombre', 'Responsable Académica');
			$this->ev_info->set_etiqueta('icono_info_escala_notas', 'Actividades - Escala de Notas');
		}
		
		return $this->ev_info;
	}

	//-----------------------------------------------------------------------------------
	//---- form_ml_notas ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_ml_notas(toba_ei_formulario_ml $form_ml)
	{
		if (isset($this->datos_notas_temp)) {
			$this->s__datos = $this->datos_notas_temp;
		} else {
			if (!isset($this->s__datos)) {
				$this->s__datos = $this->controlador()->get_equivalencias();
			}
		}
		$form_ml->set_datos($this->s__datos);
	}


	function evt__form_ml_notas__modificacion($datos)
	{
		for ($i=0;$i<guarani_arreglos::count($datos);$i++) { 
			$this->s__datos[$i]['nota'] = $datos[$i]['nota'];
			$this->s__datos[$i]['libro'] = $datos[$i]['libro'];
			$this->s__datos[$i]['folio'] = $datos[$i]['folio'];
			$this->s__datos[$i]['resultado'] = $datos[$i]['resultado'];
			$this->s__datos[$i]['fecha'] = $datos[$i]['fecha'];
			$this->s__datos[$i]['acta'] = $datos[$i]['acta'];
		}
	}

	function evt__form_ml_notas__eliminar($datos)
	{
		unset($this->s__datos[$datos]);
		$this->datos_notas_temp = array();
		foreach($this->s__datos as $dato) {
			$this->datos_notas_temp[] = $dato;
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	 * Retorna el HTML de un vínculo (con imagen) a la operación que muestra el detalle de una escala de notas.
	 */
	function get_html_icono_info_escala_notas()
	{
		$datos_act = $this->controlador()->get_seleccion();
		toba::memoria()->set_dato('datos_cuadro_escalas', $this->s__datos_elementos);
		$vinculo = toba::vinculador()->get_url(toba_proyecto::get_id(), '34000039', array('mostrar_detalle' => true, 'plan_version' => $datos_act['plan_version']), array('celda_memoria'=>'escala_notas'));
		$javascript = "abrir_popup('info','$vinculo',{'width':'550','height':'520'})";

		$salida = "<a class='icono-utileria' href='#' onclick=\"$javascript\">";
		$salida .= toba_recurso::imagen_toba('info_chico.gif', true, null, null, 'Ver detalle de escala');
		$salida .= '</a>';

		return $salida;
	}
	
	function get_datos()
	{
		return $this->s__datos;
	}

	function set_datos($datos)
	{
		$this->s__datos = $datos;
	}

	function limpiar_datos()
	{
		unset($this->s__datos);
		unset($this->datos_notas_temp);
		unset($this->s__datos_elementos);
	}
	
	//-----------------------------------------------------------------------------------
	//---- JavaScript -------------------------------------------------------------------
	//----------------------------------------------------------------------------------
	/**
	 * Retorna el código JS necesario para validar las notas de las distintas escalas de la comisión.
	 */
	function get_js_validar_nota_escalas()
	{
		$detalles_escalas = $this->controlador()->get_equivalencias();
		$esc_exist = array();
		foreach ($detalles_escalas as $esc) {
			if (!(in_array($esc['escala_nota'], $esc_exist))) {
				$esc_exist[] = $esc['escala_nota'];
				$escalas[] = $esc;
			}
		}

		$validador = escalas_notas::validador_escalas_notas();
		return $validador->get_js_validar_nota_escalas($escalas);
	}
	
}
?>