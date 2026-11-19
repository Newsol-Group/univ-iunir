<?php

namespace siu;

use SIU\Chulupi\kernel;
use siu\extension_kernel\servicios_externos_preinscripcion;
use siu\modelo\util;

class preinscripcion
{
	/*const perfil_alumno = 'alumno';
	
	const perfil_gerencial = 'gerencial';
	*/// PERFILES
	const perfil_alumno = 'Alumno';
	const perfil_docente = 'Docente';
	const perfil_gerencial = 'Gerencial';
	const perfil_anonimo = 'anonimo';
	const perfil_cursos = 'Cursos';
	const perfil_administrador_3w = 'Administrador3w';
	const perfil_gestion = 'Gestion';
	const perfil_preinscripcion = 'Preinscripcion';
	const perfil_admin = 'admin';

	const control_error = 'E';
	const control_advertencia = 'A';
	const control_info = 'M';
	const mensaje_tipo = 'mtipo';
	const mensaje_cuerpo = 'mcuerpo';
	const interfaz = 3; //WEB
	const si = 'S';
	const no = 'N';
	const charset = 'iso-8859-1';
	
	//---- Tipos de datos y expresiones regulares para tipo de documento -------
	const tipo_dato_numerico = 'E';
	const tipo_dato_alfanumerico = 'S';
	const exp_reg_numerica = '/^[[:digit:]]+$/';
	const exp_reg_alfanum = '/^[[:alnum:]]+$/';
	
	//---- Estados de una cuenta de Preinscripción -----------------------------
	const estado_pendiente = 'P';
	const estado_activado = 'A';
	const estado_preparado = 'C';
	const estado_inscripto = 'I';
	
	//---- Estados de una preinscripción a propuesta ---------------------------
	const estado_preinsc_pendiente = 'P';
	const estado_preinsc_inscripto = 'I';
	const estado_preinsc_copiada_g3 = 'C';
	const estado_solicitar_informacion = 'S';

	//---- Modos de generación de comprobante ----------------------------------
	const modo_comp_RA = 'ra';
	const modo_comp_PROPUESTA = 'propuesta';
	const modo_comp_UNICO = 'unico';
	
	//---- Estado de estudio ---------------------------------------------------
	const estado_estudio_FINALIZADO = '1';
	const estado_estudio_ABANDONADO = '2';
	const razon_abandono_OTRO = '9';
	
	//---- Pueblos originarios -------------------------------------------------
	const pueblo_originario_OTRO = '27';
	
	static protected $servicios_externos;

	//--------------------------------------------------------------------------
	//---- Modelos de usuario y administrador ----------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Instancia el modelo de usuarios de aspirantes
	 * 
	 * @return modelo\usuario
	 */
	static function usuario()
	{
		return kernel::localizador()->instanciar_singleton("modelo\\usuario");
	}

	/**
	 * Instancia el modelo de usuarios administradores
	 * 
	 * @return modelo\admin
	 */
	static function admin()
	{
		return kernel::localizador()->instanciar_singleton("modelo\\admin");
	}
	
	//--------------------------------------------------------------------------
	//---- Paths comunes -------------------------------------------------------
	//--------------------------------------------------------------------------

	static function dir()
	{
		return \realpath(\dirname(__FILE__));
	}

	static function dir_fonts()
	{
		return bootstrap::get_dir_raiz() . '/vendor/rebuy/ezpdf/src/ezpdf/fonts/';
	}

	static function dir_temp()
	{
		return kernel::proyecto()->get_dir_temp();
	}

	//--------------------------------------------------------------------------

	static function servicios_externos()
	{
		if (!isset(self::$servicios_externos)) {
			self::$servicios_externos = new servicios_externos_preinscripcion();
		}
		return self::$servicios_externos;
	}

	//--------------------------------------------------------------------------

	static function mail_configurado()
	{
		$smtp = kernel::proyecto()->get_parametros_smtp();
		if ((empty($smtp)) || (empty($smtp['from'])) || ($smtp['from'] == 'preinscripcion@gmail.com')) {
			// No hay configuración o está la configuración de ejemplo.
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Determina si la versión de la base se corresponde con la versión del sistema.
	 * 
	 * @return boolean
	 */
	static function versiones_compatibles()
	{
            try {
                // Versión del archivo version.ini
                $version_sistema = kernel::proyecto()->get_version_sistema();
                // Versión del ultimo registro de la tabla app_versiones_base columna version_base
                $version_base = util::consultas()->get_version_base();
                // https://www.php.net/manual/es/function.version-compare.php
                return version_compare($version_sistema, $version_base, '==');
            } catch (errores\error_preinscripcion_cnx_bd $e) {
                throw $e;
            }
	}

}