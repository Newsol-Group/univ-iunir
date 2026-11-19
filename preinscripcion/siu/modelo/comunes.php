<?php

namespace siu\modelo;

use SIU\Chulupi\kernel;

class comunes {

	// Tipos
	const si				= 'S';
	const si_desc			= 'Sí';
	const no				= 'N';
	const no_desc			= 'No';
	const opcional			= 'O';
	const opcional_desc		= 'Opcional';
	const todos				= 'T';
	const todas				= 'T';
	const todas_desc		= 'Todas';
	const algunas			= 'A';
	const algunas_desc		= 'Algunas';
	const estado_activo		= 'A';
    const estado_aceptado	= 'A';
	const estado_pendiente	= 'P';
	const estado_inactivo	= 'B';
	const estado_baja 		= 'B';
	const estado_deshabilitado = 'D';
	
	const estado_abierto	= 'A';
	const estado_cerrado	= 'C';
	const estado_obtenido	= 'O';

	const estado_activo_desc = 'Activo';
	const estado_baja_desc = 'Baja';
	const estado_pendiente_desc = 'Pendiente';
	const estado_inactivo_desc = 'No Activo';
	const estado_deshabilitado_desc = 'Deshabilitado';
	const estado_aceptado_desc = 'Aceptado';
	
	const formato_fecha_visual						= 'DD/MM/YYYY';
	const formato_fecha_visual_sin_anio				= 'DD/MM';
	const formato_fecha_hora_visual					= 'DD/MM/YYYY HH24:MI:SS';
	const formato_fecha_hora_visual_sin_segundos	= 'DD/MM/YYYY HH24:MI';
	const formato_hora_visual						= 'HH24:MI';
	const formato_fecha_bd							= 'YYYY-MM-DD';	
	const formato_fecha_orden						= 'YYYYMMDD';	
	const formato_fecha_hora_visual_milisegundos	= 'DD/MM/YYYY HH24:MI:SS.MS';
	const formato_anio_4_digitos_visual				= 'YYYY';

        const formato_fecha_visual_php = 'd/m/Y';
        const formato_fecha_php = 'Y-m-d';
	
	const operador_not_defecto = 0;
	
	const chars_maximo_combo = 50;

	const error_critico = 'error';
	const error_advertencia = 'warning';
	const error_informacion = 'info';

	const mensaje_critico = 'error';
	const mensaje_informacion = 'info';
	
	const resultado_tipo_competencias = 'C';

	//Resultados de notas de las escalas de notas
	const resultado_nota_aprobado = 'A';
	const resultado_nota_ausente = 'U';
	const resultado_nota_desaprobado = 'R';
        
        //resultados de inscripciones
        const resultado_inscripcion_aceptada = 'A';
        const resultado_inscripcion_rechazada = 'R';
        const resultado_inscripcion_pendiente = 'P';
	
	// Días de la semana
	const lunes = 'Lunes';
	const martes = 'Martes';
	const miercoles = 'Miercoles';
	const jueves = 'Jueves';
	const viernes = 'Viernes';
	const sabado = 'Sabado';
	const domingo = 'Domingo';
	
	// Interfaces
	const interfaz_todas		= 0;
	const interfaz_gestion		= 1;
	const interfaz_autogestion	= 2;
	
	const interfaz_consola		= 'cli';

	const navegador_recomendado = 'Firefox';

	const dimension_ug = 13;

	const pueblo_originario_otros = 27;

	const mayor = 'MAYOR';
	const mayor_descr = 'Mayor a';
	const menor = 'MENOR';
	const menor_descr = 'Menor a';
	const entre = 'ENTRE';
	const entre_descr = 'Entre';
	
	// Discapacidad - Dificultades
	const NO_HAY_DIFICULTAD_VAL = 1;
	
	// Identidad de Género y Diversidad - Ninguna de las anteriores. Especifique
	const GENERO_DIVERSIDAD_ESPECIFIQUE = 13;

	// Expresiones regulares para validación de EFs.
	const er_telefono_codigo_area = '/^\d+$/';
	const er_numero_entero = '/^[0-9]*$/';
	const er_numero_dos_decimales = '/^[0-9]+[.]?[0-9]{0,2}$/';
	const er_nota = '/^((\d){1,5})|((\d){1,5}[,\.]{1}\d{1,3})$/';
	const er_host = '/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i';
	const er_token = '/^[a-f0-9]{32}$/';
	const er_email = '/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,20}$/i';
	const er_letra = '/[A-Za-z]/';
	const er_hora = '/^([0-1][0-9]|[2][0-3]):([0-5][0-9])$/';
	
	// Tipos de documentos
	const TIPO_DOCUMENTO_DNI = 0;
	const TIPO_DOCUMENTO_DNT = 1;
	const TIPO_DOCUMENTO_CUIL_CUIT = 3;
	const TIPO_DOCUMENTO_CEDULA_DIPLOMATICA = 21;
	const TIPO_DOCUMENTO_PASAPORTE = 90;
	
	// Máximos y mínimos de números de documento
	const NRO_DOCUMENTO_DNI_DNT_MIN = 1000000;
	const NRO_DOCUMENTO_DNI_DNT_MAX = 100000000;
    
		
	const NACIONALIDAD_ARGENTINO = 1;
	const NACIONALIDAD_EXTRANJERO = 2;
	const NACIONALIDAD_NATURALIZADO = 3;
	const NACIONALIDAD_POR_OPCION = 4;
	
	static function calcular_edad($fecha, $formato_fecha = 'd/m/Y')
	{
		// https://stackoverflow.com/questions/3776682/php-calculate-age
		$edad = \DateTime::createFromFormat($formato_fecha, $fecha)->diff(new \DateTime('now'))->y;
		kernel::log()->add_debug("=== edad ===", $edad);
		return $edad;
	}
	
	/**
	 * Adaptación del count de PHP para que en la versión 7.2 no se muestre el 
	 * warning: "Parameter must be an array or an object that implements Countable".
	 * @param mixed $dato
	 * @return int 0 o el tamaño de un arreglo u objeto contable
	 */
	static function count($dato) {
		return (is_array($dato) || $dato instanceof Countable) ? count($dato) : 0;
	}
	
	// https://es.wikipedia.org/wiki/Clave_%C3%9Anica_de_Identificaci%C3%B3n_Tributaria
	static function validarCUIT( $cuit ){
		$cuit = preg_replace( '/[^\d]/', '', (string) $cuit );
		if( strlen( $cuit ) != 11 ){
			return false;
		}
		$acumulado = 0;
		$digitos = str_split( $cuit );
		$digito = array_pop( $digitos );

		for( $i = 0; $i < static::count( $digitos ); $i++ ){
			$acumulado += $digitos[ 9 - $i ] * ( 2 + ( $i % 6 ) );
		}
		$verif = 11 - ( $acumulado % 11 );
		$verif = $verif == 11? 0 : $verif;

		return $digito == $verif;
	}

}