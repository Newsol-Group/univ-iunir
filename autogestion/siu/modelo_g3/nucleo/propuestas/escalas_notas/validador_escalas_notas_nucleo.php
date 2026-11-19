<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage EscalaDeNotas
 */

class validador_escalas_notas_nucleo
{
	static protected $instancia;

	protected $escalas_notas;

	private function __construct()
	{
		$this->escalas_notas = $this->cargar_escalas_notas();
	}

	static function instancia()
	{
		if (!isset(self::$instancia)){
			self::$instancia = new validador_escalas_notas();
		}
		return self::$instancia;
	}

	private function cargar_escalas_notas()
	{
		$escalas = toba::consulta_php('co_escalas_notas')->get_listado();
		return rs_convertir_asociativo_matriz($escalas, array('escala_nota'));
	}

	private function get_rangos_resultados_escala($escala_nota)
	{
		if (!isset($this->escalas_notas[$escala_nota]['rangos'])) {
			$this->escalas_notas[$escala_nota]['rangos'] = toba::consulta_php('co_escalas_notas')->get_rangos_resultados($escala_nota);
		}
		return $this->escalas_notas[$escala_nota]['rangos'];
	}

	private function get_detalle_escala($escala_nota)
	{
		if (!isset($this->escalas_notas[$escala_nota]['detalle'])) {
			$detalles = $this->escalas_notas[$escala_nota]['detalle'] = toba::consulta_php('co_escalas_notas')->get_escalas_notas_detalles($escala_nota);
			$this->escalas_notas[$escala_nota]['detalle'] = rs_convertir_asociativo_matriz($detalles, array('nota'));
		}
		return $this->escalas_notas[$escala_nota]['detalle'];
	}

	private function get_datos_escala($escala)
	{
		return $this->escalas_notas[$escala];
	}

	//--------------------------------------------------------------------------------------------
	//--------------------------            PHP           ----------------------------------------
	//--------------------------------------------------------------------------------------------

	/**
	 * Verifica que la nota ingresada sea válida de acuerdo a la escala de notas de la instancia correspondiente al alumno.
	 */
	function es_nota_valida($nota, $escala_nota)
	{
		if (! is_null($nota) && ! empty($escala_nota)) {
			switch ($this->escalas_notas[$escala_nota]['tipo']) {
					case escalas_notas::tipo_continua :
						return $this->validar_nota_escala_continua($nota, $escala_nota);
					break;
					case escalas_notas::tipo_discreta :
						return $this->validar_nota_escala_discreta($nota, $escala_nota);
					break;
			}
		}
		return true;
	}


	/**
	 * Retorna True si es una nota valida para la escala de notas discreta
	 *
	 * @param <type> $nota
	 * @param <type> $escala_nota
	 * @return <type>
	 *
	 */
	private function validar_nota_escala_discreta($nota, $escala_nota)
	{
		$detalle = $this->get_detalle_escala($escala_nota);
		if (isset($detalle[$nota])){
			return true;
		}
		return false;
	}

	private function validar_nota_escala_continua($nota, $escala_nota)
	{
		$datos_escala = $this->get_datos_escala($escala_nota);

		//$er = "/^([0][".$datos_escala['separador_decimal']."][0-9]{0,".$datos_escala['cantidad_decimales']."}|[0]|[1-9][0-9]*([".$datos_escala['separador_decimal']."][0-9]{0,".$datos_escala['cantidad_decimales']."})?)$/";
		//$er = "/^([0-9])*[".$datos_escala['separador_decimal']."]?[0-9]{0,".$datos_escala['cantidad_decimales']."}$/";

        if($datos_escala['cantidad_decimales'] == 0){//Si no tiene parte decimal...
            $er = "/^([0-9]|[1-9][0-9]+)$/";
        }
        else{//Si tiene parte decimal...
            $er = "/^([0-9]|[1-9][0-9]+)".preg_quote($datos_escala['separador_decimal'])."[0-9]{".$datos_escala['cantidad_decimales']."}$/";
        }

        if (!preg_match($er, $nota)) {
			return false;
		}

		$nota_num = floatval($nota);
		if ($nota_num <= $datos_escala['nota_final'] && $nota_num >= $datos_escala['nota_inicial']){
			return true;
		}
		return false;
	}

	/**
     * Obtiene el resultado asociado a una escala de notas y nota dadas.
     *
     * @param integer	$escala	id de la escala de nota.
     * @param integer	$nota	id de la nota.
     */
	function get_resultado_escala_nota($escala, $nota)
	{
		$detalles_escala 	= $this->get_detalle_escala($escala);
		$detalles_nota		= $detalles_escala[$nota];
		return $detalles_nota['resultado'];
	}
	
