<?php
namespace Util;

class UrlUtil{
	public static function toAbsUrl($relative){
		global $container;
		$path = trim($relative);	// 変換対象パス
		$url = "http://";
		if(!empty($_SERVER["HTTPS"])){
			$url = "https://";
		}
		$url .= $_SERVER["HTTP_HOST"];
		$url .= $container["request"]->getUri()->getBasePath();
		//-- 変換不要
		if ($path === ''){
			return $url;
		}
		if((stripos($path, 'http://') === 0) || 
		(stripos($path, 'https://') === 0) || 
		(stripos($path, 'mailto:') === 0) || 
		(stripos($path, 'tel:') === 0)){
			return $path;
		}
		//-- #anchor
		if (strpos($path, '#') === 0) {
			return $url . $path;
		}

		//-- 基準URLを分解
		$urlAry = explode('/', $url);
		if (!isset($urlAry[2])) {
			return false;
		}

		//-- //path
		if (strpos($path, '//') === 0){
			return $urlAry[0] . $path; 
		}

		//-- 基準URLのHOME(scheme://host)
		$urlHome = $urlAry[0] . '//' . $urlAry[2];

		//-- 基準URLのパス
		if (!$pathBase = parse_url($url, PHP_URL_PATH)) {
			$pathBase = '/';
		}

		//-- ?query
		if (strpos($path, '?') === 0)
			{ return $urlHome . $pathBase . $path; 
		}

		//-- /path
		if (strpos($path, '/') === 0){
			return $urlHome . $path;
		}

		//-- ./path or ../path
		$pathBaseAry = array_filter(explode('/', $pathBase), 'strlen');
		if (strpos(end($pathBaseAry), '.') !== false){
			array_pop($pathBaseAry);
		}

		foreach (explode('/', $path) as $pathElem) {
			if ($pathElem === '.') { continue; }
			if ($pathElem === '..') { array_pop($pathBaseAry); continue; }
			if ($pathElem !== '') { $pathBaseAry[] = $pathElem; }
		}

		$abs_url = $urlHome . '/' . implode('/', $pathBaseAry);
		return $abs_url;
	}
}
