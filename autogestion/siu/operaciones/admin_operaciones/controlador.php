<?php
namespace siu\operaciones\admin_operaciones;

use SIU\Chulupi\ErrorKernel;
use SIU\Chulupi\kernel;
use siu\extension_kernel\controlador_g3w2;
use siu\bootstrap;
use SIU\Chulupi\acceso\FactoryPerfil;
use SIU\Chulupi\util\config;

class controlador extends controlador_g3w2 {
    
    /** 
     * Guardamos en un array los perfiles que no queremos visualizar en la
     * operacion Administrar Operaciones Ticket #42910
     */    
    static $perfiles_lista_negra = [ \persona::perfil_gerencial ];
    
    static $operaciones_lista_negra = [
        'acceso',
        'admin_operaciones',
        'notificador',
        'saml',
        'pruebas_stress',
        'validador_certificados',
        'portal_pagos', // Se maneja con el parámetro 'sq_usa_sanaviron'
        'boleto_estudiantil', // Se maneja con el parámetro 'usa_boleto_estudiantil'
    ];

    /**
     *
     * @return null
     */
    function modelo() {
        return null;
    }

    //https://repositorio.siu.edu.ar/trac/g3w2/wiki/conf_op
    function accion__index() {

        // Obtengo los archivos de acceso del core
        $path_conf_acceso = '/conf/acceso/';
        $path_siu =  bootstrap::instancia()->get_dir_siu();
        $path_siu_conf_acceso = $path_siu.$path_conf_acceso;
        $archivos_acceso = $this->get_archivos_acceso($path_siu_conf_acceso);

        //Si hay personalización activa
        if (kernel::proyecto()->hay_personalizacion_activa()) {
            
            // Obtengo los archivos de acceso de la personalización
            $personalizacion_activa = kernel::proyecto()->get_personalizacion_activa();
            $path_pers = bootstrap::instancia()->get_dir_raiz().'/src/pers/'.$personalizacion_activa;
            $path_pers_conf_acceso = $path_pers.$path_conf_acceso;
            $archivos_acceso_pers = $this->get_archivos_acceso($path_pers_conf_acceso);
            kernel::log()->add_debug("=== archivos_acceso_pers ===", $archivos_acceso_pers);
        
            $func = function($a, $b) {
                if(basename($a, ".php") == basename($b, ".php")){
                    return 0;
                }
                else{
                    return 1;
                }
            };

            //Obtengo los archivos de acceso de perfiles nuevos agregados en la personalización...
            $archivos_acceso_diff = array_udiff($archivos_acceso_pers, $archivos_acceso, $func);
            //...y los concateno con los que vienen por defecto en el core.
            foreach($archivos_acceso_diff as $archivo_acceso_diff){
                $archivos_acceso[] = $archivo_acceso_diff;
            }
        }
        
        kernel::log()->add_debug("=== archivos_acceso ===", $archivos_acceso);
        
        static::$perfiles_lista_negra = array_map('strtolower', static::$perfiles_lista_negra);
        
        // Recorro cada archivo de acceso
        foreach ($archivos_acceso as $archivo_acceso) {
            // Cargo el contenido del archivo de acceso en un array
            $array_acceso = config::load($archivo_acceso);
            // Si el archivo de acceso es de un perfil dentro de la "lista negra" lo salteo
            if(in_array(strtolower($array_acceso['id']), static::$perfiles_lista_negra)){
                continue;
            }
            $perfiles[] = FactoryPerfil::instancia()->construir($array_acceso['id'], kernel::proyecto()->get('bloque_acceso'));
        }

        kernel::log()->add_debug("=== perfiles ===", $perfiles);
               
        $pagelet_operaciones = $this->vista()->pagelet('operaciones');
        $pagelet_operaciones->data['perfiles'] = $perfiles;
        $pagelet_operaciones->data['form_action'] = kernel::vinculador()->crear('admin_operaciones', 'guardar');
        $pagelet_operaciones->data['lista_negra_operaciones'] = static::$operaciones_lista_negra;
    }

    function accion__guardar(){

        // Si el request es POST
        if (kernel::request()->isPost()) {

            $perfiles = kernel::request()->getPost('estado-operacion');
            kernel::log()->add_debug("=== perfiles ===", $perfiles);
        
            $mensaje_respuesta = kernel::traductor()->trans('admin_operaciones.guardado_datos_exitoso');
            $respuesta_exitosa = true;

            // Recorro los perfiles
            foreach($perfiles as $perfil => $operaciones){

                $operaciones_inactivas = [];
                // Recorro las operaciones
                foreach($operaciones as $operacion => $estado){
                    // Si es estado es "Inactiva"
                    if(!$estado){
                        // Conservo solo las operaciones "Inactivas"
                        $operaciones_inactivas[] = $operacion;

                    }

                }

                $perfil_obj = FactoryPerfil::instancia()->construir($perfil, kernel::proyecto()->get('bloque_acceso'));

                try {
                    // Guardo las operaciones "Inactivas" en un archivo json (ej: instalacion/operaciones_inactivas/ops_des01.json)
                    $perfil_obj->set_operaciones_inactivas($operaciones_inactivas);
                } catch (ErrorKernel $e) {
                    $mensaje_respuesta = kernel::traductor()->trans('admin_operaciones.hubo_error_al_guardar');
                    $respuesta_exitosa = false;
                    break;
                }

            }

            kernel::renderer()->add_to_ajax_response('info_estado', array(
                'mensaje' => $mensaje_respuesta,
                'exitoso' => $respuesta_exitosa
            ));


        } else {// Si el request es GET
            $this->nuevo_request("admin_operaciones", "index");
        }

    }

    /**
     * Obtiene los archivos .php dentro de $dir.
     *
     * @param string    $dir Directorio a buscar
     *
     * @return array Archivos .php dentro.
     */
    function get_archivos_acceso($dir){

        if (file_exists($dir)) {
            $files = glob($dir.'*.php');
            if ($files) {
                return $files;
            }
        }

        return [];
    }

}