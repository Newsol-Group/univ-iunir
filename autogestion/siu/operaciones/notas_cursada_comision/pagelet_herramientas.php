<?php
namespace siu\operaciones\notas_cursada_comision;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;
use siu\modelo\entidades\parametro;

class pagelet_herramientas extends pagelet
{
	function get_nombre()
	{
		return 'herramientas';
	}

	/**
	 * @return \siu\modelo\transacciones\carga_notas_cursada_comision
	 */
	protected function modelo()
	{
		return $this->controlador->modelo();
	}

	function prepare()
	{
		$this->data = [];

                // Recupero parámetros de la comisión
                $params_comision = $this->controlador->get_params_comision();

                $es_acta = false;
                // Si se trata de 'Carga de notas' de un acta
                if (isset($params_comision['id_acta'])) {
                    $es_acta = true;
                }

		$encabezado = $this->controlador->get_encabezado();
		$this->data['tiene_instancia_regularidad'] = !empty($encabezado['instancia_regularidad']);
		$this->data['tiene_instancia_promocion'] = !empty($encabezado['instancia_promocion']);
		$this->add_var_js('id_escala_regularidad', $encabezado['escala_nota_regularidad']);
		$this->add_var_js('id_escala_promocion', $encabezado['escala_nota_promocion']);
		$this->add_var_js('fecha_inicio', $encabezado['fecha_inicio_periodo_visual']);
		$this->add_var_js('fecha_fin', date('d/m/Y'));
		
		$this->add_mensaje_js('nota_invalida', kernel::traductor()->trans('nota_invalida'));
		$this->add_mensaje_js('fecha_invalida', kernel::traductor()->trans('fecha_invalida_acta', array('%1%' => $encabezado['fecha_inicio_periodo_visual'], '%2%' => date('d/m/Y'))));

		$comision = $this->controlador->get_id_comision();
		$atributos = catalogo::consultar('atributos_params', 'get_propuestas_comision', array('comision' => $comision));

		$cur_asentar_notas_actas_regulares = parametro::get_valor(\comision::cur_asentar_notas_actas_regulares, $atributos);
		$cur_asentar_notas_actas_promocion = parametro::get_valor(\comision::cur_asentar_notas_actas_promocion, $atributos);

		$this->data['cur_asentar_notas_actas_regulares'] = $cur_asentar_notas_actas_regulares;
		$this->data['cur_asentar_notas_actas_promocion'] = $cur_asentar_notas_actas_promocion;

		$this->data['ingreso_notas_sin_notas'] = \acta::ingreso_notas_sin_notas;
		
		$this->data['resultado_ausente'] = \escalas_notas::resultado_ausente;
		
		$this->add_mensaje_js('no_pueden_calcular_notas', kernel::traductor()->trans('notas_cursadas_comision.no_pueden_calcular_notas'));
		$this->add_mensaje_js('escalas_notas_deben_ser_numericas', kernel::traductor()->trans('notas_cursadas_comision.escalas_notas_deben_ser_numericas'));
		$this->add_mensaje_js('no_hay_evaluaciones_promediables', kernel::traductor()->trans('notas_cursadas_comision.no_hay_evaluaciones_promediables'));
		$this->add_mensaje_js('escalas_notas_deben_ser_consistentes', kernel::traductor()->trans('notas_cursadas_comision.escalas_notas_deben_ser_consistentes'));
		$this->add_mensaje_js('no_hay_evaluaciones_comision', kernel::traductor()->trans('notas_cursadas_comision.no_hay_evaluaciones_comision'));
		$this->add_mensaje_js('error_desconocido', kernel::traductor()->trans('notas_cursadas_comision.error_desconocido'));
		
		
		// Campos del "Autocompletar" combo "Completar", ver php/operaciones/cursadas/comisiones/carga_notas/form_autocompletar.php
		$this->data['autocompletar_campo_nota_cursada'] = \comision::autocompletar_campo_nota_cursada;
		$this->data['autocompletar_campo_nota_promocion'] = \comision::autocompletar_campo_nota_promocion;
		$this->data['autocompletar_campo_resultado_cursada'] = \comision::autocompletar_campo_resultado_cursada;
		$this->data['autocompletar_campo_resultado_promocion'] = \comision::autocompletar_campo_resultado_promocion;
		$this->data['autocompletar_campo_fecha'] = \comision::autocompletar_campo_fecha;
		$this->data['autocompletar_campo_observaciones'] = \comision::autocompletar_campo_observaciones;
		
		$this->add_var_js('autocompletar_campo_nota_cursada', \comision::autocompletar_campo_nota_cursada);
		$this->add_var_js('autocompletar_campo_nota_promocion', \comision::autocompletar_campo_nota_promocion);
		$this->add_var_js('autocompletar_campo_resultado_cursada', \comision::autocompletar_campo_resultado_cursada);
		$this->add_var_js('autocompletar_campo_resultado_promocion', \comision::autocompletar_campo_resultado_promocion);
		$this->add_var_js('autocompletar_campo_fecha', \comision::autocompletar_campo_fecha);
		$this->add_var_js('autocompletar_campo_observaciones', \comision::autocompletar_campo_observaciones);
		
		// Campos del "Autocompletar" combo "para", ver php/operaciones/cursadas/comisiones/carga_notas/form_autocompletar.php
		$this->data['autocompletar_para_todos_pagina'] = \comision::autocompletar_para_todos_pagina;
		$this->data['autocompletar_para_sin_datos_pagina'] = \comision::autocompletar_para_sin_datos_pagina;
		$this->data['autocompletar_para_todos'] = \comision::autocompletar_para_todos;
		$this->data['autocompletar_para_sin_datos'] = \comision::autocompletar_para_sin_datos;
		
		$this->add_var_js('autocompletar_para_todos_pagina', \comision::autocompletar_para_todos_pagina);
		$this->add_var_js('autocompletar_para_sin_datos_pagina', \comision::autocompletar_para_sin_datos_pagina);
		$this->add_var_js('autocompletar_para_todos', \comision::autocompletar_para_todos);
		$this->add_var_js('autocompletar_para_sin_datos', \comision::autocompletar_para_sin_datos);
		
		// Opciones del "Autocompletar" combo "Completar", ver función "get_opciones_combo_completar" de "php/operaciones/cursadas/comisiones/carga_notas/ci_autocompletar_calcular_notas.php"
		$this->data['autocompletar_campos'] = $this->get_autocompletar_campos();
		// Opciones del "Autocompletar" combo "para", ver función "get_opciones_combo_para" de "php/operaciones/cursadas/comisiones/carga_notas/ci_autocompletar_calcular_notas.php"
		$this->data['autocompletar_para'] = $this->get_todas_opciones_combo_para($es_acta);
		
		//$this->data['resultados'] = $this->modelo()->info__escalas_notas_resultados();
		//$this->add_var_js('resultados', array_column($this->data['resultados'], "valor"));
		
		$this->data['resultados_regularidad'] = $this->get_resultados_regularidad();
		$this->add_var_js('resultados_regularidad', array_column($this->data['resultados_regularidad'], "valor"));
		
		$this->data['resultados_promocion'] = $this->get_resultados_promocion();
		$this->add_var_js('resultados_promocion', array_column($this->data['resultados_promocion'], "valor"));
		
		$this->data['url_autocompletar'] = $this->get_url_autocompletar();
		
		// Si la escala de notas de regularidad es discreta muestro un select en lugar de un input
		$this->data['escala_nota_regularidad'] = $this->get_escala_nota_regularidad();
		
		// Si la escala de notas de promoción es discreta muestro un select en lugar de un input
		$this->data['escala_nota_promocion'] = $this->get_escala_nota_promocion();
		
		// Si se autocompleto bien muestro mensaje de éxito
		$this->add_mensaje_js('exito_autocompletar', "");
		if (kernel::sesion()->esta_seteada('exito_autocompletar')) {
			$this->add_mensaje_js('exito_autocompletar', kernel::sesion()->get('exito_autocompletar'));
			kernel::sesion()->borrar('exito_autocompletar');
		}
		
		// Si hubo un error al autocompletar muestro mensaje de error
		$this->add_mensaje_js('error_autocompletar', "");
		if (kernel::sesion()->esta_seteada('error_autocompletar')) {
			$this->add_mensaje_js('error_autocompletar', kernel::sesion()->get('error_autocompletar'));
			kernel::sesion()->borrar('error_autocompletar');
		}
		
		// Si no hubo cambios al autocompletar muestro mensaje informativo
		$this->add_mensaje_js('no_cambios_autocompletar', "");
		if (kernel::sesion()->esta_seteada('no_cambios_autocompletar')) {
			$this->add_mensaje_js('no_cambios_autocompletar', kernel::sesion()->get('no_cambios_autocompletar'));
			kernel::sesion()->borrar('no_cambios_autocompletar');
		}
		
		// Mensajes para el prompt de autocompletar
		$this->add_mensaje_js('msg_prompt_perdera_cambios', kernel::traductor()->trans('notas_cursada_comision.msg_prompt_perdera_cambios'));
		$this->add_mensaje_js('msg_prompt_modificara_todas_paginas', kernel::traductor()->trans('notas_cursada_comision.msg_prompt_modificara_todas_paginas'));
		$this->add_mensaje_js('aceptar', kernel::traductor()->trans('aceptar'));
		$this->add_mensaje_js('cancelar', kernel::traductor()->trans('cancelar'));
		
		// Mensajes de validación al autocompletar
		$this->add_mensaje_js('valor_vacio', kernel::traductor()->trans('notas_cursada_comision.valor_vacio'));
		$this->add_mensaje_js('completar_invalido', kernel::traductor()->trans('notas_cursada_comision.completar_invalido'));
		$this->add_mensaje_js('para_invalido', kernel::traductor()->trans('notas_cursada_comision.para_invalido'));
		$this->add_mensaje_js('resultado_invalido', kernel::traductor()->trans('notas_cursada_comision.resultado_invalido'));
		$this->add_mensaje_js('msg_exito_autocompletar', kernel::traductor()->trans('notas_cursada_comision.exito_autocompletar'));
		$this->add_mensaje_js('msg_no_cambios_autocompletar', kernel::traductor()->trans('notas_cursada_comision.no_cambios_autocompletar'));
		
		// Si se calculó notas bien muestro mensaje de éxito
		$this->add_mensaje_js('exito_calcular_nota', "");
		if (kernel::sesion()->esta_seteada('exito_calcular_nota')) {
			$this->add_mensaje_js('exito_calcular_nota', kernel::sesion()->get('exito_calcular_nota'));
			kernel::sesion()->borrar('exito_calcular_nota');
		}
		
		// Si no hay alumnos para calcular nota
		$this->add_mensaje_js('sin_alumnos_calcular_nota', "");
		if (kernel::sesion()->esta_seteada('sin_alumnos_calcular_nota')) {
			$this->add_mensaje_js('sin_alumnos_calcular_nota', kernel::sesion()->get('sin_alumnos_calcular_nota'));
			kernel::sesion()->borrar('sin_alumnos_calcular_nota');
		}
		
		// Si hubo un error al calcular notas muestro mensaje de error
		$this->add_mensaje_js('error_calcular_nota', "");
		if (kernel::sesion()->esta_seteada('error_calcular_nota')) {
			$this->add_mensaje_js('error_calcular_nota', kernel::sesion()->get('error_calcular_nota'));
			kernel::sesion()->borrar('error_calcular_nota');
		}
		
		// Si hubo error en la regla de cálculo (notas negativas) muestro facebox
		$this->add_mensaje_js('error_regla_calculo', "");
		if (kernel::sesion()->esta_seteada('error_regla_calculo')) {
			$this->add_mensaje_js('error_regla_calculo', kernel::sesion()->get('error_regla_calculo'));
			kernel::sesion()->borrar('error_regla_calculo');
		}
		
		// Campos y mensajes de "Calcular Notas"
		$this->data['instancias_comisiones'] = $this->get_instancias_calcular_notas();
		$this->data['opciones_combo_para'] = $this->get_todas_opciones_combo_para($es_acta);
		$this->data['reglas_calculo'] = catalogo::consultar('reglas_calculo', 'get_reglas_calculo', ['filtro' => null]);
		$this->data['url_calcular_notas'] = $this->get_url_calcular_notas();
		
		$this->add_var_js('inst_cursar', \comision::inst_cursar);
		$this->add_var_js('inst_promocionar', \comision::inst_promocionar);
		$this->add_var_js('reglas_calculo', array_column($this->data['reglas_calculo'], "regla_calculo"));
		$this->add_var_js('url_calcular_notas', $this->get_url_calcular_notas());
		
		$this->add_mensaje_js('instancia_invalida', kernel::traductor()->trans('notas_cursada_comision.instancia_invalida'));
		$this->add_mensaje_js('aplicar_a_invalido', kernel::traductor()->trans('notas_cursada_comision.aplicar_a_invalido'));
		$this->add_mensaje_js('regla_calculo_invalido', kernel::traductor()->trans('notas_cursada_comision.regla_calculo_invalido'));
		$this->add_mensaje_js('msg_sin_alumnos_calcular_nota', kernel::traductor()->trans('notas_cursada_comision.sin_alumnos_calcular_nota'));
		$this->add_mensaje_js('msg_exito_calcular_nota', kernel::traductor()->trans('notas_cursada_comision.exito_calcular_nota'));

	}
	
