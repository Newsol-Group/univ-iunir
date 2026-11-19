<?php
class ci_edi_imprimir_acta_examen extends ci_impresion
{
    protected $s__id_acta;
    protected $s__datos_acta;
    protected $s__datos_alumnos;
    protected $s__imprimir = false;
    protected $s__titulo;
    // Selección manual de libro_tomo.
    protected $s__libro_tomo;
    // Selección de impresión como copia
    protected $s__datos_imp_copia;

    function ini() {
        toba::memoria()->desactivar_reciclado();
        parent::ini();

        $this->s__id_acta = $this->controlador()->get_acta_seleccionada();
        $this->s__datos_acta = $this->controlador()->get_datos_acta();

        $this->s__titulo = 'ACTA';
        if ($this->es_rectificativa()) {
            $this->s__titulo .= ' RECTIFICATIVA DE EXAMEN';
        } else {
            $this->s__titulo .= ' DE EXAMEN';
        }
    }

    function get_libros_tomos_actas() {
        $datos_acta = $this->controlador()->get_datos_acta();
        return toba::consulta_php('co_actas')->get_libros_tomos_actas($datos_acta);
    }

    //-----------------------------------------------------------------------------------
    //---- PANTALLA INICIAL -------------------------------------------------------------
    //-----------------------------------------------------------------------------------
    //-----------------------------------------------------------------------------------
    //---- form_libro -------------------------------------------------------------------
    //-----------------------------------------------------------------------------------

