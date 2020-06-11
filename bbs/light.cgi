#!/usr/bin/perl

#┌─────────────────────────────────
#│ LightBoard : light.cgi - 2019/12/10
#│ copyright (c) kentweb, 1997-2019
#│ http://www.kent-web.com/
#└─────────────────────────────────

# モジュール宣言
use strict;
use CGI::Carp qw(fatalsToBrowser);

# 設定ファイル認識
require "./init.cgi";
my %cf = set_init();

# データ受理
my %in = parse_form();

# アクセス制限
passwd(%in) if ($cf{enter_pwd} ne '');

# 処理分岐
if ($in{mode} eq 'recv') { recv_data(); }
if ($in{mode} eq 'find') { find_data(); }
if ($in{mode} eq 'note') { note_page(); }
if ($in{mode} eq 'past') { past_page(); }
if ($in{mode} eq 'dele') { dele_data(); }
bbs_list();

#-----------------------------------------------------------
#  記事表示
#-----------------------------------------------------------
sub bbs_list {
	my %err = @_;
	
	# 記事の修正・削除
	if ($in{edit} or $in{del}) { edit_conf(); }
	
	# レス処理
	$in{res} =~ s/\D//g;
	my %res;
	if ($in{res}) {
		my ($flg,$rsub,$rcom);
		open(IN,"$cf{logfile}") or error("open err: $cf{logfile}");
		while (<IN>) {
			my ($no,$sub,$com) = (split(/<>/))[0,4,5];
			if ($in{res} == $no) {
				$flg++;
				$rsub = $sub;
				$rcom = $com;
				last;
			}
		}
		close(IN);
		
		if (!$flg) { error("該当記事が見つかりません"); }
		
		$rsub =~ s/^Re://g;
		$rsub =~ s/\[\d+\]\s?//g;
		$rsub = "Re:[$in{res}] $res{sub}";
		$rcom = "&gt; $res{com}";
		$rcom =~ s|<br( /)?>|\n&gt; |ig;
		
		$in{sub} = $rsub;
		$in{comment} = $rcom;
	}
	
	# ページ数定義
	my $pg = $in{pg} || 0;
	
	# データオープン
	my ($i,@log);
	open(IN,"$cf{logfile}") or error("open err: $cf{logfile}");
	while (<IN>) {
		$i++;
		next if ($i < $pg + 1);
		next if ($i > $pg + $cf{pg_max});
		
		push(@log,$_);
	}
	close(IN);
	
	# 繰越ボタン作成
	my $page_btn = make_pager($i,$pg);
	
	# クッキー取得
	my @cook = get_cookie();
	$cook[2] ||= 'http://';
	
	# home or logoff
	my $home = $cf{enter_pwd} eq '' ? $cf{homepage} : "$cf{bbs_cgi}?mode=logoff";
	
	# テンプレート読込
	open(IN,"$cf{tmpldir}/bbs.html") or error("open err: bbs.html");
	my $tmpl = join('', <IN>);
	close(IN);
	
	# 文字置き換え
	$tmpl =~ s/!([a-z]+_cgi)!/$cf{$1}/g;
	$tmpl =~ s/!homepage!/$home/g;
	$tmpl =~ s/<!-- page_btn -->/$page_btn/g;
	$tmpl =~ s/!bbs_title!/$cf{bbs_title}/g;
	$tmpl =~ s/!pg!/$pg/g;
	$tmpl =~ s|!icon:(\w+\.\w+)!|<img src="$cf{cmnurl}/$1" alt="" class="icon">|g;
	$tmpl =~ s/!cmnurl!/$cf{cmnurl}/g;
	
	if ($in{job_bak}) {
		$in{comment} =~ s/\t/\n/g;
		$tmpl =~ s/!(name|email|url|sub|comment|pwd)!/$in{$1}/g;
		if (!$in{cookie}) {
			$tmpl =~ s|<input type="checkbox" name="cookie" value="1" checked>|<input type="checkbox" name="cookie" value="1">|;
		}
	} else {
		$tmpl =~ s/!name!/$err{name} ne '' ? $in{name} : $cook[0]/e;
		$tmpl =~ s/!email!/$err{eml} ne '' ? $in{email} : $cook[1]/e;
		$tmpl =~ s/!url!/$err{url} ne '' ? $in{url} : $cook[2]/e;
		$tmpl =~ s/!sub!/$in{sub}/;
		$tmpl =~ s/!comment!/$in{comment}/;
		$tmpl =~ s/!pwd!//;
	}
	
	# 入力エラー時
	if (%err > 0) {
		for (keys %err) { $tmpl =~ s|<!-- err:$_ -->|<div class="err-col">$err{$_}</div>|; }
	}
	
	# テンプレート分割
	my ($head,$loop,$foot) = $tmpl =~ m|(.+)<!-- loop -->(.+?)<!-- /loop -->(.+)|s
			? ($1,$2,$3)
			: error("テンプレート不正");
	
	# ヘッダ表示
	print "Content-type: text/html; charset=utf-8\n\n";
	print $head;
	
	# ループ部
	foreach (@log) {
		my ($no,$date,$name,$eml,$sub,$com,$url,$host,$pw,$tim) = split(/<>/);
		$name = qq|<a href="mailto:$eml">$name</a>| if ($eml);
		$com = auto_link($com) if ($cf{auto_link});
		$com =~ s/([>]|^)(&gt;[^<]*)/$1<span style="color:$cf{ref_col}">$2<\/span>/g if ($cf{ref_col});
		$com .= qq|<p class="url"><a href="$url" target="_blank">$url</a></p>| if ($url);
		
		my $tmp = $loop;
		$tmp =~ s/!_num!/$no/g;
		$tmp =~ s/!_sub!/$sub/g;
		$tmp =~ s/!_name!/$name/g;
		$tmp =~ s/!_date!/$date/g;
		$tmp =~ s/!_comment!/$com/g;
		$tmp =~ s/!bbs_cgi!/$cf{bbs_cgi}/g;
		print $tmp;
	}
	
	# フッタ
	footer($foot);
}

