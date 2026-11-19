<?php
class ci_acercade extends guarani_pers_ci
{
	//-----------------------------------------------------------------------------------
	//---- acerca -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__acerca(toba_ei_formulario $form)
	{
		$datos['nombre'] = 'SIU-Guaraní 3';

		$version_app = toba::proyecto()->get_version();
		$datos['version_app'] = $version_app->__toString();

		$svn = new toba_svn;
		$revision = false;
		if ($svn->es_copia_trabajo(toba::proyecto()->get_path())) {
			$revision = $svn->get_revision(toba::proyecto()->get_path());
		}
		$datos['revision_svn'] = $revision ? 'rv: '.$revision : 'S/N';

		$version_bd = toba::consulta_php('co_parametros')->get_version_app_base();
		$datos['version_bd'] = $version_bd ? $version_bd : 'S/N';

		$form->set_datos($datos);
	}
}
?>