<?php
class form_usuario extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		// Estilos CSS
		$this->estilos();
		
		$this->generar_html_ef('usuario');
		
		// Botón para Generar Usuario
		$this->btn_generar_usuario();
		
		$this->generar_html_ef('usuario_administrador_autogestion', '220px');
	}
	
	public function estilos()
	{
		$css = '<style>';
		$css .= '#js-generar-usuario { cursor: pointer; }';
		$css .= '#js-generar-usuario:disabled, #js-generar-usuario[disabled] { cursor: not-allowed; }';
		$css .= '</style>';

		echo $css;
	}

	public function btn_generar_usuario(){
		$disabled = 'disabled';
		if ($this->controlador()->habilitar_btn_generar_usuario()) {
			$disabled = '';
		}
		
		$msj_generar_usuario = guarani_mensajes::get('800_generar_usuario');
		
		echo '<button id="js-generar-usuario" type="button" title="'.$msj_generar_usuario.'" class="ei-boton-fila" onclick="generar_usuario();" '.$disabled.'>'.$msj_generar_usuario.'</button>';
	}
}