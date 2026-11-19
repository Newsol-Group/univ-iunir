<?php
 return array (

	//--------------------------------------------------------------------------
	//---- PERFIL: Administración ----------------------------------------------
	//--------------------------------------------------------------------------

	'administracion' => 'Administración :: PERSONALIZADO',	
	
	//--------------------------------------------------------------------------
	//---- PERFIL: Alumno ------------------------------------------------------
	//--------------------------------------------------------------------------
	 
	'nombre_sistema' => 'SIU-Preinscripción :: PERSONALIZADO',
	 
	 //-------------------------------------------------------------------------
	 //---- Personalización de etiquetas de campos de formulario en reporte ----
	 //-------------------------------------------------------------------------
	 
	 /**
	  * Si se desea que la etiqueta de un campo de formulario se vea distinto en 
	  * el PDF respecto a la etiqueta que se muestra en pantalla, agregar aquí 
	  * entradas respetando la siguiente convención:
	  * 
	  * 'pdf_<nombre_campo_original>' => '<Nueva etiqueta>',
	  * 
	  * Ej: Si se quiere que para la etiqueta 'nro_documento' se imprima 
	  * 'Nro. documento' en lugar de 'Número de documento' como está definida 
	  * para la salida en pantalla, agregar una entrada:
	  * 
	  * 'pdf_nro_documento'	=> 'Nro. documento',    
	  * 
	  */
	 'pdf_nro_documento'			=> 'Nro. documento',    
);