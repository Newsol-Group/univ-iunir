<?php
/**
 * Un conjunto validable está compuesto por:
 * - El árbol de actividades y módulos correspondiente a un plan-versión + el árbol de opciones y requisitos de un certificado.
 * o bien
 * - El árbol de actividades y módulos correspondiente a un plan-versión + un conjunto de casos de test.
 * Cada caso de test tiene uno o más certificados asociados, cada uno con su árbol de opciones y requisitos correspondiente.
 *
 * @package Propuestas
 * @subpackage Validador Planes
 */
class conjunto_validable_nucleo
{
	protected $arbol_plan;
	protected $arbol_certificado;
	protected $casos_test;

	function __construct($plan_version)
	{
		$this->crear_arbol_plan($plan_version);
	}

	//------------------------------------------------------------------------
	//-- Árbol del Plan-Versión
	//------------------------------------------------------------------------

	function crear_arbol_plan($plan_version)
	{
		$constructor = new constructor_planes_validacion();
		$constructor->set_metodo_constructor($this, 'constructor_elementos');
		$constructor->set_datos(toba::consulta_php('co_casos_testeo')->get_contenido_plan($plan_version));
		$this->arbol_plan = $constructor->get_nodos();
	}

	function constructor_elementos($nodo)
	{
		switch($nodo['entidad_tipo']) {
			case elemento::tipo_modulo:
				$clase = 'modulo_validacion';
				break;
			case elemento::tipo_actividad:
				$clase = 'actividad_validacion';
				break;
		}
		
		if (isset($clase)) {
			$n = new $clase($nodo['id_nodo'], $nodo['nombre']);
			$n->set_props($nodo);
			return $n;
		}
	}

	//------------------------------------------------------------------------
	//-- Árbol del Certificado
	//------------------------------------------------------------------------

	function get_arbol_certificado($plan_version, $certificado)
	{
		$datos_certificado = toba::consulta_php('co_certificados')->get_certificado($certificado, false);
		$condicion = toba::consulta_php('co_condiciones')->get_condicion_certificado_plan_version($certificado, $plan_version);
		if (empty($condicion)) {
			throw new guarani_error("No hay condiciones especificadas de como obtener el certificado '{$datos_certificado['nombre']}' para el plan versión. Debe especificarlas al asignar el certificado.");
		}
		$arbol_certificado = new certificado_validacion($certificado, $condicion['nombre']);
		$opciones = toba::consulta_php('co_condiciones')->get_opciones_condicion($condicion['condicion']);
		foreach ($opciones as $op) {
			$opcion = new opcion_validacion($op['grupo_condicion'], $op['orden']);
			$arbol_certificado->agregar_hijo($opcion);
			$elementos = toba::consulta_php('co_condiciones')->get_requisitos_opcion($op['grupo_condicion']);
			foreach ($elementos as $elemento) {
				if ($elemento['entidad_tipo'] == elemento::tipo_modulo || $elemento['entidad_tipo'] == elemento::tipo_actividad) {
					$e = $this->arbol_plan->search($elemento['elemento']);
				} else {
					$e = new requisito_validacion($elemento['requisito_condicion'], $elemento['nombre']);
					$e->set_props(array('requisito' => $elemento['requisito']));
				}
				if (! empty($elemento['php_clase'])) {
					$regla = guarani::regla($elemento['regla']);
					$regla->set_parametros($elemento['parametros']);
					$regla->set_operador_not($elemento['operador_not']);
					$e->set_regla($op['grupo_condicion'], $regla);
				}
				
				$opcion->agregar_hijo($e);
			}
		}
		return $arbol_certificado;
	}

	function set_certificado($certificado, $arbol_certificado, $caso_test=null)
	{
		if (empty($caso_test)) {
			$this->arbol_certificado = $arbol_certificado;
		} else {
			$datos_certificado = toba::consulta_php('co_certificados')->get_certificado($certificado);
			$resultado_esperado = toba::consulta_php('co_casos_testeo')->get_resultado_caso_testeo_certificado($caso_test, $certificado);
			if (empty($resultado_esperado)) {
				$cumplido = null;
			} else {
				$resultado_esperado = current($resultado_esperado);
				$cumplido = $resultado_esperado['cumplido'] == comunes::si;
			}
			$this->casos_test[$caso_test]['certificados'][$certificado] = array('nombre' => $datos_certificado['nombre'], 'cumplido' => $cumplido, 'arbol' => $arbol_certificado);
		}
	}

