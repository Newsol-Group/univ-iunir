<?php
/**
 *
 * 
 * @package 	Tesis
 * @subpackage 	
 * 
 */
class ci_edi_circuitos_tesis extends guarani_pers_ci
{
	protected $datos_temp;

	//-----------------------------------------------------------------------------------
	//---- PANTALLA DATOS PRINCIPALES
	//-----------------------------------------------------------------------------------

	//-----------------------------------------------------------------------------------
	//---- form_circuito ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_circuito(toba_ei_formulario $form)
	{
		if (isset($this->datos_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp);
		} else {
			$datos = $this->cn()->entidad()->get_datos_generales();
			$form->set_datos($datos);
		}
	}

	function evt__form_circuito__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_generales($datos);
		} catch (guarani_error $e) {
			$this->datos_temp = $datos;
			throw $e;
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA TRANSICIONES
	//-----------------------------------------------------------------------------------	

	function evt__pant_transiciones__salida()
	{
		// Cuando se cambia de Tab se cancela la posible edición o alta de una transición.
		$this->dep('editor_camino')->evt__cancelar();
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA GRÁFICO
	//-----------------------------------------------------------------------------------	

	//-----------------------------------------------------------------------------------
	//---- grafico ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__grafico(toba_ei_esquema $esquema)
	{
		$caminos = $this->cn()->entidad()->get_datos_caminos();
		$caminos = $this->get_estados_transiciones($caminos);

		$diagrama = "digraph G { \n";
		// Al setear el tamaño se debería probar cuál es el máximo de acuerdo a la pantalla donde se insertará el grafo.
		$diagrama .= "bgcolor=\"#FFFFFF\";\n";

		foreach ($caminos['estados'] as $estado) {
			$propiedades = '';
			if ($estado['invisible']) {
				$propiedades = 'style="invis" shape="point"';
			}
			$diagrama .= "{$estado['estado']} [label = \"{$estado['nombre']}\" fontname=\"Verdana\" fontsize = \"10\" $propiedades];\n";
		}

		foreach ($caminos['transiciones'] as $transicion) {
			$diagrama .= "{$transicion['estado_inicial']} -> {$transicion['estado_final']} [label = \" {$transicion['accion_nombre']}\" fontname=\"Verdana\" fontsize = \"10\"];\n";
		}

		$diagrama .= '}';
		$esquema->set_datos($diagrama);
	}

	//-----------------------------------------------------------------------------------
	//---- operaciones auxiliares -------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_estados_transiciones($caminos)
	{
		$estados = array();
		$transiciones = array();
		foreach ($caminos as $c) {
			if (isset($c['estado_inicial'])) {
				// Si el camino tiene estado inicial...
				if (!isset($estados['estado_inicial'])) {
					// Si el estado inicial no está agregado al conjunto de estados a retornar...
					$estados[$c['estado_inicial']] = array('estado' => $c['estado_inicial'], 'nombre' => $c['estado_inicial_nombre'], 'invisible' => false);
				}
			} else {
				$estado_invisible = "invisible__{$c['accion']}";
				$c['estado_inicial'] = $estado_invisible;
				$estados[$estado_invisible] = array('estado' => $c['estado_inicial'], 'nombre' => $c['accion_nombre'], 'invisible' => true);
			}
			$transiciones[] = $c;
			if (!isset($estados[$c['estado_final']])) {
				// Si el estado inicial no está agregado al conjunto de estados a retornar...
				$estados[$c['estado_final']] = array('estado' => $c['estado_final'], 'nombre' => $c['estado_final_nombre'], 'invisible' => false);
			}
		}
		return array('estados' => $estados, 'transiciones' => $transiciones);
	}

}
?>