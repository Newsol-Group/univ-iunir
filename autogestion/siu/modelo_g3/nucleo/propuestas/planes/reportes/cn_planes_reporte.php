<?php 
class cn_planes_reporte extends cn_man_zona_planes
{
	protected $s__plan_version;
	protected $s__es_nuevo;
	protected $s__var_modulos;
	
	
	function etiquetas($dep=null)
	{
		return guarani_etiquetas::get_tabla('sga_planes');
	}
	
	/*function get_listado($filtro) {
		return toba::consulta_php("co_planes_reportes")->get_listado($filtro);
	}*/
	
	function set_nuevo()
	{
		$this->s__es_nuevo = true;
	}
	
	function es_nuevo() {
		$ret = $this->s__es_nuevo;
		$this->s__es_nuevo = false;
		return $ret;
	}

	function cargar($id, $parametros=null)
	{
		$this->s__plan_version = $id;
	}

	function set_plan_version($pv)
	{
		$this->s__plan_version = $pv;
		//$this->s__es_nuevo = true;
	}
	
	function get_plan_version() {
		return toba::consulta_php('co_planes_reportes')->get_datos_plan_version($this->s__plan_version);
	}
	
	function get_actividades($modulo=false, $mostrarme='true', $hijos_mg='false') {
		return toba::consulta_php('co_planes_reportes')->get_actividades($this->s__plan_version, $modulo, $mostrarme, $hijos_mg);
	}
	
	function get_actividad_detalle($actividad=false) {
		return toba::consulta_php('co_planes_reportes')->get_actividad_detalle($this->s__plan_version, $actividad);
	}
	
	
	function get_arbol_modulos($pantalla, $modulo = false) {
		$elementos = toba::consulta_php('co_planes_reportes')->get_modulos($this->s__plan_version, $modulo);
		$cap = new constructor_arbol_plan($elementos);
		$cap->set_metodo_configurador($this, 'conf_arbol');
		return $cap->get_elementos();
	}
	
	function get_modulo_detalle($modulo){
		return toba::consulta_php('co_planes_reportes')->get_modulo_detalle($this->s__plan_version, $modulo);
	}
	
	function get_sel_mg() {
		return toba::consulta_php('co_planes_reportes')->get_materias_genericas($this->s__plan_version);
	}
	
	/*function get_sel_certif() {
		$planv = toba::db()->quote($this->s__plan_version);
		$sql = "select 	mr.modulo_revision,
						c.certificado,
						c.nombre
				from	sga_certificados c,
						sga_planes_certificados pc,
						sga_modulo_revision mr,
						sga_modulo_revision_plan mrp
				where	c.certificado = pc.certificado and
						pc.elemento = mr.elemento and
						mr.modulo_revision = mrp.modulo_revision and
						mrp.plan_version = pc.plan_version and
						pc.plan_version = $planv ";
		return guarani_db::consultar($sql);
	}*/
	function get_certificados($certificado=false) {
		return toba::consulta_php('co_planes_reportes')->get_certificados($this->s__plan_version, $certificado);
	}
	
	/*function get_modulos_certificados($cmod=false) {
		$arbol = array();
		$certs = $this->get_certificados($cmod);
		foreach ($certs as $cert) {
			$elementos[] = $cert;
			$elementos = array_merge($elementos, toba::consulta_php('co_planes_reportes')->get_modulos($this->s__plan_version, $cert['modulo_revision']));
			$cap = new constructor_arbol_plan($elementos);
			$cap->set_metodo_configurador($this, 'conf_arbol');
			$arbol[] = $cap->get_elementos();
		}
		return $arbol;
	}*/
	
	function conf_arbol($elemento) {
		$elemento->mostrar_utilerias_edicion(false);
	}
	
