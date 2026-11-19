<?php

namespace nsg01\operaciones\datos_ctacte;

use PlusPagos\AESEncrypter;
use PlusPagos\SHA256Encript;

use nsg01\guarani;
use SIU\Chulupi\kernel;
use siu\extension_kernel\controlador_g3w2;
use siu\modelo\datos\catalogo;
use nsg01\modelo\facturacion\nsg_g3w3_facturacion;

class controlador extends controlador_g3w2
{
    /**
     * @return null
     */
    function modelo()
    {
        return null;
    }

    function accion__index() {}

    function accion__pagar_mcp()
    {
        $monto = $this->get_param('monto', 'post');
        $index = $this->get_param('index', 'post');
        $facturacion = new nsg_g3w3_facturacion(true);
        $ctacte = $this->get_ctacte($index, $facturacion);

        if (!$ctacte) {
            $this->render_raw_json(['error' => 'Índice inválido']);
            return;
        }

        if (!$this->validar_monto($monto, $ctacte['saldo'])) {
            $this->render_raw_json(['error' => 'Monto inválido']);
            return;
        } 

        $codapl = $ctacte['vtrmvc_codapl'];
        $nroapl = $ctacte['vtrmvc_nroapl'];
        $datos_final = $facturacion->get_datosFacturacionFinal($monto, $codapl, $nroapl);
        $this->render_raw_json($datos_final);
    }

    function get_ctacte($index, $facturacion) {
        //$alumno = 1420; //kernel::persona()->get_id_alumno_activo();
        // $pid = kernel::persona()->get_id();
        // if ($pid == 2) { 
        // //$ctactes = $facturacion->get_datosFacturacion(['alumno' => 1420]);

        // }else { 
        // $ctactes = $facturacion->get_datosFacturacion([]);

        // }
        $ctactes = $facturacion->get_datosFacturacion([]);

        return $ctactes[$index];
    }

    function validar_monto($monto, $saldo) 
    {
		$montoNormalizado = str_replace('.', '', $monto);
		$montoNormalizado = str_replace(',', '.', $montoNormalizado);
        return is_numeric($montoNormalizado) && $monto > 1 && (float)$montoNormalizado <= $saldo;
    }

    function accion__pagar_mp() {}

    function accion__guardar()
    {
        // Si entra por POST guardo
        if (kernel::request()->isPost()) {
            kernel::sesion()->set('hubo_error', $hubo_error);
        }

        // Redirijo al index (pantalla principal)
        $operacion = kernel::ruteador()->get_id_operacion();
        $this->nuevo_request($operacion, 'index');
    }
}
