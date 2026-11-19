<?php
namespace siu\operaciones\notas_cursada_comision;

use SIU\Chulupi\kernel;
use siu\errores\error_guarani;
use siu\modelo\_comun\comunes;
use \siu\modelo\entidades\parametro;
use siu\modelo\datos\catalogo;
use siu\modelo\entidades\persona;

class pagelet_renglones extends \siu\operaciones\_comun\pagelets\pagelet_renglones
{
	protected $precondiciones;
	
    function get_nombre()
    {
        return 'renglones';
    }

	/**
	 * @return \siu\modelo\transacciones\carga_notas_cursada_comision
	 */
	protected function modelo()
	{
		return $this->controlador->modelo();
	}

	protected function get_escalas()
	{
		$escalas = array();
		$precondiciones = $this->get_precondiciones();
		if ($precondiciones->tiene_dos_instancias() && ! $precondiciones->tiene_instancias_con_misma_escala()) {
			$escalas[] = $precondiciones->get_escala_regular();
			$escalas[] = $precondiciones->get_escala_promocion();
		} else if ($precondiciones->tiene_instancia_regular()) {
			$escalas[] = $precondiciones->get_escala_regular();
		} else {
			$escalas[] = $precondiciones->get_escala_promocion();
		}
		
		return $escalas;
	}

	protected function get_renglones()
	{
		// Se pide al controlador para hacer validación con los datos enviados al guardar
		$renglones = $this->controlador->get_pagina();
		if ($this->errores !== false && $this->errores->hay_renglones()) {
			$renglones_error = $this->errores->get_renglones();
			foreach ($renglones_error as $id => $renglon) {
				$renglones[$id]['error'] = $renglon['msg'];
				if ($renglon['error_concurrencia']) { // no se actualizan los renglones xq el cambio se invalidó
					$renglones[$id]['error_concurrencia'] = true;	
				} else {
					$renglones[$id]['nota_cursada'] = $renglon['renglon']['nota_cursada'];
					$renglones[$id]['cond_regularidad'] = $renglon['renglon']['cond_regularidad'];
					$renglones[$id]['fecha_regular'] = $renglon['renglon']['fecha_regular'];
					$renglones[$id]['resultado_cursada'] = $renglon['renglon']['resultado_cursada'];
					$renglones[$id]['nota_promocion'] = $renglon['renglon']['nota_promocion'];
					$renglones[$id]['fecha_promocion'] = $renglon['renglon']['fecha_promocion'];
					$renglones[$id]['resultado_promocion'] = $renglon['renglon']['resultado_promocion'];	
					$renglones[$id]['porc_asistencia'] = $renglon['renglon']['porc_asistencia'];
					$renglones[$id]['observacion_cursada'] = $renglon['renglon']['observacion_cursada'];
					$renglones[$id]['observacion_promocion'] = $renglon['renglon']['observacion_promocion'];
				}
				}
		}

		return $renglones;
	}

	protected function get_resultados()
	{
		return $this->modelo()->info__resultados($this->get_escala());
	}

	protected function get_condiciones()
	{
		return $this->modelo()->info__condiciones();
	}
	
	function hay_renglones()
	{
		$encabezado = $this->get_encabezado();
		return $encabezado['paginas'] > 0;
		
	}
	
	function get_pagina_actual()
	{
		return $this->controlador->pagina;
	}
	
	function get_paginas()
	{
		$encabezado = $this->get_encabezado();
		$paginas = array();
		for ($pagina = 1; $pagina <= $encabezado['paginas']; $pagina++) {
			$paginas[$pagina] = kernel::vinculador()->crear('notas_cursada_comision', 'edicion', array($this->controlador->hash_comision, $pagina));
		}
		return $paginas;
	}

	protected function get_url_guardar() 
	{
		return kernel::vinculador()->crear('notas_cursada_comision', 'guardar', array($this->controlador->hash_comision, $this->controlador->pagina));
	}
	
	function get_url_auto_alumno()
	{
		return kernel::vinculador()->crear('notas_cursada_comision', 'auto_alumno', $this->controlador->hash_comision);
	}

    function get_url_imprimir_notas()
    {
        return kernel::vinculador()->crear('notas_cursada_comision', 'imprimir_notas', $this->controlador->hash_comision);
    }
		
