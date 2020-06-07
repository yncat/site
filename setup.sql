USE actlab

CREATE TABLE informations(
	id int UNSIGNED auto_increment,
	title char(255),
	date date default null,
	url char(255) default null,
	flag int UNSIGNED default 0 NOT NULL,
	PRIMARY KEY(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO informations VALUES(default,"ラボがオープンしました。","2020-06-10",null,0);
INSERT INTO informations VALUES(default,"Twitterでも新着情報をお届けしています。","2020-06-10","https://twitter.com/act_laboratory",0);

CREATE TABLE members(
	id int UNSIGNED auto_increment,
	name char(32) NOT NULL,
	email char(255),
	introduction text,
	twitter char(32),
	URL char(255),
	github char(32),
	flag int DEFAULT 0 NOT NULL,
	PRIMARY KEY(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO members VALUES(default,"ACT Laboratory(dummy)","support@actlab.org","hello!","act_laboratory","https://actlab.org","actlaboratory",0);
INSERT INTO members VALUES(default,"member(dummy)","","yea!",null,null,null,0);



CREATE TABLE softwares(
	id INT UNSIGNED auto_increment,
	title char(255),
	keyword char(32),
	description text NOT NULL,
	gitHubURL char(255) NOT NULL,
	snapshotURL char(255) default null,
	staff INT UNSIGNED NOT NULL,
	flag int UNSIGNED default 0 NOT NULL,
	PRIMARY KEY(id),
	UNIQUE(keyword),
	FOREIGN KEY (staff) REFERENCES members(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO softwares VALUES(
	default,
	"SOC - Symple OCR Controller",
	"SOC",
	"OCR(光学文字認識)技術をシンプルに、そして使いやすく。性格・高速なgoogleエンジンとセキュリティを確保できるローカルのTesseractエンジンを使い分け、PDF文書やスキャン文書画像を簡単OCR",
	"https://github.com/actlaboratory/SOC/",
	"https://github.com/actlaboratory/SOC/releases/download/SOC-latestcommit/SOC-snapshot.zip",
	5,
	0
);


CREATE TABLE software_versions(
	id INT UNSIGNED auto_increment,
	software_id INT UNSIGNED NOT NULL,

	major TINYINT NOT NULL,
	minor TINYINT NOT NULL,
	patch TINYINT NOT NULL,

	hist_text text NOT NULL,
	package_URL char(255),
	updater_URL char(255),
	update_min_Major TINYINT,
	update_min_minor TINYINT,

	released_at datetime,
	flag int UNSIGNED default 0 NOT NULL,
	PRIMARY KEY(id),
	FOREIGN KEY (software_id) REFERENCES softwares(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO software_versions VALUES(
	default,
	1,
	0,0,1,
	"新規公開",
	"https://github.com/actlaboratory/SOC/releases/download/0.0.1/SOC-0.0.1.zip",
	"https://github.com/actlaboratory/SOC/releases/download/0.0.1/SOC-0.0.1-up.exe",
	0,0,
	"2020-06-05 20:00:00",
	0
);
