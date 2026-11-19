<?php
/**
 * Catálogo de tipos de personas
 *
 * Atencion: esta clase se instancia con: guarani::catalogo_personas()
 *
 * @package		Matrículas
 * @subpackage	Personas
 */
class persona_nucleo
{
	// Nacionalidades
	const nac_argentino = 1;
	const nac_extranjero = 2;
	const nac_naturalizado = 3;
	const nac_por_opcion = 4;
	
	// Sexos
	const sexo_femenino = 'F';
	const sexo_masculino = 'M';
	const sexo_no_binario = 'X';
    
    // Sexos_araucano
	const sexo_femenino_araucano = '2';
	const sexo_masculino_araucano = '1';
	const sexo_no_binario_araucano = '3';
    
     // Sexos_araucano
	const sexo_femenino_descr = 'Femenino';
	const sexo_masculino_descr = 'Masculino';
	const sexo_no_binario_descr = 'No binario';

	// Identidad de género
	const identidad_genero_ninguna = 13;

	// Estados Civiles
	const soltero = 1;
	const casado = 2;
	const separado = 3;
	const divorciado = 4;
	const union_consensual = 5;
	const viudo = 6;
	
	// Tipos de documento
	const doc_dni = 0;
	const doc_dni_temporal = 1;
	const doc_cedula = 2;
	const doc_CUIL = 3;
	const doc_LE = 18;
	const doc_LC = 19;
	const doc_mercosur = 20;
	const doc_diplomatica = 21;
	const doc_ciudadania = 22;
	const doc_identidad = 23;	
	const doc_pasaporte = 90;
	
	// Máximos y mínimos de números de documento
	const NRO_DOCUMENTO_DNI_DNT_MIN = 1000000;
	const NRO_DOCUMENTO_DNI_DNT_MAX = 100000000;
	
	// Tipos de documentos de SIU-Araí
	const doc_arai_DNI = 'DNI';
	const doc_arai_LC = 'LC';
	const doc_arai_LE = 'LE';
	const doc_arai_Pasaporte = 'Pasaporte';
	const doc_arai_Cedula = 'Cédula';
	
	// Tipos de datos para los documentos
	const tipo_dato_numerico = 'E';
	const tipo_dato_alfanumerico = 'S';
	const tipo_dato_cuit = 'C';
	
	// Expresiones regulares para verificar los tipos de datos.
	// Se modificaron para que puedan usarse con javascript
	const exp_reg_numerica = '^[0-9]+$';
	const exp_reg_alfanum = '^[0-9A-Za-z]+$';
	
	// Parámetro del sistema que indica documentos obligatorios.
	const param_cuil_oblig = 'personas_cuil_obligatorio';
	
	// Tipos de residencia
	const sin_residencia = 1;
	const residencia_precaria = 2;
	const residencia_temporal = 3;
	const residencia_definitiva = 4;
	
	// Tipos de visa
	const visa_temporal = 1;
	const visa_permanente = 2;
	
	// Identificadores de paises
	const pais_argentina = 54;
	
	// Tipos de contactos
	const tel_fijo = 'T';
	const tel_familiar = 'F';
	const tel_laboral = 'L';
	const tel_celular = 'C';
	const fax = 'X';
	const mail_institucional = 'MI';
	const mail_primario = 'MP';
	const mail_secundario = 'MS';
	const otro_contacto = 'O';
	const twitter = 'TW';
	const facebook = 'FB';
	const instagram = 'IG';
	const linkedin = 'LN';
	
	// Situaciones laborales (en los últimos 30 días)
	const trabajo = 1;
	const no_trabajo = 2;
	const notrabajo_nibusco = 3;
	const desconoce_trabajo = 4;
	
	// Horas semanales de trabajo.
	const trabajo_hs_hasta_10 = 1;
	const trabajo_hs_hasta_20 = 2;
	const trabajo_hs_hasta_35 = 3;
	const trabajo_hs_35_mas = 4;
	
	// Tipos Visa / Residencia
	const visa = 'V';
	const residencia = 'R';
	
	const visa_desc = 'Visa';
	const residencia_desc = 'Residencia';
	
	// Niveles de estudio
	const nivel_sin_estudio = 1;
	const nivel_primario = 2;
	const nivel_secundario = 3;
	const nivel_superior = 4;
	const nivel_universitario = 5;
	const nivel_posgrado = 6;
	const nivel_desconoce = 7;

