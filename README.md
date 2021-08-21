# ACT Laboratory webSite


## 動作環境
- PHP5.5以上かつintlが有効かされていること
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
1. 管理者ページを利用したい場合には、DBのmembersテーブルにログイン情報を手動で追加する
1. 掲示板も動かしたい場合には、各cgiファイル先頭行のperlのパスやパーミッションなどを設定
	- [詳細は掲示板モジュール配布元](https://www.kent-web.com/bbs/light.html)へ

## .htaccessファイルでの参考設定例
```
<IfModule mod_rewrite.c>
	RewriteEngine On
	RewriteCond %{REQUEST_URI} !(^/public/)
	RewriteCond %{REQUEST_URI} !(^/bbs/)
	RewriteCond %{REQUEST_URI} !(^/git-pull.php)
	RewriteCond %{REQUEST_URI} !(^/git-release.php)
	RewriteCond %{REQUEST_URI} !(^/dbdump.php)

	RewriteRule ^ index.php [QSA,L]
```

## 設定が必要な環境変数
- 設定方法はOSにより異なるが、ウェブサーバがApacheで、.htaccessが使えるなら上記設定と合わせて以下のように書く。
```
	SetEnv ENV_NAME 'local'
	SetEnv DB_HOST 'localhost'
	SetEnv DB_USER 'actlab'
	SetEnv DB_NAME 'actlab'
	SetEnv DB_PASS '********'				#DB接続用PW兼メアド変更時等のパラメータ暗号化キー
	SetEnv Dropbox_TOKEN '********'			#DropboxAPI token
	SetEnv BBS_PASS '********'				#BBS管理者PW
	SetEnv GITHUB_TOKEN '********'			#GitHub token
	SetEnv SCRIPT_PASSWORD '********'		#ルートに置いてある外部実行用スクリプトの起動PW
	SetEnv SLACK_NOTIFY_URL 'https://hooks.slack.com/services/*****'
	SetEnv SLACK_DAILY_URL 'https://hooks.slack.com/services/*****'

	SetEnv TWITTER_API_KEY '********'
	SetEnv TWITTER_API_SECRET '********'
	SetEnv TWITTER_ACCESS_TOKEN '********'
	SetEnv TWITTER_ACCESS_TOKEN_SECRET '********'
```
- その他の環境については上記を適宜読み替える。

## 利用ライブラリ
- slim/twig-view
- twig/extensions
- doctorine/dbal
- bryanjhv/slim-session
- Bootstrap4
- abraham/twitteroauth
- nojimage/twitter-text-php
- lightboard
