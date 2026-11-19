<?php
/**
 *
 *
 * @package 	Comunicaciones
 * @subpackage 	-
 * 
 */
class pro_envio_mensajes_mail_nucleo extends guarani_procesos_bk
{
	protected $datos_mensaje;
	protected $vinculo_raiz;

	const nombre = 'pro_envio_mensajes_mail';

	function set_datos($datos)
	{
		$this->datos_mensaje = $datos;
	}

	function set_vinculo_raiz($vinculo_raiz)
	{
		$this->vinculo_raiz = $vinculo_raiz;
	}

	function procesar()
	{
		$mensaje = $this->datos_mensaje['mensaje'];
		$destinatarios = guarani::co('co_mensajes')->get_destinatarios_mail($mensaje);
		$adjuntos = guarani::co('co_mensajes')->get_listado_adjuntos($mensaje);

		$asunto = $this->datos_mensaje['titulo'];
		$cuerpo = $this->datos_mensaje['cuerpo'];

		if (!empty($adjuntos)) {
			$cuerpo .= '<br/><br/><strong>Archivos adjuntos:</strong><br/><br/>';
			$cuerpo .= '<ul>';
			foreach ($adjuntos as $adjunto) {
				$parametros_formateados = "&id_archivo={$adjunto['id_archivo']}";
				$vinculo = $this->vinculo_raiz . '&' . apex_hilo_qs_parametros . '=' . toba::encriptador()->cifrar($parametros_formateados);
				$cuerpo .= "<li><a href='$vinculo'>" . $adjunto['nombre_archivo'] . '</a></li>';
			}
			$cuerpo .= '</ul>';
		}

		$to = guarani::parametros()->get_valor('email_to');

		$mail = new guarani_mail(null, $to, $asunto, $cuerpo);

		$mail->set_html(true);
		$mail->set_bcc($destinatarios);
		try {
			$mail->ejecutar();
			// Mail enviado correctamente
			guarani::act('act_mensajes')->marcar_mail($mensaje, '1');
		} catch (toba_error $e) {
			guarani::act('act_mensajes')->insertar_error_envio_mail($mensaje, $e->get_mensaje());
			// Mail no enviado
			guarani::act('act_mensajes')->marcar_mail($mensaje, '0');
		}
	}

}
?>