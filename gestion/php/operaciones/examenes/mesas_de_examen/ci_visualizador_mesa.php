<?php
class ci_visualizador_mesa extends guarani_pers_ci
{
	protected $info_mesa;
	protected $ev_info;

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		toba::memoria()->desactivar_reciclado();
		$this->info_mesa = toba::memoria()->get_parametros();

		if (!isset($this->info_mesa['mesa_examen'])) {
			return;
		}

		$this->ev_info()->set_datos($this->info_mesa);
		if (isset($this->info_mesa['titulo'])) {
			$this->ev_info()->set_titulo($this->get_titulo());
			$this->set_titulo($this->info_mesa['titulo']);
		}
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('mesa_nombre'));
			$this->ev_info->set_etiqueta('mesa_nombre', 'Mesa de Examen');
		}
		return $this->ev_info;
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_docentes --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_docentes(toba_ei_cuadro $cuadro)
	{
		if (isset($this->info_mesa['llamado_mesa'])) {
			$docentes = guarani::co('co_mesas_de_examen')->get_docentes_mesa($this->info_mesa['llamado_mesa']);
			$cuadro->set_datos($docentes);
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