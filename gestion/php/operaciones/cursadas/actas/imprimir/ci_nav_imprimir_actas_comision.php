<?php
/**
 *
 *
 * @package 	Cursadas
 * @subpackage 	Actas
 * 
 */
class ci_nav_imprimir_actas_comision extends ci_navegacion
{
    // Comisión y acta seleccionadas.
    protected $s__comision_seleccionada;
    protected $s__acta_seleccionada;
    // Datos de los Alumnos del Acta para la impresión.
    protected $s__datos_alumnos;
    // Componente Visual de Información.
    protected $ev_info_comision;
    protected $ev_info_acta;
    // Variables para manejar el llamado desde otra operación.
    protected $s__operacion_a_volver;
    protected $s__celda_a_restaurar;
    // Datos Acta Seleccionada
    protected $s__datos_acta;
    protected $s__utiliza_acta_digital;
    protected $s__imprimir = false;
    // Selección manual de libro / tomo.
    protected $s__libro_tomo;
    // Selección de impresión como copia
    protected $s__datos_imp;
    // Parámetros.
    protected $s__carga_manual_libro;
    protected $s__forma_asignacion_folio_fisico;

    function ini() {
        toba::memoria()->desactivar_reciclado();
        if (toba::zona()->cargada()) {
            $this->s__comision_seleccionada['entidad'] = toba::zona()->get_editable();
            if (empty($this->s__acta_seleccionada)) {
                $this->set_pantalla('seleccion_acta');
            }
        }
    }

    function ini__operacion() {
        $this->cn()->resetear_atributos_parametros();
        $this->cn()->resetear_dependencias_parametros();
        if (toba::zona()->cargada()) {
            $this->s__comision_seleccionada['entidad'] = toba::zona()->get_editable();
        }
        $acta = toba::memoria()->get_parametro('id_acta');
        if (isset($acta)) {
            $this->s__operacion_a_volver = toba::memoria()->get_parametro('operacion_a_volver');
            $this->s__celda_a_restaurar = toba::memoria()->get_parametro('celda_a_restaurar');
            $this->evt__cuadro_actas__seleccion(array('id_acta' => $acta));
        }
    }

    function get_msg_eof($etiqueta = null) {
        $indx_msg = $this->indx_msg_eof;
        if (isset($this->s__filtro) && !empty($this->s__filtro)) {
            $indx_msg = $this->indx_msg_eof . '_filtrado';
        }
        return guarani_mensajes::get($indx_msg, array($etiqueta));
    }

    //-----------------------------------------------------------------------------------
    //---- operaciones auxiliares -------------------------------------------------------
    //-----------------------------------------------------------------------------------
    /**
     * Retorna la entidad de la comisión seleccionada. 
     */
    function get_comision_seleccionada() {
        return $this->s__comision_seleccionada['entidad'];
    }

    /**
     * Retorna el id de acta seleccionada.
     */
    function get_acta_seleccionada() {
        return $this->s__acta_seleccionada['id_acta'];
    }

    function get_libros_tomos_actas() {
        return toba::consulta_php('co_actas')->get_libros_tomos_actas($this->s__datos_acta);
    }

    function get_datos_alumnos() {
        return $this->s__datos_alumnos;
    }

    function get_cant_alumnos() {
        return $this->s__datos_acta['cant_alumnos'];
    }

    function cargar_atributos_parametros() {
        $this->cn()->cargar_atributos_parametros($this->s__datos_acta['comision']);
        $this->cn()->cargar_dependencias_impresion($this->s__datos_acta['origen'], $this->s__datos_acta['digital']);
    }

    //-----------------------------------------------------------------------------------
    //---- PANTALLA: seleccion_comision
    //-----------------------------------------------------------------------------------

    function conf__seleccion_comision(toba_ei_pantalla $pantalla) {
        $this->cn()->resetear_atributos_parametros();
        $this->cn()->resetear_dependencias_parametros();
    }

    //-----------------------------------------------------------------------------------
    //---- cuadro_comision --------------------------------------------------------------
    //-----------------------------------------------------------------------------------

    function conf__cuadro_comision(toba_ei_cuadro $cuadro) {
        if (!isset($this->s__filtro)) {
            $msg_eof = guarani_mensajes::get($this->indx_msg_eof . '_sinfiltro');
        } else {
            $msg_eof = $this->get_msg_eof('Comisiones');
            $datos = toba::consulta_php('co_comisiones')->get_listado_con_cant_actas($this->s__filtro);
            $cuadro->set_datos($datos);
        }
        $cuadro->set_eof_mensaje($msg_eof);
    }

