<?php

namespace siu\operaciones\encuestas_respondidas;

use SIU\Chulupi\ErrorKernelValidacion;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use siu\extension_kernel\controlador_g3w2;
use siu\modelo\datos\catalogo;
use siu\modelo\entidades\encuestas_kolla;
use siu\modelo_g3\encuesta_3w;
use SIU\Chulupi\nucleo\FinalizadorRequest;

class controlador extends controlador_g3w2
{

    protected $form_builder;
    protected $datos_filtro;

    function modelo()
    {
        return null;
    }

    function accion__index()
    {

        $materia = null;
        $materia_descr = null;
        $docente = null;
        $docente_descr = null;
        $fecha_desde = null;
        $fecha_hasta = null;

        if(isset($_GET['formulario_filtro'])){//Si hay filtros...

            $form = $this->get_form();
            $validacion_ok = $this->recuperar_filtro();
            $filtros = $form->get_datos();

            $materia_hash = $filtros['materia'];
            $docente_hash = $filtros['docente'];

            if($materia_hash){
                list($materia, $materia_descr) = $this->decodificar_materia($materia_hash);
            }

            if($docente_hash){
                list($docente, $docente_descr) = $this->decodificar_docente($docente_hash);
            }

            $fecha_desde = ($filtros['fecha_desde'])? $filtros['fecha_desde'] : null;
            $fecha_hasta = ($filtros['fecha_hasta'])? $filtros['fecha_hasta'] : null;

            $form->set_datos(array("materia_descr" => $materia_descr));
            $form->set_datos(array("docente_descr" => $docente_descr));
        }

        $encuestas_respondidas = kernel::persona()->get_encuestas_respondidas($fecha_desde, $fecha_hasta, $docente, $materia);
        $pagelet_listado_encuestas = $this->vista()->pagelet('listado_encuestas');

        $operacion = kernel::ruteador()->get_id_operacion();

        $url_ver = kernel::vinculador()->crear($operacion, 'ver');
        $pagelet_listado_encuestas->add_var_js('url_ver', $url_ver);

        $url_codigo_recuperacion = kernel::vinculador()->crear($operacion, 'codigo_recuperacion');
        $pagelet_listado_encuestas->add_var_js('url_codigo_recuperacion', $url_codigo_recuperacion);

        // Genero la URL de descarga por cada encuesta no anónima
        foreach ($encuestas_respondidas as $key => $encuesta_respondida) {

            // Solo se pueden descargar las encuestas no anónimas
            if ($encuesta_respondida['anonima'] == \comunes::si) {
                continue;
            }

            $parametros_url_descarga = [
                'habilitacion' => $encuesta_respondida['kolla_id_habilitacion'],
                'formulario' => $encuesta_respondida['formulario'],
                'codigo_externo' => $encuesta_respondida['codigo_externo'],
                'unidad_gestion' => $encuesta_respondida['unidad_gestion']
            ];
            $encuestas_respondidas[$key]['url_descargar'] = kernel::vinculador()->crear($operacion, 'descargar', $parametros_url_descarga);
        }

        $pagelet_listado_encuestas->data['encuestas_respondidas'] = $encuestas_respondidas;
    }

