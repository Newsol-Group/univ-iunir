<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos de Ingreso
 * 
 */
class ci_edi_prorrogar_presentacion extends guarani_pers_ci
{
	protected $datos_temp;
	
	protected $s__datos_periodo;
	
	protected $ev_info;
	protected $ev_info_fecha_cierre;

	function ini()
	{
		$this->dep('requisitos')->set_columna_ordenamiento('nombre');
	}
	
	function unset_vars()
	{
		unset($this->s__datos_periodo);
	}

	//-----------------------------------------------------------------------------------
	//---- ev_info's --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__pant_inicial()
	{
		if (!isset($this->s__datos_periodo)) {
			$this->s__datos_periodo = toba::consulta_php('co_periodos_de_inscripcion')->get_datos_periodo_inscripcion_propuesta($this->controlador()->get_periodo_inscripcion_seleccionado());
			$formateo = new toba_formateo('html');
			$this->s__datos_periodo['fecha_cierre_requisitos'] = $formateo->formato_fecha($this->s__datos_periodo['fecha_cierre_requisitos']);
		}
		
		$this->ev_info()->set_datos($this->s__datos_periodo);
		$this->ev_info_fecha_cierre()->set_datos($this->s__datos_periodo);
	}
	
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('periodo_nombre', 'anio_academico'));
			
			$this->ev_info->set_columnas_fila(array('periodo_nombre', 'anio_academico'), 1);			
			$this->ev_info->set_columnas_fila(array('fecha_cierre_requisitos'), 2);
			
			$this->ev_info->set_max_columnas_por_fila(2);
			
			$this->ev_info->set_etiqueta('periodo_nombre', 'Período Inscripción');
			$this->ev_info->set_etiqueta('anio_academico', 'Año Académico');	
			
			$this->ev_info->set_table_width('70%');
			
		}
		return $this->ev_info;
	}
	
	function ev_info_fecha_cierre()
	{
		if (!isset($this->ev_info_fecha_cierre)) {
			$this->ev_info_fecha_cierre = guarani::ev_info_datos();
			$this->ev_info_fecha_cierre->set_columnas_a_mostrar(array('fecha_cierre_requisitos'));
			
			$this->ev_info_fecha_cierre->set_columnas_fila(array('fecha_cierre_requisitos'), 1);			
			$this->ev_info_fecha_cierre->set_max_columnas_por_fila(1);
			
			$this->ev_info_fecha_cierre->set_etiqueta('fecha_cierre_requisitos', 'Fecha de Cierre de Presentación de Requisitos del Período:');
			
			$this->ev_info_fecha_cierre->set_table_width('');
			$this->ev_info_fecha_cierre->set_style_valores('color:blue; font-weigth:bold;');
			
		}
		return $this->ev_info_fecha_cierre;
	}
	
	//-----------------------------------------------------------------------------------
	//---- requisitos -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__requisitos(toba_ei_formulario_ml $form_ml)
	{
		if (isset($this->datos_temp)) {
			$this->controlador->set_hubo_cambios();
			$datos = $this->datos_temp;
		} else {
			$datos = $this->cn()->entidad()->get_datos();
		}

		$filtro = empty($datos) ? '' : guarani_sql::armar_condicion_in($datos, 'sga_requisitos', 'requisito', 'requisito', true);
		$datos_posibles = toba::consulta_php('co_requisitos_ingreso')->get_por_periodo_inscripcion($this->controlador()->get_periodo_inscripcion_seleccionado(), $filtro);

		$form_ml->set_datos_posibles($datos_posibles);
		$form_ml->set_datos($datos);
	}

	function evt__requisitos__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_prorrogar_presentacion($datos, $this->controlador()->get_periodo_inscripcion_seleccionado());
		} catch (guarani_error $e) {
			$this->datos_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

}
?>