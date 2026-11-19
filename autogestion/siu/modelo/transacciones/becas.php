<?php
namespace siu\modelo\transacciones;

use siu\modelo\datos\catalogo;
use SIU\Chulupi\kernel;
use siu\modelo_g3\encuesta_3w;
use siu\guarani;
use SIU\Chulupi\util\ManejadorArchivos;
use siu\errores\error_guarani_usuario;
use siu\modelo\_comun\clienteRDI;

class becas extends transaccion
{
	const CLAVE_HABILITACION_SESION = '__convocatorias_becas_habilitacion';
	const CLAVE_HASH_SESION = '__convocatorias_becas_hash';
	const CLAVE_REAPERTURA_SESION = '__convocatorias_becas_reapertura';
	const CLAVE_SECCIONES_DATOS_CENSALES_SESION = '__convocatorias_becas_secciones_datos_censales';
	const CLAVE_CAMPOS_DATOS_CENSALES_SESION = '__convocatorias_becas_campos_datos_censales';

	protected $convocatorias_vigentes;
	protected $convocatorias_aplicadas;
	protected $convocatoria_impresion;

	function info__convocatorias_vigentes()
	{
		if (!isset($this->convocatorias_vigentes)) {
			$this->convocatorias_vigentes = catalogo::consultar('becas', 'get_convocatorias_vigentes_by_persona', ['persona' => $this->persona]);
		}
		kernel::log()->add_debug("=== Convocatorias Vigentes ===", $this->convocatorias_vigentes);
		return $this->convocatorias_vigentes;
	}
	
	function info__convocatorias_aplicadas()
	{
		if (!isset($this->convocatorias_aplicadas)) {
			$this->convocatorias_aplicadas = catalogo::consultar('becas', 'get_convocatorias_aplicadas_by_persona', ['persona' => $this->persona]);
		}
		kernel::log()->add_debug("=== Convocatorias Aplicadas ===", $this->convocatorias_aplicadas);
		return $this->convocatorias_aplicadas;
	}

	function info__convocatoria_impresion($convocatoria)
	{
		
		if (!isset($this->convocatoria_impresion)) {
			
			$this->convocatoria_impresion = catalogo::consultar('becas', 'get_convocatoria_impresion', ['convocatoria' => $convocatoria, 'persona' => $this->persona]);
			
		}
		
		kernel::log()->add_debug("=== Convocatoria Impresion ===", $this->convocatoria_impresion);
		return $this->convocatoria_impresion;
	}
	
	/**
	 * Obtiene el PDF correspondiente a un formulario respondido.
	 * 
	 * @param int $habilitacion
	 * @param int $formulario
	 * @param string $codigo_externo
	 * @param string $unidad_gestion
	 *
	 * @return string La ubicación del PDF.
	 */
	function info__pdf_formulario_kolla($habilitacion, $formulario, $codigo_externo, $unidad_gestion)
	{
		$path_descarga = kernel::proyecto()->get_dir_temp().'/'.'kolla.pdf';
		$retorno = encuesta_3w::get_pdf_formulario_respondido($habilitacion, $formulario, $codigo_externo, $unidad_gestion, $path_descarga);
		
		// Si falló la generación del PDF
		if (!$retorno['ok']) {
			throw new \Exception('¡Falló la generación del PDF en Kolla!');
		}
		
		return $retorno['path'];
	}
        
	function info__aspirantes_solicitudes($cuil)
	{
            return catalogo::consultar('becas', 'get_aspirante_solicitudes', ['persona' => $this->persona , 'cuil' => $cuil]);
	}

        function info__datos_reapertura($reapertura)
	{
            return catalogo::consultar('becas', 'get_datos_reapertura', ['reapertura' => $reapertura]);
	}

        function info__requisitos_convocatoria_by_persona($habilitacion)
	{
            return catalogo::consultar('becas', 'get_requisitos_convocatoria_by_persona', [
                'habilitacion' => $habilitacion,
                'persona' => $this->persona
            ]);
	}

        function limpiar_cache_requisitos_convocatoria_by_persona($habilitacion)
        {
            catalogo::limpiar_cache('becas', 'get_requisitos_convocatoria_by_persona', [
                'habilitacion' => $habilitacion,
                'persona' => $this->persona
            ]);
        }