	protected function get_url_autocompletar() 
	{
		$operacion = kernel::ruteador()->get_id_operacion();
		return kernel::vinculador()->crear($operacion, 'autocompletar', [$this->controlador->hash_comision, $this->controlador->pagina]);
	}
	
	protected function get_url_calcular_notas() 
	{
		$operacion = kernel::ruteador()->get_id_operacion();
		return kernel::vinculador()->crear($operacion, 'calcular_notas', [$this->controlador->hash_comision, $this->controlador->pagina]);
	}
	
	protected function get_autocompletar_campos()
	{
		$encabezado = $this->controlador->get_encabezado();
		$tiene_instancia_regularidad = !empty($encabezado['instancia_regularidad']);
		$tiene_instancia_promocion = !empty($encabezado['instancia_promocion']);
		
		$comision = $this->controlador->get_id_comision();
		$atributos = catalogo::consultar('atributos_params', 'get_propuestas_comision', ['comision' => $comision]);

		$cur_asentar_notas_actas_regulares = parametro::get_valor(\comision::cur_asentar_notas_actas_regulares, $atributos);
		$cur_asentar_notas_actas_promocion = parametro::get_valor(\comision::cur_asentar_notas_actas_promocion, $atributos);
		
		$opciones = [];
		
		// Si tiene la instancia de 'Regularidad' y el parámetro 'cur_asentar_notas_actas_regulares' tiene valor distinto a 'Sin Nota' entonces agrego 'Nota de Regularidad'
		if ($tiene_instancia_regularidad && ($cur_asentar_notas_actas_regulares != \acta::ingreso_notas_sin_notas)) {
			$opciones[] = ['valor' => \comision::autocompletar_campo_nota_cursada, 'descr' => kernel::traductor()->trans('notas_cursada_comision.nota_regularidad')];
		}
		
		// Si tiene la instancia de 'Promoción' y el parámetro 'cur_asentar_notas_actas_promocion' tiene valor distinto a 'Sin Nota' entonces agrego 'Nota de Promoción'
		if ($tiene_instancia_promocion && ($cur_asentar_notas_actas_promocion != \acta::ingreso_notas_sin_notas)) {
			$opciones[] = ['valor' => \comision::autocompletar_campo_nota_promocion, 'descr' => kernel::traductor()->trans('notas_cursada_comision.nota_promocion')];
		}
		
		// Si tiene la instancia de 'Regularidad' entonces agrego 'Resultado de Regularidad'
		if ($tiene_instancia_regularidad) {
			$opciones[] = ['valor' => \comision::autocompletar_campo_resultado_cursada, 'descr' => kernel::traductor()->trans('notas_cursada_comision.resultado_cursada')];
		}
		
		// Si tiene la instancia de 'Promoción' entonces agrego 'Resultado de Promoción'
		if ($tiene_instancia_promocion) {
			$opciones[] = ['valor' => \comision::autocompletar_campo_resultado_promocion, 'descr' => kernel::traductor()->trans('notas_cursada_comision.resultado_promocion')];
		}
		
		$opciones[] = ['valor' => \comision::autocompletar_campo_fecha, 'descr' => kernel::traductor()->trans('notas_cursada_comision.fecha')];
		$opciones[] = ['valor' => \comision::autocompletar_campo_observaciones, 'descr' => kernel::traductor()->trans('notas_cursada_comision.observaciones')];

		return $opciones;
	}
	
