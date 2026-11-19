<?php
class ci_vis_info_curso extends guarani_pers_ci
{
	protected $info_comision;
	protected $ev_info;

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		toba::memoria()->desactivar_reciclado();
		$this->info_comision = toba::memoria()->get_parametros();
		
		$this->info_comision['dictado'] = 'Desde el ' . $this->info_comision['fecha_inicio_dictado_visual'] . ' hasta el ' . $this->info_comision['fecha_fin_dictado_visual'];
		
		$this->ev_info()->set_datos($this->info_comision);
		if (isset($this->info_comision['titulo'])) {
			$this->ev_info()->set_titulo($this->get_titulo());
			$this->set_titulo($this->info_comision['titulo']);
		}
	}

	function ev_info()
	{
		if (! isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_td_etiqueta_width('70px');
			
			$this->ev_info->set_columnas_a_mostrar(array('actividad_comision', 'dictado', 'horario_descr', 'ubicacion_nombre', 'docentes', 'costo'));
			$this->ev_info->set_max_columnas_por_fila(1);

			$this->ev_info->set_etiqueta('actividad_comision', 'Curso');
			$this->ev_info->set_etiqueta('dictado', 'Dictado');
			$this->ev_info->set_etiqueta('horario_descr', 'Horario');
			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicación');
			$this->ev_info->set_etiqueta('docentes', 'Docentes');
			$this->ev_info->set_etiqueta('costo', 'Costo');
		}
		return $this->ev_info;
	}
	
	//--------------------------------------------------------------------------
	//---- Javascript ----------------------------------------------------------
	//--------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		{$this->objeto_js}.evt__cerrar = function()	{
			window.close();
		}
		";
	}
}
?>