	protected function prepare_escalas_y_resultados($encabezado)
	{
		$precondiciones = $this->get_precondiciones();
		$cabecera = $this->get_encabezado();
		if ($precondiciones->tiene_instancia_regular()) {
			if ($cabecera['escala_nota_tipo_regularidad'] == 'DISCRETA') { 
				// Se evita traer las escalas muy pobladas a memoria
				$this->data['escala_nota_regularidad'] = $this->modelo()->info__escala_notas($precondiciones->get_escala_regular());		
			} else {
				$this->data['escala_nota_regularidad'] = array();
			}
			
			$this->data['resultados_regularidad'] = $this->modelo()->info__resultados(\comision::inst_cursar, $precondiciones->get_escala_regular());
		}
		
		if ($precondiciones->tiene_instancia_promocion()) {
			if ($cabecera['escala_nota_tipo_promocion'] == 'DISCRETA') { 
				// Se evita traer las escalas muy pobladas a memoria
				$this->data['escala_nota_promocion'] = $this->modelo()->info__escala_notas($precondiciones->get_escala_promocion());	
			} else {
				$this->data['escala_nota_promocion'] = array();
			}
			
			$this->data['resultados_promocion'] = $this->modelo()->info__resultados(\comision::inst_promocionar, $precondiciones->get_escala_promocion());
		}
	}
	
	/**
	 * 
	 * @return precondiciones_comision
	 * @throws error_guarani
	 */
	protected function get_precondiciones()
	{
		if (!isset($this->precondiciones)) {
			throw new error_guarani("Se quiere acceder a las precondiciones de una comisión pero no se han creado");
		}
		return $this->precondiciones;
	}
	
	/**
	 * 
	 * @return precondiciones_comision
	 */
	protected function crear_precondiciones_comision($encabezado, $renglones)
	{
		if (!isset($this->precondiciones)) {
			$this->precondiciones = kernel::localizador()->instanciar_params_array('operaciones\notas_cursada_comision\precondiciones_comision', array(
				$encabezado, $renglones
			));	
		}
		
		return $this->precondiciones;
	}
	
	protected function configurar_modo_edicion()
	{
		$precondiciones = $this->get_precondiciones();
		
		if ($precondiciones->tiene_dos_instancias()) {
			$tipo = 'doble';
			$escalas = array(
				'regular' => $precondiciones->get_escala_regular(),
				'promocion' => $precondiciones->get_escala_promocion()
			);
		} else if ($precondiciones->tiene_solo_instancia_regular()) {
			$tipo = 'regular';
			$escalas = array(
				'regular' => $precondiciones->get_escala_regular(),
			);
		} else {
			$tipo = 'promocion';
			$escalas = array(
				'promocion' => $precondiciones->get_escala_promocion()
			);
		}
		$this->add_var_js('tipo_edicion', $tipo);
		$this->add_var_js('id_escalas', $escalas);
	}
	