	// Niveles de estudio descr
	const nivel_sin_estudio_descr = 'Sin estudio';
	const nivel_primario_descr = 'Primario';
	const nivel_secundario_descr = 'Secundario';
	const nivel_superior_descr = 'Superior';
	const nivel_universitario_descr = 'Universitario';
	const nivel_posgrado_descr = 'Posgrado';
	const nivel_desconoce_descr = 'Desconoce';
	
	// Estado de nivel de estudio
	const estudio_estado_termino = 'F';
	const estudio_estado_abandono = 'A';
	const estudio_estado_continua = 'C';
	
	// Razones de abandono de estudios
	const estudio_razon_abandono_otras = 9;
	
	// Estudios alcanzados.
	const sin_estudios = 1;
	const desconoce = 13;
	const primario_incompleto = 2;
	const primario_completo = 3;
	const secundario_incompleto = 4;
	const secundario_completo = 5;
	const superior_incompleto = 8;
	const superior_completo = 9;
	const universitario_incompleto = 10;
	const universitario_completo = 11;
	const posgrado_incompleto = 14;
	const posgrado_completo = 12;
	
	// Tipos de convalidación de título extranjero
	const tit_convalidado = 'C';
	const tit_revalidado = 'R';
	const tit_no_conv_rev = 'N';
	const tit_convalidado_desc = 'Convalidado';
	const tit_revalidado_desc = 'Revalidado';
	const tit_no_conv_rev_desc = 'No Convalidado/Revalidado';
	
	// Orígen colegio/institución
	const orig_extranjero = 'E';
	const orig_nacional = 'N';
			
	// Tipos de allegado
	const allegado_padre = 'P';
	const allegado_madre = 'M';
	const allegado_otro = 'O';
	
	// Situación padre/madre.
	const padre_madre_vive = 'V';
	
	// Turnos Preferidos de Cursada.
	const turno_maniana = 1;
	const turno_tarde = 2;
	const turno_noche = 3;
	
	// Trabajos.
	// No confundir con los valores posibles de las personas allegadas
	const trabajo_hace_desconoce = 5;
	
	// Visibilidad bitácora
	const bitacora_visibilidad_todos = 'TODOS';
	const bitacora_visibilidad_gestion = 'GESTION';
	const bitacora_visibilidad_docente = 'DOCENTE';
	
	// Seguimientos bitácora
	const bitacora_seguimiento_pedagogico	= 1;
	const bitacora_seguimiento_psicologico	= 2;
	const bitacora_seguimiento_actividades	= 3;
	
	// Carácter de discapacidad
	const discapacidad_caracter_permanente = 'P';
	const discapacidad_caracter_transitoria = 'T';
	
	// Tipos de vivienda
	const tipo_vivienda_otros = '4';
	const tipo_vivienda_cd_propio = '5'; // Casa o departamento propio
	const tipo_vivienda_cd_alquilado = '6'; // Casa o departamento alquilado
	const tipo_vivienda_residencia = '7';
	const tipo_vivienda_pension = '8';

	// Condiciónes de Discapacidad
	const SIN_DISCAPACIDAD_DESCR = 'No presento ninguna condición que implique Discapacidad';
	const CON_DISCAPACIDAD_DESCR = 'Declaro condición de discapacidad';
	
	const SIN_DISCAPACIDAD_DESCR_GESTION = 'No presenta ninguna condición que implique Discapacidad';
	const CON_DISCAPACIDAD_DESCR_GESTION = 'Declara condición de discapacidad';
	
	// Dificultades Discapacidad
	const NO_HAY_DIFICULTAD_VAL = 1;
	
	// Perfiles de 3W
	const perfil_alumno = 'Alumno';
	const perfil_docente = 'Docente';
	const perfil_gerencial = 'Gerencial';
	const perfil_anonimo = 'anonimo';
	const perfil_cursos = 'Cursos';
	const perfil_administrador_3w = 'Administrador3w';
	const perfil_gestion = 'Gestion';   
    
	const validado_con_renaper = 'Validado por Renaper';
	const descr_renaper = 'Aquí se indica si los datos del aspirante fueron importados automáticamente desde el Renaper. Estos datos son: Apellido, Nombre, Fecha de Nacimiento, Nacionalidad y Número de Documento';
	const no_validado_con_renaper = 'N';
	
	const arai_password_algorithm_crypt = 'crypt';
	const arai_password_algorithm_plano = 'plano';
        //parametro nombre_persona_doc_oficiales
	