    function conf__form_libro(toba_ei_formulario $form) {
        $datos = array();
        $datos['observaciones'] = $this->s__datos_acta['observaciones'];
        if (!empty($this->s__libro_tomo)) {
            $datos['libro_tomo'] = $this->s__libro_tomo;
        }
        $form->set_datos($datos);

        $form->set_solo_lectura('observaciones');
        if (!acta::solicitar_libro_impresion($this->s__datos_acta['id_acta'], guarani_arreglos::count($this->s__datos_alumnos), true)) {
            $form->desactivar_efs(array('libro_tomo'));
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
        if (isset($this->s__datos_imp_copia)) {
            $form->set_datos($this->s__datos_imp_copia);
        } else {
            if (!($this->s__datos_acta['cerrada_por_docente'] == comunes::si && $this->s__datos_acta['version'] != $this->s__datos_acta['version_impresa'])) {
                $form->ef('es_copia')->set_estado(1);
            }
        }
    }

    function evt__form_datos_imp__modificacion($datos) {
        $this->s__datos_imp_copia = $datos;
    }

    //-----------------------------------------------------------------------------------
    //---- Auxiliares -------------------------------------------------------------------
    //-----------------------------------------------------------------------------------
    /**
     * Función invocada desde el ci de navegación para limpiar la memoria de las variables utilizadas en la edición.
     */
    function resetear_variables() {
        unset($this->s__id_acta);
        unset($this->s__datos_acta);
        unset($this->s__datos_alumnos);
        $this->s__imprimir = false;
        unset($this->s__titulo);
        unset($this->s__libro_tomo);
        unset($this->s__datos_imp_copia);
    }

    function es_rectificativa() {
        return ($this->s__datos_acta['tipo_acta'] == acta::tipo_rectificativa);
    }

    function es_copia() {
        return ($this->s__datos_acta['digital'] == comunes::no &&
                $this->s__datos_acta['version_impresa'] &&
                $this->es_acta_cerrada() &&
                (!isset($this->s__datos_imp_copia) || $this->s__datos_imp_copia['es_copia']));
    }

    function usuario_puede_definir_es_copia() {
        // No se utiliza firma digital, actas cerradas y usuarios con perfiles que permitan visualizar el formulario para modificar los datos de impresión.
        return ($this->s__datos_acta['digital'] == comunes::no && $this->es_acta_cerrada() && $this->pantalla()->existe_dependencia('form_datos_imp'));
    }

    function es_acta_cerrada() {
        return ($this->s__datos_acta['estado'] == acta::estado_cerrada);
    }

    function es_acta_anulada() {
        return ($this->s__datos_acta['estado'] == acta::estado_anulada);
    }

    function es_digital() {
        return ($this->s__datos_acta['digital'] == comunes::si);
    }

    function get_datos_alumnos() {
        return $this->s__datos_alumnos;
    }

    //-----------------------------------------------------------------------------------
    //---- alumnos ----------------------------------------------------------------------
    //-----------------------------------------------------------------------------------

    function conf__alumnos(toba_ei_cuadro $cuadro) {
        //Seteo el tamaño de página del cuadro
        $lineas_folio = $this->s__datos_acta['renglones_folio'];
        $estado = $this->s__datos_acta['estado'];
        $cuadro->set_tamanio_pagina($lineas_folio);

        //Seteo los datos del cuadro
        if (!isset($this->s__datos_alumnos)) {
            $datos = $this->cn()->get_alumnos_acta_examen($this->s__datos_acta['id_acta'], $this->s__datos_acta['estado']);
            foreach ($datos as $key => $dato) {
                if ((isset($dato['estado'])) && ($dato['estado'] == acta::detalle_estado_invalidado)) {
                    $datos[$key]['persona'] .= ' === REGISTRO INVALIDADO ===';
                }
            }
            $this->s__datos_alumnos = $datos;
        }
        $cuadro->set_datos($this->s__datos_alumnos);
    }

    //-----------------------------------------------------------------------------------
    //---- Configuraciones --------------------------------------------------------------
    //-----------------------------------------------------------------------------------
    // Si se modifica aquí también modificar la función 'generar_pdf_acta_examen' de la clase 'php/nucleo/actas/acta_nucleo.php'
    function vista_jasperreports(toba_vista_jasperreports $report) {

        /**
         * Descarga desde Araí-Documentos.
         * @see https://redmine.siu.edu.ar/redmine/issues/30923 Ticket #30923
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
                $mensaje = guarani_mensajes::get('800_fallo_impresion_acta_examen');
                throw guarani::error($mensaje);
            }
        }

        // Seteo la plantilla a usar
        $path = guarani::get_path_reporte_jasper(acta::plantilla_examenes);
        $report->set_path_reporte($path);

        // Cantidad de Ejemplares
        $nombre_parametro = $this->es_acta_cerrada() ? 'exa_actas_examen_cerradas_cnt_ejemplares' : 'exa_actas_examen_abiertas_cnt_ejemplares';
        $cnt_ejemplares = guarani::parametros()->get_valor($nombre_parametro);

        //Institucion
        $institucion = current(toba::consulta_php('co_instituciones')->get_institucion_gestionante());
        $datos_encabezado = $this->controlador()->ev_info()->get_datos(false);
        $actividad = $datos_encabezado['elemento'];
        $llamado = $datos_encabezado['llamado_mesa'];
        $datos = toba::consulta_php('co_responsables_academicas')->get_ras_actividad_llamado($actividad, $llamado);
        // Es copia o acta anulada?		
        $anulada = $this->es_acta_anulada() ? 'ANULADA' : '';
        $copia = $this->es_copia() ? 'ES COPIA' : '';
        $texto = $copia . $anulada;
        // Parámetros para el Ev y las fechas de las clases para Asistencia.
        $report->set_parametro('titulo', 'S', $this->s__titulo);
        $path_logo = guarani::get_path_logo_impresion();
        $report->set_parametro('logo', 'S', $path_logo);
        $report->set_parametro('institucion', 'S', $institucion['nombre']);
        $report->set_parametro('ra_nombre', 'S', $datos['resp_acad_nombre']);
        $report->set_parametro('texto_aux', 'S', $texto);
        $str = (is_null($this->s__datos_acta['observaciones'])) ? '' : $this->s__datos_acta['observaciones'];
        $report->set_parametro('observaciones', 'S', $str);
        $report->set_parametro('nro_acta', 'S', $datos_encabezado['nro_acta']);
        $report->set_parametro('fecha', 'S', $datos_encabezado['fecha_formato_visual']);
        $report->set_parametro('rectifica_a', 'S', ($this->es_rectificativa()) ? $datos_encabezado['rectifica_a'] : "null");
        $report->set_parametro('version', 'S', $datos_encabezado['version']);
        $report->set_parametro('codigo_nombre_actividad', 'S', $datos_encabezado['cod_nombre_actividad']);
        $str = (is_null($datos_encabezado['turno_nombre'])) ? ' - ' : $datos_encabezado['turno_nombre'];
        $report->set_parametro('turno_examen', 'S', $str);
        $report->set_parametro('mesa', 'S', $datos_encabezado['mesa_nombre']);
        $str = (is_null($datos_encabezado['llamado_nombre'])) ? ' - ' : $datos_encabezado['llamado_nombre'];
        $report->set_parametro('nombre_llamado', 'S', $str);
        $report->set_parametro('anio_academico', 'E', $datos_encabezado['anio_academico']);
        $report->set_parametro('nombre_ubicacion', 'S', $datos_encabezado['ubicacion_nombre']);	
	$report->set_parametro('acta_digital', 'S', $this->s__datos_acta['digital']);
        // Docentes y Roles (Maximo 3 por acta)
		// le agrego un parametro para saber que se va a imprimir en un documento oficial y debe resperat el parametro
        $docentes_actas   = toba::consulta_php('co_mesas_de_examen')->get_docentes_mesa($this->s__datos_acta['llamado_mesa'],true);
        $docentes         = "";
        for ($i = 0; $i < 3; $i++) {
            $rol            = '';
            $docente_nombre = "";
            if (isset($docentes_actas[$i]['docente_nombre'])) {
                $docente_nombre = $docentes_actas[$i]['docente_nombre'];
                $rol            = $docentes_actas[$i]['nombre'];
                $docentes       .= "{$docente_nombre} ({$rol}) ";
            }

            $par = 'docente_'.($i + 1);
            $report->set_parametro($par, 'S', $docente_nombre);
            $par = 'rol_'.($i + 1);
            $report->set_parametro($par, 'S', $rol);
        }
        $report->set_parametro('docentes', 'S', $docentes);
        //Se agregan los datos de los alumnos
        $xml = $this->dep('alumnos')->vista_xml();
        $report->set_xml(utf8_e_seguro($xml));

        // Si hay que generar mas de una copia replico el reporte
        for ($i = 0; $i < $cnt_ejemplares; $i++) {
            $report->completar_con_datos();
        }
    }

    //-----------------------------------------------------------------------------------
    //---- Eventos ----------------------------------------------------------------------
    //-----------------------------------------------------------------------------------

    function evt__pdf() {
        $utiliza_acta_digital = $this->s__datos_acta['digital'];
        $estado_acta = $this->s__datos_acta['estado'];
        $datos = [];

        /*
         * Mejoras en la operación -Inscribir Automáticamente a Ingresantes-
         * @see https://redmine.siu.edu.ar/redmine/issues/34188 Ticket #34188
         */
        switch ($estado_acta) {
            //Siempre se imprime lo que tiene la cabecera, y sino tiene datos,
            //entonces se asume que se cerro con firma digital.
            case acta::estado_cerrada :
                $default_valor = 0;
                $actualizar_impresion = true;
                if ($utiliza_acta_digital == comunes::si) {
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
                if ($utiliza_acta_digital == comunes::no) {
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
            $datos[$i]['persona'] = $this->sanitizar_string($datos[$i]['persona']);
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
        $carga_manual_libro = guarani::parametros()->get_valor('exa_carga_manual_libro_examen');
        $forma_asignacion_folio_fisico = guarani::parametros()->get_valor('exa_forma_asignacion_folio_fisico');
        $agrega_folios_fisicos_extra = guarani::parametros()->get_valor('exa_agrega_folios_fisicos_extra');

        if ($actualizar_datos_impresion) {
            $this->cn()->actualizar_datos_impresion($this->s__datos_acta, guarani_arreglos::count($this->s__datos_alumnos), $carga_manual_libro, $forma_asignacion_folio_fisico, $agrega_folios_fisicos_extra, $this->s__libro_tomo);
        }

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

    //-----------------------------------------------------------------------------------
    //---- JAVASCRIPT -------------------------------------------------------------------
    //-----------------------------------------------------------------------------------

    function extender_objeto_js() {
        if ($this->s__imprimir) {
            $this->s__imprimir = false;
            echo "
				window.open(vinculador.get_url(null, null, 'vista_jasperreports', null, [{$this->objeto_js}._id], false, true)); 
			";
        }
    }

}