    function prepare()
    {
		$encabezado = $this->get_encabezado();
		$renglones  = $this->get_renglones();
		$precondiciones = $this->crear_precondiciones_comision($encabezado, $renglones);		
		
		$this->data = array();
		$this->data['encabezado'] = $encabezado;
		$this->data['renglones'] = $renglones;
		$this->data['precondiciones'] = $precondiciones;
		
		$this->prepare_escalas_y_resultados($encabezado);
		
		$comision = $this->controlador->get_id_comision();
		$atributos = catalogo::consultar('atributos_params', 'get_propuestas_comision', array('comision' => $comision));

		$cur_asentar_notas_actas_regulares = parametro::get_valor(\comision::cur_asentar_notas_actas_regulares, $atributos);
		$cur_asentar_notas_actas_promocion = parametro::get_valor(\comision::cur_asentar_notas_actas_promocion, $atributos);

		kernel::log()->add_debug('cur_asentar_notas_actas_regulares', $cur_asentar_notas_actas_regulares);
		kernel::log()->add_debug('cur_asentar_notas_actas_promocion', $cur_asentar_notas_actas_promocion);
		
		$this->data['comision'] = $comision;
		
		$this->data['cur_asentar_notas_actas_regulares'] = false;
		$this->data['cur_asentar_notas_actas_promocion'] = false;

		$this->add_var_js('cur_asentar_notas_actas_regulares', $cur_asentar_notas_actas_regulares);
		$this->add_var_js('cur_asentar_notas_actas_promocion', $cur_asentar_notas_actas_promocion);

		$this->add_var_js('resultado_nota_aprobado', comunes::resultado_nota_aprobado);
		$this->add_var_js('resultado_nota_desaprobado', comunes::resultado_nota_desaprobado);
		$this->add_var_js('resultado_nota_ausente', comunes::resultado_nota_ausente);

		$this->add_var_js('ingreso_notas_no_obligatorio', \acta::ingreso_notas_no_obligatorio);
		$this->add_var_js('ingreso_notas_obligatorio', \acta::ingreso_notas_obligatorio);
		$this->add_var_js('ingreso_notas_sin_notas', \acta::ingreso_notas_sin_notas);

		$this->data['condiciones'] = $this->get_condiciones();
		$this->data['url_guardar'] = $this->get_url_guardar();
		$this->data['url_imprimir_notas'] = $this->get_url_imprimir_notas();

		// Atributos para la resolución de parámetros
		$planes = array_column(\toba::consulta_php('co_comisiones')->get_planes_comision($comision), 'plan');
		$registrar_asistencia = parametro::get_valor('asistencia_registrar_asistencia', $planes);
		$this->data['registrar_asistencia'] = $registrar_asistencia;
		$this->data['asist_no_registra'] = \asistencia::asist_no_registra;


		$this->add_var_js('highlight_renglon', $this->renglon);
		
		$this->configurar_modo_edicion();
		
		$this->add_var_js('fecha_inicio', $encabezado['fecha_inicio_periodo_visual']);
		$this->add_var_js('fecha_fin', date('d/m/Y'));
		$this->add_var_js('url_autocomplete', $this->get_url_auto_alumno());

		$this->add_var_js('tiene_dos_instancias', $precondiciones->tiene_dos_instancias());
		$this->add_var_js('tiene_instancias_con_misma_escala', $precondiciones->tiene_instancias_con_misma_escala());

		$this->add_mensaje_js('no_se_encontraron_alumnos', kernel::traductor()->trans('no_se_encontraron_alumnos'));
		$this->add_mensaje_js('fecha_invalida', kernel::traductor()->trans('fecha_invalida_acta', array('%1%' => $encabezado['fecha_inicio_periodo_visual'], '%2%' => date('d/m/Y'))));
		$this->add_mensaje_js('nota_invalida', kernel::traductor()->trans('nota_invalida'));
		$this->add_mensaje_js('nota_incompatible', kernel::traductor()->trans('nota_incompatible'));
		$this->add_mensaje_js('nota_obligatoria', kernel::traductor()->trans('nota_obligatoria'));
		$this->add_mensaje_js('condicion_invalida', kernel::traductor()->trans('800CUR_error_resultado_condicion'));
		$this->add_mensaje_js('observacion_invalida', kernel::traductor()->trans('notas_cursadas_comision.observacion_invalida'));
		$this->add_mensaje_js('mostrar_actas_promocion', kernel::traductor()->trans('mostrar_actas_promocion'));
		$this->add_mensaje_js('esconder_actas_promocion', kernel::traductor()->trans('esconder_actas_promocion'));

		$this->add_mensaje_js('guardado_exitoso', kernel::traductor()->trans('notas_cursada_guardado_exitoso'));
		$this->add_mensaje_js('guardado_error', kernel::traductor()->trans('notas_cursada_guardado_error'));
		
		// Mensajes para el prompt de cambio de página
		$this->add_mensaje_js('msg_prompt_perdera_cambios', kernel::traductor()->trans('notas_cursada_comision.msg_prompt_perdera_cambios'));
		$this->add_mensaje_js('aceptar', kernel::traductor()->trans('aceptar'));
		$this->add_mensaje_js('cancelar', kernel::traductor()->trans('cancelar'));
		
	}

    function get_js_files()
    {
        $files = parent::get_js_files();
        $files[] = kernel::vinculador()->vinculo_recurso("js/utilidades.js");
        return $files;
    }
	
    function get_actas()
    {	
		return $this->modelo()->info__actas_abiertas(['comision' => $this->controlador->get_id_comision()]);
    }
}