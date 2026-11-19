<?php
namespace iunir\modelo\g3\consultas_bd;
use siu\modelo\g3\g3;
use SIU\Chulupi\kernel;
// require_once 'EncryptService.php';

require_once __DIR__ . '/EncryptService.php';

class nsg_preinsc_facturacion extends nsg_facturacion
{
    const TIPO_DOC_PRUEBA = 22; 
    const NRO_DOC_PRUEBA = '0201191384';
    const PROPUESTA_PRUEBA = 1;
    const UBICACION_PRUEBA = 1; 

    public function __construct($parametros)
    {
        parent::__construct($parametros);
    }

    public function get_referenciasPagos($parametros)
    {
        $id_preinscripcion = $parametros['id_preinscripcion'] ?? null;
        $preinscripcion_propuesta = $parametros['preinscripcion_propuesta'] ?? null;

        if (is_null($id_preinscripcion) || is_null($preinscripcion_propuesta)) return null;

        $sql = "SELECT estado_pago, to_char(fecha, 'DD/MM/YYYY') as fecha_pago 
		        FROM nsg_referencias_pagos
		        WHERE id_preinscripcion = $id_preinscripcion
                AND preinscripcion_propuesta = $preinscripcion_propuesta";

        $datos_pago = kernel::db()->consultar($sql);
        return [
            'estado_pago' => $datos_pago[0]['estado_pago'] ?? null,
            'fecha_pago' => $datos_pago[0]['fecha_pago'] ?? null
        ] ?? null;
    }

    public function act_referenciaSoftland($parametros)
    {
        $nrofor = $parametros['nrofor'] ?? null;
        $id_preinscripcion = $parametros['id_preinscripcion'] ?? null;
        $preinscripcion_propuesta = $parametros['preinscripcion_propuesta'] ?? null;

        if (is_null($nrofor) || is_null($id_preinscripcion) || is_null($preinscripcion_propuesta)) return;

        $sql = "CALL sp_actualizar_referencia_softland
                (
                    '$nrofor', 
                    $id_preinscripcion, 
                    $preinscripcion_propuesta
                )";

        kernel::db()->ejecutar($sql);
    }

    public function get_softlandConnection()
    {
        $softland_server = g3::parametros()->get_valor('softland_server');
        $softland_db = g3::parametros()->get_valor('softland_db');
        $softland_uid = g3::parametros()->get_valor('softland_uid');
        $softland_pass = g3::parametros()->get_valor('softland_pass');

        $connectionOptions = array(
            'Database' => $softland_db,
            'Uid' => $softland_uid,
            'PWD' => $softland_pass
        );

        return sqlsrv_connect($softland_server, $connectionOptions);
    }

    public function get_mcpInfoAdicional($parametros)
    {
        $id_preinscripcion = $parametros['id_preinscripcion'] ?? null;
        $preinscripcion_propuesta = $parametros['preinscripcion_propuesta'] ?? null;
        $codapl = $parametros['codapl'] ?? null;
        $nroapl = $parametros['nroapl'] ?? null;

        // if (
        //     is_null($id_preinscripcion) || is_null($preinscripcion_propuesta) ||
        //     is_null($codapl) || is_null($nroapl)
        // ) return null;

        $info = [
            'origen' => 'preinsc',
            'id_preinscripcion' => $id_preinscripcion,
            'preinscripcion_propuesta' => $preinscripcion_propuesta,
            'codapl' => $codapl,
            'nroapl' => $nroapl
        ];

        $jinfo = json_encode($info);
        return EncryptService::EncryptString($jinfo, $this->get_mcpKey());
    }

    public function get_SoftlandSP(&$softlandConn, &$parametros)
    {
        $tipo_documento = &$parametros['tipo_documento'] ?? null;
        $nro_documento = &$parametros['nro_documento'] ?? null;
        $ubicacion = &$parametros['ubicacion'] ?? null;
        $propuesta = &$parametros['propuesta'] ?? null;
        $codcpt = &$parametros['codcpt'] ?? null;

        kernel::log()->add_debug('get_SoftlandSP -> Params: ', [
            'tipo_documento' => $tipo_documento,
            'nro_documento' => $nro_documento,
            'ubicacion' => $ubicacion,
            'propuesta' => $propuesta,
            'codcpt' => $codcpt
        ]);

        //$sql = "{CALL emp_iunir.dbo.STR_INS_VTMPPH_MATRICULA(?, ?, ?, ?, ?)}"; //desa
		$sql = $this->get_SoftlandSPFirma();
        kernel::log()->add_debug('get_SoftlandSP -> SQL: ' , $sql);
        $params = [
			[&$tipo_documento, SQLSRV_PARAM_IN],
			[&$nro_documento,    SQLSRV_PARAM_IN],
			[&$ubicacion,        SQLSRV_PARAM_IN],
			[&$propuesta,        SQLSRV_PARAM_IN],
			[&$codcpt,           SQLSRV_PARAM_IN]
		];
		
        $stmt = sqlsrv_prepare($softlandConn, $sql, $params);

        if (!$stmt) {
            kernel::log()->add_error('get_SoftlandSP -> Error al preparar la consulta');
            return false;
        }

        return $stmt;
    }

