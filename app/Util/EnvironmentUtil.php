<?php
namespace Util;

class EnvironmentUtil{
	// 本番環境で実行中ならtrueを返す
	public static function isProduct(): bool{
		return getenv("ENV_NAME") === "product";
	}

	public static function getEnvName(): string{
		return getenv("ENV_NAME");
	}
}
