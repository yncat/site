# モジュール宣言/変数初期化
use strict;
my %cf;
#┌─────────────────────────────────
#│ LightBoard : init.cgi - 2019/12/10
#│ copyright (c) kentweb, 1997-2019
#│ http://www.kent-web.com/
#└─────────────────────────────────
$cf{version} = 'LightBoard v11.01';
#┌─────────────────────────────────
#│ [注意事項]
#│ 1. このプログラムはフリーソフトです。このプログラムを使用した
#│    いかなる損害に対して作者は一切の責任を負いません。
#│ 2. 設置に関する質問はサポート掲示板にお願いいたします。
#│    直接メールによる質問は一切お受けいたしておりません。
#└─────────────────────────────────

#===========================================================
# ■ 基本設定
#===========================================================

# 管理用パスワード
$cf{password} = '********';

# パスワード制限をする場合入室パスワード設定
# → 空欄の場合はパスワード制限なし
$cf{enter_pwd} = '';

# パスワード制限時のセッションの許容時間（分単位）
# → 入室後からアクセス可能時間
$cf{sestime} = 60;

# 投稿記事承認制 (0=no 1=yes)
# → 投稿記事を管理者が表示前に確認する場合（スパム対策）
$cf{approve} = 0;

# 掲示板タイトル
$cf{bbs_title} = '掲示板 - ACT Laboratory';

# 本体プログラムURL【URLパス】
$cf{bbs_cgi} = './light.cgi';

# 管理プログラムURL【URLパス】
$cf{admin_cgi} = './admin.cgi';

# ログファイル【サーバパス】
$cf{logfile} = './data/log.cgi';

# セッションファイル【サーバパス】
$cf{sesfile} = './data/ses.cgi';

# 確認画面ファイル【サーバパス】
$cf{conffile} = './data/conf.cgi';

# テンプレートディレクトリ【サーバパス】
$cf{tmpldir} = './tmpl';

# アイコンディレクトリ【URLパス】
$cf{cmnurl} = './cmn';

# 投稿承認ログディレクトリ【サーバパス】
$cf{logdir} = './data/log';

# 投稿承認用通番ファイル
$cf{numfile} = './data/num.dat';

# 最大記事数（これを超える記事は古い順に削除）
$cf{maxlog} = 1000;

# １ページあたりの記事表示件数
$cf{pg_max} = 20;

# 戻り先URL【URLパス】
$cf{homepage} = '/';

# URLの自動リンク (0=no 1=yes)
$cf{auto_link} = 1;

# 引用部色変更
#  1 : 色指定を行うと「引用部」を色変更します
#  2 : この機能を使用しない場合は何も記述しない
$cf{ref_col} = "#0000a0";

# メール通知機能
# → 0=no  1=yes
$cf{mailing} = 1;

# メール通知先アドレス（メール通知する場合）
$cf{mailto} = 'notify@example.com';

# sendmailのパス（メール通知する場合）
$cf{sendmail} = '/usr/sbin/sendmail';

# sendmailの -fコマンドが必要な場合
# 0=no 1=yes
$cf{sendm_f} = 0;

# 記事の更新は method=post に限定する場合（セキュリティ対策）
#  → 0=no 1=yes
$cf{postonly} = 1;

# 投稿制限（セキュリティ対策）
#  0 : しない
#  1 : 同一IPアドレスからの投稿間隔を制限する
#  2 : 全ての投稿間隔を制限する
$cf{regCtl} = 0;

# 制限投稿間隔（秒数）
# → $regCtl での投稿間隔
$cf{wait} = 60;

# 禁止ワード
# → 投稿時禁止するワードをコンマで区切る
$cf{no_wd} = '';

# 日本語チェック（投稿時日本語が含まれていなければ拒否する）
# 0=No  1=Yes
$cf{jp_wd} = 0;

# URL個数チェック
# → 投稿コメント中に含まれるURL個数の最大値
$cf{urlnum} = 10;

# アクセス制限（半角スペースで区切る、アスタリスク可）
#  → 拒否ホスト名を記述（後方一致）【例】*.anonymizer.com
$cf{deny_host} = '';
#  → 拒否IPアドレスを記述（前方一致）【例】210.12.345.*
$cf{deny_addr} = '';

# １回当りの最大投稿サイズ (bytes)
$cf{maxdata} = 51200;

# ホスト取得方法
# 0 : gethostbyaddr関数を使わない
# 1 : gethostbyaddr関数を使う
$cf{gethostbyaddr} = 0;

# クッキーID名（特に変更しなくてよい）
# → クッキー保存名
$cf{cookie_id}  = "light_bbs";
$cf{cookie_id3} = "light_pwd";

# -------------------------------------------------------------- #
# [ 以下は「過去ログ」機能の設定 ]

# 過去ログ用NOファイル【サーバパス】
$cf{nofile}  = './data/pastno.dat';