	/**
	 * Verifica que el resultado seteado para el alumno sea el que se corresponde con la escala de notas asignada al
	 * mismo (según su instancia) y la nota que se seteó para él, o en su defecto que el resultado sea Ausente y al
	 * alumno no se le haya seteado nota.
	 * Además se verifica que si hay cargado un resultado debe haberse cargado una nota y viceversa.
	 */
	function es_resultado_valido($nota, $escala_nota, $resultado)
	{
		if (! is_null($nota) && ! empty($escala_nota)) {
			switch ($this->escalas_notas[$escala_nota]['tipo']) {
					case escalas_notas::tipo_continua :
						return $this->validar_resultado_escala_continua($nota, $escala_nota, $resultado);
					break;
					case escalas_notas::tipo_discreta :
						return $this->validar_resultado_escala_discreta($nota, $escala_nota, $resultado);
					break;
				}
		}
		return true;
		
	}

	private function validar_resultado_escala_discreta($nota, $escala_nota, $resultado)
	{
		$detalle = $this->get_detalle_escala($escala_nota);
		if ($detalle[$nota]['resultado'] == $resultado) {
			return true;
		}
		return false;
	}

	private function validar_resultado_escala_continua($nota, $escala_nota, $resultado)
	{
		$rangos		= $this->get_rangos_resultados_escala($escala_nota);
		$nota_num	= floatval($nota);
		foreach ($rangos as $rango){
			if (($rango['nota_inicial'] <= $nota_num) && ($nota_num <= $rango['nota_final']))	{
				if ($resultado == $rango['resultado'])
					return true;
			}
		}
		return false;
	}


	
	
	
	//--------------------------------------------------------------------------------------------
	//--------------------------         JAVASCRIPT       ----------------------------------------
	//--------------------------------------------------------------------------------------------


	static function get_js_validacion_nota_escala_continua($cant_decimales, $nota_inicial, $nota_final, $escala_nota=null)
	{
		$nota_inicial = str_replace(',', '.', $nota_inicial);
		$nota_final = str_replace(',', '.', $nota_final);
		
		if(isset($escala_nota)) {
			$signatura = "$escala_nota";
		} else {
			$signatura = "nueva";
		}
		$js = "
				function es_nota_valida_para_escala_$signatura(nota)
				{
					if(/^([0-9])*[.|,]?[0-9]{0,$cant_decimales}0*$/.test(nota)) {
							nota = nota.replace(',','.');
							if (!isNaN(nota)) {
								var nota_num = parseFloat(nota);
							}
							if (nota_num <= $nota_final && nota_num >= $nota_inicial){
								return true
							}
						}
						return false;
				}
				";

		return $js;
	}

	static function get_fs_formatear_nota_escala_continua($cantidad_decimales, $separador_decimal, $escala_nota=null)
	{
		if(isset($escala_nota)) {
			$signatura = "$escala_nota";
		} else {
			$signatura = "nueva";
		}
	
		$js = "
				function formatear_nota_escala_$signatura(nota)
				{
					var cant_decimales	= '$cantidad_decimales';
					var separador	    = '$separador_decimal';

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

					// parte Entera
					if (parte_entera == ''){
						parte_entera = '0';
					}
					parte_entera = parseInt(parte_entera,10).toString();

					// parte Decimal
					var cantidad = cant_decimales - parte_decimal.length;
					if (cantidad < 0){
						return parte_entera+separador+parte_decimal.substring(0,parte_decimal.length+cantidad)
					}
					for (x = 0; x < cantidad; x++) {
						parte_decimal = parte_decimal + '0';
					}

					return parte_entera+separador+parte_decimal;
				}
			";
		return $js;
	}

	
	protected function generar_js_validacion_escala_nueva($cantidad_decimales, $nota_inicial, $nota_final)
	{
		return static::get_js_validacion_nota_escala_continua($cantidad_decimales, $nota_inicial, $nota_final);
	}
	
	protected function generar_js_formatear_nota_escala_nueva($cantidad_decimales, $separador_decimal)
	{
		return static::get_fs_formatear_nota_escala_continua($cantidad_decimales, $separador_decimal);
	}


	/**
	 * Genera la fs js para validación de la nota perteneciente a una escala continua
	 * Genera la fs js correspondiente a la escala continua para obtener los resultados de la nota cargada
	 * @param <type> $info_escala
	 * @return <type>
	 */

	protected function generar_js_validacion_y_resultado_continua($escala)
	{
		$datos_escala	= $this->get_datos_escala($escala);
		$rangos			= $this->get_rangos_resultados_escala($datos_escala['escala_nota']);

		// fs js que valida la nota en la escala
		$js = static::get_js_validacion_nota_escala_continua($datos_escala['cantidad_decimales'],$datos_escala['nota_inicial'],$datos_escala['nota_final'],$datos_escala['escala_nota']);
		// fs que obtiene el resultado
		$js .="
				function get_resultado_para_escala_{$datos_escala['escala_nota']}(nota)
				{
					nota = nota.replace(',','.');
					if (!isNaN(nota)) {
						var nota_num = parseFloat(nota);
					}
				";
				foreach ($rangos as $rango){
					$js .= "if (({$rango['nota_inicial']} <= nota_num) && (nota_num <= {$rango['nota_final']})) {
									return '{$rango['resultado']}';
								}
								";
				}
			$js .= "}
					";

		return $js;

	}
	