	const nombre_persona_doc_oficiales_n= 'N';//Las personas se visualizan con su nombre oficial
	const nombre_persona_doc_oficiales_o = 'O';//Las personas se visualizan con las iniciales del nombre,el apellido completo, día y año de nacimiento, número de documento y nombre elegido
	const nombre_persona_doc_oficiales_s = 'S'; //Las personas se visualizan con su nombre elegido

	static protected $instancia;

	static function instancia()
	{
		if (!isset(self::$instancia)) {
			self::$instancia = new persona();
		}
		return self::$instancia;
	}

	static function es_contacto_telefonico($tipo_contacto)
	{
		if ($tipo_contacto == self::tel_fijo || $tipo_contacto == self::tel_familiar || $tipo_contacto == self::tel_laboral ||
				$tipo_contacto == self::tel_celular || $tipo_contacto == self::fax) {
			return true;
		} else {
			return false;
		}
	}

	static function es_contacto_telefono_cel($tipo_contacto)
	{
		return $tipo_contacto == self::tel_celular;
	}

	static function es_contacto_mail($tipo_contacto)
	{
		if ($tipo_contacto == self::mail_institucional || $tipo_contacto == self::mail_primario || $tipo_contacto == self::mail_secundario) {
			return true;
		} else {
			return false;
		}
	}

	static function es_prefijo_valido_cuit($prefijo)
	{
		return in_array($prefijo, array(20, 23, 25, 27, 90), true);
	}

	static function generar_token()
	{
		return sha1(uniqid(rand(0, 100)));
	}

	static function obfuscate_mail($mail)
	{
		$aux = substr_replace($mail, '***', 2, strpos($mail, '@') - 2);
		$pos_punto = strpos($aux, '.', strpos($aux, '@'));
		$aux = substr_replace($aux, '***', $pos_punto);
		return $aux;
	}

	static function get_datos_tipo_documento()
	{
		$tipo_documentos = guarani::co('co_personas')->get_datos_tipo_documento();
		$tipos = array();
		foreach ($tipo_documentos as $tipo_doc) {
			$indice = $tipo_doc['tipo_documento'];
			unset($tipo_doc['tipo_documento']);
			$tipos[$indice] = $tipo_doc;
		}
		return $tipos;
	}
	static function get_tipos_documentos_x_pais($pais = null)
	{
		$tipo_documentos = guarani::co('co_personas')->get_datos_tipos_documentos_x_pais($pais);
		$tipos = array();
		foreach ($tipo_documentos as $tipo_doc) {
			$indice = $tipo_doc['valor']."_".$tipo_doc['pais'];
			unset($tipo_doc['tipo_documento'],$tipo_doc['pais']);
			$tipos[$indice] = $tipo_doc;
		}
		return $tipos;
	}

	static function formatearCUIT($cuit){
		
		$prefijo	= substr($cuit, 0, 2);
		$dni		= substr($cuit, 2, -1);
		$sufijo		= substr($cuit, -1);
		
		return "$prefijo-$dni-$sufijo";
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

		for( $i = 0; $i < guarani_arreglos::count( $digitos ); $i++ ){
			$acumulado += $digitos[ 9 - $i ] * ( 2 + ( $i % 6 ) );
		}
		$verif = 11 - ( $acumulado % 11 );
		$verif = $verif == 11? 0 : $verif;

		return $digito == $verif;
	}

        /**
         * Devuelve usuario que esta ejecutando el comando de consola.
         *
         * @see https://stackoverflow.com/questions/7771586/how-to-check-what-user-php-is-running-as How to check what user php is running as?
         * 
         * @return string Usuario que esta ejecutando el comando de consola.
         */
	static function get_usuario_consola()
	{

            $usuario_consola = comunes::usuario_consola;

            $output = [];
            $result_code = 0;
            $usuario = exec('whoami', $output, $result_code);
            
            // Si el comando se ejecuta bien
            if ($result_code == 0) {
                $usuario_consola = "$usuario ($usuario_consola)";
            }

            return $usuario_consola;
	}
        
        
        /**
         * Si la persona tiene documentos pendientes de autorización en SIU-Araí.
         * @param string $usuario_arai Usuario de SIU-Araí.
         * @param bool $solo_guarani Si solo trae los documentos de SIU-Guaraní, o todos los pendiente (por defecto trae todos).
         * @return bool TRUE si tiene documentos pendientes autorización, FALSE en caso contrario.
         * @throws \Exception
         */
        public static function tiene_documentos_pendientes_autorizacion($usuario_arai, $solo_guarani = false)
        {
            $tiene_documentos_pendientes = false;
            $arai_documentos_class = (\comunes::es_interfaz_autogestion())? \SIU\Chulupi\kernel::localizador()->instanciar('modelo_g3\arai_documentos') : 'arai_documentos';
            $autorizaciones_pendientes = $arai_documentos_class::getAutorizacionesPendientes($usuario_arai);
            if (isset($autorizaciones_pendientes['listado']) && (!empty($autorizaciones_pendientes['listado']))) {
                
                if ($solo_guarani) {
                    
                    foreach ($autorizaciones_pendientes['listado'] as $solicitud) {
                        if (isset($solicitud['referencia_interna']['sistema']) && $solicitud['referencia_interna']['sistema'] == arai_documentos::ID_INSTALACION) {
                            $tiene_documentos_pendientes = true;
                            break;
                        }
                    }
                    
                } else {
                    $tiene_documentos_pendientes = true;
                }
                
            }
            return $tiene_documentos_pendientes;
        }
		
