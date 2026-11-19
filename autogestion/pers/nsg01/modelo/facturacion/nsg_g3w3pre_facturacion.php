<?php

namespace nsg01\modelo\facturacion;

use siu\modelo\g3\g3;
use SIU\Chulupi\kernel;

require_once __DIR__ . '/EncryptService.php';


class nsg_g3w3pre_facturacion extends nsg_facturacion
{
    const ALUMNO_PRUEBA = 1420;
    const TIPO_DOC_PRUEBA = 0;
    const NRO_DOC_PRUEBA = '31116453';
    const PROPUESTA_PRUEBA = 1;
    const UBICACION_PRUEBA = 1;

    public function get_propuestas_sin_arancel_insc() {
        $propuestas = \toba::consulta_php('co_facturacion')->get_propuestas_sin_arancel_inscripcion();
        return array_column($propuestas, 'propuesta');
    }

    public function get_referenciasPagos($parametros)
    {
        $preinscripcion = $parametros['preinscripcion'] ?? null;
        $datos_pagos = \toba::consulta_php('co_facturacion')->get_datos_pagos_guarani($preinscripcion, 'g3w3pre');
        return $datos_pagos;
    }

    function get_estado_pago($datos_pagos, $preinscripcion)
    {
        foreach ($datos_pagos as $dato_pago) {
            //if ($dato_pago['preinscripcion'] == $preinscripcion && $dato_pago['estado_pago'] != 3)
            //    continue;

            if ($dato_pago['preinscripcion'] == $preinscripcion) {
                return [$dato_pago['estado_pago'], $dato_pago['fecha_pago']]; //realizada = 3
            }
        }

        return [];
    }

    private function get_tipo_documento($parametros)
    {
        return ($this->es_ambiente_prod) ?
            kernel::persona()->get_tipo_documento() :
            $parametros['tipo_documento'] ?? self::TIPO_DOC_PRUEBA;
    }

    private function get_nro_documento($parametros)
    {
        return ($this->es_ambiente_prod) ?
            kernel::persona()->get_nro_documento() :
            $parametros['nro_documento'] ?? self::NRO_DOC_PRUEBA;
    }

    private function preparar_parametros($parametros)
    {
        /*
        $tipo_documento = ($this->es_ambiente_prod) ? 
                            kernel::persona()->get_tipo_documento() :   
                            $parametros['tipo_documento'] ?? self::TIPO_DOC_PRUEBA;

        $nro_documento = ($this->es_ambiente_prod) ? 
                            kernel::persona()->get_nro_documento() : 
                            $parametros['nro_documento'] ?? self::NRO_DOC_PRUEBA;
        */

        $tipo_documento = $this->get_tipo_documento($parametros);
        $nro_documento = $this->get_nro_documento($parametros);

        $alumno = ($this->es_ambiente_prod) ?
            kernel::persona()->get_id_alumno_activo() :
            $parametros['alumno'] ?? self::ALUMNO_PRUEBA;

        $datos_preinscripciones = $parametros['datos_preinscripciones'] ?? null;

        if (is_null($tipo_documento) || is_null($nro_documento) || is_null($alumno) || is_null($datos_preinscripciones)) return null;

        return [
            'tipo_documento' => $tipo_documento,
            'nro_documento' => $nro_documento,
            'alumno' => $alumno,
            'datos_preinscripciones' => $datos_preinscripciones,
            'forzar_propuesta' => $parametros['forzar_propuesta'] ?? null,
            'forzar_ubicacion' => $parametros['forzar_ubicacion'] ?? null
        ];
    }