    function conf_evt__cuadro_comision__seleccion(toba_evento_usuario $evento, $fila) {
        $datos = $this->dependencia('cuadro_comision')->get_datos();
        if ($datos[$fila]['cant_actas'] == 0) {
            $evento->anular();
        }
    }

    function evt__cuadro_comision__seleccion($seleccion) {
        $this->s__comision_seleccionada = $seleccion;
        toba::zona()->cargar($this->get_comision_seleccionada());
        $this->set_pantalla('seleccion_acta');
    }

    //-----------------------------------------------------------------------------------
    //---- filtro -----------------------------------------------------------------------
    //-----------------------------------------------------------------------------------

    function conf__filtro(toba_ei_filtro $filtro) {
        parent::conf__filtro($filtro);
        $filtro->columna('anio_academico')->set_condicion_fija('es_igual_a');
        $filtro->columna('periodos_nombre')->set_condicion_fija('es_igual_a');
        $filtro->columna('actividad')->set_condicion_fija('contiene');
        $filtro->columna('nro_acta')->set_condicion_fija('es_igual_a');
    }

    function evt__filtro__filtrar($datos) {
        if (isset($datos)) {
            $this->s__datos = $datos;
            $filtro_nro_acta = '';
            if (isset($datos['nro_acta'])) {
                $filtro_nro_acta = " AND EXISTS (SELECT * FROM sga_actas WHERE sga_actas.comision = sga_comisiones.comision AND sga_actas.nro_acta = '{$datos['nro_acta']['valor']}')";
                $this->dep('filtro')->columna('nro_acta')->resetear_estado();
            }
            $this->s__filtro = "{$this->dep('filtro')->get_sql_where()} $filtro_nro_acta";
        } else {
            unset($this->s__datos);
            $this->s__filtro = '';
        }
    }

    //-----------------------------------------------------------------------------------
    //---- PANTALLA: seleccion_acta
    //-----------------------------------------------------------------------------------

    function conf__seleccion_acta(toba_ei_pantalla $pantalla) {
        $filtro = "sga_comisiones.entidad = {$this->get_comision_seleccionada()}";
        $comision = current(toba::consulta_php('co_comisiones')->get_listado($filtro));
        $this->ev_info_comision()->set_datos($comision);

        $this->cn()->resetear_atributos_parametros();
        $this->cn()->resetear_dependencias_parametros();
    }

    function ev_info_comision() {
        if (!isset($this->ev_info_comision)) {
            $this->ev_info_comision = guarani::ev_info_datos();
            $this->ev_info_comision->set_columnas_a_mostrar(array('nombre', 'nombre_turno', 'nombre_modalidad', 'anio_academico_periodo', 'nombre_periodo', 'codigo_y_nombre_actividad', 'nombre_ubicacion'));
            $this->ev_info_comision->set_columnas_fila(array('nombre', 'nombre_turno', 'nombre_modalidad'), 1);
            $this->ev_info_comision->set_columnas_fila(array('anio_academico_periodo', 'nombre_periodo'), 2);
            $this->ev_info_comision->set_columnas_fila(array('codigo_y_nombre_actividad', 'nombre_ubicacion'), 3);
            $this->ev_info_comision->set_max_columnas_por_fila(3);
            $this->ev_info_comision->set_etiqueta('nombre', 'Comisión');
            $this->ev_info_comision->set_etiqueta('nombre_turno', 'Turno');
            $this->ev_info_comision->set_etiqueta('nombre_modalidad', 'Modalidad');
            $this->ev_info_comision->set_etiqueta('anio_academico_periodo', 'Año Académico');
            $this->ev_info_comision->set_etiqueta('nombre_periodo', 'Período Lectivo');
            $this->ev_info_comision->set_etiqueta('codigo_y_nombre_actividad', 'Actividad');
            $this->ev_info_comision->set_etiqueta('nombre_ubicacion', 'Ubicación');
        }
        return $this->ev_info_comision;
    }

    //-----------------------------------------------------------------------------------
    //---- cuadro_actas -----------------------------------------------------------------
    //-----------------------------------------------------------------------------------

