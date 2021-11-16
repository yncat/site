<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->get('/disclose/leagal', function (Request $request, Response $response) {
    $data = [];

	$data["info"]= [
		"販売者" => "AccessibleToolsLaboratory",
		"運営責任者" => "代表理事　河内　勇樹",
		"住所" => "メールにて開示の求めがあれば、遅滞なく開示します。",
		"電話番号" => "メールにて開示の求めがあれば、遅滞なく開示します。",
		"メールアドレス" => "support(at)actlab.org ※(at)を@に変えてお送りください。",
		"商品の価格" => "各ソフトウェアのページに記載",
		"支払方法" => "クレジットカード決済(Visa、Mastercard、American Express、JCB、Diners Club、Discoverが利用可能)・銀行振込",
		"商品代金以外に<wbr>必要な料金" => "クレジットカード決済の場合：なし\n銀行振込の場合：処理手数料550円(税込)＋ご利用の金融機関が定める振込手数料",
		"商品の引渡時期" => "クレジットカード決済の場合：決済語数分以内にダウンロード方法を記載したメールをお送りします。\n銀行振込の場合：入金完了を当方に通知後原則５銀行営業日以内にダウンロード方法をメールにてご案内します。",
		"支払期限" => "クレジットカード決済の場合：ご契約のクレジットカード会社が定める日\n銀行振込の場合：お申込翌日から起算して7日以内",
		"返品の可否" => "ダウンロード商品の性格上、返品はお受けできません。",
		"ソフトウェアの<wbr>動作環境" => "各ソフトウェアのページに記載"
	];


    // Render index view
    return $this->view->render($response, 'disclose/leagal.twig', $data);
});

