<?php 
/**
 * 
 *
 *
 * @package Propuestas
 * @subpackage Validador Planes
 */
class cn_validador_casos_prueba extends guarani_pers_cn
{
	protected $s__plan_version;

	protected $s__casos_testeo;
	protected $s__caso_testeo;

	protected $s__certificado;
	protected $s__certificados;

	protected $s__conjunto;

	/*
	 * Se contruye un árbol central (del plan de estudios).
	 * Se crean varios árboles (calamares), uno por certificado en cada caso de test.
	 * Los árboles de certificados se "enganchan" del árbol del plan de estudios y setean reglas (por opción de cumplimiento) sólo a los elementos que deben evaluar.
	 * Las validaciones se lanzan desde los árboles calamares.
	 */
	function validar()
	{
		$this->s__conjunto = new conjunto_validable($this->s__plan_version);

		foreach ($this->s__casos_testeo as $caso) {
			$this->s__conjunto->set_caso_test($caso);
			foreach ($this->s__certificados as $cert) {
				$arbol_cert = $this->s__conjunto->get_arbol_certificado($this->s__plan_version, $cert);
				$this->s__conjunto->set_certificado($cert, $arbol_cert, $caso);
			}
		}

		$this->s__conjunto->validar();
	}

	function set_datos($datos)
	{
		$this->s__plan_version = $datos['plan_version'];
		$this->s__certificados = $datos['certificados'];
		$this->s__casos_testeo = $datos['casos_testeo'];
	}

	function get_datos()
	{
		if (isset($this->s__plan_version) && isset($this->s__certificados) && isset($this->s__casos_testeo)) {
			return array(	'plan_version' => $this->s__plan_version,
							'certificados' => $this->s__certificados,
							'casos_testeo' => $this->s__casos_testeo);
		}
		return array();
	}

	function get_listado_certificados($plan_version)
	{
		return toba::consulta_php('co_certificados')->get_certificados_plan_version($plan_version);
	}

	function get_listado_casos_testeo($plan_version)
	{
		$filtro = "ct_plan.plan_version = $plan_version";
		return toba::consulta_php('co_casos_testeo')->get_listado($filtro);
	}

	function seleccionar($seleccion)
	{
		$this->s__certificado = $seleccion['certificado'];
		$this->s__caso_testeo = $seleccion['caso_testeo'];
	}

	function get_resultados()
	{
		return $this->s__conjunto->get_resultados();
	}

	function get_observaciones()
	{
		return $this->s__conjunto->get_observaciones($this->s__caso_testeo, $this->s__certificado);
	}

	function get_cabecera()
	{
		return $this->s__conjunto->get_cabecera($this->s__plan_version, $this->s__caso_testeo, $this->s__certificado);
	}
}
?>