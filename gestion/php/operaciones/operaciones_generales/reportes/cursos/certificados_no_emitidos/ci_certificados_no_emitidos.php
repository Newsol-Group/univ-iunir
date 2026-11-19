<?php
class ci_certificados_no_emitidos extends ci_impresion_popup 
{
	protected $ev_info;

	function ini() 
	{
		parent::ini();
		$this->s__titulo = guarani_datos_impresion::instancia()->get_datos('titulo');
		$this->s__datos = rs_ordenar_por_columna(guarani_datos_impresion::instancia()->get_datos('alumnos'), 'apellido_y_nombre');
	}

	function conf__pant_inicial(toba_ei_pantalla $pantalla) 
	{
		$this->ev_info()->set_datos(current($this->s__datos_conf));
	}

	function conf__cuadro(toba_ei_cuadro $cuadro) 
	{
		$cuadro->set_datos($this->s__datos);
	}

	function ev_info() 
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_td_etiqueta_width('140px');
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_columnas_a_mostrar(array(
				'actividad_comision',
				'fecha_inicio_dictado_visual',
				'fecha_fin_dictado_visual',
				'horario_descr',
				'ubicacion_nombre'
			));
			$this->ev_info->set_max_columnas_por_fila(1);

			$this->ev_info->set_etiqueta('actividad_comision', 'Curso');
			$this->ev_info->set_etiqueta('fecha_inicio_dictado_visual', 'Inicio de Clases');
			$this->ev_info->set_etiqueta('fecha_fin_dictado_visual', 'Finalización de Clases');
			$this->ev_info->set_etiqueta('horario_descr', 'Horario');
			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicación');
		}
		return $this->ev_info;
	}

	function vista_xml($inicial = false, $xmlns = null) 
	{
		// ----------------------------------------------------------
		//     ENCABEZADO
		// ----------------------------------------------------------

		$this->xml_set_alto_cabecera('4cm');
		$this->xml_set_titulo($this->s__titulo);
		$path_logo = guarani::get_path_logo_impresion_chico();
		$this->xml_set_logo($path_logo);

		// ----------------------------------------------------------
		//    CUERPO
		// ----------------------------------------------------------

		$xml = $this->xml_get_tag_inicio();
		if ($this->dep('cuadro')->get_datos()) {
			$xml .= $this->dep('cuadro')->vista_xml(false, $xmlns);
		}
		$xml .= $this->xml_get_tag_fin();
		return $xml;
	}
}
?>