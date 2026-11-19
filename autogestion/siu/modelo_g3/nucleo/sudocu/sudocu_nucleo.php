<?php

use SIU\DocumentosCli\Entities\Autorizacion;
use Ramsey\Uuid\Uuid;

/**
 * Clase para consumir Trámites/Expedientes de SUDOCU
 * @package SUDOCU
 */
class sudocu_nucleo
{
	
	const EGR_USA_EXPEDIENTE_ELECTRONICO = 'egr_usa_expediente_electronico';
	const PLACEHOLDER_EXPEDIENTE_VACIO = '-';
    
	protected static $tramites = [];
	protected static $tramitesComboEditable = [];
	
	/**
	 * Devuelve los Trámites/Expedientes de SUDOCU
	 * @param string|null $idUsuarioSso     Usuario Araí (Opcional). Por defecto toma el usuario de Araí del usuario logueado.
	 * @param string|null $idArea           Área SUDOCU (Opcional).
	 * @return array Trámites/Expedientes de SUDOCU.
	 */
	public static function getTramites($idUsuarioSso = null, $idArea = null)
	{
		
		try{
			
			$arai_documentos_class = (\comunes::es_interfaz_autogestion())? \SIU\Chulupi\kernel::localizador()->instanciar('modelo_g3\arai_documentos') : 'arai_documentos';
                        $params['metodo'] = __METHOD__;
			$idUsuarioSso = $idUsuarioSso ?? $arai_documentos_class::get_cuenta_usuario_origen_arai_documentos($params);
			
			// Si no se cargaron tramites previamente para el usuario en cuestion
			if (!isset(static::$tramites[$idUsuarioSso])) {
				static::$tramites[$idUsuarioSso] = $arai_documentos_class::getTramites($idUsuarioSso, $idArea);
			}
			
		} catch (Exception $e) {
			toba::logger()->error($e);
			return [];
		}
		
		toba::logger()->debug("=== Tramites de SUDOCU para '{$idUsuarioSso}' ===");
		toba::logger()->debug(print_r(static::$tramites[$idUsuarioSso], true));
		return static::$tramites[$idUsuarioSso];
	}
	
	/**
	 * Devuelve los Trámites/Expedientes de SUDOCU para Combo Editable
	 * @param string|null $idUsuarioSso     Usuario Araí (Opcional). Por defecto toma el usuario de Araí del usuario logueado.
	 * @return array Trámites/Expedientes de SUDOCU para Combo Editable.
	 */
	public static function getTramitesComboEditable($idUsuarioSso = null)
	{
		try{
			
			$arai_documentos_class = (\comunes::es_interfaz_autogestion())? \SIU\Chulupi\kernel::localizador()->instanciar('modelo_g3\arai_documentos') : 'arai_documentos';
                        $params['metodo'] = __METHOD__;
			$idUsuarioSso = $idUsuarioSso ?? $arai_documentos_class::get_cuenta_usuario_origen_arai_documentos($params);
			
			// Si no se cargaron tramites previamente para el usuario en cuestion
			if (!isset(static::$tramitesComboEditable[$idUsuarioSso])) {

				static::$tramitesComboEditable[$idUsuarioSso] = [];

				$tramites = static::getTramites($idUsuarioSso);

				foreach ($tramites as $tramite) {
					static::$tramitesComboEditable[$idUsuarioSso][$tramite['id_tramite']]['valor'] = $tramite['id_tramite'];
					static::$tramitesComboEditable[$idUsuarioSso][$tramite['id_tramite']]['descr'] = static::getDescripcionFormateadaTramite($tramite);
				}

			}
		
		} catch (Exception $e) {
			toba::logger()->error($e);
			return [];
		}
		
		toba::logger()->debug("=== Tramites de SUDOCU Combo Editable para '{$idUsuarioSso}' ===");
		toba::logger()->debug(print_r(static::$tramitesComboEditable[$idUsuarioSso], true));
		return static::$tramitesComboEditable[$idUsuarioSso];
		
	}
    
    /**
     * @param array $tramite    Trámite de SUDOCU.
     * Consta del siguiente formato:
     * <code>
     *       [
     *           [id_tramite] => "b978baf6-fd57-4b25-9b16-7a63f087d046"           
     *           [titulo] => "Expediente X"                
     *           [identificador_visible] => "5 / 2022"                
     *           [data] => [ 
     *              [nro] => 5           
     *              [tipo] => "EXP"                
     *              [anio] => 2022             
     *              [organismo] => "UUNN"
     *           ]
     *       ]
     * </code>
     * @return string   Retorna descripción del trámite de SUDOCU formateada para el usuario.
     */
    public static function getDescripcionFormateadaTramite($tramite)
    {
        $numeroTramite = trim($tramite['identificador_visible']);
        $tituloTramite = trim($tramite['titulo']);
        return utf8_decode("{$numeroTramite} - {$tituloTramite}");
    }
    
