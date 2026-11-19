<?php
class ci_visualizador_comision extends guarani_pers_ci
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

		if (!isset($this->info_comision['comision'])) {
			return;
		}

		if (!isset($this->info_comision['modalidades'])) {
			$this->info_comision['modalidades'] = toba::consulta_php('co_comisiones')->get_comision_modalidades($this->info_comision['comision']);
		}

		if (!isset($this->info_comision['instancias'])) {
			$this->info_comision['instancias'] = toba::consulta_php('co_comisiones')->get_comision_instancias($this->info_comision['comision']);
		}
		
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
			$this->ev_info->set_columnas_a_mostrar(array('comision_nombre','subcomision_nombre','propuesta_descr','plan_nombre','plan_version_nombre','ubicacion_nombre','descr', 'instancias', 'modalidades'));
			
			$this->ev_info->set_max_columnas_por_fila(2);
			
			$this->ev_info->set_columnas_fila(array('comision_nombre'), 0);
			$this->ev_info->set_columnas_fila(array('subcomision_nombre'), 1);
			$this->ev_info->set_columnas_fila(array('propuesta_descr'), 2);
			$this->ev_info->set_columnas_fila(array('plan_nombre','plan_version_nombre'), 3);
			$this->ev_info->set_columnas_fila(array('descr'), 4);
			$this->ev_info->set_columnas_fila(array('instancias'), 6);
			$this->ev_info->set_columnas_fila(array('modalidades'), 7);
			$this->ev_info->set_columnas_fila(array('ubicacion_nombre'), 8);

			$this->ev_info->set_etiqueta('comision_nombre', 'Comisión');
			$this->ev_info->set_etiqueta('subcomision_nombre', 'Subcomisión');
			$this->ev_info->set_etiqueta('propuesta_descr', 'Propuesta');
			$this->ev_info->set_etiqueta('plan_nombre', 'Plan');
			$this->ev_info->set_etiqueta('plan_version_nombre', 'Versión');
			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicación');
			$this->ev_info->set_etiqueta('descr', 'Actividad');
		}
		return $this->ev_info;
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_docentes --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_docentes(toba_ei_cuadro $cuadro)
	{
		if (isset($this->info_comision['subcomision'])) {
			$docentes = toba::consulta_php('co_comisiones')->get_docentes_subcomision($this->info_comision['subcomision']);
			$cuadro->set_datos($docentes);
		} else {
			if (isset($this->info_comision['comision'])) {
				$docentes = toba::consulta_php('co_comisiones')->get_docentes_comision($this->info_comision['comision']);
				$cuadro->set_datos($docentes);
			}
		}
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_horarios --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_horarios(toba_ei_cuadro $cuadro)
	{
		if (isset($this->info_comision['subcomision'])) {
			$horarios = toba::consulta_php('co_bandas_horarias')->get_listado_asignaciones_subcomision($this->info_comision['subcomision']);
			$cuadro->set_datos($horarios);
		} else {
			if (isset($this->info_comision['comision'])) {
				$horarios = toba::consulta_php('co_bandas_horarias')->get_listado_asignaciones_comision($this->info_comision['comision']);
				$cuadro->set_datos($horarios);
			}
		}
	}

	//-----------------------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

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