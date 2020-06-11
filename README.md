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
1. 作成したdb名が「actlab」でないならsetup.sqlの1行目を修正
1. 任意のmysqlサーバでsetup.sqlを実行
	- 配布版と非公開版は一部異なる場合があるが、テーブル構造は同じである
1. ./src/settings.phpのDBの情報を修正
1. 掲示板も動かしたい場合には、各cgiファイル先頭行のperlのパスやパーミッションなどを設定
	- [詳細は掲示板モジュール配布元](https://www.kent-web.com/bbs/light.html)へ


## 利用ライブラリ
- slim/twig-view
- twig/extensions
- doctorine/dbal
- bryanjhv/slim-session
- Bootstrap4
- lightboard