# 過去ログのディレクトリ【サーバパス】
$cf{pastdir} = './data/past';

# 過去ログ１ファイルの行数
# → この行数を超えると次ページを自動生成します
$cf{max_line} = 500;

# -------------------------------------------------------------- #
# [ 以下は「画像認証機能」機能（スパム対策）を使用する場合の設定 ]
#
# 画像認証機能の使用
# 0 : しない
# 1 : ライブラリ版（pngren.pl）
# 2 : モジュール版（GD::SecurityImage + Image::Magick）→ Image::Magick必須
$cf{use_captcha} = 0;

# 認証用画像生成ファイル【URLパス】
$cf{captcha_cgi} = './captcha.cgi';

# 画像認証プログラム【サーバパス】
$cf{captcha_pl} = './lib/captcha.pl';
$cf{captsec_pl} = './lib/captsec.pl';
$cf{pngren_pl}  = './lib/pngren.pl';

# 画像認証機能用暗号化キー（暗号化/復号化をするためのキー）
# → 適当に変更してください。
$cf{captcha_key} = 'CaptchaKey';

# 投稿キー許容時間（分単位）
# → 投稿フォーム表示後、送信ボタンが押されるまでの可能時間。
$cf{cap_time} = 30;

# 投稿キーの文字数
# ライブラリ版 : 4～8文字で設定
# モジュール版 : 6～8文字で設定
$cf{cap_len} = 6;

# 画像/フォント格納ディレクトリ【サーバパス】
$cf{bin_dir} = './lib/bin';

# [ライブラリ版] 画像ファイル [ ファイル名のみ ]
$cf{si_png} = "br3.png";

# [モジュール版] 画像フォント [ ファイル名のみ ]
$cf{font_ttl} = "tempest.ttf";

#===========================================================
# ■ 設定完了
#===========================================================

# 設定値を返す
sub set_init { return %cf; }

#-----------------------------------------------------------
#  フォームデコード
#-----------------------------------------------------------
sub parse_form {
	my ($buf,%in);
	if ($ENV{REQUEST_METHOD} eq "POST") {
		error('受理できません') if ($ENV{CONTENT_LENGTH} > $cf{maxdata});
		read(STDIN, $buf, $ENV{CONTENT_LENGTH});
	} else {
		$buf = $ENV{QUERY_STRING};
	}
	foreach ( split(/&/, $buf) ) {
		my ($key,$val) = split(/=/);
		$val =~ tr/+/ /;
		$val =~ s/%([a-fA-F0-9][a-fA-F0-9])/pack("H2", $1)/eg;
		
		# 無効化
		$val =~ s/&/&amp;/g;
		$val =~ s/</&lt;/g;
		$val =~ s/>/&gt;/g;
		$val =~ s/"/&quot;/g;
		$val =~ s/'/&#39;/g;
		$val =~ s|\r\n|<br>|g;
		$val =~ s|[\n\n]|<br>|g;
		
		$in{$key} .= "\0" if (defined $in{$key});
		$in{$key} .= $val;
	}
	return %in;
}

#-----------------------------------------------------------
#  エラー画面
#-----------------------------------------------------------
sub error {
	my $err = shift;
	
	open(IN,"$cf{tmpldir}/error.html") or die;
	my $tmpl = join('',<IN>);
	close(IN);
	
	$tmpl =~ s/!error!/$err/g;
	$tmpl =~ s/!cmnurl!/$cf{cmnurl}/g;
	$tmpl =~ s/!bbs_title!/$cf{bbs_title}/g;
	
	print "Content-type: text/html; charset=utf-8\n\n";
	print $tmpl;
	exit;
}

#-----------------------------------------------------------
#  過去ログ生成
#-----------------------------------------------------------
sub make_pastlog {
	my @past = @_;
	
	# 過去ログNOファイル
	open(NO,"+< $cf{nofile}") or error("open err: $cf{nofile}");
	eval "flock(NO,2);";
	my $num = <NO>;
	
	# 過去ログを定義
	my $pastfile = "$cf{pastdir}/" . sprintf("%04d",$num) . ".cgi";
	
	# 過去ログを開く
	open(DAT,"+< $pastfile") or error("open err: $pastfile");
	eval "flock(DAT,2);";
	my @data = <DAT>;
	
	# 規定の行数をオーバーすると次ファイルを自動生成
	if (@data >= $cf{max_line}) {
		
		# 過去ログを閉じる
		@data = ();
		close(DAT);
		
		# 過去NO更新
		seek(NO,0,0);
		print NO ++$num;
		truncate(NO,tell(NO));
		close(NO);
		
		$pastfile = "$cf{pastdir}/" . sprintf("%04d",$num) . ".cgi";
		
		open(DAT,"+> $pastfile");
		eval "flock(DAT,2);";
		print DAT @past;
		close(DAT);
		
		chmod(0666, $pastfile);
	
	} else {
		
		close(NO);
		
		# 過去ログを更新
		seek(DAT,0,0);
		print DAT @past;
		print DAT @data;
		truncate(DAT,tell(DAT));
		close(DAT);
	}
}