#-----------------------------------------------------------
#  ワード検索
#-----------------------------------------------------------
sub find_data {
	# 条件
	$in{cond} =~ s/\D//g;
	$in{word} =~ s|<br>||g;
	
	# 検索条件プルダウン
	my %op = (1 => 'AND', 0 => 'OR');
	my $op_cond;
	foreach (1,0) {
		if ($in{cond} eq $_) {
			$op_cond .= qq|<option value="$_" selected>$op{$_}</option>\n|;
		} else {
			$op_cond .= qq|<option value="$_">$op{$_}</option>\n|;
		}
	}
	
	# 検索実行
	my ($hit,@log) = search($in{word},$in{cond},$cf{logfile}) if ($in{word} ne '');
	
	# テンプレート
	open(IN,"$cf{tmpldir}/find.html") or error("open err: find.html");
	my $tmpl = join('',<IN>);
	close(IN);
	
	$tmpl =~ s/!(bbs_cgi|cmnurl|bbs_title)!/$cf{$1}/g;
	$tmpl =~ s/<!-- op_cond -->/$op_cond/;
	$tmpl =~ s/!word!/$in{word}/;
	$tmpl =~ s|!icon:(\w+\.\w+)!|<img src="$cf{cmnurl}/$1" alt="" class="icon">|g;
	
	# 分割
	my ($head,$loop,$foot) = $tmpl =~ m|(.+)<!-- loop -->(.+?)<!-- /loop -->(.+)|s
			? ($1,$2,$3)
			: error('テンプレート不正');
	
	# ヘッダ部
	print "Content-type: text/html; charset=utf-8\n\n";
	print $head;
	
	# ループ部
	foreach (@log) {
		my ($no,$date,$name,$eml,$sub,$com,$url,$host,$pw,$tim) = split(/<>/);
		$name = qq|<a href="mailto:$eml">$name</a>| if ($eml);
		$com  = auto_link($com) if ($cf{auto_link});
		$com =~ s/([>]|^)(&gt;[^<]*)/$1<span style="color:$cf{ref_col}">$2<\/span>/g if ($cf{ref_col});
		$url  = qq|&lt;<a href="$url" target="_blank">URL</a>&gt;| if ($url);
		
		my $tmp = $loop;
		$tmp =~ s/!num!/$no/g;
		$tmp =~ s/!sub!/$sub/g;
		$tmp =~ s/!date!/$date/g;
		$tmp =~ s/!name!/$name/g;
		$tmp =~ s/!home!/$url/g;
		$tmp =~ s/!comment!/$com/g;
		print $tmp;
	}
	
	# フッタ
	footer($foot);
}

#-----------------------------------------------------------
#  検索実行
#-----------------------------------------------------------
sub search {
	my ($word,$cond,$file,$list) = @_;
	
	# キーワードを配列化
	$word =~ s/　/ /g;
	my @wd = split(/\s+/,$word);
	
	# UTF-8定義
	my $byte1 = '[\x00-\x7f]';
	my $byte2 = '[\xC0-\xDF][\x80-\xBF]';
	my $byte3 = '[\xE0-\xEF][\x80-\xBF]{2}';
	my $byte4 = '[\xF0-\xF7][\x80-\xBF]{3}';
	
	# 検索処理
	my ($i,@log);
	open(IN,"$file") or error("open err: $file");
	while (<IN>) {
		my ($no,$date,$nam,$eml,$sub,$com,$url,$hos,$pw,$tim) = split(/<>/);
		
		my $flg;
		foreach my $wd (@wd) {
			if ("$nam $eml $sub $com $url" =~ /^(?:$byte1|$byte2|$byte3|$byte4)*?\Q$wd\E/i) {
				$flg++;
				if ($cond == 0) { last; }
			} else {
				if ($cond == 1) { $flg = 0; last; }
			}
		}
		next if (!$flg);
		
		$i++;
		if ($list > 0) {
			next if ($i < $in{pg} + 1);
			next if ($i > $in{pg} + $list);
		}
		
		push(@log,$_);
	}
	close(IN);
	
	# 検索結果
	return ($i,@log);
}

