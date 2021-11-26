<?php
namespace Util;

use Slim\Views\Twig;


class MailUtil{
	private const DEFAULT_MAIL_FROM = "ACTLaboratory<support@actlab.org>";
	private const DEFAULT_ENVELOPE_FROM = "sender@actlab.org";


	public static function sendWithTemplate($mail_to, string $title, string $template_name, array $data = []){
		$twig = new Twig(__DIR__ . '/../mail_templates');
		$content = $twig->fetch($template_name, $data);

		self::send($mail_to, $title, $content);
	}

	private static function send(
				string $mail_to,
				string $title,
				string $content,
				$mail_from = self::DEFAULT_MAIL_FROM,
				$envelope_from = self::DEFAULT_ENVELOPE_FROM
			){
		mb_language("Japanese");
		mb_internal_encoding("UTF-8");
		$header = "From: " . $mail_from;
		mb_send_mail($mail_to, $title, $content,$header,"-f " . $envelope_from);
	}
}
