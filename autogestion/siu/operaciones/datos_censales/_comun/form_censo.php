<?php
namespace siu\operaciones\datos_censales\_comun;

require_once 'util_form_censo.php';

use SIU\Chulupi\util\form as form_base;
use SIU\Chulupi\util\validador;
use SIU\Chulupi\kernel;

abstract class form_censo extends form_base
{
	protected $autoset_activo = false;
	
	const NO_SELECCION = 'null';
	
	/**
	 * OBSERVACIÓN: El ID html de los formularios de esta operación se 
	 * conformará por el prefijo 'form_' seguido del ID de pagelet.
	 */
	function get_id_seccion()
	{
		// Se obtiene el ID de la sección quitando el prefijo 'form_' del ID html
		// del formulairo.
		return substr($this->get_id_html(), 5); 
	}
	
	function set_autoset_activo($activo = false)
	{
		$this->autoset_activo = $activo;
	}
	
	function is_autoset_activo()
	{
		return $this->autoset_activo;
	}
	
	protected function set_campo($nombre, $def)
	{
		if ($this->autoset_activo) {
			$def[self::clase_css] = isset($def[self::clase_css]) ? $def[self::clase_css] . ' js-editable' : 'js-editable';
		}
		parent::set_campo($nombre, $def);
	}
	
	function agregar_error($nombre, $msg)
	{
		if (!isset($this->errores[$nombre])) {
			$this->errores[$nombre] = $msg;
		}
	}
	
	//--------------------------------------------------------------------------
	
	/**
	 * Obtiene el campo ID de un campo descriptivo
	 * Si no tiene, retorna null
	 */
	function get_campo_id($campo_descr)
	{
		return util_form_censo::get_campo_id($campo_descr);
	}
	
	/**
	 * Obtiene el campo descriptivo a partir de un campo ID
	 * Si no tiene, retorna null
	 */
	function get_campo_descr($campo_id)
	{
		return util_form_censo::get_campo_descr($campo_id);
	}
	
	//--------------------------------------------------------------------------
	
	function def_obligatorio($nombre)
	{
		return util_form_censo::obligatorio($nombre);
	}
	
	function def_obligatorio_becas($nombre)
	{
		return util_form_censo::obligatorio_becas($nombre);
	}

        function def_obligatorio_reapertura($nombre)
	{
		return util_form_censo::obligatorio_reapertura($nombre);
	}

	function def_visible($nombre) 
	{
		return util_form_censo::visible($nombre);
	}
	
	//--------------------------------------------------------------------------
	
	/**
	 * Verifica si existe al menos un campo en un agrupado que esté definido 
	 * como visible
	 */
	function hay_visibles_agrupado($agrupado)
	{
		$seccion = $this->get_id_seccion();
		return util_form_censo::hay_visibles_agrupado($seccion, $agrupado);
	}
	
	//--------------------------------------------------------------------------
	
	function control_group($nombre, $clase_control_group = '')
	{
		if ($this->def_visible($nombre)) {
			if (!$this->check_activo($nombre)) {return '';}
			if ($this->get_elemento_html_tipo($nombre) == 'hidden') {
				return $this->elemento($nombre);
			}
			$label = $this->label($nombre);

			if (($this->get_obligatorio($nombre)) || ($this->def_obligatorio($nombre))) {
				$label = str_replace('class="optional"', 'class="label-obligatorio"', $label);
				$label = str_replace('</label>', ' (*)</label>', $label);
			}
			
			$elemento = $this->elemento($nombre);
			$tiene_error = $this->tiene_error($nombre);
			$clase_error = ($tiene_error) ? 'error' : '';
			$obligatorio_becas = $this->def_obligatorio_becas($nombre);
			$clase_obligatorio_becas = ($obligatorio_becas) ? 'obligatorio_becas' : '';

                        $obligatorio_reapertura = $this->def_obligatorio_reapertura($nombre);
                        $clase_obligatorio_reapertura = ($obligatorio_reapertura) ? 'obligatorio_reapertura' : '';

			$html  = "<div class='control-group $clase_control_group $clase_error $clase_obligatorio_becas $clase_obligatorio_reapertura'>";
			$html .= $label;
			$html .= $elemento;
			$html .= $this->ayuda($nombre);
			if ($tiene_error) {
				$html .= "<br/>";
				$html .= "<span class='help-inline'>{$this->error($nombre)}</span>";
			}
			$html .= "</div>";

			return $html;
		} else {
			return '';
		}
	}
	
	function checkbox($nombre)
	{
		if ($this->def_visible($nombre)) {
			if (!$this->check_activo($nombre)) {
				return '';
			}

                        $obligatorio_becas = $this->def_obligatorio_becas($nombre);
			$clase_obligatorio_becas = ($obligatorio_becas) ? 'obligatorio_becas' : '';
                        
                        $obligatorio_reapertura = $this->def_obligatorio_reapertura($nombre);
                        $clase_obligatorio_reapertura = ($obligatorio_reapertura) ? 'obligatorio_reapertura' : '';

			$id = $this->zend_form->getElement($nombre)->getId();
			$html  = "<label for='$id' class='optional checkbox $clase_obligatorio_becas $clase_obligatorio_reapertura'>";

			$label = kernel::traductor()->trans($this->raw_label($nombre));
			$ayuda = $this->ayuda($nombre);
			if (!$this->get_obligatorio($nombre)) {
				$label = '<span class="label-no-obligatorio">'.$label.'</span>';
			}
			$label .= $ayuda;
			$html .= $label.$this->elemento($nombre);
			$html .= "</label>";
			return $html;
		} else {
			return '';
		}
	}
	
	protected function validar_campo($nombre, $valor)
	{
		// http://framework.zend.com/manual/en/zend.form.elements.html#zend.form.elements.validators
		$filtro		 = $this->get_filtro($nombre);
		$filtro_params = $this->get_filtro_params($nombre);
		$obligatorio = $this->check_activo($nombre) && $this->get_obligatorio($nombre);
		$largo		 = $this->get_largo($nombre);
		
		$error_filtro = ! validador::es_valido($valor, $filtro, $filtro_params);
		$error_largo  = ($largo > 0) && mb_strlen($valor) > $largo;
		
		$error_vacio  = $obligatorio && ((empty($valor) && $valor !== '0' && $valor !== 0) || (is_null($valor) || ($valor == self::NO_SELECCION)));
		$error_select = false;
		if ($this->get_elemento_html_tipo($nombre) == 'select' && $this->get_validar_select($nombre)) {
			// se hace el chequeo del validador inarray
			$error_select = ! $this->zend_form->getElement($nombre)->isValid($valor);
		}
		
		$es_valido = ! $error_filtro && ! $error_largo && ! $error_vacio && ! $error_select;
		$msg = '';
		
		if (! $es_valido) {
			$msg = 'form_validacion_error.';
			if ($error_filtro) {
				$msg .= "filtro_$filtro";
			} elseif ($error_largo) {
				$msg .= 'largo';
			} elseif ($error_vacio) {
				$msg .= 'vacio';
			} elseif ($error_select) {
				$msg .= 'select';
			}
		}
		if (!$es_valido) {
			\klog("Error validación => $nombre:$msg");
		}
		return array($es_valido, $msg);
	}
}