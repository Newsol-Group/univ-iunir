<?php
class ci_mails_popup extends guarani_pers_ci
{
	protected $s__persona;
	protected $archivo;
	protected $s__datos_mail;
	protected $envio_ok = false;

	function ini()
	{
		//Obtengo el id de la persona a la que se debe enviar el mail.
		$this->s__persona = toba::memoria()->get_dato('id_persona');
		if (!isset($this->s__persona)) {
			throw new guarani_error('No es posible obtener la persona a la cual se le va a enviar el correo electrónico.');
		}
		//Obtengo el nombre del archivo con el comprobante.
		$this->archivo = toba::memoria()->get_dato('path_comp');
		if (!isset($this->archivo)) {
			throw new guarani_error('No se puede obtener el comprobante a enviar.');
		}

		//Obtengo el asunto del mail a enviar y limpio la variable
		$asunto = toba::memoria()->get_dato('asunto');
		toba::memoria()->set_dato('asunto', null);
		$this->enviar_mail($asunto);

//		toba::memoria()->set_dato('path_comp', null);
	}

	function enviar_mail($asunto)
	{
		$this->s__datos_mail = null;

		$mail = new guarani_mail($this->s__persona, null, $asunto);
		$mail->set_html(true);

		$mail->agregar_adjunto("Comprobante.pdf", $this->archivo, 'base64', 'pdf');

		try {
			$this->s__datos_mail = $mail->get_datos_mail();
			$mail->ejecutar();
			$this->envio_ok = true;
		} catch (toba_error $e) {
			$this->envio_ok = false;
		}
	}

	function get_datos_mail()
	{
		return $this->s__datos_mail;
	}

	function envio_ok()
	{
		return $this->envio_ok;
	}
	
	//--------------------------------------------------------------------------
	//---- Extensión Javascript ------------------------------------------------
	//--------------------------------------------------------------------------

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