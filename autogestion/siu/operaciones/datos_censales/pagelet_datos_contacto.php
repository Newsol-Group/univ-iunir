<?php
namespace siu\operaciones\datos_censales;

use \siu\operaciones\datos_censales\_comun\pagelet_con_edicion;
use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;

class pagelet_datos_contacto extends pagelet_con_edicion
{
	function get_nombre()
    {
        return 'datos_contacto';
    }
	
	function get_form($id_html = null)
	{
		if (! isset($this->form)) {
			$this->form = kernel::localizador()->instanciar('operaciones\datos_censales\datos_contacto\form_datos_contacto', $this->controlador);
		}
		return $this->form;
	}
	
	function get_datos_contacto()
	{
		$datos = $this->controlador->modelo()->info__datos_contacto();
		
		$mail_ppal = $this->controlador->get_mail_principal();
		$hay_mail_ppal = !empty($mail_ppal);
		
		if ($hay_mail_ppal) {
			$mail_principal = $mail_ppal;
			$editar_mp_descr = kernel::traductor()->trans('datos_censales.dc.mail_principal_editar');
		} else {
			$datos = array_merge(array(array('contacto_tipo' => \persona::mail_primario)), $datos);
			$mail_principal = kernel::traductor()->trans('datos_censales.dc.sin_mail_principal');
			$editar_mp_descr = kernel::traductor()->trans('datos_censales.dc.mail_principal_agregar');
		}			
		$link_editar_mp = kernel::vinculador()->crear('configuracion');
		
		foreach ($datos as $key => $dato) {
			if ($dato['contacto_tipo'] == \persona::mail_primario) {
				$datos[$key]['contacto_tipo_descr'] = kernel::traductor()->trans('datos_censales.dc.mail_principal');
				$datos[$key]['contacto_descripcion'] = $mail_principal;
				$datos[$key]['editar_mp_descr'] = $editar_mp_descr;
				$datos[$key]['link_editar_mp'] = $link_editar_mp;
			} else {
				$datos[$key]['LINK_EDITAR'] = kernel::vinculador()->crear('datos_censales', 'editar_datos_contacto', array('datos_contacto', $dato[catalogo::id]));
				$datos[$key]['LINK_ELIMINAR'] = kernel::vinculador()->crear('datos_censales', 'eliminar', array('datos_contacto', $dato[catalogo::id]));
			}
		}
		
		return $datos;
	}
	
	function get_js_files()
	{
		$files = parent::get_js_files();
		$files[] = kernel::vinculador()->vinculo_recurso("js/jqueryui.extra.js");
		$files[] = kernel::vinculador()->vinculo_recurso("js/utilidades.js");
		return $files;
	}
	
	function prepare()
	{
		$this->add_var_js('msj_sin_cambios', kernel::traductor()->trans('form_sin_cambios'));
		$this->add_var_js('error_vacio', kernel::traductor()->trans('form_validacion_error.vacio'));
		$this->add_var_js('msj_confirmar_eliminar', kernel::traductor()->trans('datos_censales.msj_confirmar_eliminar'));
		
		$this->add_var_js('tipo_MP', \persona::mail_primario);
		$this->add_var_js('tipo_MI', \persona::mail_institucional);
		$this->add_var_js('tipo_TW', \persona::twitter);
		$this->add_var_js('tipo_FB', \persona::facebook);
		$this->add_var_js('tipo_IG', \persona::instagram);
		$this->add_var_js('tipo_IN', \persona::linkedin);
		$this->add_var_js('tipo_MS', \persona::mail_secundario);
		$this->add_var_js('tipo_OTRO', \persona::otro_contacto);
		
		$form = $this->get_form();
		
		if ($this->pantalla == self::ESTADO_LISTADO) {
			$this->data['tipo_mail_principal'] = \persona::mail_primario;
		} elseif ($this->pantalla == self::ESTADO_EDICION) {
			switch ($this->estado) {
				case self::FORM_ERROR_VALIDACION:
					kernel::renderer()->add_to_ajax_response('info_estado', array(
						'mensaje' => kernel::traductor()->trans('guardado_datos_error'),
						'exitoso' => false
					));
					break;
				case self::FORM_GUARDADO:
					kernel::renderer()->add_to_ajax_response('info_estado', array(
						'mensaje' => kernel::traductor()->trans('guardado_datos_exitoso'),
						'exitoso' => true
					));
				default:
					$form->inicializar();
					$datos = $this->controlador->get_dato_contacto();
					$form->set_datos($datos);
			}
		}
		
		$form->activar_csrf($this->generar_csrf());
		$this->add_form($form);
		
		$this->data['cant_contactos'] = \guarani_arreglos::count($this->get_datos_contacto());
		$this->data['modo'] = $this->pantalla;
	}
}
?>