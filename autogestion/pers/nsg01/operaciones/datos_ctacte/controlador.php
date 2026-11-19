<?php

namespace nsg01\operaciones\datos_ctacte;

use PlusPagos\AESEncrypter;
use PlusPagos\SHA256Encript;

use nsg01\guarani;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
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

    function get_propuesta_ctacte($metodo = 'get')
    {
        return $this->validate_param('propuesta', $metodo, validador::TIPO_INT, ['default' => null]);
    }

    function accion__pagar_mcp()
    {
        $monto = $this->get_param('monto', 'post');
        $comprobante = $this->get_param('comprobante', 'post');
        $facturacion = new nsg_g3w3_facturacion(true);
        $ctacte = $this->get_ctacte($comprobante, $facturacion, $this->get_propuesta_ctacte('post'));

        if (!$ctacte) {
            $this->render_raw_json(['error' => kernel::traductor()->trans('datos_ctacte.comprobante_no_disponible')]);
            return;
        }

        if (!$this->validar_monto($monto, $ctacte['saldo'])) {
            $this->render_raw_json(['error' => kernel::traductor()->trans('datos_ctacte.err_monto')]);
            return;
        } 

        $codapl = $ctacte['vtrmvc_codapl'];
        $nroapl = $ctacte['vtrmvc_nroapl'];
        $datos_final = $facturacion->get_datosFacturacionFinal($monto, $codapl, $nroapl);
        $this->render_raw_json($datos_final);
    }

    function get_ctacte($comprobante, $facturacion, $propuesta = null)
    {
        if (!is_string($comprobante) || !preg_match('/^[a-f0-9]{40}$/D', $comprobante)) {
            return null;
        }
        $ctactes = $facturacion->get_datosFacturacion(['propuesta' => $propuesta]);
        if (empty($ctactes)) {
            return null;
        }
        foreach ($ctactes as $ctacte) {
            if ($ctacte['id_pago'] === $comprobante && !empty($ctacte['link']) && $ctacte['estado_pago'] != 3) {
                return $ctacte;
            }
        }
        return null;
    }

    function validar_monto($monto, $saldo) 
    {
		$montoNormalizado = str_replace('.', '', $monto);
		$montoNormalizado = str_replace(',', '.', $montoNormalizado);
        return is_numeric($montoNormalizado) && $monto > 1 && (float)$montoNormalizado <= $saldo;
    }

    function accion__pagar_mp()
    {
        $facturacion = new nsg_g3w3_facturacion(true);
        $ctacte = $this->get_ctacte(
            $this->get_param('comprobante', 'post'),
            $facturacion,
            $this->get_propuesta_ctacte('post')
        );
        if (!$ctacte) {
            $this->render_raw_json(['error' => kernel::traductor()->trans('datos_ctacte.comprobante_no_disponible')]);
            return;
        }
        $this->render_raw_json(['link' => $ctacte['link']]);
    }

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
