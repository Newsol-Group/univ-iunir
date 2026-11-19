<?php
/**
 *
 *
 * @package		Matriculas
 * @subpackage	Personas
 * 
 */
class form_contactos extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$tel_celular = persona::tel_celular;
		$tel_fijo = persona::tel_fijo;
		$tel_familiar = persona::tel_familiar;
		$tel_laboral = persona::tel_laboral;
		$fax = persona::fax;
		$email_institucional = persona::mail_institucional;
		$email_primario = persona::mail_primario;
		$email_secundario = persona::mail_secundario;
		$otro_contacto = persona::otro_contacto;
		$twitter = persona::twitter;
		$facebook = persona::facebook;
		$instagram = persona::instagram;
		$mensaje_mail_oblig = guarani_mensajes::get('800_dato_dep_obligatorio', array('Email', 'tipo de contacto (email)'));
		$mensaje_tel_oblig = guarani_mensajes::get('800_dato_dep_obligatorio', array('Código de Área y Número', 'tipo de contacto (telefónico)'));
		$mensaje_otro_oblig = guarani_mensajes::get('800_dato_dep_obligatorio', array('Descripción', 'tipo de contacto (otros contactos)'));

		echo "
	
		//---- Validacion general --------------------------------
		
		{$this->objeto_js}.evt__validar_datos = function()
		{
			var campos_tipo_contacto_ok = true;
			var tipo_contacto = this.ef('contacto_tipo').get_estado();
				
			if (tipo_contacto == '$email_institucional' || tipo_contacto == '$email_primario' || tipo_contacto == '$email_secundario') {
				if (! this.ef('email').tiene_estado()) {
					notificacion.agregar('$mensaje_mail_oblig');
					campos_tipo_contacto_ok = false;
				}
			} else if (tipo_contacto == '$tel_fijo' || tipo_contacto == '$tel_familiar' || tipo_contacto == '$tel_laboral' || tipo_contacto == '$fax' || tipo_contacto == '$tel_celular') {
				if ((! this.ef('telefono_codigo_area').tiene_estado()) || (! this.ef('telefono_numero').tiene_estado())) {				
					notificacion.agregar('$mensaje_tel_oblig');
					campos_tipo_contacto_ok = false;
				}
			} else {
				if (this.ef('contacto_tipo').tiene_estado() && ! this.ef('otros_contactos').tiene_estado()) {
					notificacion.agregar('$mensaje_otro_oblig');
					campos_tipo_contacto_ok = false;
				}
			}
				
			return campos_tipo_contacto_ok;
		}
		
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__contacto_tipo__procesar = function(es_inicial)
		{
			var contacto_tel = false;
			var contacto_mail = false;
			var contacto_celular = false;
			var otro = false;
				
			var tipo_contacto = this.ef('contacto_tipo').get_estado();
				
			if (tipo_contacto == '$tel_celular' || tipo_contacto == '$tel_fijo' || tipo_contacto == '$tel_familiar' ||
				tipo_contacto == '$tel_laboral' || tipo_contacto == '$fax') {
				contacto_tel = true;
				if (tipo_contacto == '$tel_celular'){
					contacto_celular = true;
				}	
			} else if (tipo_contacto == '$email_institucional' || tipo_contacto == '$email_primario' || tipo_contacto == '$email_secundario') {
				contacto_mail = true;
			} else if (tipo_contacto == '$otro_contacto' || tipo_contacto == '$twitter' || tipo_contacto == '$facebook' || tipo_contacto == '$instagram') {								// El contacto es del tipo Otros Contactos.
				otro = true;
			}

			// Los campos que no se muestran, es decir se ocultan, tienen el parámetro para resetear en true.
				
			this.ef('otros_contactos').mostrar(otro,!otro);
			this.ef('email').mostrar(contacto_mail,!contacto_mail);
			this.ef('telefono_codigo_area').mostrar(contacto_tel,!contacto_tel);
			this.ef('telefono_numero').mostrar(contacto_tel,!contacto_tel);
		} 
	
		";
	}

}
?>