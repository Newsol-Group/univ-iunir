<?php
/**
 * @package Cobro
 * @subpackage Pagos
 */

class act_pagos_nucleo
{

    	/**
	 * Inserta un nuevo registro en la tabla int_sq_pagos
	 * @param int $nro_transaccion (relacionado a sga_insc_cursada.nro_transaccion o sga_propuestas_aspira.nro_transaccion)
	 * @param string $estado Posibles valores 'P' (Pago) o 'V' (Vencido)
	 * @param string $concepto Posibles valores 'C' (Comisión) o 'P' (Propuesta)
	 * @return void
	 */
	function registrar_pago($nro_transaccion, $estado, $concepto)
        {
		$estado = toba::db()->quote($estado);
		$concepto = toba::db()->quote($concepto);

		$sql = "INSERT INTO int_sq_pagos (nro_transaccion, estado, concepto, fecha_alta)
                        VALUES ($nro_transaccion, $estado, $concepto, CURRENT_TIMESTAMP)
                        ON CONFLICT (nro_transaccion) DO
                        UPDATE SET estado = EXCLUDED.estado, concepto = EXCLUDED.concepto, fecha_alta = EXCLUDED.fecha_alta";
		guarani_db::ejecutar($sql);
	}
}