	/**
	 * Fs Auxiliar que formatea la notas que son númericas, al formato válido
	 *
	 * @param <type> $info_escala
	 * @return <type> 
	 */

 	protected function generar_js_formatear_nota_escala($escala)
	{
		$datos_escala = $this->get_datos_escala($escala);
        if ($datos_escala['tipo'] == escalas_notas::tipo_discreta) {
			$js ="
					function formatear_nota_escala_{$datos_escala['escala_nota']}(nota)
					{
						return nota;
					}
			     ";
			return $js;
		}
		return static::get_fs_formatear_nota_escala_continua($datos_escala['cantidad_decimales'],$datos_escala['separador_decimal'], $datos_escala['escala_nota']);
	}

	/**
	 * Fs Auxiliar que resuelve un implode de un array bidimensional "datos", segun $separadores y $separador_grupo
	 *
	 * EJ:	$separadores		= array ('nota' => '=>', 'resultado' => '||');
	 *		$separador_grupo	= '**';
	 *		$datos				= array (
	 *									 array ('nota' => 'D' , 'resultado' => 'R', 'descripcion' => 'Desaprobado'),
	 *									 array ('nota' => 'A' , 'resultado' => 'A', 'descripcion' => 'Aprobado')
	 *									 )
	 *
	 *		Retorna = '**D=>R||**A=>A||**'
	 *
	 * @param <type> $separadores
	 * @param <type> $separador_grupo
	 * @param <type> $datos
	 * @return <type>
	 */

	static function implode_m ($separadores, $separador_grupo ,$datos)
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



	/**
	 * Genera la fs js para validación de la nota perteneciente a una escala discreta
	 * Genera la fs js correspondiente a la escala discreta para obtener los resultados de la nota cargada
	 *
	 * @param <type> $info_escala
	 * @return <type>
	 */
	protected function generar_js_validacion_y_resultado_discreta($escala)
	{

		$datos_escala	= $this->get_datos_escala($escala);
		$detalle_escala = $this->get_detalle_escala($escala);

		$sep_nota = '=>';
		$sep_resultado = '||';
		$separador_grupo = '**';
		$separadores = array ('nota' => $sep_nota, 'resultado' => $sep_resultado);

		$escalas_string = toba::db()->quote(static::implode_m($separadores, $separador_grupo, $detalle_escala));

		// fs js que valida la nota en la escala
		$js =	"
				function es_nota_valida_para_escala_{$datos_escala['escala_nota']}(nota)
				{
					var escalast		= $escalas_string;
					var string_buscar	= '$separador_grupo' + nota + '$sep_nota';
					var posicion_ini	= escalast.indexOf(string_buscar);
					if (posicion_ini == -1) {
						return false;
					} else {
						return true;
					}
				}";

		// fs que obtiene el resultado
		$js .=	"
				function get_resultado_para_escala_{$datos_escala['escala_nota']}(nota)
				{
					var escalast		= $escalas_string;
					var nota_bucar		= '$separador_grupo' + nota + '$sep_nota';
					var posicion_ini	= escalast.indexOf(nota_bucar);
					if (posicion_ini == -1) {
						return '';
					} else {
						posicion_ini = posicion_ini + nota_bucar.length;
						return escalast.substr(posicion_ini,1);
					}
				}";


		return $js;
	}




	/**
	 * Genera las fs js para la validación de la nota y obtención del resultado correspondiente.
	 *
	 * @param <type> $escalas
	 * @return <type>
	 */
	function get_js_validar_nota_escalas($escalas)
	{

		$js = '';

		foreach ($escalas as $escala) {
			$js .= static::generar_js_formatear_nota_escala($escala['escala_nota']);
			//switch ($escala['tipo']) {
			switch ($this->escalas_notas[$escala['escala_nota']]['tipo']) {
				case escalas_notas::tipo_continua :
					$js .= static::generar_js_validacion_y_resultado_continua($escala['escala_nota']);
				break;
				case escalas_notas::tipo_discreta :
					$js .= static::generar_js_validacion_y_resultado_discreta($escala['escala_nota']);
				break;
			}

		}
		
		// Para que no se rompa cuando no se elige escala de notas
		$js .=	"
				function es_nota_valida_para_escala_nopar(nota)
				{	
					return false;
				}";
		
		return $js;
	}
	
	function get_js_validar_nota_escala_nueva($cantidad_decimales, $nota_inicial, $nota_final, $separador_decimal)
	{
		$js = '';
		$js .= static::generar_js_formatear_nota_escala_nueva($cantidad_decimales, $separador_decimal);
		$js .= static::generar_js_validacion_escala_nueva($cantidad_decimales, $nota_inicial, $nota_final);
		return $js;
	}



}
?>