    public function get_datosFacturacion($parametros)
    {
        $softlandConn = $this->get_softlandConnection();

        if (!$softlandConn) {
            kernel::log()->add_error('Error al conectar a Softland');
            return false;
        }

        sqlsrv_query($softlandConn, 'SET NOCOUNT ON;');

        //         sqlsrv_query($softlandConn, "
        //     SET NOCOUNT ON;
        //     SET ANSI_NULLS ON;
        //     SET ANSI_WARNINGS ON;
        //     SET QUOTED_IDENTIFIER ON;
        //     SET CONCAT_NULL_YIELDS_NULL OFF;  -- <==== CLAVE
        // ");

        $parametros = $this->preparar_parametros($parametros);

        $datos_preinscripciones = $parametros['datos_preinscripciones'] ?? null;
        $alumno = $parametros['alumno'] ?? null;

        //if (is_null($tipo_documento) || is_null($nro_documento) || is_null($alumno) || is_null($datos_preinscripciones)) return null;

        $mcp_data = $this->get_mcpData();
        $matriculas = [];

        foreach ($datos_preinscripciones as $dato_preinsc) {
            $preinsc = $dato_preinsc['preinscripcion'];
            $datos_pagos = $this->get_referenciasPagos([
                'preinscripcion' => $preinsc
            ]);
            $estado_pago = $this->get_estado_pago($datos_pagos, $preinsc);
            $estado_pago_desc = $this->get_estadoPagoDescripcion($estado_pago[0], 'g3w3pre');

            $matriculas[$preinsc]['fecha_pago'] = $estado_pago[1] ?? null;
            $matriculas[$preinsc]['estado_pago'] = $estado_pago[0];
            $matriculas[$preinsc]['estado_pago_desc'] = $estado_pago_desc;
            //$matriculas[$preinsc]['link_err'] = true;

            if ($estado_pago[0] == 3) continue;

            $parametros['ubicacion'] = $parametros['forzar_ubicacion'] ?? $dato_preinsc['ubicacion'];
            $parametros['propuesta'] = $parametros['forzar_propuesta'] ?? $dato_preinsc['propuesta'];
            $parametros['codcpt'] = 18;

            $stmt = $this->get_SoftlandSP($softlandConn, $parametros);

            if (!$stmt) {
                kernel::log()->add_error('get_datosFacturacion -> Error al preparar la consulta');
                return false;
            }

            if (!sqlsrv_execute($stmt)) {
                kernel::log()->add_error('get_datosFacturacion -> Error al ejecutar la consulta');
                return false;
            }

            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            //kernel::log()->add_debug('Fila obtenida de Softland para preinscripción ' . $preinsc . ': ', print_r($row, true));

            //if ($row === null) continue;

            //$link_err = empty($row['IMPNAC']) || $row['STATUS'] !== 'OK';
            //if ($link_err) continue;

            $monto = $row['IMPNAC'];
            $nrofor = $row['NROFOR'] ?? null;

            $this->act_referenciaSoftland([
                'nrofor' => $nrofor,
                'alumno' => $alumno,
                'preinscripcion' => $preinsc
            ]);

            $matriculas[$preinsc]['link_err'] = false;//$link_err;
            $matriculas[$preinsc]['saldo'] = $row['IMPNAC'] ?? 0;
            $matriculas[$preinsc]['link'] = $row['LINK'] ?? '';
            $matriculas[$preinsc]['codfor'] = $row['CODFOR'] ?? null;
            $matriculas[$preinsc]['nrofor'] = $nrofor;
            $matriculas[$preinsc]['codpro'] = $row['grcfor_descrp'] ?? null;
            $matriculas[$preinsc]['monto'] = EncryptService::EncryptString(
                $monto,
                $mcp_data['key']
            );
            $matriculas[$preinsc]['hash'] = $this->get_mcpHash(
                $mcp_data['key'],
                $mcp_data['comercio'],
                '',
                $monto
            );
            $matriculas[$preinsc]['ref_data'] = $this->get_mcpInfoAdicional(
                [
                    'preinscripcion' => $preinsc,
                    'codapl' => $row['CODFOR'], //$dato_preinsc['ubicacion'],
                    'nroapl' => $nrofor //$dato_preinsc['propuesta']
                ]
            );
            //$matriculas[$preinsc]['preinsc'] = $preinsc;
        }

        return $matriculas;
    }

    public function get_mcpTransId($parametros) //$codfor, $nrofor, $tipo_documento, $nro_documento
    {
        $codfor = $parametros['codfor'] ?? null;
        $nrofor = $parametros['nrofor'] ?? null;

        $tipo_documento = $this->get_tipo_documento($parametros);
        $nro_documento = $this->get_nro_documento($parametros);

        return
            $codfor . '_' .
            $nrofor . '_' .
            date("Y-m-d H:i:s") . '_' .
            $tipo_documento . '_' .
            $nro_documento;
    }

    public function act_referenciaSoftland($parametros)
    {
        $nrofor = $parametros['nrofor'] ?? null;
        $alumno = $parametros['alumno'] ?? null;
        $preinscripcion = $parametros['preinscripcion'] ?? null;

        if (is_null($nrofor) || is_null($alumno) || is_null($preinscripcion)) return;

        $sql = "call negocio_nsg.sp_actualizar_referencia_softland
                (
                    '$nrofor', 
                    $alumno, 
                    $preinscripcion
                )";

        kernel::db()->ejecutar($sql);
    }

    public function get_softlandConnection()
    {
        return \toba::consulta_php('co_facturacion')->get_softland_conn();
    }

    public function get_mcpInfoAdicional($parametros)
    {
        $preinscripcion = $parametros['preinscripcion'] ?? null;
        $codapl = $parametros['codapl'] ?? null;
        $nroapl = $parametros['nroapl'] ?? null;

       // if (is_null($preinscripcion) || is_null($codapl) || is_null($nroapl)) return null;

        $info = [
            'origen' => 'g3w3pre',
            'preinscripcion' => $preinscripcion,
            'codapl' => $codapl,
            'nroapl' => $nroapl
        ];
        $jinfo = json_encode($info);
        return EncryptService::EncryptString($jinfo, $this->get_mcpKey());
    }

    public function get_SoftlandSP(&$softlandConn, &$parametros)
    {

        $sql = $this->get_SoftlandSPFirma();

        // usar refs DIRECTAS al array del caller (no variables locales)
        $params = [
            [&$parametros['tipo_documento'], SQLSRV_PARAM_IN],
            [&$parametros['nro_documento'],  SQLSRV_PARAM_IN],
            [&$parametros['ubicacion'],      SQLSRV_PARAM_IN],
            [&$parametros['propuesta'],      SQLSRV_PARAM_IN],
            [&$parametros['codcpt'],         SQLSRV_PARAM_IN],
        ];

        // kernel::log()->add_debug('get_SoftlandSP -> Preparando consulta SP: ', print_r($params, true));

        $stmt = sqlsrv_prepare($softlandConn, $sql, $params);

        if (!$stmt) {
            kernel::log()->add_error('get_SoftlandSP -> Error al preparar la consulta');
            return false;
        }

        //kernel::log()->add_debug('get_SoftlandSP stmt prepared: ', print_r($stmt, true));

        return $stmt;
    }
}
