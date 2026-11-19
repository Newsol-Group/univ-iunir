<?php

/**
 *
 *
 * @package 	Constancias y Certificados
 * @subpackage 	Administrar Solicitudes
 * 
 */
class pant_imprimir_rechazar_nsg extends guarani_pers_ei_pantalla
//pant_imprimir_rechazar
{

	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<hr/>';
		parent::generar_layout();

		$url_documento = $this->controlador()->get_preview_documento_url();

		$icon_path = \toba::proyecto()->get_www() . '/img/acciones/editar.gif'; // => .../guarani/www/img/acciones/editar.gif
		$icon_src  = '';

		if (is_readable($icon_path)) {
			$icon_src = 'data:image/gif;base64,' . base64_encode(file_get_contents($icon_path));
		}

	

		if (!empty($url_documento)) {
			echo '<div id="div_documento_constancia">';
			echo 	'<div>';
			echo 		'<span id="span_documento_constancia_0"><b>Documento adjunto:</b></span>';
			echo 	'</div>';
			echo 	'<div>';
			echo 		'<span id="span_documento_constancia_1">Documento respaldatorio de la solicitud de constancia actual.</span>';
			echo 	'</div>';
			echo 	'</br>';
			echo 	'<img id="doc-preview-img" class="img-fluid mx-auto d-block" src="' . $url_documento . '" alt="Documento" style="max-height:150px; object-fit:contain;">';
			echo 	'<button id="btn_download">
						Descargar
					</button>';
			echo '</div>';
		}
	}

	function extender_objeto_js()
	{
		$url_download = $this->controlador()->get_download_documento_url();
		if (empty($url_download)) {
			return;
		}

		echo "
		$(function () {
			var \$container = $('#div_documento_constancia');
			var \$img = $('#doc-preview-img');
			var \$btn = $('#btn_download');

			if (!\$container.length || !\$img.length || !\$btn.length) return;

			var \$wrap = $('<div id=\"doc-preview-wrap\"></div>').css({
				'display': 'inline-block',
				'text-align': 'center'
			});

			\$wrap.append(\$img);
			\$wrap.append(\$btn);
			\$container.append(\$wrap);

			\$img.css({
				'display': 'block',
				'margin': '0 auto'
			});

			\$btn.css({
				'display': 'block',
				'margin': '8px auto 0 auto'
			});

			\$btn.off('click.nsgDownload').on('click.nsgDownload', function () {
				var url = '" . $url_download . "';
				window.open(url, '_blank');
			});
		});
		";
	}
}
