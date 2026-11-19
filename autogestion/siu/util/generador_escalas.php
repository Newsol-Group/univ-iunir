<?php
namespace siu\util;

use siu\guarani;
use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;

class generador_escalas {
	static function generar()
	{
		$path = guarani::path_escalas_js();
		\SIU\Chulupi\util\ManejadorArchivos::crear_arbol_directorios($path);
		static::generar_escalas_continuas($path);
		static::generar_escalas_discretas($path);
		static::generar_condiciones_y_resultados($path);
	}
	
	static function generar_escalas_continuas($path)
	{
		$escalas = catalogo::consultar('carga_notas_cursada_comision', 'lista_escala_notas', array(
			'tipo' => 'CONTINUA'
		));
		
		foreach ($escalas as $escala) {
			$js = static::generar_js_escala_continua(
					$escala['escala_nota'], 
					$escala['cantidad_decimales'],
					$escala['separador_decimal'],
					$escala['nota_inicial'], 
					$escala['nota_final']
			);
			file_put_contents("$path/escala_{$escala['escala_nota']}.js", $js);
		}
	}
	
	static function generar_escalas_discretas($path)
	{
		$escalas = catalogo::consultar('carga_notas_cursada_comision', 'lista_escala_notas', array(
			'tipo' => 'DISCRETA'
		));
		
		foreach ($escalas as $escala) {
			$js = static::generar_js_escala_discreta($escala['escala_nota']);
			file_put_contents("$path/escala_{$escala['escala_nota']}.js", $js);
		}
	}
	
	protected static function generar_js_escala_continua($id_escala, $cant_decimales, $separador_decimal, $nota_inicial, $nota_final)
	{
		$rangos = catalogo::consultar('carga_notas_cursada_comision', 'rango_escala_notas', array(
			'escala' => $id_escala
		));
		
		$js_get_resultado = "";
		foreach ($rangos as $rango) {
			$js_get_resultado .= "
			if (({$rango['nota_inicial']} <= nota_num) && (nota_num <= {$rango['nota_final']})) {
				return '{$rango['resultado']}';
			}
			";
		}
		return <<<EOT
guarani.escalas().registrar('$id_escala', function() {
	return {
		get_tipo: function() {
			return 'continua';
		},
		validar: function(nota) {
			if(/^([0-9])*[.|,]?[0-9]{0,$cant_decimales}0*$/.test(nota)) {
				nota = nota.replace(',','.');
				if (!isNaN(nota)) {
					var nota_num = parseFloat(nota);
				}
				if (nota_num <= $nota_final && nota_num >= $nota_inicial){
					return true;
				}
			}
			return false;
		},
		formatear: function(nota) {
			var cant_decimales = '$cant_decimales';
			var separador = '$separador_decimal';

			nota = nota.replace(',',separador);
			nota = nota.replace('.',separador);

			if (nota.indexOf(separador) < 0 && cant_decimales > 0) {
				nota = nota + separador;
			}

			pos = nota.indexOf(separador);

			if (cant_decimales == 0) {
				if (pos < 0) {
					return nota;
				} else {
					return nota.substring(0, pos);
				}
			}

			var parte_entera = nota.substring(0,pos);
			var parte_decimal = nota.substring(pos+1,nota.length);

			if (parte_entera == ''){
				parte_entera = '0';
			}
			parte_entera = parseInt(parte_entera,10).toString();

			var cantidad = cant_decimales - parte_decimal.length;
			if (cantidad < 0){
				return parte_entera+separador+parte_decimal.substring(0,parte_decimal.length+cantidad)
			}
			for (x = 0; x < cantidad; x++) {
				parte_decimal = parte_decimal + '0';
			}

			return parte_entera+separador+parte_decimal;
		},
		get_resultado: function(nota) {
			nota = nota.replace(',','.');
			if (!isNaN(nota)) {
				var nota_num = parseFloat(nota);
			}
			$js_get_resultado
		},
		get_resultado_descr: function(nota) {
			return _resultados[guarani.escalas().recuperar('$id_escala').get_resultado(nota)].descr;
		}
	}
});
EOT;
	}
	
	static function generar_js_escala_discreta($id_escala)
	{
		$detalle_escala = catalogo::consultar('carga_notas_cursada_comision', 'detalle_escala_notas', array(
			'escala' => $id_escala
		));
		$sep_nota = '=>';
		$sep_resultado = '||';
		$separador_grupo = '**';
		$separadores = array ('nota' => $sep_nota, 'resultado' => $sep_resultado);
		$escalas_string = static::implode_m($separadores, $separador_grupo, $detalle_escala);
		
		return <<<EOT
guarani.escalas().registrar('$id_escala', function() {
	return {
		get_tipo: function() {
			return 'discreta';
		},
		validar: function(nota) {
			var escalast		= '$escalas_string';
			var string_buscar	= '$separador_grupo' + nota + '$sep_nota';
			var posicion_ini	= escalast.indexOf(string_buscar);
			return posicion_ini != -1;
		},
		get_resultado: function(nota) {
			var escalast		= '$escalas_string';
			var nota_bucar		= '$separador_grupo' + nota + '$sep_nota';
			var posicion_ini	= escalast.indexOf(nota_bucar);
			if (posicion_ini == -1) {
				return '';
			} else {
				posicion_ini = posicion_ini + nota_bucar.length;
				return escalast.substr(posicion_ini,1);
			}
		},
		get_resultado_descr: function(nota) {
			return _resultados[guarani.escalas().recuperar('$id_escala').get_resultado(nota)].descr;
		}
	}
});
EOT;
	}
		
	// se saca guarani/php/nucleo/propuestas/escalas_notas/validador_escalas_notas.php
	// no se invoca directamente por las dudas que cambie
	protected static function implode_m ($separadores, $separador_grupo ,$datos)
	{
		$string = '';
		foreach ($datos as $key => $columnas) {
			foreach ($columnas as $columna => $valor) {
				foreach ($separadores as $columna_s => $separador){
					if ($columna == $columna_s) {
						$string .= $valor . $separador;
					}
				}
			}
			$string .= $separador_grupo;
		}
		return $separador_grupo.$string;
	}
	static function generar_condiciones_y_resultados($path)
    {
		$raw_condiciones = catalogo::consultar('carga_notas_cursada_comision', 'condiciones_activas');
		
		$condiciones = array();
		foreach ($raw_condiciones as $condicion) {
			$condiciones[$condicion['cond_regularidad']] = array(
				'cond'		=> $condicion['cond_regularidad'], // se repite para acceder más fácilmente desde js
				'desc'		=> $condicion['nombre'],
				'resultado' => $condicion['resultado'],
				'preset'	=> $condicion['es_condicion_defecto']
			);
		}
		$raw_resultados = catalogo::consultar('carga_notas_cursada_comision', 'resultados_escala_nota');
		
		$resultados = array();
		foreach ($raw_resultados as $resultado) {
			$resultados[$resultado['valor']] = $resultado;
		}
		
		$cond_js = kernel::renderer()->json_encode($condiciones);
		$res_js = kernel::renderer()->json_encode($resultados);
		$js = "var _condiciones = $cond_js;\nvar _resultados  = $res_js;";
		
		file_put_contents("$path/cond_res.js", $js);
    }
}

?>
