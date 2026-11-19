<?php
class ci_nav_instituciones extends ci_navegacion
{
	//-----------------------------------------------------------------------------------
	//---- PANTALLAS --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		/**
		 * En caso de edición se verifica si la Institución es la Inst gestionante por defecto, ya que en dicho caso no
		 * se permite su eliminación.
		 */
		if (!$this->cn()->entidad_es_nueva()) {
			$inst = $this->cn()->entidad()->get_datos();
			if ($inst['institucion'] == institucion::get_gestionante()) {
				$pantalla->evento('eliminar')->desactivar();
			}
		} 
		parent::conf__edicion($pantalla);
	}
}
?>