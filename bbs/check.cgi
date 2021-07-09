#!/usr/bin/perl

#┌─────────────────────────────────
#│ LightBoard : check.cgi - 2019/12/08
#│ copyright (c) kentweb, 1997-2019
#│ http://www.kent-web.com/
#└─────────────────────────────────

# モジュール宣言
use strict;
use CGI::Carp qw(fatalsToBrowser);

# 外部ファイル取り込み
require './init.cgi';
my %cf = set_init();

print <<EOM;
Content-type: text/html; charset=utf-8

<!doctype html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="robots" content="noindex">
<title>Check Mode</title>
</head>
<body>
<b>Check Mode: [ $cf{version} ]</b>
<ul>
EOM

# データファイル
my %log = (
	logfile  => 'ログファイル',
	nofile   => '過去ログ記事ファイル',
	numfile  => '投稿承認用通番ファイル',
	sesfile  => 'セッションファイル',
	conffile => '確認ファイル',
	);
foreach ( keys %log ) {
	if (-f $cf{$_}) {
		print "<li>$log{$_}パス : OK\n";
		if (-r $cf{$_} && -w $cf{$_}) {
			print "<li>$log{$_}パーミッション : OK\n";
		} else {
			print "<li>$log{$_}パーミッション : NG\n";
		}
	} else {
		print "<li>$log{$_}パス : NG\n";
	}
}

# ディレクトリ
my %dir = (
	pastdir => '過去ログディレクトリ',
	logdir  => '投稿用承認ディレクトリ',
	);
foreach ( keys(%dir) ) {
	if (-d $cf{pastdir}) {
		print "<li>$dir{$_}ディレクトリ : OK\n";
		if (-r $cf{$_} && -w $cf{$_} && -x $cf{$_}) {
			print "<li>$dir{$_}パーミッション : OK\n";
		} else {
			print "<li>$dir{$_}パーミッション : NG\n";
		}
	} else {
		print "<li>$dir{$_}ディレクトリ : NG\n";
	}
}

# テンプレート
foreach (qw(bbs error find conf mesg note past enter del)) {
	if (-f "$cf{tmpldir}/$_.html") {
		print "<li>テンプレート( $_.html ) : OK\n";
	} else {
		print "<li>テンプレート( $_.html ) : NG\n";
	}
}

# Image-Magick動作確認
eval { require Image::Magick; };
if ($@) {
	print "<li>Image-Magick動作: NG\n";
} else {
	print "<li>Image-Magick動作: OK\n";
}

print <<EOM;
</ul>
</body>
</html>
EOM
exit;

