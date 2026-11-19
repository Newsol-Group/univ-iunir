<?php
/**
 *
 *
 * @package		Matriculas
 * @subpackage	Personas
 * 
 */
class form_fotos extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		$this->generar_html_ef('imagen', '1px');
		$this->generar_html_ef('imagen_vista_previa');
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$mensaje_extension_invalida = guarani_mensajes::get('800_tipo_archivo_invalido');
		$mensaje_tamanio_invalido = guarani_mensajes::get('800_tamanio_archivo_invalido');
		$mensaje_nombre_invalido = guarani_mensajes::get('800_dato_invalido', array('nombre de archivo', 'no respeta el formato: nombre_archivo.extension'));

		echo "
		
		//---- Validacion de EFs -----------------------------------
		
		//Se chequea que la imagen seleccionada por el usuario sea de uno de los tipos válidos.
		
		{$this->objeto_js}.evt__imagen__validar = function()
		{
			//Se obtiene el string correspondiente al nombre del archivo para validar su extensión.
			
			var ok = true;
			var estado;
			var input = this.ef('imagen').input();
			if (input !== null) {
				estado = input.value;
				//8M son 8388608 bytes
				if((typeof input.files[0] !== 'undefined') && (input.files[0].size > 8388608)){
					//El tamanio del archivo es inválido (demasiado grande).
					this.ef('imagen').set_error('$mensaje_tamanio_invalido');
					ok = false;	
				}
			}
			if (estado !== '') {
				var imagen = estado.toString();
				var index = imagen.indexOf('.');

				if (index != -1 && index < imagen.length) {	//Si esta el separador . en el string de imagen y el string continua despues del mismo
					var extension = imagen.substring(index + 1, imagen.length).toLowerCase();
					if (extension != 'jpeg' && extension != 'jpg' && extension != 'png' && extension != 'gif') {
						this.ef('imagen').set_error('$mensaje_extension_invalida');
					ok = false;
					}
				} else {										
					//El nombre de archivo es inválido (se supone que no va a pasar porque no podría haberse creado el archivo).
					this.ef('imagen').set_error('$mensaje_nombre_invalido');
					ok = false;	
				}
			}
			return ok;
		}
			
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__imagen__procesar = function(es_inicial)
		{
			if (!es_inicial) {
				this.submit();
			}
		}
		";
	}

}
?>