#-----------------------------------------------------------
#  過去ログページ
#-----------------------------------------------------------
sub past_page {
	# 過去ログ番号
	open(IN,"$cf{nofile}") or error("open err: $cf{nofile}");
	my $pastnum = <IN>;
	close(IN);
	
	my $pastnum = sprintf("%04d",$pastnum);
	$in{pno} =~ s/\D//g;
	$in{pno} ||= $pastnum;
	
	# プルダウンタグ作成
	my $op_pno;
	for ( my $i = $pastnum; $i > 0; $i-- ) {
		$i = sprintf("%04d",$i);
		
		if ($in{pno} == $i) {
			$op_pno .= qq|<option value="$i" selected>$i</option>\n|;
		} else {
			$op_pno .= qq|<option value="$i">$i</option>\n|;
		}
	}
	
	# ページ数
	my $pg = $in{pg} || 0;
	
	# 初期化
	my ($hit,$page_btn,@log);
	
	# 対象ログ定義
	my $file = "$cf{pastdir}/" . sprintf("%04d", $in{pno}) . ".cgi";
	
	# ワード検索
	if ($in{find} && $in{word} ne '') {
		# 検索
		($hit,@log) = search($in{word},$in{cond},$file,$in{list});
		
		# 結果
		$page_btn = "検索結果：<b>$hit</b>件 &nbsp;&nbsp;" . pgbtn_old($hit,$in{pno},$pg,'past');
	
	# ログ一覧
	} else {
		# 過去ログオープン
		my $i = 0;
		open(IN,"$file") or error("open err: $file");
		while(<IN>) {
			$i++;
			next if ($i < $pg + 1);
			next if ($i > $pg + $cf{pg_max});
			
			push(@log,$_);
		}
		close(IN);
		
		# 繰越ボタン作成
		$page_btn = pgbtn_old($i,$in{pno},$pg);
	}
	
	# プルダウン作成（検索条件）
	my %op = make_op();
	
	# テンプレート読み込み
	my ($flg,$loop);
	open(IN,"$cf{tmpldir}/past.html") or error("open err: past.html");
	my $tmpl = join('',<IN>);
	close(IN);
	
	$tmpl =~ s/!past_num!/$in{pno}/g;
	$tmpl =~ s|!icon:(\w+\.\w+)!|<img src="$cf{cmnurl}/$1" alt="" class="icon">|g;
	$tmpl =~ s/!([a-z]+_cgi)!/$cf{$1}/g;
	$tmpl =~ s/<!-- op_pno -->/$op_pno/g;
	$tmpl =~ s/<!-- op_(\w+) -->/$op{$1}/g;
	$tmpl =~ s/!word!/$in{word}/g;
	$tmpl =~ s/!page_btn!/$page_btn/g;
	$tmpl =~ s/!cmnurl!/$cf{cmnurl}/g;
	$tmpl =~ s/!bbs_title!/$cf{bbs_title}/g;
	
	# テンプレート分割
	my ($head,$loop,$foot) = $tmpl =~ m|(.+)<!-- loop -->(.+?)<!-- /loop -->(.+)|s
			? ($1,$2,$3)
			: error('テンプレート不正');
	
	if ($in{change}) { $in{word} = ''; }
	
	# 画面表示
	print "Content-type: text/html; charset=utf-8\n\n";
	print $head;
	foreach (@log) {
		my ($no,$date,$nam,$eml,$sub,$com,$url,$hos,$pw,$tim) = split(/<>/);
		$nam = qq|<a href="mailto:$eml">$nam</a>| if ($eml);
		$com = auto_link($com) if ($cf{auto_link});
		$com =~ s/([>]|^)(&gt;[^<]*)/$1<span style="color:$cf{ref_col}">$2<\/span>/g if ($cf{ref_col});
		$url = qq|&lt;<a href="$url" target="_blank">URL</a>&gt;| if ($url);
		
		my $tmp = $loop;
		$tmp =~ s/!num!/$no/g;
		$tmp =~ s/!sub!/$sub/g;
		$tmp =~ s/!date!/$date/g;
		$tmp =~ s/!name!/$nam/g;
		$tmp =~ s/!home!/$url/g;
		$tmp =~ s/!comment!/$com/g;
		print $tmp;
	}
	
	# フッタ
	print footer($foot);
	exit;
}

#-----------------------------------------------------------
#  留意事項表示
#-----------------------------------------------------------
sub note_page {
	open(IN,"$cf{tmpldir}/note.html") or error("open err: note.html");
	my $tmpl = join('',<IN>);
	close(IN);
	
	$tmpl =~ s/!(cmnurl|bbs_title)!/$cf{$1}/g;
	
	print "Content-type: text/html; charset=utf-8\n\n";
	print $tmpl;
	exit;
}

