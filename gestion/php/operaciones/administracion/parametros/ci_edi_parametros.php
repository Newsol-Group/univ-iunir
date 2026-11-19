<?php
/**
 *
 *
 * @package 	Administración
 * @subpackage 	Parámetros
 * 
 */
class ci_edi_parametros extends guarani_pers_ci
{
	protected $valor_parametro_temp;
	protected $s__campo_valor;
	
	/**
	 * Componente visual de información
	 * @var ev_info_datos
	 */
	protected $ev_info;

	function ini()
	{
		toba_ef_combo::set_maximo_descripcion(255);
	}

	function conf()
	{
		$datos = $this->cn()->entidad()->get_datos();
		if ($datos['nivel'] == parametro::nivel_default) {
			$this->pantalla()->tab('nivel_entidad')->desactivar();
		}
	}
	
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('parametro', 'descripcion', 'referencia', 'operaciones', 'modulos'));
			$this->ev_info->set_max_columnas_por_fila(1);
			$this->ev_info->set_td_etiqueta_width('130px');
			$this->ev_info->set_etiqueta('parametro', 'Parámetro:');
			$this->ev_info->set_etiqueta('descripcion', 'Desc. abreviada:');
			$this->ev_info->set_etiqueta('referencia', 'Referencia:');
			$this->ev_info->set_etiqueta('operaciones', 'Usado en operaciones:');
			$this->ev_info->set_etiqueta('modulos', 'Usado en módulos:');
		}
		return $this->ev_info;
	}

	function get_datos_ev_info()
	{
		$datos = $this->cn()->entidad()->get_datos();
		
		// Operaciones donde se usa el parámetro
		$operaciones = toba::consulta_php('co_parametros')->get_operaciones_de_parametro($datos['parametro']);
		$html_operaciones = '';
		foreach ($operaciones as $operacion) {
			$html_operaciones .= $operacion['operacion'] . '<br/>';
		}
		if (!empty($html_operaciones)) {
			$datos['operaciones'] = $html_operaciones;
		}
		
		// Módulos donde se usa el parámetro
		$moludos = toba::consulta_php('co_parametros')->get_modulos_de_parametro($datos['parametro']);
		$html_modulos = '';
		foreach ($moludos as $moludo) {
			$html_modulos .= $moludo['modulo'] . '<br/>';
		}
		if (!empty($html_modulos)) {
			$datos['modulos'] = $html_modulos;
		}
		
		return $datos;
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA principal
	//-----------------------------------------------------------------------------------

	function conf__principal(toba_ei_pantalla $pantalla)
	{
		$datos = $this->get_datos_ev_info();
		$this->ev_info()->set_datos($datos);
	}
	
	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__formulario(toba_ei_formulario $formulario)
	{
		$parametro = $this->cn()->entidad()->get_datos();
		$tipo_de_parametro = $parametro['tipo_de_parametro'];

		if (isset($this->valor_parametro_temp)) {
			$formulario->set_datos($this->valor_parametro_temp);
		} else {
			// Se define el ef valor a mostrar de acuerdo al tipo de parámetro y tipo de dato			
			if ($tipo_de_parametro == parametro::tipo_param_enumerado) {
				$this->s__campo_valor = 'valor_enumerado';
				$formulario->desactivar_efs(array('valor_fecha', 'valor_cadena', 'valor_entero', 'valor_hora', 'valor_punto_flotante'));
			} else {
				switch ($parametro['tipo_de_dato']) {
					case parametro::tipo_dato_fecha:
					case parametro::tipo_dato_fecha_y_hr:
						$this->s__campo_valor = 'valor_fecha';
						$formulario->desactivar_efs(array('valor_cadena', 'valor_entero', 'valor_hora', 'valor_punto_flotante', 'valor_enumerado'));
						break;
					case parametro::tipo_dato_hora:
						$this->s__campo_valor = 'valor_hora';
						$formulario->desactivar_efs(array('valor_cadena', 'valor_entero', 'valor_fecha', 'valor_punto_flotante', 'valor_enumerado'));
						break;
					case parametro::tipo_dato_entero:
						$this->s__campo_valor = 'valor_entero';
						$formulario->desactivar_efs(array('valor_cadena', 'valor_fecha', 'valor_hora', 'valor_punto_flotante', 'valor_enumerado'));
						break;
					case parametro::tipo_dato_punto_flot:
						$this->s__campo_valor = 'valor_punto_flotante';
						$formulario->desactivar_efs(array('valor_cadena', 'valor_fecha', 'valor_hora', 'valor_entero', 'valor_enumerado'));
						break;
					case parametro::tipo_dato_cadena:
						$this->s__campo_valor = 'valor_cadena';
						$formulario->desactivar_efs(array('valor_fecha', 'valor_entero', 'valor_hora', 'valor_punto_flotante', 'valor_enumerado'));
						break;
				}
			}

			// Se setean los valores del formulario de encabezado
			$formulario->set_datos(array(
				'parametro' => $parametro['parametro'],
				$this->s__campo_valor => $parametro['valor_default']
			));
		}

		// Se ocultan los ef's correspondientes al Valor Desde y Valor Hasta en caso de que el Tipo de parámetro no sea Rango
		if ($tipo_de_parametro != parametro::tipo_param_rango) {
			$formulario->desactivar_efs(array('valor_desde', 'valor_hasta'));
		} else {
			$desde = $parametro['valor_desde'];
			$hasta = $parametro['valor_hasta'];
			if ($parametro['tipo_de_dato'] == parametro::tipo_dato_hora) {
				$desde = substr($desde, 0, 2) . ':' . substr($desde, 2, 2) . ':' . substr($desde, 4, 2);
				$hasta = substr($hasta, 0, 2) . ':' . substr($hasta, 2, 2) . ':' . substr($hasta, 4, 2);
			}
			$formulario->set_datos(array('valor_desde' => $desde,
				'valor_hasta' => $hasta
			));
			$formulario->set_solo_lectura(array('valor_desde', 'valor_hasta'));
		}

		// Se setea la obligatoriedad del campo valor y el resto como de solo lectura
		$tipos_perfil_datos = g3entidad::get_tipos_niveles_perfil_datos();
		if (in_array(g3entidad::tipo_institucion, $tipos_perfil_datos)) {
			$formulario->ef($this->s__campo_valor)->set_obligatorio(true);
		} else {
			$formulario->set_solo_lectura($this->s__campo_valor);
		}
	}

	function evt__formulario__modificacion($datos)
	{
		$datos['valor_default'] = $datos[$this->s__campo_valor];
		try {
			$this->cn()->entidad()->set_datos($datos);
		} catch (guarani_error $e) {
			$this->valor_parametro_temp = $datos;
			throw $e;
		}
	}

}
?>