	//------------------------------------------------------------------------
	//-- Casos de Test
	//------------------------------------------------------------------------

	function set_caso_test($caso_test)
	{
		$datos_caso_test = toba::consulta_php('co_casos_testeo')->get_datos_caso_testeo($caso_test);
		$this->casos_test[$caso_test] = array('nombre' => $datos_caso_test['nombre']);
	}

	//------------------------------------------------------------------------
	//-- Validación
	//------------------------------------------------------------------------

	function validar($alumno=null) 
	{
		if (isset($this->arbol_certificado)) {
			return $this->arbol_certificado->validar($alumno);
		} else {
			foreach ($this->casos_test as $clave_caso => $caso) {
				foreach ($caso['certificados'] as $clave_cert => $cert) {
					$this->casos_test[$clave_caso]['certificados'][$clave_cert]['estado'] = $cert['arbol']->validar($alumno, $clave_caso);
				}
			}
		}
	}

	//------------------------------------------------------------------------
	//-- Resultados
	//------------------------------------------------------------------------

	function get_resultados()
	{
		$res = array();
		foreach ($this->casos_test as $clave_caso => $caso) {
			if (array_key_exists('certificados', $caso)) {
				foreach ($caso['certificados'] as $clave_cert => $cert) {
					$res[] = array(
								'caso_testeo' => $clave_caso,
								'nombre_caso' => $caso['nombre'],
								'certificado' => $clave_cert,
								'nombre_certificado' => $cert['nombre'],
								'estado' => $cert['estado'] ? 'tilde.gif' : 'error.png',
								'resultado_esperado' => !is_null($cert['cumplido']) ? ($cert['cumplido'] ? 'tilde.gif' : 'error.png') : 'nulo.gif',
								'observaciones' => $cert['arbol']->get_texto_observaciones(),
								'resultado_obtenido' => !is_null($cert['cumplido']) ? (($cert['estado'] && $cert['cumplido'] || !$cert['estado'] && !$cert['cumplido']) ? 'tilde.gif' : 'error.png') : 'nulo.gif'
							);
				}
			}
		}
		return $res;
	}
	
	function get_observaciones($caso_test=null, $certificado=null)
	{
		$observaciones = empty($caso_test) ? $this->arbol_certificado->get_observaciones() : $this->casos_test[$caso_test]['certificados'][$certificado]['arbol']->get_observaciones();
		return '<ul>'.$observaciones.'</ul>';
	}
	
	function get_config_ha($caso_test=null, $certificado=null)
	{
		$config_ha = empty($caso_test) ? $this->arbol_certificado->get_config_ha() : $this->casos_test[$caso_test]['certificados'][$certificado]['arbol']->get_config_ha();
		return '<ul>'.$config_ha.'</ul>';
	}

	function get_cabecera($plan_version, $caso_test, $certificado)
	{
		$filtro = "sga_planes_versiones.plan_version = $plan_version";
		$datos_plan_version = current(toba::consulta_php('co_planes')->get_lista_planes($filtro));

		$datos_caso_test = $this->casos_test[$caso_test];

		$datos_certificado = $datos_caso_test['certificados'][$certificado];
		$resultado = $datos_certificado['estado'] && $datos_certificado['cumplido'] || !$datos_certificado['estado'] && !$datos_certificado['cumplido'];

		$cabecera = "<div style='font-weight:bold'>{$datos_plan_version['propuesta_nombre']} - {$datos_plan_version['plan_nombre']} - {$datos_plan_version['version_nombre']}</div>";
		$cabecera .= "<div><span style='font-weight:bold'>Certificado: </span>{$datos_certificado['nombre']}</div>";
		$cabecera .= "<div><span style='font-weight:bold'>Caso de prueba: </span>{$datos_caso_test['nombre']}</div>";
		$cabecera .= "<div><span style='font-weight:bold'>Resultados Esperados: </span>";
		if (!is_null($datos_certificado['cumplido'])) {
			$img_tilde = toba_recurso::imagen_toba('tilde.gif');
			$img_error = toba_recurso::imagen_toba('error.png');
			$cabecera .= $resultado ? "<img src='$img_tilde'/> El resultado de la validación coincide con el resultado esperado." : "<img src='$img_error'/> El resultado de la validación no coincide con el resultado esperado.";
		} else {
			$cabecera .= "La validación no contiene resultados esperados.";
		}
		$cabecera .= '</div>';

		return $cabecera;		
	}
}
?>