        public static function viene_de_convocatorias_becas()
	{
            kernel::log()->add_debug('=== $_SERVER["HTTP_REFERER"] ===', $_SERVER["HTTP_REFERER"] ?? null);
            $operacion_actual = kernel::ruteador()->get_id_operacion();
            kernel::log()->add_debug("=== OPERACION ACTUAL ===", $operacion_actual);
            
            $eliminar_convocatoria_sesion = true;
            // Cuando se navega desde otra operación
            if (isset($_SERVER["HTTP_REFERER"])) {
                
                $referer_path = parse_url($_SERVER["HTTP_REFERER"], PHP_URL_PATH);
                
                // Viene de "Trámites > Convocatorias a Becas"
                $viene_de_convocatorias_becas = (strpos($referer_path, 'convocatorias_becas') !== false);
                // Viene de "Mis datos personales"
                $viene_de_datos_censales = (strpos($referer_path, 'datos_censales') !== false);
                // Viene de "Documentos digitales de la convocatoria"
                $viene_de_requisitos_becas = (strpos($referer_path, 'requisitos_becas') !== false);

                // Si estoy en "Mis datos personales" y vengo de "Trámites > Convocatorias a Becas" o "Mis datos personales"
                if ($operacion_actual === 'datos_censales' && ($viene_de_convocatorias_becas || $viene_de_datos_censales)) {
                    $eliminar_convocatoria_sesion = false;
                }

                // Si estoy en "Documentos digitales de la convocatoria" y vengo de "Trámites > Convocatorias a Becas" o "Mis datos personales" o "Documentos digitales de la convocatoria"
                if ($operacion_actual === 'requisitos_becas' && ($viene_de_convocatorias_becas || $viene_de_datos_censales || $viene_de_requisitos_becas)) {
                    $eliminar_convocatoria_sesion = false;
                }
                
            // Cuando se tipea la URL o se hace F5
            } else {
                    $eliminar_convocatoria_sesion = false;
            }
                
            if ($eliminar_convocatoria_sesion) {
                // ...quito los campos obligatorios por convocatorias a becas
                static::borrar_sesion_convocatoria();
            }
            
            return kernel::sesion()->esta_seteada(static::CLAVE_HABILITACION_SESION);
	}

        public static function borrar_sesion_convocatoria()
        {
            kernel::sesion()->borrar(static::CLAVE_HABILITACION_SESION);
            kernel::sesion()->borrar(static::CLAVE_REAPERTURA_SESION);
            kernel::sesion()->borrar(static::CLAVE_HASH_SESION);
            kernel::sesion()->borrar(static::CLAVE_SECCIONES_DATOS_CENSALES_SESION);
            kernel::sesion()->borrar(static::CLAVE_CAMPOS_DATOS_CENSALES_SESION);
        }

        public static function get_url_siguiente_paso()
	{
            $url = '#';

            if (static::viene_de_convocatorias_becas()) {

                $habilitacion = kernel::sesion()->get(static::CLAVE_HABILITACION_SESION);

                $requisitos = guarani::becas()->info__requisitos_convocatoria_by_persona($habilitacion);

                // Si no hay requisitos lo llevo directamente a la encuesta
                if (empty($requisitos)) {
                    $url = kernel::vinculador()->crear('encuestas_kolla', 'index', kernel::sesion()->get(static::CLAVE_HASH_SESION));
                // Si hay requisitos lo llevo a "Documentos digitales de la convocatoria"
                } else {
                    $url = kernel::vinculador()->crear('requisitos_becas', 'index');
                }

            }
            return $url;
	}

