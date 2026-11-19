<?php
/**
 * 
 * 
 * @package 	Comunicación
 * @subpackage 	-
 *
 */
class cn_man_envio_mensajes extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'envio_mensaje';
	}

	function get_listado($filtro)
	{
		if ($filtro != '') {
			$filtro .= ' AND ';
		}
		// Recupera mensajes que aun no finalizó su publicación, que son visibles en gestion y que no son de envío automático.
		// Y que fueron enviados desde gestión.
		$filtro .= " CURRENT_DATE <= men_mensajes.fecha_hasta";
		$filtro .= " AND men_mensajes.interfaz = ".  quote(comunes::interfaz_gestion);
		$filtro .= " AND men_mensajes.visible_en_gestion = ".  quote(comunes::si);
		$filtro .= " AND men_alcances.envio_automatico = ".  quote(comunes::no);
		
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$todos = array(mensajes::todos_alumnos_y_docentes, mensajes::todos_alumnos, mensajes::todos_docentes);
			$filtro .= ' AND '.guarani_sql::armar_condicion_in($todos, 'men_alcances', 'alcance', 'alcance', true);			
		}
		
		$datos = guarani::co('co_mensajes')->get_listado($filtro);
		
		
		if (!empty($datos)) {
			$imagen_mail = toba_recurso::imagen_toba('mail.png', true, null, null, 'Enviado por mail');
			$imagen_unsent_mail = toba_recurso::imagen_proyecto('unsent-mail.png', true, null, null, 'Errores en el envío por mail');
			$imagen_twitter = toba_recurso::imagen_proyecto('redes_sociales/icono-twitter.png', true, null, null, 'Publicado en Twitter');
			$imagen_facebook = toba_recurso::imagen_proyecto('redes_sociales/icono-facebook.png', true, null, null, 'Publicado en Facebook');

			// Se agregan los íconos de Mail, Facebook y Twitter si corresponde
			foreach ($datos as $key => $dato) {
				if (!empty($dato['error_envio_mail'])) {
					$datos[$key]['enviado_por_mail'] = $imagen_unsent_mail;
				} else {
					$datos[$key]['enviado_por_mail'] = ($dato['enviar_por_mail'] == 1) ? $imagen_mail : '';
				}
				$datos[$key]['publicado_twitter'] = ($dato['enviar_por_twitter'] == 1) ? $imagen_twitter : '';
				$datos[$key]['publicado_facebook'] = ($dato['enviar_por_facebook'] == 1) ? $imagen_facebook : '';
			}
		}
		return $datos;
	}
	
	function get_alcances()
	{	
		/*
		 * Se hardcodea hasta que se defina la forma de quitar las opciones de una forma mas dinámica.
		 */
		$todos = array(mensajes::todos_alumnos_y_docentes, mensajes::todos_alumnos, mensajes::todos_docentes);
		$where = '';
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$where = ' AND '.guarani_sql::armar_condicion_in($todos, 'men_alcances', 'alcance', 'alcance', true);			
		}
		return guarani::co('co_mensajes')->get_alcances($where);
	}

}
?>