#-----------------------------------------------------------
#  自動リンク
#-----------------------------------------------------------
sub auto_link {
	my $text = shift;
	
	$text =~ s/(s?https?:\/\/([\w-.!~*'();\/?:\@=+\$,%#]|&amp;)+)/<a href="$1" target="_blank">$1<\/a>/g;
	return $text;
}

#-----------------------------------------------------------
#  ページ送り作成
#-----------------------------------------------------------
sub make_pager {
	my ($i,$pg) = @_;
	
	# ページ繰越数定義
	$cf{pg_max} ||= 10;
	my $next = $pg + $cf{pg_max};
	my $back = $pg - $cf{pg_max};
	
	# ページ繰越ボタン作成
	my @pg;
	if ($back >= 0 || $next < $i) {
		my $flg;
		my ($w,$x,$y,$z) = (0,1,0,$i);
		while ($z > 0) {
			if ($pg == $y) {
				$flg++;
				push(@pg,qq!<a href="#" class="active">$x</a>\n!);
			} else {
				push(@pg,qq!<a href="$cf{bbs_cgi}?pg=$y">$x</a>\n!);
			}
			$x++;
			$y += $cf{pg_max};
			$z -= $cf{pg_max};
			
			if ($flg) { $w++; }
			last if ($w >= 5 && @pg >= 10);
		}
	}
	while( @pg >= 11 ) { shift(@pg); }
	my $ret = join('', @pg);
	if ($back >= 0) {
		$ret = qq!<a href="$cf{bbs_cgi}?pg=$back">&laquo;</a>\n! . $ret;
	}
	if ($next < $i) {
		$ret .= qq!<a href="$cf{bbs_cgi}?pg=$next">&raquo;</a>\n!;
	}
	
	# 結果を返す
	return $ret ? qq|<div class="pagination">\n$ret</div>| : '';
}

#-----------------------------------------------------------
#  クッキー取得
#-----------------------------------------------------------
sub get_cookie {
	# クッキー取得
	my $cook = $ENV{HTTP_COOKIE};
	
	# 該当IDを取り出す
	my %cook;
	foreach ( split(/;/,$cook) ) {
		my ($key,$val) = split(/=/);
		$key =~ s/\s//g;
		$cook{$key} = $val;
	}
	
	# URLデコード
	my @cook;
	foreach ( split(/<>/,$cook{$cf{cookie_id}}) ) {
		s/%([0-9A-Fa-f][0-9A-Fa-f])/pack("H2", $1)/eg;
		s/[&"'<>]//g;
		
		push(@cook,$_);
	}
	return @cook;
}

#-----------------------------------------------------------
#  繰越ボタン作成 [ 過去ログ ]
#-----------------------------------------------------------
sub pgbtn_old {
	my ($i,$pno,$pg,$stat) = @_;
	
	# ページ繰越定義
	my $next = $pg + $cf{pg_max};
	my $back = $pg - $cf{pg_max};
	
	my $link;
	if ($stat eq 'past') {
		my $wd = url_enc($in{word});
		$link = "$cf{bbs_cgi}?mode=$in{mode}&amp;pno=$pno&amp;find=1&amp;word=$wd";
	} else {
		$link = "$cf{bbs_cgi}?mode=$in{mode}&amp;pno=$pno";
	}
	
	# ページ繰越ボタン作成
	my @pg;
	if ($back >= 0 || $next < $i) {
		my $flg;
		my ($w,$x,$y,$z) = (0,1,0,$i);
		while ($z > 0) {
			if ($pg == $y) {
				$flg++;
				push(@pg,qq!<a href="#" class="active">$x</a>\n!);
			} else {
				push(@pg,qq!<a href="$link&amp;pg=$y">$x</a>\n!);
			}
			$x++;
			$y += $cf{pg_max};
			$i -= $cf{pg_max};
			
			if ($flg) { $w++; }
			last if ($w >= 5 && @pg >= 10);
		}
	}
	while( @pg >= 11 ) { shift(@pg); }
	my $ret = join('', @pg);
	if ($back >= 0) {
		$ret = qq!<a href="$link&amp;pg=$back">&laquo;</a>\n! . $ret;
	}
	if ($next < $i) {
		$ret .= qq!<a href="$link&amp;pg=$next">&raquo;</a>\n!;
	}
	
	# 結果を返す
	return $ret ? qq|<div class="pagination">\n$ret</div>| : '';
}

#-----------------------------------------------------------
#  プルダウン作成 [ 検索条件 ]
#-----------------------------------------------------------
sub make_op {
	my %op;
	my %cond = (1 => 'AND', 0 => 'OR');
	foreach (1,0) {
		if ($in{cond} eq $_) {
			$op{cond} .= qq|<option value="$_" selected>$cond{$_}</option>\n|;
		} else {
			$op{cond} .= qq|<option value="$_">$cond{$_}</option>\n|;
		}
	}
	for ( my $i = 10; $i <= 30; $i += 5 ) {
		if ($in{list} == $i) {
			$op{list} .= qq|<option value="$i" selected>$i件</option>\n|;
		} else {
			$op{list} .= qq|<option value="$i">$i件</option>\n|;
		}
	}
	return %op;
}

#-----------------------------------------------------------
#  URLエンコード
#-----------------------------------------------------------
sub url_enc {
	local($_) = @_;
	
	s/(\W)/'%' . unpack('H2', $1)/eg;
	s/\s/+/g;
	$_;
}

#-----------------------------------------------------------
#  記事削除確認
#-----------------------------------------------------------
sub edit_conf {
	my %log;
	open(IN,"$cf{logfile}") or error("open err: $cf{logfile}");
	while (<IN>) {
		my ($no,$date,$name,$eml,$sub,$com,$url,$host,$pw,$tim) = split(/<>/);
		if ($in{edit} == $no or $in{del} == $no) {
			$log{flg}++;
			$log{num}  = $no;
			$log{sub}  = $sub;
			$log{date} = $date;
			$log{name} = $name;
			last;
		}
	}
	close(IN);
	
	if (!$log{flg}) { error("該当記事が見つかりません"); }
	
	open(IN,"$cf{tmpldir}/del.html") or error("open err: del.html");
	my $tmpl = join('',<IN>);
	close(IN);
	
	my $pg = $in{pg} || 0;
	$tmpl =~ s/!(\w+_\w+)!/$cf{$1}/g;
	$tmpl =~ s/!(sub|name|date|num)!/$log{$1}/g;
	$tmpl =~ s/!pg!/$pg/g;
	$tmpl =~ s|!icon:(\w+\.\w+)!|<img src="$cf{cmnurl}/$1" alt="" class="icon">|g;
	$tmpl =~ s/!cmnurl!/$cf{cmnurl}/g;
	
	print "Content-type: text/html; charset=utf-8\n\n";
	print $tmpl;
	exit;
}

#-----------------------------------------------------------
#  投稿処理
#-----------------------------------------------------------
sub recv_data {
	# 戻り
	if ($in{job_bak}) { bbs_list(); }
	
	# 投稿チェック
	check_post();
	
	# 確認画面
	if ($in{job_reg} eq '') {
		conf_form();
	
	# セッション確認
	} else {
		check_ses();
	}
	
	# ホスト取得
	my ($host,$addr) = get_host();
	
	# 削除キー暗号化
	my $pwd = encrypt($in{pwd}) if ($in{pwd} ne "");
	
	# 時間取得
	my $time = time;
	my ($min,$hour,$mday,$mon,$year,$wday) = (localtime($time))[1..6];
	my @wk = ('Sun','Mon','Tue','Wed','Thu','Fri','Sat');
	my $date = sprintf("%04d/%02d/%02d(%s) %02d:%02d",
				$year+1900,$mon+1,$mday,$wk[$wday],$hour,$min);
	
	# 一時保存
	my $msg;
	if ($cf{approve} == 1) {
		save_tmp($date,$host,$pwd,$time);
		$msg = '記事を受理しました。記事は管理者の承認後に表示されます。';
	
	# 直接保存
	} else {
		save_log($date,$host,$pwd,$time);
		$msg = 'ありがとうございます。記事を受理しました。';
	}
	
	# クッキー格納
	set_cookie($in{name},$in{email},$in{url}) if ($in{cookie});
	
	# メール通知
	mail_to($date,$host) if ($cf{mailing});
	
	# 完了画面
	message($msg);
}

#-----------------------------------------------------------
#  ユーザ記事削除
#-----------------------------------------------------------
sub dele_data {
	# 入力チェック
	$in{num} =~ s/\D//g;
	if ($in{num} eq '' or $in{pwd} eq '') {
		error("削除Noまたは削除キーが入力モレです");
	}
	
	my ($flg,$crypt,@log);
	open(DAT,"+< $cf{logfile}") or error("open err: $cf{logfile}");
	eval "flock(DAT,2);";
	while (<DAT>) {
		my ($no,$date,$nam,$eml,$sub,$com,$url,$hos,$pw,$tim) = split(/<>/);
		
		if ($in{num} == $no) {
			$flg++;
			$crypt = $pw;
			next;
		}
		push(@log,$_);
	}
	
	if (!$flg || $crypt eq '') {
		close(DAT);
		error("削除キーが設定されていないか又は記事が見当たりません");
	}
	
	# 削除キーを照合
	if (decrypt($in{pwd},$crypt) != 1) {
		close(DAT);
		error("認証できません");
	}
	
	# ログ更新
	seek(DAT,0,0);
	print DAT @log;
	truncate(DAT,tell(DAT));
	close(DAT);
	
	# 完了画面
	message("記事を削除しました");
}

#-----------------------------------------------------------
#  メール送信
#-----------------------------------------------------------
sub mail_to {
	my ($date,$host) = @_;
	
	# 件名をMIMEエンコード
	require './lib/jacode.pl';
	my $msub = mime_unstructured_header("BBS: $in{sub}");
	
	# コメント内の改行復元
	my $com = $in{comment};
	$com =~ s|<br>|\n|g;
	$com =~ s/&lt;/>/g;
	$com =~ s/&gt;/</g;
	$com =~ s/&quot;/"/g;
	$com =~ s/&amp;/&/g;
	$com =~ s/&#39;/'/g;
	
	# メール本文を定義
	my $mbody = <<EOM;
掲示板に投稿がありました。

投稿日：$date
ホスト：$host

件名  ：$in{sub}
お名前：$in{name}
E-mail：$in{email}
URL   ：$in{url}

$com
EOM

	my $body;
	for my $tmp ( split(/\n/,$mbody) ) {
		jcode::convert(\$tmp,'jis','utf8');
		$body .= "$tmp\n";
	}
	
	# メールアドレスがない場合は管理者メールに置き換え
	$in{email} ||= $cf{mailto};
	
	# sendmailコマンド
	my $scmd = "$cf{sendmail} -t -i";
	$scmd .= " -f $in{email}" if ($cf{sendm_f});
	
	# 送信
	open(MAIL,"| $scmd") or error("送信失敗");
	print MAIL "To: $cf{mailto}\n";
	print MAIL "From: $in{email}\n";
	print MAIL "Subject: $msub\n";
	print MAIL "MIME-Version: 1.0\n";
	print MAIL "Content-type: text/plain; charset=ISO-2022-JP\n";
	print MAIL "Content-Transfer-Encoding: 7bit\n";
	print MAIL "X-Mailer: $cf{version}\n\n";
	print MAIL "$body\n";
	close(MAIL);
}

#-----------------------------------------------------------
#  確認画面
#-----------------------------------------------------------
sub conf_form {
	# ホスト取得
	my ($host,$addr) = get_host();
	
	open(IN,"$cf{logfile}") or error("open err: $cf{logfile}");
	my $top = <IN>;
	close(IN);
	
	chomp $top;
	my ($no,$dat,$nam,$eml,$sub,$com,$url,$hos,$pw,$tim) = split(/<>/,$top);
	if ($in{name} eq $nam && $in{comment} eq $com) {
		error("二重投稿は禁止です");
	}
	if ($host eq $hos && time - $tim < $cf{wait_time}) {
		error("連続投稿は$cf{wait_time}秒以上空けてください");
	}
	
	# 画像認証用
	my @dig = (0 .. 9);
	my $dig;
	for (1 .. $cf{cap_len}) { $dig .= $dig[int(rand(@dig))]; }
	
	# セッション文字
	my @wd = (0 .. 9, 'a' .. 'z', 'A' .. 'Z', '_');
	my $ses;
	for (1 .. 25) { $ses .= $wd[int(rand(@wd))]; }
	
	# セッションファイル記録
	my $now = time;
	my @log;
	open(DAT,"+< $cf{conffile}") or error("open err: $cf{conffile}");
	eval "flock(DAT,2);";
	while(<DAT>) {
		my ($time,$rand,$fig) = split(/\t/);
		next if ($now - $time > $cf{cap_time}*60);
		
		push(@log,$_);
	}
	unshift(@log,"$now\t$ses\t$dig\t$addr\n");
	seek(DAT,0,0);
	print DAT @log;
	truncate(DAT,tell(DAT));
	close(DAT);
	
	# 引数
	my $hid;
	for (qw(name email sub url pwd cookie)) {
		$hid .= qq|<input type="hidden" name="$_" value="$in{$_}">\n|;
	}
	my $com = $in{comment};
	$com =~ s/<br>/\t/g;
	$hid .= qq|<input type="hidden" name="comment" value="$com">\n|;
	$hid .= qq|<input type="hidden" name="ses" value="$ses">\n|;
	
	my $pwd = $in{pwd};
	$pwd =~ s/./*/g;
	
	# テンプレート読み込み
	open(IN,"$cf{tmpldir}/conf.html") or die;
	my $tmpl = join('',<IN>);
	close(IN);
	
	$tmpl =~ s/!(bbs_cgi|cmnurl|bbs_title)!/$cf{$1}/g;
	$tmpl =~ s/!(name|email|comment|url|sub)!/$in{$1}/g;
	$tmpl =~ s/!pwd!/$pwd/g;
	$tmpl =~ s/!cookie!/$in{cookie} == 1 ? 'する' : 'しない'/e;
	$tmpl =~ s/<!-- hidden -->/$hid/;
	$tmpl =~ s|!icon:(\w+\.\w+)!|<img src="$cf{cmnurl}/$1" class="icon" alt="$1">|g;
	$tmpl =~ s|!captcha!|<img src="$cf{captcha_cgi}?$ses" class="icon" alt="投稿キー">|;
	
	# 画面表記
	print "Content-type: text/html; charset=utf-8\n\n";
	print $tmpl;
	exit;
}

#-----------------------------------------------------------
#  禁止ワードチェック
#-----------------------------------------------------------
sub no_wd {
	my $flg;
	foreach ( split(/,/,$cf{no_wd}) ) {
		if (index("$in{name} $in{sub} $in{comment}", $_) >= 0) {
			$flg = 1;
			last;
		}
	}
	if ($flg) { error("禁止ワードが含まれています"); }
}

#-----------------------------------------------------------
#  日本語チェック
#-----------------------------------------------------------
sub jp_wd {
	if ($in{comment} !~ /(?:[\xC0-\xDF][\x80-\xBF]|[\xE0-\xEF][\x80-\xBF]{2}|[\xF0-\xF7][\x80-\xBF]{3})/x) {
		error("メッセージに日本語が含まれていません");
	}
}

#-----------------------------------------------------------
#  URL個数チェック
#-----------------------------------------------------------
sub urlnum {
	my $com = $in{comment};
	my ($num) = ($com =~ s|(https?://)|$1|ig);
	if ($num > $cf{urlnum}) {
		error("コメント中のURLアドレスは最大$cf{urlnum}個までです");
	}
}

#-----------------------------------------------------------
#  アクセス制限
#-----------------------------------------------------------
sub get_host {
	# IP&ホスト取得
	my $host = $ENV{REMOTE_HOST};
	my $addr = $ENV{REMOTE_ADDR};
	
	if ($cf{gethostbyaddr} && ($host eq "" || $host eq $addr)) {
		$host = gethostbyaddr(pack("C4", split(/\./, $addr)), 2);
	}
	
	# IPチェック
	my $flg;
	foreach ( split(/\s+/, $cf{deny_addr}) ) {
		s/\./\\\./g;
		s/\*/\.\*/g;
		
		if ($addr =~ /^$_/i) { $flg = 1; last; }
	}
	if ($flg) {
		error("アクセスを許可されていません");
	
	# ホストチェック
	} elsif ($host) {
		
		foreach ( split(/\s+/, $cf{deny_host}) ) {
			s/\./\\\./g;
			s/\*/\.\*/g;
			
			if ($host =~ /$_$/i) { $flg = 1; last; }
		}
		if ($flg) {
			error("アクセスを許可されていません");
		}
	}
	if ($host eq "") { $host = $addr; }
	return ($host,$addr);
}

#-----------------------------------------------------------
#  crypt暗号
#-----------------------------------------------------------
sub encrypt {
	my $in = shift;
	
	my @wd = ('a'..'z', 'A'..'Z', 0..9, '.', '/');
	my $salt = $wd[int(rand(@wd))] . $wd[int(rand(@wd))];
	crypt($in,$salt) || crypt ($in,'$1$'.$salt);
}

#-----------------------------------------------------------
#  crypt照合
#-----------------------------------------------------------
sub decrypt {
	my ($in, $dec) = @_;
	
	my $salt = $dec =~ /^\$1\$(.*)\$/ ? $1 : substr($dec,0,2);
	if (crypt($in,$salt) eq $dec || crypt($in,'$1$'.$salt) eq $dec) {
		return 1;
	} else {
		return 0;
	}
}

#-----------------------------------------------------------
#  完了メッセージ
#-----------------------------------------------------------
sub message {
	my $msg = shift;
	
	open(IN,"$cf{tmpldir}/mesg.html") or error("open err: mesg.html");
	my $tmpl = join('',<IN>);
	close(IN);
	
	$tmpl =~ s/!(bbs_cgi|bbs_title|cmnurl)!/$cf{$1}/g;
	$tmpl =~ s/!message!/$msg/g;
	
	print "Content-type: text/html; charset=utf-8\n\n";
	print $tmpl;
	exit;
}

#-----------------------------------------------------------
#  クッキー発行
#-----------------------------------------------------------
sub set_cookie {
	my @data = @_;
	
	my ($sec,$min,$hour,$mday,$mon,$year,$wday,undef,undef) = gmtime(time + 60*24*60*60);
	my @mon  = qw|Jan Feb Mar Apr May Jun Jul Aug Sep Oct Nov Dec|;
	my @week = qw|Sun Mon Tue Wed Thu Fri Sat|;
	
	# 時刻フォーマット
	my $gmt = sprintf("%s, %02d-%s-%04d %02d:%02d:%02d GMT",
				$week[$wday],$mday,$mon[$mon],$year+1900,$hour,$min,$sec);
	
	# URLエンコード
	my $cook;
	foreach (@data) {
		s/(\W)/sprintf("%%%02X", unpack("C", $1))/eg;
		$cook .= "$_<>";
	}
	
	print "Set-Cookie: $cf{cookie_id}=$cook; expires=$gmt\n";
}

#-----------------------------------------------------------
#  記事追加
#-----------------------------------------------------------
sub save_log {
	my ($date,$host,$pwd,$time) = @_;
	$in{comment} =~ s/\t/<br>/g;
	
	open(DAT,"+< $cf{logfile}") or error("open err: $cf{logfile}");
	eval "flock(DAT,2);";
	my $top = <DAT>;
	
	my ($no,$dat,$nam,$eml,$sub,$com,$url,$hos,$pw,$tim) = split(/<>/,$top);
	if ($in{name} eq $nam && $in{comment} eq $com) {
		close(DAT);
		error("二重投稿は禁止です");
	}
	# 連続投稿チェック
	my $flg;
	if ($cf{regCtl} == 1) {
		if ($host eq $hos && $time - $tim < $cf{wait}) { $flg = 1; }
	} elsif ($cf{regCtl} == 2) {
		if ($time - $tim < $cf{wait}) { $flg = 1; }
	}
	if ($flg) {
		close(DAT);
		error("現在投稿制限中です。もうしばらくたってから投稿をお願いします");
	}
	
	# 記事No採番
	$no++;
	
	# 最大記事数処理
	my $i = 0;
	my (@log,@old);
	seek(DAT, 0, 0);
	while (<DAT>) {
		$i++;
		my ($no,$dat,$nam,$eml,$sub,$com,$url,$hos,$pw,$tim) = split(/<>/);
		
		if ($i <= $cf{maxlog} - 1) {
			push(@log,$_);
		} else {
			push(@old,$_);
		}
	}
	
	# 新記事
	unshift(@log,"$no<>$date<>$in{name}<>$in{email}<>$in{sub}<>$in{comment}<>$in{url}<>$host<>$pwd<>$time<>\n");
	
	# 更新
	seek(DAT,0,0);
	print DAT @log;
	truncate(DAT,tell(DAT));
	close(DAT);
	
	# 過去ログ更新
	if ($cf{pastkey} && @old > 0) { make_pastlog(@old);	}
}

#-----------------------------------------------------------
#  一時ファイル保存
#-----------------------------------------------------------
sub save_tmp {
	my ($date,$host,$pwd,$time) = @_;
	
	# 採番
	open(NO,"+< $cf{numfile}") or error("open err: $cf{numfile}");
	eval "flock(NO,2);";
	my $num = <NO>;
	
	# 前ログ
	if (-e "$cf{logdir}/$num.cgi") {
		open(LOG,"$cf{logdir}/$num.cgi");
		my $log = <LOG>;
		close(LOG);
		
		# 連続投稿チェック
		my ($dat,$nam,$eml,$sub,$com,$url,$hos,$pw,$tim) = split(/<>/,$log);
		if ($in{name} eq $nam && $in{comment} eq $com) {
			close(NO);
			error("二重投稿は禁止です");
		}
		my $flg;
		if ($cf{regCtl} == 1) {
			if ($host eq $hos && $time - $tim < $cf{wait}) { $flg = 1; }
		} elsif ($cf{regCtl} == 2) {
			if ($time - $tim < $cf{wait}) { $flg = 1; }
		}
		if ($flg) {
			close(NO);
			error("現在投稿制限中です。もうしばらくたってから投稿をお願いします");
		}
	}
	
	# 通番ファイル保存
	seek(NO,0,0);
	print NO ++$num;
	truncate(NO,tell(NO));
	close(NO);
	
	# ファイル生成
	open(TMP,"+> $cf{logdir}/$num.cgi") or error("write err: $num.cgi");
	eval "flock(TMP,2);";
	print TMP "$date<>$in{name}<>$in{email}<>$in{sub}<>$in{comment}<>$in{url}<>$host<>$pwd<>$time<>";
	close(TMP);
	
	chmod(0666,"$cf{logdir}/$num.cgi");
}

#-----------------------------------------------------------
#  mimeエンコード
#  [quote] http://www.din.or.jp/~ohzaki/perl.htm#JP_Base64
#-----------------------------------------------------------
sub mime_unstructured_header {
  my $oldheader = shift;
  jcode::convert(\$oldheader,'euc','utf8');
  my ($header,@words,@wordstmp,$i);
  my $crlf = $oldheader =~ /\n$/;
  $oldheader =~ s/\s+$//;
  @wordstmp = split /\s+/, $oldheader;
  for ($i = 0; $i < $#wordstmp; $i++) {
    if ($wordstmp[$i] !~ /^[\x21-\x7E]+$/ and
	$wordstmp[$i + 1] !~ /^[\x21-\x7E]+$/) {
      $wordstmp[$i + 1] = "$wordstmp[$i] $wordstmp[$i + 1]";
    } else {
      push(@words, $wordstmp[$i]);
    }
  }
  push(@words, $wordstmp[-1]);
  foreach my $word (@words) {
    if ($word =~ /^[\x21-\x7E]+$/) {
      $header =~ /(?:.*\n)*(.*)/;
      if (length($1) + length($word) > 76) {
	$header .= "\n $word";
      } else {
	$header .= $word;
      }
    } else {
      $header = add_encoded_word($word, $header);
    }
    $header =~ /(?:.*\n)*(.*)/;
    if (length($1) == 76) {
      $header .= "\n ";
    } else {
      $header .= ' ';
    }
  }
  $header =~ s/\n? $//mg;
  $crlf ? "$header\n" : $header;
}
sub add_encoded_word {
  my ($str, $line) = @_;
  my $result;
  my $ascii = '[\x00-\x7F]';
  my $twoBytes = '[\x8E\xA1-\xFE][\xA1-\xFE]';
  my $threeBytes = '\x8F[\xA1-\xFE][\xA1-\xFE]';
  while (length($str)) {
    my $target = $str;
    $str = '';
    if (length($line) + 22 +
	($target =~ /^(?:$twoBytes|$threeBytes)/o) * 8 > 76) {
      $line =~ s/[ \t\n\r]*$/\n/;
      $result .= $line;
      $line = ' ';
    }
    while (1) {
      my $encoded = '=?ISO-2022-JP?B?' .
      b64encode(jcode::jis($target,'euc','z')) . '?=';
      if (length($encoded) + length($line) > 76) {
	$target =~ s/($threeBytes|$twoBytes|$ascii)$//o;
	$str = $1 . $str;
      } else {
	$line .= $encoded;
	last;
      }
    }
  }
  $result . $line;
}
# [quote] http://www.tohoho-web.com/perl/encode.htm
sub b64encode {
    my $buf = shift;
    my ($mode,$tmp,$ret);
    my $b64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZ"
                . "abcdefghijklmnopqrstuvwxyz"
                . "0123456789+/";
	
    $mode = length($buf) % 3;
    if ($mode == 1) { $buf .= "\0\0"; }
    if ($mode == 2) { $buf .= "\0"; }
    $buf =~ s/(...)/{
        $tmp = unpack("B*", $1);
        $tmp =~ s|(......)|substr($b64, ord(pack("B*", "00$1")), 1)|eg;
        $ret .= $tmp;
    }/eg;
    if ($mode == 1) { $ret =~ s/..$/==/; }
    if ($mode == 2) { $ret =~ s/.$/=/; }
    
    return $ret;
}

#-----------------------------------------------------------
#  入力チェック
#-----------------------------------------------------------
sub check_post {
	# 投稿チェック
	if ($cf{postonly} && $ENV{REQUEST_METHOD} ne 'POST') {
		error("不正なリクエストです");
	}
	
	# 不要改行カット
	$in{sub}  =~ s|<br>||g;
	$in{name} =~ s|<br>||g;
	$in{pwd}  =~ s|<br>||g;
	$in{comment} =~ s|(<br>)+$||g;
	
	if ($cf{no_wd}) { no_wd(); }
	if ($cf{urlnum} > 0) { urlnum(); }
	
	# 未入力の場合
	if ($in{url} eq "http://") { $in{url} = ""; }
	if ($in{sub} eq '') { $in{sub} = '無題'; }
	
	# フォーム内容をチェック
	my %err;
	if ($in{name} eq "") { $err{name} = "名前が入力されていません"; }
	if ($in{email} ne '' && $in{email} !~ /^[\w\.\-]+\@[\w\.\-]+\.[a-zA-Z]{2,6}$/) {
		$err{eml} = "e-mailの入力内容が不正です";
	}
	if ($in{comment} eq "") { $err{com} = "メッセージが入力されていません"; }
	elsif ($cf{jp_wd} && $in{comment} !~ /(?:[\xC0-\xDF][\x80-\xBF]|[\xE0-\xEF][\x80-\xBF]{2}|[\xF0-\xF7][\x80-\xBF]{3})/x) {
		$err{com} = "メッセージに日本語が含まれていません";
	}
	if ($in{url} ne '' && $in{url} !~ /^https?:\/\/[\w-.!~*'();\/?:\@&=+\$,%#]+$/i) {
		$err{url} = "参照先URLの入力内容が不正です";
	}
	if (%err > 0) { bbs_list(%err); }
}

#-----------------------------------------------------------
#  セッション確認
#-----------------------------------------------------------
sub check_ses {
	my $now = time;
	my ($flg,@log);
	open(DAT,"+< $cf{conffile}");
	eval "flock(DAT,2);";
	while(<DAT>) {
		chomp;
		my ($time,$rand,$fig) = split(/\t/);
		next if ($now - $time > $cf{cap_time}*60);
		
		if ($in{ses} eq $rand) {
			$flg = 1;
			if ($cf{use_captcha} > 0 && $fig ne $in{captcha}) {
				$flg = -1;
				last;
			}
			next;
		}
		push(@log,"$_\n");
	}
	if ($flg == -1) {
		close(DAT);
		error("画像認証できません");
	}
	seek(DAT,0,0);
	print DAT @log;
	truncate(DAT,tell(DAT));
	close(DAT);
	
	if (!$flg) {
		my $msg = "セッションが不正です<br>";
		$msg .= "以下をクリックして再度投稿し直してください\n";
		my $btn .= qq|<input type="button" class="color red button" onclick="window.open('$cf{bbs_cgi}','_self')" value="掲示板に戻る">|;
		error($msg,$btn);
	}
}