	protected function get_todas_opciones_combo_para($es_acta = false)
	{
		return $this->modelo()->info__todas_opciones_combo_para($es_acta);
	}
	
	protected function get_escala_nota_regularidad()
	{
		$encabezado = $this->controlador->get_encabezado();
		$escala_nota_regularidad = [];
		// Si la escala de notas de regularidad es discreta muestro un select en lugar de un input
		if ($encabezado['escala_nota_tipo_regularidad'] == 'DISCRETA') {
			$escala_nota_regularidad = $this->modelo()->info__escala_notas($encabezado['escala_nota_regularidad']);
		}
		
		return $escala_nota_regularidad;
	}
	
	protected function get_escala_nota_promocion()
	{
		$encabezado = $this->controlador->get_encabezado();
		$escala_nota_promocion = [];
		// Si la escala de notas de promoción es discreta muestro un select en lugar de un input
		if ($encabezado['escala_nota_tipo_promocion'] == 'DISCRETA') {
			$escala_nota_promocion = $this->modelo()->info__escala_notas($encabezado['escala_nota_promocion']);
		}
		
		return $escala_nota_promocion;
	}
	
	protected function get_resultados_regularidad()
	{
		$encabezado = $this->controlador->get_encabezado();
		$tiene_instancia_regularidad = !empty($encabezado['instancia_regularidad']);
		
		$resultados_regularidad = [];
		
		// Si tiene instancia de Regularidad
		if ($tiene_instancia_regularidad) {
			
			$comision = $this->controlador->get_id_comision();
			$atributos = catalogo::consultar('atributos_params', 'get_propuestas_comision', ['comision' => $comision]);
			$cur_asentar_notas_actas_regulares = parametro::get_valor(\comision::cur_asentar_notas_actas_regulares, $atributos);

			// Si el parámetro 'cur_asentar_notas_actas_regulares' tiene el valor 'Sin Nota'
			if ($cur_asentar_notas_actas_regulares == \acta::ingreso_notas_sin_notas) {
				// Devuelvo todos los resultados posibles para la instancia de regularidad
				$resultados_regularidad = $this->modelo()->info__resultados(\comision::inst_cursar, $encabezado['escala_nota_regularidad']);
			} else {// Si el parámetro 'cur_asentar_notas_actas_regulares' es distinto a 'Sin Nota'
				// Devuelvo solo resultado 'Ausente' 
				$resultados_regularidad = $this->modelo()->info__resultados_solo_ausente();
			}
		
		}
		
		return $resultados_regularidad;
	}
	