    function conf__cuadro_actas(toba_ei_cuadro $cuadro) {
        $filtro = "sga_comisiones.entidad = {$this->get_comision_seleccionada()}";
        $datos_actas = toba::consulta_php('co_actas')->get_listado($filtro);

        foreach ($datos_actas as $clave => $valor) {
            if ($datos_actas[$clave]['estado'] == acta::estado_abierta) {
                $datos_actas[$clave]['estado_semaforo'] = toba_recurso::imagen_proyecto('iconos_estados/verde.png', true, null, null, 'Acta Abierta', null);
            } elseif ($datos_actas[$clave]['estado'] == acta::estado_cerrada) {
                $datos_actas[$clave]['estado_semaforo'] = toba_recurso::imagen_proyecto('iconos_estados/rojo.png', true, null, null, 'Acta Cerrada', null);
            } elseif ($datos_actas[$clave]['estado'] == acta::estado_anulada) {
                $datos_actas[$clave]['estado_semaforo'] = toba_recurso::imagen_proyecto('iconos_estados/azul.png', true, null, null, 'Acta Anulada', null);
            }
        }

        $cuadro->set_datos($datos_actas);

        $msj_eof = $this->get_msg_eof('Actas');
        $cuadro->set_eof_mensaje($msj_eof);
    }

    function evt__cuadro_actas__seleccion($seleccion) {
        $this->s__acta_seleccionada = $seleccion;
        $this->s__datos_acta = toba::consulta_php('co_actas')->get_datos_acta($seleccion['id_acta']);
        $this->set_pantalla('previsualizacion');
        $this->cargar_atributos_parametros();
    }

    //-----------------------------------------------------------------------------------
    //---- PANTALLA: previsualizacion
    //-----------------------------------------------------------------------------------

    function conf__previsualizacion(toba_ei_pantalla $pantalla) {
        $datos = $this->s__datos_acta;
        $datos['icono_info_escala_notas'] = $this->get_html_icono_info_escala_notas();
        $this->ev_info_acta()->set_datos($datos);

        $this->s__utiliza_acta_digital = $datos['digital'];
        if ($this->s__utiliza_acta_digital == comunes::no) {
            if ($this->es_acta_regulares()) {
                $this->s__carga_manual_libro = guarani::parametros()->get_valor('cur_carga_manual_libro_regulares');
                $this->s__forma_asignacion_folio_fisico = guarani::parametros()->get_valor('cur_forma_asignacion_folio_fisico_regul');
            } else {
                $this->s__carga_manual_libro = guarani::parametros()->get_valor('cur_carga_manual_libro_promocion');
                $this->s__forma_asignacion_folio_fisico = guarani::parametros()->get_valor('cur_forma_asignacion_folio_fisico_promo');
            }
        }
    }

    function ev_info_acta() {
        if (!isset($this->ev_info_acta)) {
            $this->ev_info_acta = guarani::ev_info_datos();

            $this->ev_info_acta->set_columnas_a_mostrar(array('nro_acta', 'libro_nombre', 'anio_academico_periodo', 'periodo_nombre', 'comision_nombre', 'modalidad_nombre', 'codigo_y_nombre_actividad', 'nombre_ubicacion', 'icono_info_escala_notas', 'nombre_turno', 'observaciones', 'version'));

            $this->ev_info_acta->set_columnas_fila(array('nro_acta', 'comision_nombre', 'libro_nombre'), 1);
            $this->ev_info_acta->set_columnas_fila(array('anio_academico_periodo', 'periodo_nombre', 'modalidad_nombre'), 2);
            $this->ev_info_acta->set_columnas_fila(array('codigo_y_nombre_actividad', 'nombre_ubicacion', 'icono_info_escala_notas'), 3);
            $this->ev_info_acta->set_columnas_fila(array('nombre_turno', 'version'), 4);
            $this->ev_info_acta->set_columnas_fila(array('observaciones'), 5);

            $this->ev_info_acta->set_max_columnas_por_fila(3);

            $this->ev_info_acta->set_etiqueta('nro_acta', 'Acta');
            $this->ev_info_acta->set_etiqueta('comision_nombre', 'Comisión');
            $this->ev_info_acta->set_etiqueta('libro_nombre', 'Libro');

            $this->ev_info_acta->set_etiqueta('anio_academico_periodo', 'Año Académico');
            $this->ev_info_acta->set_etiqueta('periodo_nombre', 'P. Lectivo');
            $this->ev_info_acta->set_etiqueta('modalidad_nombre', 'Modalidad');

            $this->ev_info_acta->set_etiqueta('codigo_y_nombre_actividad', 'Actividad');
            $this->ev_info_acta->set_etiqueta('nombre_ubicacion', 'Ubicación');
            $this->ev_info_acta->set_etiqueta('icono_info_escala_notas', 'Escala de Notas');

            $this->ev_info_acta->set_etiqueta('nombre_turno', 'Turno');
            $this->ev_info_acta->set_etiqueta('version', 'Código de Verificación');
            $this->ev_info_acta->set_etiqueta('observaciones', 'Observaciones');
        }
        return $this->ev_info_acta;
    }

