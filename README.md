# ACT Laboratory webSite


## 動作環境
- PHP5.5以上
- Perl5系
- Apache 2.4
- Mysql5系

## 更新方法
- GitにPushすると自動で反映されるように設定してある
- 手元でよくテストしてからmasterにpushする

## 手元で動かすには
1. ここをクローン
1. mysqlでデータベースを作成
1. 作成したDBでsetup.sqlを実行
	- 配布版と非公開版は一部異なる場合があるが、テーブル構造は同じである
	- 先頭行のDB名は適宜修正してから実行する
1. ウェブサーバを設定する。Apache2.4の場合は、.htaccessファイルを作成し、次項のとおり設定
1. このファイルで示す、必要な環境変数を設定
1. 掲示板も動かしたい場合には、各cgiファイル先頭行のperlのパスやパーミッションなどを設定
	- [詳細は掲示板モジュール配布元](https://www.kent-web.com/bbs/light.html)へ

## .htaccessファイルでの参考設定例
<IfModule mod_rewrite.c>
	RewriteEngine On
	RewriteCond %{REQUEST_URI} !(^/public/)
	RewriteCond %{REQUEST_URI} !(^/bbs/)
	RewriteCond %{REQUEST_URI} !(^/git-pull.php)
	RewriteRule ^ index.php [QSA,L]


## 設定が必要な環境変数
- 設定方法はOSにより異なるが、ウェブサーバがApacheで、.htaccessが使えるなら上記設定と合わせて以下のように書く。
	SetEnv DB_HOST 'localhost'
	SetEnv DB_USER 'actlab'
	SetEnv DB_NAME 'actlab'
	SetEnv DB_PASS '********'
	SetEnv BBS_PASS '********'
- その他の環境については上記を適宜読み替える。

## 利用ライブラリ
- slim/twig-view
- twig/extensions
- doctorine/dbal
- bryanjhv/slim-session
- Bootstrap4
- lightboard