	function get_arbol_certificado($certificado) {
		$elementos = toba::consulta_php('co_planes_reportes')->get_arbol_certificado($this->s__plan_version, $certificado);
		$constructor = new constructor_arbol_restricciones($elementos);
		$constructor->set_metodo_configurador($this,'conf_restric');
		$arbol[] = $constructor->get_elementos();
		return $arbol;
	}
	
	function get_requisitos_plan_version($elemento='') {
		if($elemento) {
			$e = toba::db()->quote($elemento);
			$elemento = "get_plan_contenido.elemento = $e or get_plan_contenido.elemento_padre = $e";
		}
		$elementos = toba::consulta_php('co_planes_reportes')->get_requisitos_plan_version($this->s__plan_version, $elemento);
		$constructor = new constructor_arbol_plan( $elementos );
       	$constructor->set_metodo_configurador($this,'conf_arbol');
       	return $constructor->get_elementos();
	}

	function get_restricciones($actividad, $tipo=false)
	{
		$condicion = ($tipo === false || $tipo == 'A') ? condicion::tipo_rendir : $condicion = condicion::tipo_cursar;
		$elementos = toba::consulta_php('co_planes_reportes')->get_restricciones($this->s__plan_version, $actividad, $condicion);
		for($i = 0 ; $i < guarani_arreglos::count($elementos) ; $i++) {
			//si el operador_not está seteado y vale 1
			if(isset($elementos[$i]['operador_not']) && $elementos[$i]['operador_not']) {
				if($elementos[$i]['elemento_clase_gui'] == 'plan_elemento_gui_requisito') { //es un requisito
					$elementos[$i]['nombre'] = '(No) '.$elementos[$i]['nombre'];
				} else { //es un módulo o una actividad
					$elementos[$i]['condicion'] = '(No) '.$elementos[$i]['condicion'];
				}
			}
			unset($elementos[$i]['operador_not']);
		}
		$constructor = new constructor_arbol_restricciones($elementos);
		$constructor->set_metodo_configurador($this, 'conf_restric');
		$arbol[] = $constructor->get_elementos();
		return $arbol;
	}

	function get_restricciones_correlativas($actividad, $tipo=false)
	{
		$condicion = ($tipo === false || $tipo == 'A') ? condicion::tipo_rendir : $condicion = condicion::tipo_cursar;
		$elementos = toba::consulta_php('co_planes_reportes')->get_restricciones($this->s__plan_version, $actividad, $condicion);
		$restricciones = array();
		for($i = 0 ; $i < guarani_arreglos::count($elementos) ; $i++) {
			if($elementos[$i]['elemento_clase_gui'] == 'plan_elemento_gui_grupo') {
				$restricciones[$elementos[$i]['elemento']] = [$elementos[$i]['nombre'],[]];
			}
		}
		for($i = 0 ; $i < guarani_arreglos::count($elementos) ; $i++) {
			if( ($elementos[$i]['elemento_clase_gui'] != 'plan_elemento_gui_grupo') &&
				($elementos[$i]['elemento_clase_gui'] != 'plan_elemento_gui_accion') ) {
					$j = $elementos[$i]['elemento_padre'];
					$opcion = substr((string) $elementos[$i]['orden'], 0, 1);
					$regla = $elementos[$i]['condicion_tipo'];
					if (strpos($elementos[$i]['elemento_clase_gui'], 'actividad') !== false) {
						$clave = $opcion."*".$regla."*"."a_".$elementos[$i]['elemento'];
						if($elementos[$i]['operador_not']) {
							$clave .= "_not";
						}
						array_push($restricciones[$j][1],$clave);
					} else if (strpos($elementos[$i]['elemento_clase_gui'], 'modulo') !== false) {
						$clave = $opcion."*".$regla."*"."m_".$elementos[$i]['elemento'];
						if($elementos[$i]['operador_not']) {
							$clave .= "_not";
						}
						array_push($restricciones[$j][1],$clave);
					} else {
						$clave = $opcion."*".$regla."*".$elementos[$i]['elemento'];
						if($elementos[$i]['operador_not']) {
							$clave .= "_not";
						}
						array_push($restricciones[$j][1],$clave);
					}
			}
		}
		return $restricciones;
	}
	