    //-----------------------------------------------------------------------------------
    //---- form_libro -------------------------------------------------------------------
    //-----------------------------------------------------------------------------------

    function conf__form_libro(toba_ei_formulario $form) {
        if (!empty($this->s__libro_tomo)) {
            $form->set_datos(array('libro_tomo' => $this->s__libro_tomo));
        }
    }

    function evt__form_libro__modificacion($datos) {
        if (!empty($datos['libro_tomo'])) {
            $this->s__libro_tomo = $datos['libro_tomo'];
        }
    }

    //-----------------------------------------------------------------------------------
    //---- form_datos_imp ---------------------------------------------------------------
    //-----------------------------------------------------------------------------------

    function conf__form_datos_imp(toba_ei_formulario $form) {
        if (isset($this->s__datos_imp)) {
            $form->set_datos($this->s__datos_imp);
        } else {
            if (!($this->s__datos_acta['cerrada_por_docente'] == comunes::si && $this->s__datos_acta['version'] != $this->s__datos_acta['version_impresa'])) {
                $form->ef('es_copia')->set_estado(1);
            }
        }
    }

    function evt__form_datos_imp__modificacion($datos) {
        $this->s__datos_imp = $datos;
    }

    //-----------------------------------------------------------------------------------
    //---- cuadro_alumnos ---------------------------------------------------------------
    //-----------------------------------------------------------------------------------

    function conf__cuadro_alumnos(toba_ei_cuadro $cuadro) {
        $msj = guarani_mensajes::get($this->indx_msg_eof, array('Alumnos asociados al Acta'));
        $cuadro->set_eof_mensaje($msj);
        if ($this->es_acta_regulares()) {
            $instancia = comision::inst_cursar;
            // Parámetros para la consulta
            $params_sistema = array(
                'cur_orden_detalle_actas' => guarani::parametros()->get_valor('cur_orden_detalle_actas'),
            );
        } else {
            $this->dep('cuadro_alumnos')->eliminar_columnas(array('cond_regularidad_nombre'));
            $instancia = comision::inst_promocionar;
            // Parámetros para la consulta
            $params_sistema = array(
                'cur_inserta_alumnos_no_promocionales_en_acta' => guarani::parametros()->get_valor('cur_inserta_alumnos_no_promocionales_en_acta'),
                'cur_orden_detalle_actas' => guarani::parametros()->get_valor('cur_orden_detalle_actas'),
            );
        }

        if ($this->es_acta_anulada() || $this->es_acta_cerrada()) {
            $instancia = null;
        }

        if (!isset($this->s__datos_alumnos)) {
            $datos = toba::consulta_php('co_actas')->get_alumnos_acta_cursadas($this->get_acta_seleccionada(), $instancia, '', '', '', false, $params_sistema);
            foreach ($datos as $key => $dato) {
                if ((isset($dato['estado'])) && ($dato['estado'] == acta::detalle_estado_invalidado)) {
                    $datos[$key]['apellido_y_nombre'] .= ' === REGISTRO INVALIDADO ===';
                }
            }
            $this->s__datos_alumnos = $datos;
        }

        $lineas_folio = $this->s__datos_acta['renglones_folio'];
        $cuadro->set_tamanio_pagina($lineas_folio);
        $cuadro->set_datos($this->s__datos_alumnos);
    }

    //-----------------------------------------------------------------------------------
    //---- Eventos ----------------------------------------------------------------------
    //-----------------------------------------------------------------------------------

    function evt__cancelar_preseleccion() {
        unset($this->s__comision_seleccionada);
        toba::zona()->resetear();
        $this->set_pantalla('seleccion_comision');
    }

