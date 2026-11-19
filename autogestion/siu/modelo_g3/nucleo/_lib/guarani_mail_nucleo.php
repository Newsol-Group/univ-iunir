<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class guarani_mail_nucleo extends toba_mail
{
	
	protected $s__datos_mail;
	
	/**
	 * Constructor de la clase
	 * @param string $persona id de la persona a la cual se le enviara el mail
	 * @param string $hacia  Direccion de email a la cual se enviará
	 * @param string $asunto
	 * @param string $cuerpo Contenido del email
	 * @param string $desde Direccion de email desde la cual se envia (opcionalmente se obtiene desde los parametros)
	 */
	function __construct($persona = null, $hacia = null, $asunto = null, $cuerpo = null, $desde = null)
	{
		$this->s__datos_mail = null;

		if (!is_null($persona)) {
			// Se obtienen los datos de contacto de la persona
			$datos = toba::consulta_php('co_personas')->get_datos_persona_mail($persona);

			if (!empty($datos)) {
				$this->s__datos_mail = current($datos);
				// Se obtienen los datos de email en este orden: MP,MS,MI
				// Si la persona no tiene configurado un mail no se completan los datos
				if ((is_null($hacia)) && (trim($this->s__datos_mail['email']))) {
					$hacia = $this->s__datos_mail['email'];
					if (is_null($asunto)) {
						$asunto = $this->get_asunto();
					}
					if (is_null($cuerpo)) {
						$cuerpo = $this->get_cuerpo();
					}
				}
			}
		}

		parent::__construct($hacia, $asunto, $cuerpo, $desde);
	}
	
	/**
	 * Asunto del mail.
	 * Implementado como función separada para facilitar personalización.
	 * 
	 * @return string
	 */
	protected function get_asunto()
	{
		return "Comprobante de Inscripción - SIU-Guaraní 3";
	}
	
	/**
	 * Cuerpo del mail.
	 * Implementado como función separada para facilitar personalización.
	 * 
	 * @return string
	 */
	protected function get_cuerpo()
	{
		$cuerpo = "<strong>" . $this->s__datos_mail['nombre_completo'] . "</strong><br>";
		$cuerpo .= "Este es un mail automático del sistema SIU-Guaraní donde se adjunta el comprobante correspondiente.<br><br>";
		return $cuerpo;
	}

	//--------------------------------------------------------------------------
	
	/**
	 * Obtiene la configuración del servidor SMTP desde los parámetros del sistema.
	 * 
	 * Dado que la configuracion de los parametros del mail estan dados a nivel 
	 * de responsable academica, la clase que realice el envío de mails debe setear 
	 * este dato en memoria para el envio correcto del mail
	 * 
	 * @return array
	 */
	function get_datos_configuracion_smtp()
	{
		//Se obtiene la configuración del SMTP
		$datos = mail_job::get_configuracion_smtp();
		
		$this->set_reply($datos['responder_a']);
		$this->set_remitente($datos['nombre_remitente']);

		return $datos;
	}

	function get_datos_mail()
	{
		return $this->s__datos_mail;
	}
	
	/**
	 *  Realiza el envio del email propiamente dicho
	 */
	function enviar()
	{
		//Se obtiene la configuración del SMTP
		$this->datos_configuracion = $this->get_datos_configuracion_smtp();
		if (! isset($this->desde)) {
			$this->desde = $this->datos_configuracion['from'];
		}

		//Construye y envia el mail
	   	$mail = new PHPMailer();
		mail_job::configurar_smtp($mail);
		
	   	if ($this->debug) {
	   		$mail->SMTPDebug = SMTP::DEBUG_SERVER;
	   	}
		$mail->Timeout = $this->timeout;
		
		if (isset($this->datos_configuracion['nombre_from']) && trim($this->datos_configuracion['nombre_from']) != '') {
			$this->desde_nombre = $this->datos_configuracion['nombre_from'];
		}
		if (isset($this->desde_nombre)){
			$mail->setFrom($this->desde, $this->desde_nombre);
		} else {
			$mail->setFrom($this->desde, $this->desde);
		}

		//Alterno entre mail de prueba y destino oficial
		if (isset($this->datos_configuracion['destino_prueba']) && trim($this->datos_configuracion['destino_prueba']) != '') {
			$mail->AddAddress(trim($this->datos_configuracion['destino_prueba']));
		} else {
			$mail->AddAddress($this->hacia);
			//Agrego copias
			foreach($this->cc as $copia) {
					$mail->AddCC($copia);
			}
			//Agrego copias ocultas
			foreach($this->bcc as $copia) {
					$mail->AddBCC($copia);
			}
		}
		//Direccion de respuesta
		if (isset($this->reply_to)) {
			$mail->AddReplyTo($this->reply_to);
		}

		if (isset($this->confirmacion)) {
			$mail->ConfirmReadingTo = $this->confirmacion;
		}

		$mail->Subject  = $this->asunto;
		$mail->Body     = $this->cuerpo;
		$mail->IsHTML($this->html);
		$temporales = [];
		$dir_temp = toba::proyecto()->get_path_temp();
		foreach (array_keys($this->adjuntos) as $id_adjunto) {
			$archivo = tempnam($dir_temp, 'adjunto');
			file_put_contents($archivo, $this->adjuntos[$id_adjunto]['archivo']);
			$temporales[] = $archivo;
		   	$tipo = $mail->_mime_types($this->adjuntos[$id_adjunto]['tipo']);
			$mail->AddAttachment($archivo, $this->adjuntos[$id_adjunto]['nombre'], 'base64', $tipo);
		}
		$exito = $mail->Send();
		toba::logger()->debug("Enviado mail con asunto {$this->asunto} a {$this->hacia}");

		//Elimina los temporales creado para los attachments
		foreach ($temporales as $temp) {
			unlink($temp);
		}
		if (!$exito) {
			throw new toba_error("Imposible enviar mail. Mensaje de error: {$mail->ErrorInfo}");
		}

	}

}