        function evt__actualizar_requisito_aspirante($habilitacion, $requisito_id, $id_documento, $no_posee)
        {

            $hubo_error = false;
            $msg_error = '';

            try {
                
                kernel::db()->abrir_transaccion();

                $requisitos = $this->info__requisitos_convocatoria_by_persona($habilitacion);

                $requisito = $requisitos[$requisito_id] ?? null;

                // Si el requisito no existe
                if (is_null($requisito)) {
                    throw new error_guarani_usuario(kernel::traductor()->trans('requisitos_becas.error_existe_requisito'));
                }

                // true cuando viene del botón "Modificar Datos", false cuando viene del botón "Aplicar"
                $es_reapertura = kernel::sesion()->esta_seteada(becas::CLAVE_REAPERTURA_SESION);
                // Si es reapertura y no esta rechazado entonces no le permito modificar
                if ($es_reapertura && ($requisito['rechazado'] === \comunes::no)) {
                    throw new \Exception(kernel::traductor()->trans('requisitos.msj_error_generico'));
                }

                // Si el requisito tiene documento en el RDI
                // y no fue copiado de los requisitos de ingreso de la persona
                if (
                    isset($requisito['id_documento'])
                    && !is_null($requisito['id_documento'])
                    && ($requisito['copiado'] === \comunes::no)
                ) {
                    // Elimino documento del RDI
					clienteRDI::eliminar_repositorio($requisito['tipo_documento_digital'], $requisito['id_documento']);
                }

                // Actualizo el requisito del aspirante en la DB
                catalogo::consultar('becas', 'upsert_requisito_aspirante', [
                    'habilitacion' => $habilitacion,
                    'requisito' => $requisito_id,
                    'persona' => $this->persona,
                    'id_documento' => $id_documento,
                    'extension' => null,
                    'no_posee' => $no_posee
                ]);

                // Limpio cache
                $this->limpiar_cache_requisitos_convocatoria_by_persona($habilitacion);
                
                kernel::db()->cerrar_transaccion();
                
            // Error RDI
            } catch (\RDIExcepcion $e) {
                kernel::log()->add_error($e);
                kernel::db()->abortar_transaccion();
                $hubo_error = true;
                $msg_error = kernel::traductor()->trans('requisitos.msj_error_rdi');
            // Error a mostrar al usuario
            } catch (error_guarani_usuario $e) {
                kernel::log()->add_error($e);
                kernel::db()->abortar_transaccion();
                $hubo_error = true;
                $msg_error = $e->getMessage();
            // Error genérico
            } catch (\Exception $e) {
                kernel::log()->add_error($e);
                kernel::db()->abortar_transaccion();
                $hubo_error = true;
                $msg_error = kernel::traductor()->trans('requisitos.msj_error_generico');
            }

            return [$hubo_error, $msg_error];
        }

        function evt__descargar_requisito_aspirante($habilitacion, $requisito_id)
        {

            $hubo_error = false;
            $msg_error = '';
            $path = '';
            $nombre_descarga = '';
            
            try {

                $requisitos = $this->info__requisitos_convocatoria_by_persona($habilitacion);

                $requisito = $requisitos[$requisito_id] ?? null;

                // Si el requisito no existe
                if (is_null($requisito)) {
                    throw new error_guarani_usuario(kernel::traductor()->trans('requisitos_becas.error_existe_requisito'));
                }

                // Si el requisito no tiene documento en el RDI
                if (is_null($requisito['id_documento'])) {
                    throw new error_guarani_usuario(kernel::traductor()->trans('requisitos.error_requisito_sin_documento'));
                }

                // Descargo el documento desde RDI
                $srv = kernel::proyecto()->rdi()->servicio($requisito['tipo_documento_digital']);
                $content = $srv->recuperarContenido($requisito['id_documento']);

				// Path de descarga
				$path = kernel::proyecto()->get_www_temp() . '/' . $requisito['id_documento'];
				file_put_contents($path, $content);

				// Nombre de descarga
				$finfo = finfo_open(FILEINFO_MIME_TYPE);
				$mime_type = finfo_file($finfo, $path);
				finfo_close($finfo);
				$ext = ManejadorArchivos::mime2ext($mime_type);
				$nombre_descarga = "{$requisito['nombre']}.{$ext}";

            // Error RDI
            } catch (\RDIExcepcion $e) {
                kernel::log()->add_error($e);
                kernel::db()->abortar_transaccion();
                $hubo_error = true;
                $msg_error = kernel::traductor()->trans('requisitos.msj_error_rdi');
            // Error a mostrar al usuario
            } catch (error_guarani_usuario $e) {
                kernel::log()->add_error($e);
                kernel::db()->abortar_transaccion();
                $hubo_error = true;
                $msg_error = $e->getMessage();
            // Error genérico
            } catch (\Exception $e) {
                kernel::log()->add_error($e);
                kernel::db()->abortar_transaccion();
                $hubo_error = true;
                $msg_error = kernel::traductor()->trans('requisitos.msj_error_generico');
            }

            return [$hubo_error, $msg_error, $path, $nombre_descarga];
        }
        
