<?php
class ci_atributos_optativa_plan extends guarani_pers_ci
{
	protected $s__elemento_revision;
	protected $s__plan_version;

	function ini()
	{
		$parametro_elemento_revision = toba::memoria()->get_parametro('elemento_revision');
		if (!empty($parametro_elemento_revision)) {
			$this->s__elemento_revision = $parametro_elemento_revision;
		}
		$parametro_plan_version = toba::memoria()->get_parametro('plan_version');
		if (!empty($parametro_plan_version)) {
			$this->s__plan_version = $parametro_plan_version;
		}
	}

	function conf()
	{
		$this->dep('formulario')->set_solo_lectura();
	}

	function conf__formulario(toba_ei_formulario $form)
	{
		if (isset($this->s__elemento_revision) && isset($this->s__plan_version)) {
			$datos = toba::consulta_php('co_elementos')->get_datos_actividad_en_plan_version($this->s__elemento_revision, $this->s__plan_version);
			$datos['modalidad'] = aplanar_matriz(toba::consulta_php('co_planes')->get_modalidad_elemento_plan($datos['elemento_plan']), 'modalidad');
			$form->set_datos($datos);
		}
	}

	function extender_objeto_js()
	{
		echo "
			{$this->objeto_js}.evt__cerrar = function() {
				window.close();
			}
		";
	}
}
?>