    private function get_tipo_documento($parametros) {
        return ($this->es_ambiente_prod) ? 
                    kernel::persona()->datos()->get_tipo_doc() :   
                    $parametros['tipo_documento'] ?? self::TIPO_DOC_PRUEBA;
    }
    private function get_nro_documento($parametros) {
        return ($this->es_ambiente_prod) ? 
                    kernel::persona()->datos()->get_nro_documento() : 
                    $parametros['nro_documento'] ?? self::NRO_DOC_PRUEBA;
    }

    public function preparar_parametros($parametros)
    {
        $tipo_documento = $this->get_tipo_documento($parametros);
        $nro_documento = $this->get_nro_documento($parametros);

        $datos_preinscripciones = $parametros['datos_preinscripciones'] ?? null;

        return [
            'tipo_documento' => $tipo_documento,
            'nro_documento' => $nro_documento,
            'datos_preinscripciones' => $datos_preinscripciones, 
            'forzar_propuesta' => $parametros['forzar_propuesta'] ?? null,
            'forzar_ubicacion' => $parametros['forzar_ubicacion'] ?? null,
            'codcpt'=> 18 
        ];
    }

	public function get_mcpTransId($parametros) 
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

    public function get_datosFacturacion($parametros)
    {
        $softlandConn = $this->get_softlandConnection();

        if (!$softlandConn) {
			kernel::log()->add_error('get_datos_softland -> Error al conectar a Softland');
            return false;
        }

		sqlsrv_query($softlandConn, 'SET NOCOUNT ON;');

        $parametros = $this->preparar_parametros($parametros);
     
        $datos_preinscripciones = $parametros['datos_preinscripciones'] ?? null;
        if (is_null($datos_preinscripciones)) {
            $datos_preinscripciones = kernel::persona()->datos()->get_datos_inscripciones();
        }

        $stmt = $this->get_SoftlandSP($softlandConn, $parametros);
        if (!$stmt) {
            kernel::log()->add_error('get_datosFacturacion -> Error al preparar la consulta');
            return false;
        }

        $mcp_data = $this->get_mcpData();

        $matriculas = [];

        foreach($datos_preinscripciones as $dato_preinsc) 
        {
            $id_preinsc = $dato_preinsc['id_preinscripcion'];
            $preinsc_propuesta = $dato_preinsc['preinscripcion_propuesta'];
            $datos_pagos = $this->get_referenciasPagos([
                'id_preinscripcion' => $id_preinsc,
                'preinscripcion_propuesta' => $preinsc_propuesta
            ]);

            $parametros['ubicacion'] = $parametros['forzar_ubicacion'] ?? $dato_preinsc['ubicacion'];
            $parametros['propuesta'] = $parametros['forzar_propuesta'] ?? $dato_preinsc['propuesta'];

            //kernel::log()->add_debug('Calling Softland SP with params: ', print_r($parametros, true));

            if (!sqlsrv_execute($stmt)) {
                kernel::log()->add_error('get_datosFacturacion -> Error al ejecutar la consulta');
                return false;
            }

            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            if ($row === null) continue;

            $link_err = empty($row['IMPNAC']) || $row['STATUS'] !== 'OK';
			if ($link_err) continue;

			$nrofor = $row['NROFOR'] ?? null;
            $this->act_referenciaSoftland([
                'nrofor' => $nrofor,
                'id_preinscripcion' => $id_preinsc,
                'preinscripcion_propuesta' => $preinsc_propuesta
            ]);

            $monto = $row['IMPNAC'];
			$estado_pago = $datos_pagos['estado_pago'] ?? null;

			$matriculas[$preinsc_propuesta]['fecha_pago'] = $datos_pagos['fecha_pago'] ?? null;
			$matriculas[$preinsc_propuesta]['estado_pago'] = $estado_pago;
			$matriculas[$preinsc_propuesta]['estado_pago_desc'] = $this->get_estadoPagoDescripcion($estado_pago);
			$matriculas[$preinsc_propuesta]['id_preinsc'] = $id_preinsc;
			$matriculas[$preinsc_propuesta]['link_err'] = $link_err;
			$matriculas[$preinsc_propuesta]['saldo'] = $row['IMPNAC'] ?? 0;
			$matriculas[$preinsc_propuesta]['link'] = $row['LINK'] ?? '';
			$matriculas[$preinsc_propuesta]['codfor'] = $row['CODFOR'] ?? null;
			$matriculas[$preinsc_propuesta]['nrofor'] = $nrofor;
			$matriculas[$preinsc_propuesta]['codpro'] = $row['grcfor_descrp'] ?? null;
			$matriculas[$preinsc_propuesta]['monto'] = EncryptService::EncryptString
            (
				$monto,
				$mcp_data['key']
			);
			$matriculas[$preinsc_propuesta]['hash'] = $this->get_mcpHash
            (
				$mcp_data['key'],
				$mcp_data['comercio'],
				'',
				$monto
			);
			$matriculas[$preinsc_propuesta]['ref_data'] = $this->get_mcpInfoAdicional
            (
                [
                    'id_preinscripcion' => $id_preinsc,
                    'preinscripcion_propuesta' => $preinsc_propuesta,
                    'codapl' => $row['CODFOR'],
                    'nroapl' => $nrofor
                ]
			);
        }

		sqlsrv_free_stmt($stmt);
        return $matriculas;
    }
}
