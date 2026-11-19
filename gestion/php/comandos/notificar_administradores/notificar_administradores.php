<?php

try {

	$existen_actas_fallaron_arai = toba::consulta_php('co_actas')->existen_actas_fallaron_arai();
	$existen_equivalencias_fallaron_arai = toba::consulta_php('co_equivalencias')->existen_equivalencias_fallaron_arai();

	// Si hay para notificar
	if ($existen_actas_fallaron_arai || $existen_equivalencias_fallaron_arai) {

		$administradores = toba::consulta_php('co_personas')->get_personas_admin();

		$asunto = guarani_mensajes::get('800_notificar_admin_email_asunto');
		$cuerpo = guarani_mensajes::get('800_notificar_admin_email_cuerpo');

		foreach ($administradores as $administrador) {

			$mail = new guarani_mail($administrador['persona'], null, $asunto, $cuerpo);
			$mail->set_html(true);
			$mail->enviar();

		}

		echo guarani_mensajes::get('800_notificar_admin_exito');
		exit(0);

	} else {

		echo guarani_mensajes::get('800_notificar_admin_vacio');
		exit(2);

	}

} catch (Exception $e) {

	echo guarani_mensajes::get('800_notificar_admin_error', [$e->getMessage()]);
	exit(1);

}