<?php

namespace siu\extension_kernel;

use SIU\Chulupi\util\ServiciosExternos as servicios_externos;

class servicios_externos_preinscripcion extends servicios_externos
{
	protected function get_external_url($url, $params, $post = false)
	{
		$timeout = 10;
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

		if ($post) {
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		} else {
			$query = http_build_query($params);
			curl_setopt($ch, CURLOPT_URL, $url . '?' . $query);
		}

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

		$data = curl_exec($ch);
		klog(curl_error($ch));
		curl_close($ch);
		return $data;
	}

}
?>