    function evt__cancelar() {
        if (!empty($this->s__operacion_a_volver) && !empty($this->s__celda_a_restaurar)) {
            echo toba_js::abrir();
            echo "document.location.href='" .
            toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), $this->s__operacion_a_volver, array('volvio_imp' => true, 'id_acta' => $this->s__acta_seleccionada['id_acta']), array('celda_memoria' => $this->s__celda_a_restaurar)) . "'\n";
            echo toba_js::cerrar();
        } else {
            unset($this->s__acta_seleccionada);
            unset($this->s__datos_acta);
            unset($this->s__libro_tomo);
            unset($this->s__datos_imp);
            unset($this->s__datos_alumnos);
            $this->s__imprimir = false;
            $this->set_pantalla('seleccion_acta');
        }
    }

    function evt__pdf() {
        $estado_acta = $this->s__datos_acta['estado'];
        $datos = [];

        /*
         * Mejoras en la operación -Inscribir Automáticamente a Ingresantes-
         * @see https://redmine.siu.edu.ar/redmine/issues/34188 Ticket #34188
         * Verifico el estado del acta.
         */
        switch ($estado_acta) {
            //Siempre se imprime lo que tiene la cabecera, y sino tiene datos,
            //entonces se asume que se cerro con firma digital.
            case acta::estado_cerrada :
                $default_valor = 0;
                $actualizar_impresion = true;
                if ($this->s__utiliza_acta_digital == comunes::si) {
                    $default_valor = acta::libro_no_aplica;
                    //No se actualiza impresion si se esta usando firma digital
                    $actualizar_impresion = false;
                }
                $datos = $this->datos_alumnos($default_valor, $actualizar_impresion);
                break;

            //Cuando el acta esta abierta o anulada debo verificar el 
            //parametro de firma digital.
            case acta::estado_abierta:
            case acta::estado_anulada:
                $default_valor = 0;
                if ($this->s__utiliza_acta_digital == comunes::no) {
                    $datos = $this->datos_alumnos($default_valor);
                } else {
                    // Completo los datos del folio, libro y tomo con valores vacíos.
                    $datos = array();
                    foreach ($this->s__datos_alumnos as $dato_alumno) {
                        $dato_alumno['folio_fisico'] = acta::libro_no_aplica;
                        $dato_alumno['libro'] = acta::libro_no_aplica;
                        $dato_alumno['tomo'] = acta::libro_no_aplica;
                        $datos[] = $dato_alumno;
                    }
                }
                break;
        }

        $i = 0;
//        ei_arbol($datos);
//        throw new toba_error('aaaa');
        while ($i < sizeof($datos)) {
            $datos[$i]['apellido_y_nombre'] = $this->sanitizar_string($datos[$i]['apellido_y_nombre']);
            $i++;
        }

        $this->s__datos_alumnos = $datos;
        $this->s__imprimir = true;
    }

    /*
     * Mejoras en la operación -Inscribir Automáticamente a Ingresantes-
     * @see https://redmine.siu.edu.ar/redmine/issues/34188 Ticket #34188
     */
    function sanitizar_string($string = '') {
        $string = str_replace("'", "`", $string);
        return $string;
    }

    function datos_alumnos($default_valor, $actualizar_datos_impresion = true) {
        if ($actualizar_datos_impresion) {
            $this->cn()->actualizar_datos_impresion($this->s__datos_acta, $this->s__carga_manual_libro, $this->s__forma_asignacion_folio_fisico, $this->s__libro_tomo);
        }

        // Se actualizan los datos del acta (puede haber cambiado la versión impresa)
        $this->s__datos_acta = toba::consulta_php('co_actas')->get_datos_acta($this->s__acta_seleccionada['id_acta']);

        // Obtengo los datos de Libro y Tomo para cada linea (alumno) ya que pueden variar para pasarselos al reporte
        $datos = array();
        foreach ($this->s__datos_alumnos as $dato_alumno) {
            $datos_folio_acta = toba::consulta_php('co_actas')->get_datos_folio_acta($this->s__datos_acta['id_acta'], $dato_alumno['folio']);
            $dato_alumno['folio_fisico'] = (isset($datos_folio_acta['folio_fisico'])) ? $datos_folio_acta['folio_fisico'] : $default_valor;
            $dato_alumno['libro'] = (isset($datos_folio_acta['nombre_libro'])) ? $datos_folio_acta['nombre_libro'] : $default_valor;
            $dato_alumno['tomo'] = (isset($datos_folio_acta['nro_tomo'])) ? $datos_folio_acta['nro_tomo'] : $default_valor;
            $datos[] = $dato_alumno;
        }
        return $datos;
    }

    function extender_objeto_js() {
        if ($this->s__imprimir) {
            $this->s__imprimir = false;
            echo "
				window.open(vinculador.get_url(null, null, 'vista_jasperreports', null, [{$this->objeto_js}._id], false, true)); 
			";
        }
    }

    // Si se modifica aquí también modificar la función 'generar_pdf_acta_cursada' de la clase 'php/nucleo/actas/acta_nucleo.php'
    function vista_jasperreports(toba_vista_jasperreports $report) {

        /**
         * Descarga desde Araí-Documentos.
         * @see https://redmine.siu.edu.ar/redmine/issues/32518 Ticket #32518
         * @see https://redmine.siu.edu.ar/redmine/issues/47649 Ticket #47649
         */
        // Si el acta esta cerrada y es digital
        if ($this->es_acta_cerrada() && $this->es_digital()) {
            $fallo = false;

            switch ($this->s__datos_acta['estado_documento_digital']) {
                // Si falló la creación del documento en SIU-Araí.
                case arai_documentos::ESTADO_DOCUMENTO_DIGITAL_FALLO:
                    $fallo = true;
                    break;
                // Si aún no se creo el documento en SIU-Araí.
                case arai_documentos::ESTADO_DOCUMENTO_DIGITAL_PENDIENTE:
                    $fallo = true;
                    break;
                // Si la creación del documento en SIU-Araí fue exitosa (incluye autorizados).
                case arai_documentos::ESTADO_DOCUMENTO_DIGITAL_EXITO:
                case arai_documentos::ESTADO_DOCUMENTO_DIGITAL_AUTORIZADO:
                    // Si no hay documento de SIU-Araí asociado
                    if (is_null($this->s__datos_acta['id_documento_digital'])) {
                        $fallo = true;
                    } else {
                        arai_documentos::descargarDocumento($this->s__datos_acta['id_documento_digital']);
                        exit;
                    }
                    break;
            }

            if ($fallo) {
                toba::logger()->error("id_documento_digital: {$this->s__datos_acta['id_documento_digital']}, estado_documento_digital: {$this->s__datos_acta['estado_documento_digital']}");
                $mensaje = guarani_mensajes::get('800_fallo_impresion_acta_cursada');
                throw guarani::error($mensaje);
            }
        }

        $nombre_parametro = 'cur_actas_';
        $titulo = 'ACTA';
        if ($this->es_rectificativa()) {
            $titulo .= ' RECTIFICATIVA ';
        }

        if ($this->es_acta_regulares()) {
            $nombre_parametro .= 'regulares';
            $path = acta::plantilla_regulares;
            $titulo .= ' DE REGULARES ';
        } else {
            $nombre_parametro .= 'promocion';
            $path = acta::plantilla_promocion;
            $titulo .= ' DE PROMOCIÓN ';
        }

        // Seteo la plantilla a usar
        $path = guarani::get_path_reporte_jasper($path);
        $report->set_path_reporte($path);

        // Cantidad de Ejemplares
        $nombre_parametro .= $this->es_acta_cerrada() ? '_cerradas' : '_abiertas';
        $nombre_parametro .= '_cnt_ejemplares';
        $cnt_ejemplares = guarani::parametros()->get_valor($nombre_parametro);

        //Institucion
        $institucion = current(toba::consulta_php('co_instituciones')->get_institucion_gestionante());
        // Es copia o acta anulada?		
        $copia = $this->es_copia() ? 'ES COPIA' : '';
        $anulada = $this->es_acta_anulada() ? 'ANULADA' : '';
        $text_aux = $copia . $anulada;
        $actividad = $this->s__datos_acta['elemento'];
        $comision = $this->s__datos_acta['comision'];
        $datos = toba::consulta_php('co_responsables_academicas')->get_ras_actividad_comision($actividad, $comision);

        // Parámetros para el Ev y las fechas de las clases para Asistencia.
        $report->set_parametro('titulo', 'S', $titulo);
        $path_logo = guarani::get_path_logo_impresion();
        $report->set_parametro('logo', 'S', $path_logo);
        $report->set_parametro('institucion', 'S', $institucion['nombre']);
        $report->set_parametro('ra_nombre', 'S', $datos['resp_acad_nombre']);
        $report->set_parametro('texto_aux', 'S', $text_aux);
        $str = (is_null($this->s__datos_acta['observaciones'])) ? '' : $this->s__datos_acta['observaciones'];
        $report->set_parametro('observaciones', 'S', $str);
        $report->set_parametro('version', 'S', $this->s__datos_acta['version']);
        $report->set_parametro('comision_nombre', 'S', $this->s__datos_acta['comision_nombre']);
        $report->set_parametro('anio_academico_periodo', 'E', $this->s__datos_acta['anio_academico_periodo']);
        $report->set_parametro('nro_acta', 'S', $this->s__datos_acta['nro_acta']);
        $report->set_parametro('codigo_y_nombre_actividad', 'S', $this->s__datos_acta['codigo_y_nombre_actividad']);
        $report->set_parametro('comision_nombre', 'S', $this->s__datos_acta['comision_nombre']);
        $report->set_parametro('periodo_nombre', 'S', $this->s__datos_acta['periodo_lectivo_nombre']);
        $report->set_parametro('nombre_ubicacion', 'S', $this->s__datos_acta['nombre_ubicacion']);
	$report->set_parametro('acta_digital', 'S', $this->s__datos_acta['digital']);
        if ($this->es_rectificativa()) {
            $nro_acta_rectificada = toba::consulta_php('co_actas')->get_nro_acta_rectificada($this->s__datos_acta['acta_referencia']);
            if (!empty($nro_acta_rectificada)) {
                $report->set_parametro('acta_referencia', 'S', $nro_acta_rectificada[0]['nro_acta_rectificada']);
            }
        }
        $report->set_parametro('fecha_inicio', 'S', $this->s__datos_acta['fecha_inicio_periodo_visual']);
        $report->set_parametro('fecha_fin', 'S', $this->s__datos_acta['fecha_fin_periodo_visual']);
        $docentes = (is_null($this->s__datos_acta['docentes'])) ? '' : $this->s__datos_acta['docentes'];
        $report->set_parametro('docentes', 'S', $docentes);

        // Se agregan los datos de los alumnos.
        $xml = $this->dep('cuadro_alumnos')->vista_xml();
        $report->set_xml(utf8_e_seguro($xml));

        // Si hay que generar mas de una copia replico el reporte
        for ($i = 0; $i < $cnt_ejemplares; $i++) {
            $report->completar_con_datos();
        }
    }

    //-----------------------------------------------------------------------------------
    //---- Operaciones auxiliares -------------------------------------------------------
    //-----------------------------------------------------------------------------------

    function es_acta_regulares() {
        return ($this->s__datos_acta['origen'] == acta::origen_regulares);
    }

    function es_acta_cerrada() {
        return ($this->s__datos_acta['estado'] == acta::estado_cerrada);
    }

    function es_acta_anulada() {
        return ($this->s__datos_acta['estado'] == acta::estado_anulada);
    }

    function es_copia() {
        return ($this->s__utiliza_acta_digital == comunes::no && $this->s__datos_acta['version_impresa'] && $this->es_acta_cerrada() && (!isset($this->s__datos_imp) || $this->s__datos_imp['es_copia']));
    }

    function es_rectificativa() {
        return ($this->s__datos_acta['tipo_acta'] == acta::tipo_rectificativa);
    }

    function es_digital()
    {
        return ($this->s__datos_acta['digital'] == comunes::si);
    }

    function usuario_puede_definir_es_copia() {
        // No se utiliza firma digital, actas cerradas y usuarios con perfiles que permitan visualizar el formulario para modificar los datos de impresión.
        return ($this->s__utiliza_acta_digital == comunes::no && $this->es_acta_cerrada() && $this->pantalla()->existe_dependencia('form_datos_imp'));
    }

    /**
     * Retorna el HTML de un vínculo (con imagen) a la operación que muestra el detalle de una escala de notas.
     */
    function get_html_icono_info_escala_notas() {
        $vinculo = toba::vinculador()->get_url(toba_proyecto::get_id(), '37000054', array('escala' => $this->s__datos_acta['escala_nota']), array('celda_memoria' => 'escala_notas'));
        $javascript = "abrir_popup('info','$vinculo',{'width':'450','height':'420'})";

        $salida = "<a class='icono-utileria' href='#' onclick=\"$javascript\">";
        $salida .= toba_recurso::imagen_toba('info_chico.gif', true, null, null, 'Ver detalle de escala');
        $salida .= '</a>';

        return $salida;
    }

}