        function evt__eliminar_requisito_aspirante($habilitacion, $requisito_id)
        {

            $hubo_error = false;
            $msg_error = '';

            try {

                kernel::db()->abrir_transaccion();

                $requisitos = $this->info__requisitos_convocatoria_by_persona($habilitacion);

                $requisito = $requisitos[$requisito_id] ?? null;

                // Si el requisito no existe
                if (is_null($requisito)) {
                    throw new error_guarani_usuario(kernel::traductor()->trans('requisitos_becas.error_existe_requisito'));
                }

                // true cuando viene del botón "Modificar Datos", false cuando viene del botón "Aplicar"
                $es_reapertura = kernel::sesion()->esta_seteada(becas::CLAVE_REAPERTURA_SESION);
                // Si es reapertura y no esta rechazado entonces no le permito modificar
                if ($es_reapertura && ($requisito['rechazado'] === \comunes::no)) {
                    throw new \Exception(kernel::traductor()->trans('requisitos.msj_error_generico'));
                }

                // Si el requisito tiene documento en el RDI
                // y no fue copiado de los requisitos de ingreso de la persona
                if (
                    isset($requisito['id_documento'])
                    && !is_null($requisito['id_documento'])
                    && ($requisito['copiado'] === \comunes::no)
                ) {
					// Elimino documento del RDI
					clienteRDI::eliminar_repositorio($requisito['tipo_documento_digital'], $requisito['id_documento']);
                }

                catalogo::consultar('becas', 'eliminar_requisito_aspirante', [
                    'habilitacion' => $habilitacion,
                    'requisito' => $requisito_id,
                    'persona' => $this->persona
                ]);

                // Limpio cache
                $this->limpiar_cache_requisitos_convocatoria_by_persona($habilitacion);

                kernel::db()->cerrar_transaccion();

            // Error RDI
            } catch (\RDIExcepcion $e) {
                kernel::log()->add_error($e);
                kernel::db()->abortar_transaccion();
                $hubo_error = true;
                $msg_error = kernel::traductor()->trans('requisitos.msj_error_rdi');
            // Error a mostrar al usuario
            } catch (error_guarani_usuario $e) {
                kernel::log()->add_error($e);
                kernel::db()->abortar_transaccion();
                $hubo_error = true;
                $msg_error = $e->getMessage();
            // Error genérico
            } catch (\Exception $e) {
                kernel::log()->add_error($e);
                kernel::db()->abortar_transaccion();
                $hubo_error = true;
                $msg_error = kernel::traductor()->trans('requisitos.msj_error_generico');
            }

            return [$hubo_error, $msg_error];
        }

        function evt__subir_requisito_aspirante($habilitacion, $requisito_id, $path_archivo_subido)
        {

            $hubo_error = false;
            $msg_error = '';

            try {

                kernel::db()->abrir_transaccion();

                // Si el archivo no existe
                if (!file_exists($path_archivo_subido)) {
                   throw new error_guarani_usuario(kernel::traductor()->trans('upload_err_generico'));
                }
                
                $requisitos = $this->info__requisitos_convocatoria_by_persona($habilitacion);

                $requisito = $requisitos[$requisito_id] ?? null;

                // Si el requisito no existe
                if (is_null($requisito)) {
                    throw new error_guarani_usuario(kernel::traductor()->trans('requisitos_becas.error_existe_requisito'));
                }

                // true cuando viene del botón "Modificar Datos", false cuando viene del botón "Aplicar"
                $es_reapertura = kernel::sesion()->esta_seteada(becas::CLAVE_REAPERTURA_SESION);
                // Si es reapertura y no esta rechazado entonces no le permito modificar
                if ($es_reapertura && ($requisito['rechazado'] === \comunes::no)) {
                    throw new \Exception(kernel::traductor()->trans('requisitos.msj_error_generico'));
                }

                // Si se copio de los requisitos de ingreso hago un alta en lugar de una modificación
                $id_documento = ($requisito['copiado'] === \comunes::si) ? null : $requisito['id_documento'];
                // Alta/Modificación de recurso en RDI
                $id_recurso = clienteRDI::enviar_repositorio($path_archivo_subido, $requisito['tipo_documento_digital'], $id_documento);

                // Recupero la extensión
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($finfo, $path_archivo_subido);
                finfo_close($finfo);
                $extension = ManejadorArchivos::mime2ext($mime_type);

                // Actualizo el requisito del aspirante en la DB
                catalogo::consultar('becas', 'upsert_requisito_aspirante', [
                    'habilitacion' => $habilitacion,
                    'requisito' => $requisito_id,
                    'persona' => $this->persona,
                    'id_documento' => $id_recurso,
                    'extension' => $extension,
                    'no_posee' => 0
                ]);

                // Limpio cache
                $this->limpiar_cache_requisitos_convocatoria_by_persona($habilitacion);

                kernel::db()->cerrar_transaccion();

            // Error RDI
            } catch (\RDIExcepcion $e) {
                kernel::log()->add_error($e);
                kernel::db()->abortar_transaccion();
                $hubo_error = true;
                $msg_error = kernel::traductor()->trans('requisitos.msj_error_rdi');
            // Error a mostrar al usuario
            } catch (error_guarani_usuario $e) {
                kernel::log()->add_error($e);
                kernel::db()->abortar_transaccion();
                $hubo_error = true;
                $msg_error = $e->getMessage();
            // Error genérico
            } catch (\Exception $e) {
                kernel::log()->add_error($e);
                kernel::db()->abortar_transaccion();
                $hubo_error = true;
                $msg_error = kernel::traductor()->trans('requisitos.msj_error_generico');
            // Entra ya sea que salga todo bien o falle
            } finally {
                // Elimino el archivo temporal
                if (file_exists($path_archivo_subido)) {
                    unlink($path_archivo_subido);
                }
            }

            return [$hubo_error, $msg_error];
        }