#-----------------------------------------------------------
#  パスワード制限
#-----------------------------------------------------------
sub passwd {
	my %in = @_;
	
	# 入室フォーム指定のとき
	if ($in{mode} eq 'enter') { pwd_form(); }
	
	# 時間取得
	my $now = time;
	
	# ログインのとき
	if ($in{login}) {
		# 認証
		if ($in{pw} ne $cf{enter_pwd}) { error("認証できません"); }
		
		# セッション発行
		my @wd = (0 .. 9, 'a' .. 'z', 'A' .. 'Z', '_');
		my $ses;
		for (1 .. 25) {	$ses .= $wd[int(rand(@wd))]; }
		
		# セッション更新
		my @log;
		open(DAT,"+< $cf{sesfile}") or error("write err: $cf{sesfile}");
		eval 'flock(DAT,2);';
		while(<DAT>) {
			chomp;
			my ($id,$time) = split(/\t/);
			next if ($now - $time > $cf{sestime} * 60);
			
			push(@log,"$_\n");
		}
		unshift(@log,"$ses\t$now\n");
		seek(DAT,0,0);
		print DAT @log;
		truncate(DAT,tell(DAT));
		close(DAT);
		
		# クッキー格納
		print "Set-Cookie: $cf{cookie_id3}=$ses\n";
	
	# セッション確認
	} else {
		
		# クッキー取得
		my $cook = $ENV{HTTP_COOKIE};
		
		# 該当IDを取り出す
		my %cook;
		foreach ( split(/;/,$cook) ) {
			my ($key,$val) = split(/=/);
			$key =~ s/\s//g;
			$cook{$key} = $val;
		}
		
		# クッキーなし
		if ($cook{$cf{cookie_id3}} eq '') { pwd_form(); }
		
		# ログオフのとき
		if ($in{mode} eq 'logoff') {
			
			my @log;
			open(DAT,"+< $cf{sesfile}") or error("write err: $cf{sesfile}");
			eval 'flock(DAT,2);';
			while(<DAT>) {
				my ($id,undef) = split(/\t/);
				next if ($cook{$cf{cookie_id3}} eq $id);
				
				push(@log,$_);
			}
			seek(DAT,0,0);
			print DAT @log;
			truncate(DAT,tell(DAT));
			close(DAT);
			
			if ($ENV{PERLXS} eq "PerlIS") {
				print "HTTP/1.0 302 Temporary Redirection\r\n";
				print "Content-type: text/html\n";
			}
			print "Set-Cookie: $cf{cookie_id3}=;\n";
			print "Location: $cf{homepage}\n\n";
			exit;
		}
		
		# セッションチェック
		my $flg;
		open(DAT,"$cf{sesfile}") or error("open err: $cf{sesfile}");
		while(<DAT>) {
			chomp;
			my ($id,$time) = split(/\t/);
			
			if ($cook{$cf{cookie_id3}} eq $id) {
				# 時間オーバー
				if ($now - $time > $cf{sestime} * 60) {
					$flg = -1;
				
				# OK
				} else {
					$flg = 1;
				}
				last;
			}
		}
		close(DAT);
		
		# 時間オーバー
		if ($flg == -1) {
			my $msg = qq|入室時間が経過しました。再度ログインしてください<br />\n|;
			$msg .= qq|[<a href="$cf{bbs_cgi}?mode=enter">ログイン</a>]\n|;
			error($msg);
		
		# セッション情報なし
		} elsif (!$flg) {
			pwd_form();
		}
	}
}

#-----------------------------------------------------------
#  入室画面
#-----------------------------------------------------------
sub pwd_form {
	open(IN,"$cf{tmpldir}/enter.html") or error('open err: enter.html');
	my $tmpl = join('',<IN>);
	close(IN);
	
	$tmpl =~ s/!(bbs_cgi|cmnurl|bbs_title)!/$cf{$1}/g;
	$tmpl =~ s|!icon:(\w+\.\w+)!|<img src="$cf{cmnurl}/$1" alt="" class="icon">|g;
	
	print "Content-type: text/html; charset=utf-8\n\n";
	footer($tmpl);
}

#-----------------------------------------------------------
#  フッター
#-----------------------------------------------------------
sub footer {
	my $foot = shift;
	
	# 著作権表記（削除・改変禁止）
	my $copy = <<EOM;
		<p style="margin-top:2em;text-align:center;font-family:Verdana,Helvetica,Arial;font-size:10px;">
			- <a href="http://www.kent-web.com/" target="_top">LightBoard</a> -
		</p>
EOM

	if ($foot =~ /(.+)(<\/body[^>]*>.*)/si) {
		print "$1$copy$2\n";
	} else {
		print "$foot$copy\n";
		print "</body></html>\n";
	}
	exit;
}

1;

