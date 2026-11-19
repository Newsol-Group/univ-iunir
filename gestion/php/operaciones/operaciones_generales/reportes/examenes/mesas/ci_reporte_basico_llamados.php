<?php
class ci_reporte_basico_llamados extends ci_impresion_popup
{
	//Elemento Visual de Información
	protected $ev_info;
	//Parámetro que indica el índice del reporte a recuperar y columnas a mostrar.
	protected $s__mostrar_obs;
	
	function ini()
	{
		parent::ini();
		$this->s__datos	= guarani_datos_impresion::instancia()->get_datos(mesa_de_examen::ind_mesas_llamados);
		/**
		 * Se chequea si está seteada como true la condición de mostrar observaciones de las Mesas creadas en los 
		 * parámetros del popup. Esto permite ocultar o mostrar las columnas que correspondan, ya que este item se 
		 * utiliza desde más de una operación y para varios reportes.
		 */
		$param_mostrar_obs = toba::memoria()->get_parametro(mesa_de_examen::mostrar_observaciones);
		if (!empty($param_mostrar_obs)) {
			$this->s__mostrar_obs = $param_mostrar_obs;
		}
		if ($this->s__mostrar_obs) {
			$this->s__titulo = 'Reporte de Mesas creadas con Observaciones';
		} else {
			$this->s__titulo = 'Reporte de Mesas creadas correctamente';
			$this->dependencia('cuadro')->eliminar_columnas(array('observaciones'));
		}
	}
	
	function conf__pant_reporte(toba_ei_pantalla $pantalla)
	{
		//Seteo de datos en ev
		$this->ev_info()->set_datos($this->s__datos_conf);
	}
	
	//---- Componente visual para mostrar Información ------------------------------------
	
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_titulo('Modificación aplicada');
			$this->ev_info->set_style('border-style: solid; border-width: 1px; border-color: #DDD;');
			$this->ev_info->set_style_fila('height: 20px;');	
			$this->ev_info->set_td_etiqueta_width('80px');
			$this->ev_info->set_td_valores_width('150px');
			$this->ev_info->set_columnas_a_mostrar(array('anio', 'tipo_modificacion_desc', 'motivo_desc', 'fecha_formato_visual', 'hora_inicio', 'hora_finalizacion'));
			$this->ev_info->set_columnas_fila(array('anio', 'tipo_modificacion_desc', 'motivo_desc'), 1);
			$this->ev_info->set_columnas_fila(array('fecha_formato_visual'), 2);
			$this->ev_info->set_columnas_fila(array('hora_inicio', 'hora_finalizacion'), 3);
			$this->ev_info->set_etiqueta('anio', 'Año académico');
			$this->ev_info->set_etiqueta('tipo_modificacion_desc', 'Modificación');
			$this->ev_info->set_etiqueta('motivo_desc', 'Motivo');
			$this->ev_info->set_etiqueta('fecha_formato_visual', 'Fecha');
			$this->ev_info->set_etiqueta('hora_inicio', 'Hora Inicio');
			$this->ev_info->set_etiqueta('hora_finalizacion', 'Hora Fin');
		}
		return $this->ev_info;
	}
	
	//---- cuadro -----------------------------------------------------------------------
	
	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_datos($this->s__datos);
	}
}
?>