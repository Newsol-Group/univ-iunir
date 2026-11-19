<?php
namespace siu\operaciones\datos_censales\_comun;

use SIU\Chulupi\kernel;
use siu\modelo\transacciones\becas;

abstract class pagelet_con_edicion extends pagelet_con_mensajes
{
	const FORM_ERROR_VALIDACION = 'form_error_validacion';
	const FORM_GUARDADO = 'form_guardado';
	
	const ESTADO_LISTADO = 'listado';
	const ESTADO_EDICION = 'edicion';
	
	protected $form;
	protected $pantalla = 'listado';

	function set_pantalla($id)
	{
        $this->pantalla = $id;
	}
	
	function get_nombre_seccion()
	{
		return 'datos_censales.form.'.$this->get_nombre();
	}
	
	function get_accion_edicion()
	{
		return kernel::vinculador()->crear('datos_censales', 'edicion', array($this->controlador->vista()->get_id_form_activo()));
	}
	
	function get_accion_volver_edicion()
	{
		return kernel::vinculador()->crear('datos_censales', 'volver_edicion', array($this->controlador->vista()->get_id_form_activo()));
	}
	
	function hay_campos_faltantes()
	{
		return $this->controlador->hay_datos_faltantes();
	}

	function hay_datos_faltantes_convocatoria()
	{
		return $this->controlador->hay_datos_faltantes_convocatoria();
	}

	function viene_de_convocatorias_becas()
	{
            return becas::viene_de_convocatorias_becas();
	}
	
	function get_url_siguiente_paso()
	{
            return becas::get_url_siguiente_paso();
	}
	
}