    function accion__ver(){

        if(kernel::request()->isPost()){

            $hubo_error = false;
            $codigo_recuperacion = null;
            $hash_validacion = null;

            try{

                try{
                    $parametro = "habilitacion";
                    $habilitacion = $this->validate_param('habilitacion', 'post', validador::TIPO_INT);
                    $parametro = "formulario";
                    $formulario = $this->validate_param('formulario', 'post', validador::TIPO_INT);
                    $parametro = "codigo externo";
                    $codigo_externo = $this->validate_param('codigo_externo', 'post', validador::TIPO_ALPHANUM);
                    $parametro = "password";
                    $password = $this->validate_param('password', 'post', validador::TIPO_ALPHANUM);
                    $parametro = "anonima";
                    $anonima = $this->validate_param('anonima', 'post', validador::TIPO_INT);
                    if($anonima){
                        $parametro = "código recuperación";
                        $codigo_recuperacion = $this->validate_param('codigo_recuperacion', 'post', validador::TIPO_INT);
                        $parametro = "hash validación";
                        $hash_validacion = $this->validate_param('hash_validacion', 'post', validador::TIPO_ALPHANUM);
                    }
                }
                catch(ErrorKernelValidacion $e){
                    throw new \Exception(kernel::traductor()->trans('encuestas_respondidas.parametro_invalido', array("%parametro%" => $parametro)));
                }

                $data['url_kolla'] = encuestas_kolla::generar_url($habilitacion, $formulario, $codigo_externo, $password, 'ver_respuestas', $codigo_recuperacion, $hash_validacion);

            }
            catch(\Exception $e2){
                $hubo_error = true;
                kernel::renderer()->add_to_ajax_response('mensaje', $e2->getMessage());
            }

            kernel::renderer()->add_to_ajax_response('hubo_error', $hubo_error);

            if(!$hubo_error){
                $this->render_template('encuesta_respondida.twig', $data);
            }

        }
    }

    function accion__codigo_recuperacion(){

        if(kernel::request()->isPost()){

            $hubo_error = false;

            try{

                try{
                    $parametro = "habilitacion";
                    $data['habilitacion'] = $this->validate_param('habilitacion', 'post', validador::TIPO_INT);
                    $parametro = "formulario";
                    $data['formulario'] = $this->validate_param('formulario', 'post', validador::TIPO_INT);
                    $parametro = "codigo externo";
                    $data['codigo_externo'] = $this->validate_param('codigo_externo', 'post', validador::TIPO_ALPHANUM);
                    $parametro = "password";
                    $data['password'] = $this->validate_param('password', 'post', validador::TIPO_ALPHANUM);
                    $parametro = "anonima";
                    $data['anonima'] = $this->validate_param('anonima', 'post', validador::TIPO_INT);
                }
                catch(ErrorKernelValidacion $e){
                    throw new \Exception(kernel::traductor()->trans('encuestas_respondidas.parametro_invalido', array("%parametro%" => $parametro)));
                }

            }
            catch(\Exception $e2){
                $hubo_error = true;
                kernel::renderer()->add_to_ajax_response('mensaje', $e2->getMessage());
            }

            kernel::renderer()->add_to_ajax_response('hubo_error', $hubo_error);

            if(!$hubo_error){
                $this->render_template('codigo_recuperacion.twig', $data);
            }

        }

    }

    /**
     * Solo la vista 'vista' tiene el form. Falla en otro caso
     */
    private function recuperar_filtro()
    {
        $form = $this->get_form();

        $validacion_ok = $form->procesar('GET');
        //si no se llega aca, aplican los valores default
        $this->datos_filtro = $form->get_datos();
        return $validacion_ok;
    }

    /**
     * @return guarani_form
     */
    function get_form()
    {
        return $this->get_form_builder()->get_formulario();
    }

    function get_vista_form(){
        return $this->get_form_builder()->get_vista();
    }

    /**
     * @return builder_form_filtro
     */
    function get_form_builder()
    {
        // se hace esta función para no construir cada vez el builder
        if (! isset($this->form_builder)) {
            $this->form_builder = kernel::localizador()->instanciar('operaciones\encuestas_respondidas\filtros\builder_form_filtro');
        }

        return $this->form_builder;
    }


    function accion__buscar_materias() {

        $term = $this->getAutocompleteTerm();

        $paquete = array();

        $persona = kernel::persona()->get_id();

        $materias = catalogo::consultar('usuario_anonimo', 'lista_actividades_propuesta', array('propuesta' => '', 'plan' => '', 'materia' => $term, 'persona' => $persona));

        $paquete = array_map(function($materia) {
            return array(
                'id' => $materia['_ID_'],
                'label' => "({$materia['codigo']}) {$materia['nombre']}"
            );
        }, $materias);

        $this->render_raw_json($paquete);
    }

