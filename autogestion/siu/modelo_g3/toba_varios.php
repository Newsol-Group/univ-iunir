<?php
	define("apex_qs_separador","||");			//Separador utilizado para diferenciar campos de valores compuestos

	/**
	 * Toma una matriz y lo aplana a una sola dimension, si no se especifica un campo, se elige el primero.
	 * Util para aplanar recordset de consultas de un solo campo.
	 * Ej: array(0 => array('campo' => 'cero'), 1 => array('campo' => 'uno'))  --->  array('cero', 'uno')
	 */
	function aplanar_matriz($matriz, $campo = null)
	{
		$aplanado = array();
		foreach ($matriz as $clave => $arreglo) {
			if ($campo === null) {
				$aplanado[$clave] = current($arreglo);
			}elseif (isset($arreglo[$campo])) {
				$aplanado[$clave] = $arreglo[$campo];
			}			
		}
		return $aplanado;
	}

    /**
	 * Toma una matriz en formato recordset y retorna la misma matriz pero con la primer componente asociativa
	 *
	 * @param array $datos_recordset Matriz en formato recordset
	 * @param array $claves Campos (asociativos o numericos) claves de cada registro
	 * @param string $valores Campos valor (asociativo o numerico) de cada registro, se asumen todos los campos
	 * @return array 
	 */
    function rs_convertir_asociativo_matriz($datos_recordset, $claves, $valores=null)
    {
    	if (!isset($datos_recordset)) {
    		return array();	
    	}
		$salida = array();
		foreach ($datos_recordset as $fila){
			$valores_clave = array();
			foreach($claves as $clave) {
				$valores_clave[] = $fila[$clave];
			}
			if (isset($valores)) {
				foreach ($valores as $valor) {
	            	$salida[implode(apex_qs_separador, $valores_clave)][$valor] = $fila[$valor];
				}
			} else {
				$clave_temp = implode(apex_qs_separador, $valores_clave);
				if ($clave_temp != '') {
					$salida[$clave_temp] = $fila;
				} else {
					$salida[''] = $fila;
				}
			}
		}
        return $salida;
    }

    /**
     * Toma una matriz en formato recordset y retorna un arreglo asociativo clave => valor
     *
     * @param array $datos_recordset Matriz en formato recordset
     * @param array $claves Campos (asociativos o numericos) claves de cada registro
     * @param string $valor Campo valor (asociativo o numerico) de cada registro
     * @return array
     */
    function rs_convertir_asociativo($datos_recordset, $claves=array(0), $valor=1)
    {
        if (!isset($datos_recordset)) {
            return array();
        }
        $valores = array();
        foreach ($datos_recordset as $fila){
            $valores_clave = array();
            foreach($claves as $clave) {
                if (isset($fila[$clave])) {
                    $valores_clave[] = $fila[$clave];
                } else {
                    throw new \siu\errores\error_guarani("La fila del recordset no contiene la clave '$clave'. ".var_export($fila, true));
                }
            }

            if (! isset($fila[$valor])){
                throw new \siu\errores\error_guarani("La fila del recordset no contiene la columna '$valor'. ".var_export($fila, true));
            }else{
                $valores[implode(apex_qs_separador, $valores_clave)] = $fila[$valor];
            }
        }
        return $valores;
    }

?>
