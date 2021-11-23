<?php

const FLG_HIDDEN = 0x1;
const FLG_NO_SNAPSHOT = 0x2;

const PAYMENT_TYPE = [
    "credit"=> 0,
    "transfer"=> 1
];

const ORDER_STATUS_TMP = 0;
const ORDER_STATUS_PROCESSING = 1;
const ORDER_STATUS_WAIT_PAY = 2;
const ORDER_STATUS_PAID = 3;
const TAX_RATE = 0.1;
const TRANSFER_FEE = 500;
TAX_RATE: 0.1;
const BANK_NAME = "三菱UFJ銀行";
const BANK_BRANCH = "玉川支店";
const BANK_ACCOUNT = "普通";
const BANK_ACCOUNT_NO = "0909939";
const BANK_ACCOUNT_OWNER = "アクセシブル　ツールズ　ラボラトリー　コウチ　ユウキ";