    function decodificar_materia($materia_hash) {

        $datos = array();

        $persona = kernel::persona()->get_id();

        $materias = catalogo::consultar('usuario_anonimo', 'lista_actividades_propuesta', array('propuesta' => '', 'plan' => '', 'materia' => '', 'persona' => $persona));

        $datos = array_map(function($materia) {
            return array(
                'id' => $materia['_ID_'],
                'materia' => $materia['elemento'],
                'label' => "({$materia['codigo']}) {$materia['nombre']}"
            );
        }, $materias);

        if (!empty($datos)) {
            foreach($datos as $value){
                if ($value['id'] == $materia_hash){
                    return array($value['materia'], $value['label']);
                }
            }
        }
        return array(null, null);
    }

    function accion__buscar_docentes() {
        $term = $this->getAutocompleteTerm();

        $datos = array();
        if (!is_null($term)){
            $datos = catalogo::consultar('docente', 'get_docentes_activos', array('apellido' => $term));
        }
        foreach($datos as $key => $value){
            $datos[$key]['id'] = catalogo::generar_id($value['docente']);
            $datos[$key]['label'] = "(".$value['persona'].") ".$value['nombres']." ".$value['apellido'];
        }
        

        $this->render_raw_json($datos);
    }

    function decodificar_docente($docente_hash) {
        $docentes = catalogo::consultar('docente', 'get_docentes_activos', array('apellido' => ""));

        $datos = array_map(function($docente) {
            return array(
                'id' => catalogo::generar_id($docente['docente']),
                'docente' => $docente['docente'],
                'label' => "({$docente['persona']}) {$docente['nombres']} {$docente['apellido']}"
            );
        }, $docentes);

        if (!empty($datos)) {
            foreach($datos as $value){
                if ($value['id'] == $docente_hash){
                    return array($value['docente'], $value['label']);
                }
            }
        }
        return array(null, null);
    }


    function accion__descargar()
    {
        try {

            try {
                $parametro      = "habilitacion";
                $habilitacion   = $this->validate_param('habilitacion', 'get', validador::TIPO_INT);
                $parametro      = "formulario";
                $formulario     = $this->validate_param('formulario', 'get', validador::TIPO_INT);
                $parametro      = "codigo_externo";
                $codigo_externo = $this->validate_param('codigo_externo', 'get', validador::TIPO_ALPHANUM);
                $parametro      = "unidad_gestion";
                $unidad_gestion = $this->validate_param('unidad_gestion', 'get', validador::TIPO_TEXTO);
            } catch (ErrorKernelValidacion $e1) {
                // Si hay error en los parámetros
                throw new \Exception(kernel::traductor()->trans('encuestas_respondidas.parametro_invalido', ["%parametro%" => $parametro]));
            }

            $path_descarga = kernel::proyecto()->get_dir_temp().'/encuesta_respondida.pdf';
            $retorno       = encuesta_3w::get_pdf_formulario_respondido($habilitacion, $formulario, $codigo_externo, $unidad_gestion, $path_descarga);
            
            // Si falló la generación del PDF
            if (!$retorno['ok']) {
                throw new \Exception('¡Falló la generación del PDF en Kolla!');
            }

            $this->descargar_archivo($retorno['path'], 'Definicion_de_Encuesta.pdf', true);
            
        } catch (FinalizadorRequest $e2) {
            // Si se logra descargar el PDF se lanza una excepción de tipo 'FinalizadorRequest', esto no se trata de un error.
            throw $e2;
        } catch (\Exception $e3) {
            kernel::log()->add_error($e3);
            $mensaje = kernel::traductor()->trans('encuestas_respondidas.error_descarga_encuesta');
            kernel::sesion()->set('error_descarga_encuesta', $mensaje);
            $this->nuevo_request_url($_SERVER["HTTP_REFERER"]);
        }
    }
}