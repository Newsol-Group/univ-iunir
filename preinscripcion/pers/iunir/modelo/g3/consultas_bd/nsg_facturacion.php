<?php

namespace iunir\modelo\g3\consultas_bd;

use SIU\Chulupi\kernel;
use Dotenv\Dotenv;

// require_once __DIR__ . '/vendor/autoload.php';
// require_once 'EncryptService.php';
require_once __DIR__ . '/EncryptService.php';

require_once dirname(__DIR__, 6) . '/vendor/autoload.php'; // ajustar niveles según tu estructura
$dotenv = Dotenv::createImmutable(dirname(__DIR__, 6));
$dotenv->load();

abstract class nsg_facturacion
{
    const PAGO_REALIZADO = 3;
    protected $es_ambiente_prod;

    public function __construct($es_ambiente_prod)
    {
        $this->es_ambiente_prod = $es_ambiente_prod;
    }

    protected abstract function get_referenciasPagos($parametros);
    protected abstract function get_datosFacturacion($parametros);
    protected abstract function act_referenciaSoftland($parametros);
    
    protected function get_SoftlandSPFirma() {
        return ($this->es_ambiente_prod) ?
            "{CALL emp_iunir.dbo.STR_INS_VTMPPH_MATRICULA(?, ?, ?, ?, ?)}" :
            "{CALL emp_iunir_desarrollo.dbo.STR_INS_VTMPPH_MATRICULA(?, ?, ?, ?, ?)}";
    }

    protected function get_estadoPagoDescripcion($estado_pago)
    {
		if (is_null($estado_pago)) return null;

		$sql = "SELECT descripcion
				FROM nsg_estados_pagos
				WHERE estado_pago = $estado_pago";
		$descripcion = kernel::db()->consultar($sql);
		return $descripcion[0]['descripcion'] ?? null;
    }

    protected abstract function get_softlandConnection();
    protected abstract function get_mcpInfoAdicional($parametros);
    protected abstract function get_SoftlandSP(&$softlandConn, &$parametros);

    protected function get_mcpKey()
    {
        return ($this->es_ambiente_prod) ?
            $_ENV['MCP_KEY_PROD'] :
            $_ENV['MCP_KEY_DEV'];
    }
    protected function get_mcpComercio()
    {
        return ($this->es_ambiente_prod) ?
            $_ENV['MCP_COMERCIO_PROD'] :
            $_ENV['MCP_COMERCIO_DEV'];
    }
    protected function get_mcpEndpoint()
    {
        return ($this->es_ambiente_prod) ?
            $_ENV['MCP_ENDPOINT_PROD'] :
            $_ENV['MCP_ENDPOINT_DEV'];
    }
    protected function get_callbackSuccess()
    {
        return ($this->es_ambiente_prod) ?
            $_ENV['CALLBACK_SUCCESS_PROD'] :
            $_ENV['CALLBACK_SUCCESS_DEV'];
    }
    protected function get_callbackError()
    {
        return ($this->es_ambiente_prod) ?
            $_ENV['CALLBACK_ERROR_PROD'] :
            $_ENV['CALLBACK_ERROR_DEV'];
    }
    public function get_mcpData()
    {
        $key = $this->get_mcpKey();
        return array(
            'key' => $key,
            'comercio' => $this->get_mcpComercio(),
            'sucursal' => EncryptService::EncryptString('', $key),
            'endpoint' => $this->get_mcpEndpoint(),
            'callback_success' => EncryptService::EncryptString($this->get_callbackSuccess(), $key),
            'callback_error' => EncryptService::EncryptString($this->get_callbackError(), $key)
        );
    }

    protected function get_mcpHash($secretKey, $comercio, $sucursal, $monto)
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $clientIp = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $clientIp = $_SERVER['REMOTE_ADDR'];
        }

        return EncryptService::Generate($clientIp, $secretKey, $comercio, $sucursal, $monto);
    }
}
