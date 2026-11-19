<?php

namespace nsg01\modelo\facturacion;

use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;

require_once __DIR__ . '/EncryptService.php';

class nsg_g3w3_facturacion extends nsg_facturacion
{
    const ALUMNO_PRUEBA = 1420; //1420; //1692; //14;

    public function get_referenciasPagos($parametros)
    {
        return \toba::consulta_php('co_facturacion')->get_datos_pagos_guarani($parametros['alumno'], 'g3w3');
    }
    
    function get_estado_pago($datos_pagos_guarani, $codapl, $nroapl) {
		foreach ($datos_pagos_guarani as $pago) {
			if ($pago['codapl'] == $codapl && $pago['nroapl'] == $nroapl) {
				return [$pago['estado_pago'], $pago['fecha_pago']]; //realizada = 3
			}
		}
		return null;
	}

    /** @return array|null Null si no se pudo resolver un alumno para la seleccion. */
    public function get_datosFacturacion($parametros)
    {
        $alumno = ($this->es_ambiente_prod) ?
            catalogo::consultar('alumno', 'get_alumno_propuesta_cursos', [
                'propuesta' => $parametros['propuesta'] ?? null
            ]) :
            $parametros['alumno'] ?? self::ALUMNO_PRUEBA;

        if (empty($alumno)) {
            return null;
        }

        $ctactes = catalogo::consultar('alumno', 'get_datos_ctacte', ['alumno' => $alumno]);

        //kernel::log()->add_debug('Facturacion G3W3 - Datos CtaCte', $ctactes);

        $datos_pagos_guarani = $this->get_referenciasPagos(['alumno' => $alumno]);
        
        foreach($ctactes as $key => $ctacte) {
            $codapl = $ctacte['vtrmvc_codapl'];
            $nroapl = $ctacte['vtrmvc_nroapl'];

            $ctactes[$key]['vtrmvc_codapl'] = $codapl;
            $ctactes[$key]['grcfor_descrp'] = $ctacte['grcfor_descrp'];
            $ctactes[$key]['vtrmvc_nroapl'] = $nroapl;
            $ctactes[$key]['vtrmvc_fchvnc'] = $ctacte['vtrmvc_fchvnc'];
            $ctactes[$key]['usr_vtmclh_nocrsa'] = $ctacte['usr_vtmclh_nocrsa'];
            $ctactes[$key]['link'] = $ctacte['link'];
            $ctactes[$key]['index'] = $key;
            // Identidad estable del comprobante, independiente del orden o de otras pestanas.
            $ctactes[$key]['id_pago'] = catalogo::generar_id(json_encode([
                'datos_ctacte',
                (string) kernel::persona()->get_id(),
                kernel::persona()->get_id_perfil_activo(),
                (string) $alumno,
                (string) $codapl,
                (string) $nroapl
            ]));

            $estado_pago = $this->get_estado_pago($datos_pagos_guarani, $codapl, $nroapl);
            $ctactes[$key]['estado_pago'] = $estado_pago[0] ?? null;
            $ctactes[$key]['fecha_pago'] = $estado_pago[1] ?? '-';

            $estado_pago_desc = $this->get_estadoPagoDescripcion($ctactes[$key]['estado_pago'], 'g3w3');
            $ctactes[$key]['estado_pago_desc'] = $estado_pago_desc ?? 'Pendiente';
            $ctactes[$key]['ref_data'] = $this->get_mcpInfoAdicional([
                'alumno' => $alumno,
                'codapl' => $codapl,
                'nroapl' => $nroapl
            ]);
        }

        return $ctactes;
    }

    function get_mcpTransId($codapl, $nroapl)
	{
		return $codapl . '_' . $nroapl . '_' . date("Y-m-d H:i:s");
	}

    public function get_datosFacturacionFinal($in_monto, $codapl, $nroapl)
    {
        $mcp_data = $this->get_mcpData();
        $monto = EncryptService::EncryptString($in_monto, $mcp_data['key']);
        $hash = $this->get_mcpHash
        (
            $mcp_data['key'], 
            $mcp_data['comercio'], 
            $mcp_data['sucursal'], 
            $in_monto
        );

        $trans_id = $this->get_mcpTransId($codapl, $nroapl);

        return [
            'monto' => $monto,
            'hash' => $hash,
            'trans_id' => $trans_id
        ];
    }

    public function act_referenciaSoftland($parametros)
    {
        // TODO: Implement logic
        return true;
    }

    public function get_softlandConnection()
    {
        // TODO: Implement logic
        return null;
    }

    public function get_mcpInfoAdicional($parametros)
    {
        $alumno = $parametros['alumno'] ?? null;
        $codapl = $parametros['codapl'] ?? null;
        $nroapl = $parametros['nroapl'] ?? null;

        $info = [
            'origen' => 'g3w3',
            'alumno' => $alumno,
            'codapl' => $codapl,
            'nroapl' => $nroapl
        ];
        $jinfo = json_encode($info);
        return EncryptService::EncryptString($jinfo, $this->get_mcpKey());
    }

    public function get_SoftlandSP(&$spName, &$parametros)
    {
        // TODO: Implement logic
        return [];
    }
}
