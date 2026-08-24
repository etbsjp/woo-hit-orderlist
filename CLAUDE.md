# woo-hit-orderlist

etbs が配布する WordPress プラグイン。共通ルールの正本は `~/.claude/etbs-plugin-rules.md`。

## レビュー工程に大（シニアエンジニア）を追加する

このリポジトリでは、安藤（`vk-code-reviewer`）のレビューのあと、**PR を作成する前に**
大（`etbs-senior-wp`）の監査を必ず通すこと。大は etbs の申し送りと過去に踏んだ罠に照らして
「リリースできる形になっているか」を見る担当で、安藤の一般的なコード品質レビューとは層が違う。

- `Agent` ツールで `subagent_type: etbs-senior-wp`、`name: etbs-senior-wp`、
  **`run_in_background: false`** で起動する
- **`isolation: "worktree"` は使えるなら付ける**（付けないと起動応答は「成功」と返るのに
  一度も作業せず待機状態に入ることがある）。ただし ★★ **作業ディレクトリが git リポジトリでないと使えない。**
  その場合は **isolation なしで起動してよい**（2026-08-19 実績あり）。
  **見分け方は起動応答の形**——`output_file` 付きの正常形なら動いている
- prompt には対象リポジトリ・ブランチ・差分（または PR 番号）を渡す
- 大には **出力の末尾に `監査結果: PASS` または `監査結果: FAIL` を必ず書くよう指示する**
  （★ 大の定義ファイルには出力形式の指定が無いため、指示しないと合否を機械判定できない）
- `監査結果: PASS` を受け取るまで PR を作成しない。`FAIL` なら和田へ差し戻して再監査する

★ 大は vk-agents のメンバー表に登録されていないため、指示が無いと**永久に呼ばれない**。

## 検証環境

Local の `order-memo`（`ordermemo.etbs.lc`）。**シンボリックリンク設置でよい**
（このプラグインは `dirname( __FILE__, N )` を使っていない）。
★ このサイトは sigusa.jp のクローンで**実顧客の注文37件**を含む。氏名・メール・住所を出力しないこと。

CLI 検証では Local の php.ini を `-c` で渡すこと。渡さないと「データベース接続確立エラー」になり、
**サイトが停止しているように見える**（実際は動いている）。`<runId>` は
`ls -d ~/Library/Application\ Support/Local/run/*/mysql/mysqld.sock` で特定する。

## アンインストール

★ `uninstall.php` の方針は**案A**（task-queue #108）。判定は3分類。

| 利用者が作ったコンテンツ（投稿・投稿メタ） | 利用者が設定した値（オプション） | 一時状態・自分が仕掛けた cron |
|---|---|---|
| **消さない** | **消さない** | **消す** |

理由は害の非対称性。消さないことの害は「DB に少量のレコードが残る」だけだが、消すことの害は
復旧不可能。迷ったら残す側に倒す。

このプラグインでの当てはめ:

- **残す** … 該当なし（永続状態を一切持たない）
- **消す** … 該当なし（独自テーブルも cron も持たない）

★ `uninstall.php` に `//delete_option('woohitorderlist');` の残骸コメントがあったが、この名前の
オプションはどこからも保存していない。「消し忘れがある」と読まれるため削除した。

★ 配布8本すべてがこの3分類で説明できる状態にしてある。テーブルと cron を持つのは editlock だけ、
一時状態のオプションを持つのは pageguard だけで、そこだけが「消す」に該当する。
**他のプラグインで「何も消していない」のは判断の結果であって書き忘れではない。**
横並びで「消す」側へ揃えにこないこと。

## 版数

版数は**2箇所**。片方だけ上げないこと。

- `woo-hit-orderlist.php` の `Version:` ヘッダ
- `readme.txt` の `Stable tag`

```sh
grep -nE "^ \* Version:|^Stable tag:" woo-hit-orderlist.php readme.txt
```

JS の enqueue は SheetJS 自身の版（`0.20.3`）を使っておりキャッシュバスターを兼ねていないため、
これ以外に追随させる箇所は無い。

★★ **`readme.txt` は 1.1.3 で新設した。** PUC は readme.txt があるとそこから
`requires` / `requires_php` / `tested` を**本体ヘッダより後に上書きする**
（`Puc/v5p5/Vcs/PluginUpdateChecker.php:186-194`）。つまり**過剰宣言の置き場が2つになった**。
下の「宣言（Requires）の方針」は readme.txt 側にも同じように効く。
現在 readme.txt には `Requires at least` を**書いていない**（実在する下限が無いため）。

## 配布物

`dist` ブランチへのマージ＝配信。PUC が配る zip には**追跡しているファイルが全部入る**ため、
`.gitignore`（追跡させない）と `.gitattributes` の `export-ignore`（zip から落とす）は役割が別。
両方を維持すること。

## 宣言（Requires）の方針

★★ `Requires at least` / `Requires PHP` は**実在する下限があるときだけ書く。無ければ書かない。**
**他のプラグインと横並びで揃えない。**

- 過剰宣言は WordPress が**有効化そのものを拒否する**（`validate_plugin_requirements()`）。
  更新も `Plugin_Upgrader::check_package()` の段階で `incompatible_wp_required_version` で止まる
  （★ PUC は `requires` を更新トランジェントに入れないため、**更新リンクは出るのに押すと失敗する**という形になる）
- このリポジトリは 2026-08-19 に `Requires at least: 6.7` を**削除**した。
  理由：ブロックを登録しておらず、自前コードの最も新しい WP API が `sanitize_textarea_field()`（WP 4.7）、
  同梱 PUC を含めても `wp_doing_cron()`（WP 4.8）で、**6.x 帯に下限が存在しない**。
  6.7 は初版 `5e45967`（0.1.0 / 2025-05-12）からの定型文で、特定の API に紐づいたものではなかった
- `Requires PHP: 7.4` は据え置き（PHP 7.4.30 の実バイナリで全ファイルの構文チェックが通ることを確認済み）
- ★★ **「据え置き」と「新規に足す」は別問題**（2026-08-25 / task-queue #111 で再確認）。既に宣言している版を据え置いても新たに締め出す個体は生まれないが、**無宣言のプラグインに `Requires PHP` を新しく足すと、いま更新が届いている個体を以後届かなくする**。`woo-checkout-colorbox` と `widget-shortcode-tools` が無宣言なのは、この理由による意図的な判断。**8本で揃えにこないこと**