		/**
         * Devuelve SQL para el ORDER BY por tipo de email.
         * @return string SQL para el ORDER BY por tipo de email.
         */
		public static function orden_tipo_email()
		{
			return "CASE mdp_personas_contactos.contacto_tipo 
						WHEN '".static::mail_primario."' THEN 1
						WHEN '".static::mail_secundario."' THEN 2
						WHEN '".static::mail_institucional."' THEN 3
						ELSE 4
					END";
		}
		
		/**
         * Devuelve SQL para el ORDER BY por tipo de teléfono.
         * @return string SQL para el ORDER BY por tipo de teléfono.
         */
		public static function orden_tipo_telefono()
		{
			return "CASE mdp_personas_contactos.contacto_tipo
						WHEN '".static::tel_celular."' THEN 1
						WHEN '".static::tel_familiar."' THEN 2
						WHEN '".static::tel_fijo."' THEN 3
						WHEN '".static::tel_laboral."' THEN 4
						ELSE 5
					END";
		}
		
		/**
		 * Devuelve el tipo de documento de SIU-Araí.
		 * @see https://gitlab.siu.edu.ar/siu-arai/arai-json-migrator/-/blob/master/schema/arai-migrator-schema.json#L78
		 * @param int $tipo_documento_guarani Tipo de documento de SIU-Guaraní.
		 * @return string tipo de documento de SIU-Araí.
		 */
		public static function get_tipo_documento_arai($tipo_documento_guarani)
		{
			
			$tipo_documento_arai = '';
			
			switch ($tipo_documento_guarani) {
				case static::doc_dni:
				case static::doc_dni_temporal:
					$tipo_documento_arai = static::doc_arai_DNI;
					break;
				case static::doc_LC:
					$tipo_documento_arai = static::doc_arai_LC;
					break;
				case static::doc_LE:
					$tipo_documento_arai = static::doc_arai_LE;
					break;
				case static::doc_pasaporte:
					$tipo_documento_arai = static::doc_arai_Pasaporte;
					break;
				default:
					$tipo_documento_arai = static::doc_arai_Cedula;
					break;
			}
			
			return utf8_encode($tipo_documento_arai);
	}
        		/**
     * Devuelve segun el parametro nombre_persona_doc_oficiales como se obtiene el nombre elegido para los documentos oficiales
     *
     * @return string 
     /**/
    public static function get_param_nombre_persona_doc_oficiales() {
        $param_nombre_persona_doc_oficiales = guarani::parametros()->get_valor('nombre_persona_doc_oficiales',null,false);
        
        $apellido_y_nombre = "CONCAT(mdp_personas.apellido, ', ', mdp_personas.nombres)";
        if ($param_nombre_persona_doc_oficiales == persona::nombre_persona_doc_oficiales_n) {
            //Las personas se visualizan con su nombre oficial
            $apellido_y_nombre = "CONCAT(mdp_personas.apellido, ', ', mdp_personas.nombres)";
        } elseif ($param_nombre_persona_doc_oficiales == persona::nombre_persona_doc_oficiales_s) {
            //Las personas se visualizan con su nombre elegido
            $apellido_y_nombre = 'vw_personas.apellido_nombres_elegido';
        } else {
            //Las personas se visualizan con las iniciales del nombre,el apellido completo, día y año de nacimiento, número de documento y nombre elegido
            $apellido_y_nombre = "
                       CASE
                            WHEN vw_personas.nombres_elegido != mdp_personas.nombres THEN 
                               vw_personas.apellido_nombres_ley
                            ELSE
                               vw_personas.apellido_nombres
                        END ";
        }
        return $apellido_y_nombre;
    }
}