	protected function get_resultados_promocion()
	{
		$encabezado = $this->controlador->get_encabezado();
		$tiene_instancia_promocion = !empty($encabezado['instancia_promocion']);
		
		$resultados_promocion = [];
		
		// Si tiene instancia de Promoción
		if ($tiene_instancia_promocion) {
			
			$comision = $this->controlador->get_id_comision();
			$atributos = catalogo::consultar('atributos_params', 'get_propuestas_comision', ['comision' => $comision]);
			$cur_asentar_notas_actas_promocion = parametro::get_valor(\comision::cur_asentar_notas_actas_promocion, $atributos);
			
			// Si el parámetro 'cur_asentar_notas_actas_promocion' tiene el valor 'Sin Nota'
			if ($cur_asentar_notas_actas_promocion == \acta::ingreso_notas_sin_notas) {
				// Devuelvo todos los resultados posibles para la instancia de promoción
				$resultados_promocion = $this->modelo()->info__resultados(\comision::inst_promocionar, $encabezado['escala_nota_promocion']);
			} else {// Si el parámetro 'cur_asentar_notas_actas_promocion' es distinto a 'Sin Nota'
				// Devuelvo solo resultado 'Ausente' 
				$resultados_promocion = $this->modelo()->info__resultados_solo_ausente();
			}
		}
		
		return $resultados_promocion;
	}
	
	protected function get_instancias_calcular_notas()
	{
		return $this->controlador->get_instancias_calcular_notas();
	}
}
