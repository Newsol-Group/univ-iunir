<?php
class form_recuperar extends guarani_pers_ei_formulario
{    
    //-----------------------------------------------------------------------------------
    //---- JAVASCRIPT -------------------------------------------------------------------
    //-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
        //Tengo los tipos de documentos con sus expresiones regulares
        //Paso el array de php a javascript para poder hacer las validaciones        
        $tipo_doc = json_encode(comunes::array_a_utf8(persona::get_datos_tipo_documento()));
        
        echo "            
            var documentos = ".$tipo_doc.";
		//---- Validacion de EFs -----------------------------------
		
		{$this->objeto_js}.evt__nro_documento__validar = function()
		{
		     this.ef('tipo_documento').resetear_error();
		     this.ef('nro_documento').resetear_error();
		     //this.ef('tipo_documento').set_error('');
		     //this.ef('nro_documento').set_error('');

		     var tipo_documento = this.ef('tipo_documento').get_estado();
		     var nro_documento = this.ef('nro_documento').get_estado();
             var expresion = documentos[tipo_documento]['exp_regular'];
             var mensaje = documentos[tipo_documento]['mensaje'];             
             
		     if(nro_documento.trim() == ''){//Si el numero de documento es vacio
		        this.ef('nro_documento').set_error('".guarani_mensajes::get('800_error_es_obligatorio')."');
		        return false;
		     }

            //Si el tipo de documento es numerico
		    
            var patt = new RegExp(expresion);            
            if(patt.test(nro_documento)){//Si el documento es valido
                return true;
            }
            else{//sino es valido
                    this.ef('nro_documento').set_error(mensaje);
                    return false;
		     }
		}

		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__tipo_documento__procesar = function(es_inicial)
		{
		    //Elimino error en el campo nro_documento
		    this.ef('nro_documento').resetear_error();
		    $(this.ef('nro_documento').input()).prev('img').remove();
		    //Limpio el campo nro_documento y le doy foco
		    $(this.ef('nro_documento').input()).val('').focus(); 
		}

		";
	}
}
?>