# CMS Migration Tool

WebサイトのHTMLページをブロック型CMSへデータ移行するPHPプログラム

## 技術スタック

- PHP 8.2
- Symfony DomCrawler (DOM要素の抽出)
- Symfony CSS Selector (CSSセレクタのサポート)

## 機能概要

このプログラムは以下の処理を実行します：

1. 定義されたURLからHTMLコンテンツを取得
2. `<main id="main" role="main">` 要素内のコンテンツのみを対象に抽出（不要な要素を除外）
3. CSSセレクタを使用してDOM要素を抽出
4. セレクタとコンポーネント名のマッピングに基づいて連想配列を構築
5. 結果をJSON形式で出力・保存

## インストール

### 前提条件

- PHP 8.2以上
- Composer

### セットアップ

```bash
# 依存関係のインストール
composer install
```

## 使用方法

### 基本的な実行

```bash
php migrate.php
```

### 出力

プログラムは以下の3つの形式で結果を出力します：

1. **コンソール出力（配列形式）**: 実行中にprint_rで配列構造を表示
2. **コンソール出力（JSON形式）**: 整形されたJSON形式で表示
3. **ファイル出力**: `migration_output.json` に結果を保存

## 設定のカスタマイズ

### ページ情報の定義

`migrate.php` の `PAGE_INFO` 定数を編集してください：

```php
private const PAGE_INFO = [
    [
        'page_id' => 1000,
        'url' => 'https://example.com/page1.html'
    ],
    [
        'page_id' => 1001,
        'url' => 'https://example.com/page2.html'
    ]
];
```

### セレクタとコンポーネントのマッピング

`migrate.php` の `SELECTOR_MAPPING` 定数を編集してください：

```php
private const SELECTOR_MAPPING = [
    'span#term_id[data-value]' => 'h1-title',
    'span.txt.-suppress._fz-xl' => 'sub-title',
    'i.ico-label.-navy' => 'label',
    'p.txt' => 'text'
];
```

#### セレクタの記法

- **ID**: `#element_id` または `element#element_id`
- **クラス**: `.class-name` または `element.class-name`
- **複数クラス**: `.class1.class2.class3`
- **属性**: `element[attribute]` または `element[attribute="value"]`
- **組み合わせ**: `span#id.class1.class2[data-value]`

## 出力データ構造

```json
{
    "1000": {
        "url": "https://example.com/page.html",
        "contents": {
            "1": {
                "block_id": null,
                "component_name": "h1-title",
                "value": "抽出されたテキスト"
            },
            "2": {
                "block_id": null,
                "component_name": "sub-title",
                "value": "抽出されたテキスト"
            }
        }
    }
}
```

### データ構造の説明

- **トップレベルキー**: ページID（`PAGE_INFO`で定義）
- **url**: 移行元のページURL
- **contents**: 抽出されたコンテンツの配列
  - **キー**: 連番（seq_no）
  - **block_id**: コンポーネントのブロックID（現在はnull、DB連携時に使用）
  - **component_name**: マッピングで定義されたコンポーネント名
  - **value**: 抽出されたDOM要素のテキストコンテンツ

## 特殊機能

### メインコンテンツの自動フィルタリング

プログラムは自動的に `<main id="main" role="main">` 要素内のコンテンツのみを抽出対象とします。これにより、ヘッダー、フッター、サイドバーなどの不要な要素を除外し、本文コンテンツのみを正確に抽出できます。

対象ページに `<main id="main" role="main">` 要素が存在しない場合は、警告メッセージを表示した上でページ全体から抽出を行います。

### data-value属性の処理

要素に `data-value` 属性がある場合、その値とテキストコンテンツを組み合わせて出力します：

```html
<span id="term_id" data-value="A02904">106万円の壁</span>
```

出力：
```
"value": "A02904 - 106万円の壁"
```

## トラブルシューティング

### HTMLの取得に失敗する場合

- URLが正しいか確認してください
- ネットワーク接続を確認してください
- 対象サイトがアクセス制限をしていないか確認してください

### セレクタで要素が抽出できない場合

- ブラウザの開発者ツールで実際のHTML構造を確認してください
- CSSセレクタの記法が正しいか確認してください
- 動的に生成される要素（JavaScript）は取得できません

## 拡張方法

### 複数ページの一括処理

`PAGE_INFO` 配列に複数のページ情報を追加するだけで、自動的に全ページを処理します。

### カスタム抽出ロジック

`extractElementValue()` メソッドを編集することで、要素からの値の抽出方法をカスタマイズできます。

### データベース連携

`block_id` の取得が必要な場合は、以下の手順で実装できます：

1. MySQLへの接続設定を追加
2. `extractContents()` メソッド内でSQLクエリを実行
3. 取得した `block_id` を配列に設定

## ライセンス

このプロジェクトは移行作業用のツールです。
