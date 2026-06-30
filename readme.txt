=== Link Map Insights ===
Contributors: seoryco
Tags: internal links, seo, link map, visualization, internal linking
Requires at least: 5.0
Tested up to: 6.9.1
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

ワンクリックで内部リンクマップを作成し、被リンク・発リンクがゼロのページを可視化できます。

== Description ==

Link Map Insights は、サイト内の内部リンク構造をワンクリックで可視化する管理画面向けプラグインです。
公開済みの投稿・固定ページ・カスタム投稿タイプを解析し、ページ同士の内部リンクをネットワーク図として描画します。

主な機能:

* ワンクリックで内部リンクマップを生成
* 投稿タイプ・タクソノミー（カテゴリー／タグ等）によるフィルタリング
* ノード検索、拡大・縮小、タイトル省略表示
* 被リンクがゼロのページの一覧表示
* 発リンクがゼロのページの一覧表示

内部リンクの最適化や、孤立ページの発見に役立ちます。

This plugin visualizes the internal link structure of your site in one click. It analyzes published posts, pages, and custom post types, then renders the internal links between them as a network graph. It also lists pages with no inbound links and pages with no outbound links.

== External services ==

このプラグインは外部サービスへデータを送信しません。すべての解析はあなたのWordPress内で完結します。

This plugin does not send any data to external services. All analysis is performed locally within your WordPress installation.

== Third-party libraries ==

このプラグインは可視化のために vis-network (https://visjs.github.io/vis-network/) を同梱しています。vis-network は MIT / Apache-2.0 ライセンスで配布されています。

This plugin bundles vis-network (https://visjs.github.io/vis-network/) for graph visualization, distributed under the MIT / Apache-2.0 license.

== Installation ==

1. プラグインをアップロードして有効化します。
2. 管理画面の左メニュー「Link Map」を開きます。
3. 「Generate」ボタンをクリックすると内部リンクマップが表示されます。

== Frequently Asked Questions ==

= どの投稿タイプが解析対象ですか？ =

公開（public）に設定されたすべての投稿タイプが対象です（添付ファイルを除く）。画面上のチェックボックスで表示対象を絞り込めます。

= 解析結果はどこに保存されますか？ =

結果は一時データ（transient）としてサイト内に保存されます。外部へは一切送信されません。

== Screenshots ==

1. 内部リンクマップの表示画面
2. 被リンクゼロ・発リンクゼロのページ一覧

== Changelog ==

= 1.1.0 =
* WordPress.org 公式ディレクトリ向けに整備（外部アップデート機構の削除、vis-network のローカル同梱、readme.txt の追加）。

= 1.0.0 =
* 初回リリース。

== Upgrade Notice ==

= 1.1.0 =
更新の取得元が WordPress.org 公式ディレクトリに切り替わります。
