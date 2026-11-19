<?php

/**
 *	Representa los datos de facturaci�n
 *
 * @package Matr�cula
 * @subpackage Actualizaciones
 */
class cn_ent_datos_facturacion_nsg extends cn_entidad
{
	function get_datos($tabla = null)
	{
		return $this->tabla('datos_facturacion')->get();
	}

	function set_datos_facturacion($alumno, $datos)
	{
		$datos['alumno'] = $alumno;
		//$datos['porcentaje_facturacion_alumno'] = 100;

		$this->validar_datos($datos);
		$this->tabla('datos_facturacion')->set($datos);
	}

	function validar_datos_alumno($datos) { 
		
		$mp_cuo = isset($datos['medio_pago_alumno_cuota']) ? $datos['medio_pago_alumno_cuota'] : null;
		$cbu_cuo = isset($datos['cbu_alumno_cuota']) ? $datos['cbu_alumno_cuota'] : null;
		if ($mp_cuo == 2 && ($cbu_cuo === null || strlen($cbu_cuo) != 22)) {
			$this->set_error('800_dato_dep_invalido', ['CBU de cuota del alumno', 'invalido']);
			return;
		}

		$tar_cuo = isset($datos['nro_tarjeta_alumno_cuota']) ? $datos['nro_tarjeta_alumno_cuota'] : null;
		if ($mp_cuo == 1 && ($tar_cuo === null || !$this->validateCreditCard($tar_cuo))) {
			$this->set_error('800_dato_dep_invalido', ['Tarjeta de cuota del alumno', 'invalida']);
			return;
		}

		$mp_mat = isset($datos['medio_pago_alumno_mat']) ? $datos['medio_pago_alumno_mat'] : null;
		$cbu_mat = isset($datos['cbu_alumno_mat']) ? $datos['cbu_alumno_mat'] : null;
		if ($mp_mat == 2 && ($cbu_mat === null || strlen($cbu_mat) != 22)) {
			$this->set_error('800_dato_dep_invalido', ['CBU de matrícula del alumno', 'invalido']);
			return;
		}

		$tar_mat = isset($datos['nro_tarjeta_alumno_mat']) ? $datos['nro_tarjeta_alumno_mat'] : null;
		if ($mp_mat == '1' && ($tar_mat === null || !$this->validateCreditCard($tar_mat))) {
			$this->set_error('800_dato_dep_invalido', ['Tarjeta de matrícula del alumno', 'invalida']);
			return;
		}

		$pct_fact_emp = isset($datos['porcentaje_facturacion_empresa']) ? $datos['porcentaje_facturacion_empresa'] : null;
		$pct_fact_alu = isset($datos['porcentaje_facturacion_alumno']) ? $datos['porcentaje_facturacion_alumno'] : null;

		if ($pct_fact_emp + $pct_fact_alu != 100) {
			$this->set_error('800_dato_dep_invalido', ['porcentaje de facturación del alumno', 'debe ser 100 si no hay porcentaje para la empresa']);
			return;
		}

		if ($pct_fact_alu >0){
			if ($mp_cuo === null && $mp_mat === null) {
				$this->set_error('800_dato_dep_invalido', [
					'Medios de Pago del alumno', 
					'debe seleccionar por lo menos un medio de pago del alumno'
				]);
				return;
			}
		}

	}

	function validar_datos_empresa($datos) {
		$pago_empresa = isset($datos['pago_a_cargo_empresa']) ? $datos['pago_a_cargo_empresa'] : null;
		if ($pago_empresa == '0') return;

		$mp_cuo = isset($datos['medio_pago_empresa_cuota']) ? $datos['medio_pago_empresa_cuota'] : null;
		$cbu_cuo = isset($datos['cbu_empresa_cuota']) ? $datos['cbu_empresa_cuota'] : null;
		if ($mp_cuo == 2 && ($cbu_cuo === null || strlen($cbu_cuo) != 22)) {
			$this->set_error('800_dato_dep_invalido', ['CBU de cuota de la empresa', 'invalido']);
			return;
		}
		$tar_cuo = isset($datos['nro_tarjeta_empresa_cuota']) ? $datos['nro_tarjeta_empresa_cuota'] : null;
		if ($mp_cuo == 1 && ($tar_cuo === null || !$this->validateCreditCard($tar_cuo))) {
			$this->set_error('800_dato_dep_invalido', ['Tarjeta de cuota de la empresa', 'invalida']);
			return;	
		}
		$mp_mat = isset($datos['medio_pago_empresa_mat']) ? $datos['medio_pago_empresa_mat'] : null;
		$cbu_mat = isset($datos['cbu_empresa_mat']) ? $datos['cbu_empresa_mat'] : null;
		if ($mp_mat == 2 && ($cbu_mat === null || strlen($cbu_mat) != 22)) {
			$this->set_error('800_dato_dep_invalido', ['CBU de matrícula de la empresa', 'invalido']);
			return;
		}
		$tar_mat = isset($datos['nro_tarjeta_empresa_mat']) ? $datos['nro_tarjeta_empresa_mat'] : null;
		if ($mp_mat == 1 && ($tar_mat === null || !$this->validateCreditCard($tar_mat))) {
			$this->set_error('800_dato_dep_invalido', ['Tarjeta de matrícula de la empresa', 'invalida']);
			return;
		}
		if ($mp_cuo === null && $mp_mat === null) {
			$this->set_error('800_dato_dep_invalido', [
				'Medios de Pago de la empresa', 
				'debe seleccionar por lo menos un medio de pago de la empresa'
			]);
			return;
		}
		$pct_fact_emp = isset($datos['porcentaje_facturacion_empresa']) ? $datos['porcentaje_facturacion_empresa'] : null;
		$pct_fact_alu = isset($datos['porcentaje_facturacion_alumno']) ? $datos['porcentaje_facturacion_alumno'] : null;

		if ($pct_fact_emp + $pct_fact_alu != 100) {
			$this->set_error('800_dato_dep_invalido', ['porcentaje de facturación de la empresa', 'debe ser mayor a 0 si hay porcentaje para la empresa']);
			return;
		}

		if ($pct_fact_emp >0){
			if ($mp_cuo === null && $mp_mat === null) {
				$this->set_error('800_dato_dep_invalido', [
					'Medios de Pago de la empresa', 
					'debe seleccionar por lo menos un medio de pago de la empresa'
				]);
				return;
			}
		}

	}

	function validar_datos($datos)
	{
		$this->validar_datos_alumno($datos);
		$this->validar_datos_empresa($datos);

		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	function validateCreditCard($number)
	{
		// Remove any non-digit characters (e.g., spaces, hyphens)
		$number = preg_replace('/\D/', '', $number);

		if (strlen($number) < 13 || strlen($number) > 19) {
			return false;
		}

		$sum = 0;
		$numDigits = strlen($number);
		$parity = $numDigits % 2;

		for ($i = 0; $i < $numDigits; $i++) {
			$digit = (int)$number[$i];

			if ($i % 2 === $parity) {
				$digit *= 2;

				if ($digit > 9) {
					$digit -= 9;
				}
			}
			$sum += $digit;
		}

		return ($sum % 10) === 0;
	}
}