        function info__habilitacion($habilitacion)
	{
            return catalogo::consultar('becas', 'get_info_habilitacion', [
                'habilitacion' => $habilitacion
            ]);
	}

        function insertar_requisitos_convocatoria($habilitacion)
	{
            try {

                kernel::db()->abrir_transaccion();

                $requisitos_insertados = catalogo::consultar('becas', 'insertar_requisitos_convocatoria', [
                    'persona' => $this->persona,
                    'habilitacion' => $habilitacion
                ]);

                // Por cada documento copiado
                foreach ($requisitos_insertados as $requisito) {

                    // Si no se copia documento del RDI salto al siguiente
                    if (is_null($requisito['id_documento'])) {
                        continue;
                    }

                    $tipo_documento_digital = catalogo::consultar('becas', 'get_tipo_documento_digital', [
                        'requisito' => $requisito['requisito']
                    ]);
                    
                    // Descargo el documento desde RDI
                    $srv = kernel::proyecto()->rdi()->servicio($tipo_documento_digital);
                    $content = $srv->recuperarContenido($requisito['id_documento']);

                    $nombre_descarga = $requisito['id_documento'];
                    $archivo_temp = md5($nombre_descarga);
                    $nombre = kernel::proyecto()->get_www_temp() . '/' . $archivo_temp;
                    file_put_contents($nombre, $content);

                    // Recupero la extensión
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime_type = finfo_file($finfo, $nombre);
                    finfo_close($finfo);
                    $extension = ManejadorArchivos::mime2ext($mime_type);

                    // Elimino el archivo temporal
                    unlink($nombre);

                    $requisitos_insertados = catalogo::consultar('becas', 'update_extension_requisitos_convocatoria', [
                        'habilitacion' => $requisito['habilitacion'],
                        'requisito' => $requisito['requisito'],
                        'persona' => $requisito['persona'],
                        'extension' => $extension
                    ]);
                }

                kernel::db()->cerrar_transaccion();

                // Limpio cache
                $this->limpiar_cache_requisitos_convocatoria_by_persona($habilitacion);
            } catch (\Exception $e) {
                kernel::db()->abortar_transaccion();
                throw $e;
            }
	}

        function marcar_requisitos_convocatoria_no_rechazados($habilitacion)
	{
            catalogo::consultar('becas', 'marcar_requisitos_convocatoria_no_rechazados', [
                'persona' => $this->persona,
                'habilitacion' => $habilitacion
            ]);

            // Limpio cache
            $this->limpiar_cache_requisitos_convocatoria_by_persona($habilitacion);
	}

        function get_requisitos_habilitacion($habilitacion)
	{
            return catalogo::consultar('becas', 'get_requisitos_habilitacion', [
                'habilitacion' => $habilitacion
            ]);
	}

	function get_datos_academicos_formulario_by_persona($formulario)
	{
		return catalogo::consultar('becas', 'get_datos_academicos_formulario_by_persona', [
			'formulario' => $formulario,
			'persona' => $this->persona
		]);
	}

	function actualizar_datos_academicos_aspirante_becas($habilitacion)
	{
		catalogo::consultar('becas', 'actualizar_datos_academicos_aspirante_becas', [
			'habilitacion' => $habilitacion,
			'persona' => $this->persona
		]);
	}
}