<?php
class cuadro_actividades_salida_html extends toba_ei_cuadro_salida_html
{
	function generar_layout_fila($columnas, $datos, $id_fila,  $clave_fila, $evt_multiples, $objeto_js, $estilo_fila, $formateo)
	{
		echo "<tr class='$estilo_fila' >\n";

		$count = 0;
		foreach (array_keys($columnas) as $a) {
			$valor = '';
			if (isset($columnas[$a]['clave'])) {
				if (isset($datos[$id_fila][$columnas[$a]['clave']])) {
					$valor_real = $datos[$id_fila][$columnas[$a]['clave']];
					if (!isset($columnas[$a]['permitir_html']) || $columnas[$a]['permitir_html'] == 0) {
						$valor_real = texto_plano($valor_real);
					}
				} else {
					$valor_real = null;
				}
				if (isset($columnas[$a]['formateo'])) {
					if ($count >= guarani_arreglos::count($columnas) - 4) {
						$valor = "<input type='checkbox' disabled ".(($valor_real == 'S')?'checked':'').'/>';
					} else {
						$funcion = "formato_{$columnas[$a]['formateo']}";
						$valor = $formateo->$funcion($valor_real);
					}
				} else {
					$valor = $valor_real;
				}
			}
			$count++;

			$ancho = '';
			if (isset($columnas[$a]['ancho'])) {
				$ancho = " width='{$columnas[$a]['ancho']}'";
			}
			echo "<td class='ei-cuadro-fila {$columnas[$a]['estilo']}' $ancho>\n";
			if (trim($valor) !== '') {
				echo $valor;
			} else {
				echo '&nbsp;';
			}
			echo "</td>\n";
		}

		echo "</tr>\n";
	}
}
?>