	function get_actividades_restringidas()
	{
		return toba::consulta_php('co_planes_reportes')->get_actividades_restringidas($this->s__plan_version);
	}
	
	function get_datos_diagrama_plan($tipo=false, $elemento='')
	{
		$actividades = array();
		$modulos = array();
		$modulos_con_actividades = array();
		$requisitos = array();
		$transiciones = array();
		$resultado = array();

		$condicion = ($tipo === false || $tipo == 'A') ? condicion::tipo_rendir : condicion::tipo_cursar;
		//Se obtiene los nodos que son actividades del plan
		$sql_resultado = toba::consulta_php('co_planes_reportes')->get_nodos_plan($this->s__plan_version);
		foreach($sql_resultado as $item) {
			$item['pertenece_a_modulo'] = false;
			$indice = $item['elemento_revision'];
			$actividades[$indice] = $item;
		}
		//Se obtiene los nodos que son módulos del plan
		$sql_resultado = toba::consulta_php('co_planes_reportes')->get_nodos_plan($this->s__plan_version, false);
		foreach($sql_resultado as $item) {
			$indice = $item['elemento_revision'];
			$modulos[$indice] = $item;
		}
		//Se obtiene los las actividades que pertenecen a cada módulo
		foreach ($modulos as $modulo) {
			$aux = toba::consulta_php('co_planes_reportes')->get_modulo_contenido($this->s__plan_version, $modulo['elemento_revision']);
			$index = $modulo['elemento_revision'];
			$modulos_con_actividades[$index] = $aux;
		}
		//Se obtiene los elementos y la respectiva revisión utilizada en el plan
		$elementos = toba::consulta_php('co_planes_reportes')->get_elementos_plan_version($this->s__plan_version);
		//Se genera un mapeo de (elemento)->(elemento_revision)
		$mapeo_elementos = array();
		for($i = 0 ; $i < guarani_arreglos::count($elementos) ; $i++) {
			$clave = $elementos[$i]['elemento'];
			$mapeo_elementos[$clave] = $elementos[$i]['elemento_revision'];
		}
		//Se obtiene las restricciones (correlativas)
		foreach ($actividades as $actividad) {
			$correlativas = toba::consulta_php('co_planes_reportes')->get_transiciones_correlativas_plan($this->s__plan_version, $condicion, $actividad['elemento']);
			
			for ($i = 0; $i < guarani_arreglos::count($correlativas); $i++) {
				if(!isset($correlativas[$i]['origen'])) { //se trata de un requisito
					$requisito_id = $correlativas[$i]['regla'];
					$requisitos[$requisito_id] = $correlativas[$i]['nombre'];
					$correlativas[$i]['nombre'] = "";
					$correlativas[$i]['origen'] = $requisito_id;
					$mapeo_elementos[$requisito_id] = $requisito_id;
				} else {
					$correlativas[$i]['origen'] = $mapeo_elementos[$correlativas[$i]['origen']];
					$correlativas[$i]['destino'] = $mapeo_elementos[$correlativas[$i]['destino']];
				}
			}
			
			$index = $actividad['elemento_revision'];
			$transiciones[$index] = $correlativas;
		}

		$resultado['actividades'] = $actividades;
		$resultado['modulos'] = $modulos;
		$resultado['modulos_con_actividades'] = $modulos_con_actividades;
		$resultado['requisitos'] = $requisitos;
		$resultado['transiciones'] = $transiciones;
		$resultado['mapeo_elementos'] = $mapeo_elementos;
		return $resultado;
	}
	
	function conf_restric($elemento)
	{
		$elemento->set_mostrar_input(false);
		$elemento->set_apertura(true);
		$elemento->mostrar_utilerias_edicion(false);
	}
	
}

?>