    /**
     * Si el ID de Trámite de SUDOCU es un UUID válido o no.
     * @param string $idTramite    ID de Trámite de SUDOCU.
     * @return bool   Si el ID de Trámite de SUDOCU es un UUID válido o no.
     */
    public static function idTramiteValido($idTramite)
    {
        return Uuid::isValid($idTramite);
    }
    
    /**
     * Obtiene la descripción del Trámite de Certificación.
     * @param array $tramite    Datos del Trámite de Certificación de Guaraní.
     * Consta del siguiente formato:
     * <code>
     *       [
     *           [nro_expediente] => "8d6dfdfe-cd3c-40be-9429-01a91736314e"           
     *           [propuesta] => 14                
     *           [nro_expediente_descr] => "2 / 2022 - Expediente X"                
     *           [nro_solicitud] => 7                
     *       ]
     * </code>
     * @return string   Descripción del Trámite.
     */
    public static function getDescripcionTramiteCertificacion($tramite)
    {
        
        // Si el Expediente es null le pongo '-' y salgo
        if (is_null($tramite['nro_expediente'])) {
            return static::PLACEHOLDER_EXPEDIENTE_VACIO;
        }

        $descripcionTramite = $tramite['nro_expediente'];

        // Obtengo el parámetro "egr_usa_expediente_electronico" para el Trámite de Certificación
        $ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($tramite['propuesta']);
        $egr_usa_expediente_electronico = guarani::parametros()->get_valor(static::EGR_USA_EXPEDIENTE_ELECTRONICO, $ras);

        // Si el Trámite de Certificación usa EEI
        if ($egr_usa_expediente_electronico == comunes::si) {

            // Si el Expediente es un UUID válido lo busco en SUDOCU
            if (static::idTramiteValido($tramite['nro_expediente'])) {
                // Busco la descripción del trámite en SUDOCU

                $descrTramiteSUDOCU = static::getDescripcionTramiteComboEditable($tramite['nro_expediente']);

                // Si no se pudo obtener la descripción del trámite en SUDOCU
                if (is_null($descrTramiteSUDOCU)) {
                    // Muestro la descripción del trámite en SUDOCU desde la DB (sga_certificados_otorg.nro_expediente_descr)
                    $descripcionTramite = $tramite['nro_expediente_descr'];
                } else {// Si se pudo obtener la descripción del trámite en SUDOCU
                    // Muestro la descripción del trámite en SUDOCU desde la API
                    $descripcionTramite = $descrTramiteSUDOCU;

                    // La descripción del trámite en SUDOCU puede cambiar al editarse
                    // Si la descripción del trámite de SUDOCU de la API y la DB son diferentes actualizo en la DB
                    if ($tramite['nro_expediente_descr'] != $descrTramiteSUDOCU) {
                        guarani::act('act_tramites_certificacion')->actualizar_nro_expediente_descr_tramite_certificacion($tramite['nro_solicitud'], $descrTramiteSUDOCU);
                    }
                }
            }
        }

        return $descripcionTramite;
    }

    /**
     * Recupera listado de trámites de SUDOCU para los combos editables.
     * @param string $filtro    Filtro del combo editable.
     * @return array   listado de trámites de SUDOCU.
     */
    static function getOpcionesTramitesComboEditable($filtro = '')
    {

        toba::logger()->debug('soducu::getOpcionesTramitesComboEditable $filtro');
        toba::logger()->debug($filtro);

        // Obtengo todos los trámites de SUDOCU
        $tramites = static::getTramitesComboEditable();

        // Si viene el filtro y es distinto a '%' (cuando inserta un *)
        if ($filtro != '' && $filtro != '%') {

            // Le limpio los acentos al filtro
            $filtro = guarani_texto::limpiar_acentos($filtro);
            
            // Filtro los trámites
            foreach ($tramites as $key => $tramite) {
                // https://www.php.net/manual/es/function.stripos.php
                if (stripos(guarani_texto::limpiar_acentos($tramite['descr']), $filtro) === false) {
                    unset($tramites[$key]);
                }
            }
        }
        
        return $tramites;
    }

    /**
     * Recupera descripción de un trámite de SUDOCU.
     * @param string $idTramite    ID de trámite de SUDOCU.
     * @return string   Descripción de un trámite de SUDOCU.
     */
    static function getDescripcionTramiteComboEditable($idTramite = null)
    {
        toba::logger()->debug('soducu::getDescripcionTramiteComboEditable $idTramite');
        toba::logger()->debug($idTramite);
        
        // Si $idTramite es null
        if (is_null($idTramite)) {
            return null;
        }

        // Obtengo todos los trámites de SUDOCU
        $tramites = static::getTramitesComboEditable();
        // Recupero la descripción del trámite seleccionado o null si no se encontró ninguno
        return $tramites[$idTramite]['descr'] ?? null;
    }

