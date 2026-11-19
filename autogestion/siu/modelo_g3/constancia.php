<?php
namespace siu\modelo_g3;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\VistaJasperreports;
use siu\modelo_g3\arai_reportes;
use siu\modelo_g3\toba_vista_araireportes;

class constancia{

    public static function descargar_comprobante($solicitud)
	{
		$datos_solicitud = \toba::consulta_php('co_constancias')->get_datos_solicitud($solicitud);
                
		$plantilla = \toba::consulta_php('co_constancias')->get_constancia_archivo_plantilla($datos_solicitud['constancia'], );
                $parametro_sistema= \persona::get_param_nombre_persona_doc_oficiales();
                //Genero el QR en una carpeta temporal
		$path_carpeta_qr = kernel::proyecto()->get_dir_temp() . "/constancias_qr";
		if (!file_exists($path_carpeta_qr)) {
			mkdir($path_carpeta_qr);
		}
		$path_qr = $path_carpeta_qr . '/' . $datos_solicitud['solicitud'] . ".png";

                $url_verificacion = kernel::vinculador()->crear('validador_certificados', 'index');
                $url_verificacion_con_parametros = kernel::vinculador()->crear('validador_certificados', 'index', [$datos_solicitud['nro_documento'], $datos_solicitud['codigo_verificacion']]);

		\guarani_qrcode::generar_codigo_qr($path_qr, $url_verificacion_con_parametros);

                // Me fijo si esta habilitado para Araí Reportes
                if (arai_reportes::esReporteHabilitado($datos_solicitud['constancia'])) {
                    
                    $report = new toba_vista_araireportes();

                    // Recupero información del reporte
                    $informacion_reporte = arai_reportes::getInformacionReporteById($datos_solicitud['constancia']);
                    // Recupero la URI del reporte
                    $uri_reporte    = \toba::consulta_php('co_arai_reportes')->get_uri_reporte($informacion_reporte['id']);
                    $report->set_path_reporte($uri_reporte);

                    // Recupero la URI del logo
                    $uri_logo    = arai_reportes::getUriLogoImpresion();
                    $report->set_parametro('uri_logo', 'S', $uri_logo);
                    
                    // Seteo el título del reporte
                    $report->set_parametro('titulo', 'S', $informacion_reporte['titulo']);

                    // Recupero datos para el detalle del reporte si corresponde
                    if (isset($informacion_reporte['clase_consulta']) && isset($informacion_reporte['metodo_consulta'])) {
                        $clase_consulta = $informacion_reporte['clase_consulta'];
                        $metodo_consulta = $informacion_reporte['metodo_consulta'];
                        $datos  = \toba::consulta_php($clase_consulta)->$metodo_consulta($solicitud);

                        // Seteo datos para el reporte
                        $report->set_json(rest_encode($datos));
                    }

                    // Genero el base64 del QR a partir de la imagen
                    $base64_qr = base64_encode(file_get_contents($path_qr));
                    // Elimino la imagen del QR
                    unlink($path_qr);
                    $report->set_parametro('verificacion_qr', 'S', $base64_qr);
                    
                } else {

                    $report = new VistaJasperreports();
                    // Se setea la plantilla.
                    $path = estatica('guarani', 'get_path_rep_jasper', array($plantilla));
                    $report->set_path_reporte($path);
                    //Seteo el logo de la Institución correspondiente, si no existe seteo el logo de Guarani
                    $path_logo = kernel::localizador()->encontrar_path_archivo('www/img', 'logo-transparente.png');
                    $report->set_parametro('logo', 'S', $path_logo);
                    $report->set_parametro('solicitud', 'E', $solicitud);
                    $report->set_parametro('apellido_y_nombre', 'S', $parametro_sistema);
                    $report->set_parametro('verificacion_qr', 'S', $path_qr);
                  
                }

		//Seteo los parámetros para verificación: QR, URL y código
		$report->set_parametro('verificacion_url', 'S', $url_verificacion);
		$report->set_parametro('verificacion_codigo', 'S', $datos_solicitud['codigo_verificacion']);
		$report->set_parametro('ocultar_firma', 'B', true);

		$parametros = \toba::consulta_php('co_constancias')->get_solicitud_params($solicitud);
		foreach ($parametros as $param) {
			$report->set_parametro($param['campo'], $param['tipo_de_dato'], $param['valor']);
		}
                
		$report->generar_salida();
		return $report->get_nombre_archivo_generado();
		
	}
}