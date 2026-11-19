<?php
namespace nsg01\operaciones\datos_censales;

use \siu\operaciones\datos_censales\_comun\pagelet_con_edicion;
use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;

class pagelet_datos_contacto extends \siu\operaciones\datos_censales\pagelet_datos_contacto
{
	function get_datos_contacto()
	{
		$datos = $this->controlador->modelo()->info__datos_contacto();
		$es_docente = kernel::persona()->es_perfil_docente();
		
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
				
				//if (not $es_docente) {
					$datos[$key]['editar_mp_descr'] = $editar_mp_descr;
					$datos[$key]['link_editar_mp'] = $link_editar_mp;
				//}
				//else {
				//	$datos[$key]['editar_mp_descr'] = '';
				//	$datos[$key]['link_editar_mp'] = '';
				//}
			} else {
				$datos[$key]['LINK_EDITAR'] = kernel::vinculador()->crear('datos_censales', 'editar_datos_contacto', array('datos_contacto', $dato[catalogo::id]));
				$datos[$key]['LINK_ELIMINAR'] = kernel::vinculador()->crear('datos_censales', 'eliminar', array('datos_contacto', $dato[catalogo::id]));
			}
		}
		
		return $datos;
	}
}
?>