    /**
	 * Envía un expediente de egreso a Araí-Documentos.
	 * @see https://redmine.siu.edu.ar/redmine/issues/34801 Ticket #34801
	 * @param string $documento_titulo Título del documento.
	 * @param string $documento_descripcion Descripción del documento.
	 * @param string $modelo_autorizacion Modelo de autorización [simple|cadena].
	 * @param string[] $autoridades_firmantes Array de autoridades firmantes.
	 * @param array $parametros Parámetros necesarios, consta del siguiente formato:
	 * <code>
	  // id_tramite de SUDOCU, si no se pasa no se asocia el documento a SUDOCU (EEI)
	  $parametros['id_tramite'] = '901e7f46-4e07-42ae-addc-8f75b383aee9';
	  $parametros['nro_solicitud'] = '30923';
	  $parametros['propuesta'] = 2;
	 * </code>
	 * @param string $archivo Path del archivo PDF del expediente de egreso.
	 * @param bool $borrar_archivo Si el archivo debe ser borrado una vez que se lo envió a SIU-Araí Documentos o no.
	 * @throws \Exception Si falla algo en el proceso
	 * @return string|null UID del documento creado en Araí, null en caso de error.
	 */
	public static function enviar_expediente_egreso_arai_documentos($documento_titulo, $documento_descripcion, $modelo_autorizacion, $autoridades_firmantes, $parametros, $archivo, $borrar_archivo = true)
	{

		try {

			$data = [];
			$data['autorizacion']['modelo'] = $modelo_autorizacion;
            
			$data['documento']['tipo'] = arai_documentos::getTipoDocumento(arai_documentos::TIPO_DOCUMENTO_CERTIFICADO);

			// Si no existe el archivo
			if (!file_exists($archivo)) {
				$mensaje = guarani_mensajes::get('800_pdf_inexistente', [$archivo]);
				throw new \Exception($mensaje);
			} else {
				// Si existe el archivo
				$data['archivo'] = $archivo;
			}

			$data['documento']['titulo'] = $documento_titulo;
			$data['documento']['descripcion'] = $documento_descripcion;
			// Ver Ticket #42326 (https://redmine.siu.edu.ar/redmine/issues/42326)
			$data['documento']['id_tramite'] = ($parametros['id_tramite'] ?? null);
			/*
             * @see https://documentacion.siu.edu.ar/documentos/docs/repo-docs/#request 
             * Este campo se utiliza para almacenar en formato json, los datos que requiera la aplicación que genera el documento.
             */
			$data['documento']['referencia_interna'] = [
				'nro_solicitud' => $parametros['nro_solicitud'],
				'sistema' => arai_documentos::ID_INSTALACION
			];
			$data['documento']['identificador'] = $parametros['nro_solicitud'];
            /*
             * @see https://documentacion.siu.edu.ar/documentos/docs/repo-docs/#request 
             * Atributos adicionales del documento.
             */
			$data['documento']['atributos'] = [];

			$secuencia = 0;
			foreach ($autoridades_firmantes as $autoridad_firmante) {
				$data['autorizantes'][] = [
					'usuario' => $autoridad_firmante,
					'nivel_autorizacion' => Autorizacion::NIVEL_FIRMA,
                    // Si el modelo de autorización es simple no se pasa la secuencia
					'secuencia' => ($modelo_autorizacion == Autorizacion::MODELO_SIMPLE)? null : $secuencia
				];
				$secuencia++;
			}

			$arai_documentos_class = (\comunes::es_interfaz_autogestion()) ? \SIU\Chulupi\kernel::localizador()->instanciar('modelo_g3\arai_documentos') : 'arai_documentos';

                        $params['documento_tipo'] = $data['documento']['tipo'];
                        $params['id'] = $parametros['nro_solicitud'];
                        $params['ras'] = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($parametros['propuesta']);
                        $params['metodo'] = __METHOD__;
			$origen = $arai_documentos_class::get_cuenta_usuario_origen_arai_documentos($params);
			$data['origen']['id_usuario_sso'] = $origen;
			$data['origen']['cuenta_usuario'] = $origen;
			// Se le debe pasar 'id_instalacion' ya que se muestra en la estampa
			$data['origen']['id_instalacion'] = arai_documentos::ID_INSTALACION_ESTAMPA;
				
			try {
				$documento = $arai_documentos_class::armarDocumento($data, false);
				$datos_documento = $arai_documentos_class::postDocumento($documento);
				return $datos_documento['uid_documento'];
			} catch (\Exception $e) {
				$mensaje = $e->getMessage();
				throw new \Exception(utf8_decode($mensaje));
			}
		} catch (\Exception $e) {

			toba::logger()->error($e->getMessage());
			$mensaje = guarani_mensajes::get('800_fallo_creacion_documento_arai');
			throw guarani::error($mensaje);
			
		} finally {

			// Borro el archivo
			if ($borrar_archivo && file_exists($archivo)) {
				unlink($archivo);
			}
				
		}
	}

}