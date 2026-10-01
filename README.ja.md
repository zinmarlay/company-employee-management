# 社員管理システム

[English](README.md) | 日本語

## 概要

Company Employee Management System は、社員、組織、派遣契約、社員ポートフォリオ、システムユーザーを管理するサーバーサイドレンダリング型の社内向けWebアプリケーションです。Pure PHPで実装し、HTTPリクエストの流れ、認証・認可、入力検証、PDOによる永続化を明示的な境界として構成しています。

ローカル開発、技術面接での説明、管理された本番デプロイを想定しています。公開ユーザー登録、APIプラットフォーム、SPAではありません。

## プロジェクトの目的

社員情報と組織情報を一元管理し、無効化やアーカイブによって履歴を保持します。また、フルスタックフレームワークやORMに依存せず、依存性注入、DTO、アプリケーションサービス、Repository、PDOの責務分離を実践します。

## 主な機能

- 社員の登録、編集、詳細表示、無効化、履歴保持
- キーワード、支店、部署、雇用区分、ステータスによる社員検索、許可リスト方式のソート、ページネーション
- 会社、支店、部署の管理と有効・無効ライフサイクル
- 派遣会社と派遣契約の管理、社員ごとの契約期間重複防止
- 契約終了日の分類（通常、30日以内、7日以内、期限切れ）
- スキル、プロジェクト、資格のポートフォリオ管理とアーカイブ
- ADMIN／USERのシステムユーザー管理、認証、再有効化、ライフサイクル保護
- 英語・日本語の表示ローカライズ

## 使用技術

- PHP 8.3以上、MySQLまたはMariaDB、PDO、`pdo_mysql`
- Composer、PSR-4オートロード、PHPUnit
- PHPテンプレートによるサーバーサイドレンダリング、HTML5/CSS、最小限の同一オリジンJavaScript
- `mbstring`（必須）

フルスタックフレームワーク、ORM、React/Vue SPA、キュー、Redis、ファイルアップロード機能は使用していません。現在ファイルアップロード機能がないため、`fileinfo`は必須ではありません。

## システム構成とリクエストライフサイクル

```text
ブラウザ
  -> public/index.php              フロントコントローラ
  -> ApplicationBootstrap           コンポジションルート
  -> Request -> HttpKernel
  -> MiddlewarePipeline             セッション、ロケール、認証、認可、CSRF
  -> Router -> Controller
  -> Application Service -> Repository -> PDO / MySQL・MariaDB
```

レスポンスは`Controller -> ViewRenderer -> Response -> ResponseEmitter -> ブラウザ`の経路で返ります。`ApplicationBootstrap`は依存関係を組み立て、ControllerはHTTP入力をサービスへ渡し、サービスは業務フローを調整し、RepositoryはPDOクエリとトランザクションを担当します。

## ディレクトリ構成

```text
config/                 アプリケーション設定
database/migrations/    スキーママイグレーション
docs/                   デプロイ・開発運用ドキュメント
public/                 Web公開ルートと公開アセット
resources/lang/         英語・日本語の翻訳
resources/views/        PHPテンプレート
routes/                 ルート登録とアクセス区分
src/Bootstrap/           設定とコンポジションルート
src/Domain/              契約とドメイン例外
src/Application/         DTO、バリデータ、サービス
src/Infrastructure/      PDO Repository実装
src/Http/                HTTP境界、ルーティング、画面、Controller
src/Security/            セッション、認証、CSRF
src/Logging/             error_log向けロガー
tests/                   Unit、HTTP Feature、DB Integrationテスト
bin/                     マイグレーション、シード、ユーザー管理CLI
```

## データベースとドメイン

```text
Company 1---* Branch 1---* Department
                         |
                         *---* Employee
Employee *---* Skill / Employee 1---* Project
Employee 1---* Certification
Employee *---* Dispatch Contract *---1 Dispatch Company
System User / Employee Code Sequence
```

外部キー、ユニーク制約、ステータス制約、日付制約、`utf8mb4`を使用します。社員、組織、ポートフォリオ、ユーザーは原則として物理削除ではなく無効化・アーカイブで履歴を保持します。社員番号と重要な組織識別情報は安定させ、派遣契約は履歴として保存します。

## 認証、認可、セキュリティ対策

ログインではメールアドレスを正規化して検索し、パスワードハッシュを検証します。存在しないユーザーや無効ユーザーにはダミーハッシュを使用し、成功時にはセッションIDを再生成し、CSRFトークンをローテーションします。既存セッションも各リクエストでDB上のユーザー状態を再確認します。

ルートは`public`、`authenticated`、`authenticated_read`、`admin`に分類し、認可をミドルウェアで強制します。ADMIN／USERの境界、CSRF、POST限定の状態変更、パスワードハッシュ、HTML/JSONエスケープ、PDOプリペアドステートメント、入力上限、セキュアセッションを実装しています。

HTMLにはCSP（`script-src 'self'`、`frame-ancestors 'none'`）、`X-Frame-Options: DENY`、`nosniff`、Referrer Policyを付与します。予期しない例外と起動失敗はブラウザには一般化して返し、PHPの設定済み`error_log`へ安全な運用メタデータと相関IDを記録します。本番環境では、アプリケーションが直接HTTPS接続を確認できる構成のみをサポートし、`X-Forwarded-Proto`や`Forwarded`は信頼しません。派遣契約は社員行ロックを保持したトランザクションで重複を防止します。

これらは実装済みの対策であり、完全な安全性を保証するものではありません。TLS、DB権限、PHPランタイム、監視、バックアップ、運用手順はデプロイ環境の責務です。

## 機能領域

- **社員管理**: 登録、編集、参照、無効化、検索、フィルター、ソート、ページネーション。部署選択の補助JavaScriptは[`public/assets/js/employee-search.js`](public/assets/js/employee-search.js)に分離しています。
- **組織管理**: 会社、支店、部署を階層管理し、無効な組織も履歴参照のため保持します。
- **派遣契約管理**: 派遣会社、契約期間、更新履歴、終了日分類、期間重複防止を扱います。
- **ポートフォリオ**: スキル、習熟度、経験年数、プロジェクト、資格、アーカイブ状態を管理します。
- **システムユーザー管理**: ADMIN／USER、ユーザーの有効・無効、最後のADMIN保護、パスワード更新を扱います。
- **多言語対応**: 英語・日本語の翻訳リソースと`app_locale` Cookieによるロケール切り替えを提供します。

## 環境構築

必要条件はPHP 8.3以上、Composer、MySQL/MariaDB、`pdo_mysql`、`mbstring`です。

```sh
git clone <repository-url>
cd company-employee-management
composer install
cp .env.example .env
```

`.env`にローカルDBの値を設定してください。`.env`や実際の認証情報をコミット・掲載しないでください。

### Local / Test / Production

- **Local**: `APP_ENV=local`、`APP_DEBUG=true`、ローカルHTTPの`APP_URL`、ローカル用`DB_*`。開発用シードを利用できます。
- **Test**: `APP_ENV=test`と、`DB_TEST_*`で指定した専用DB。DB設定がない場合、統合テストは安全にスキップされます。
- **Production**: `APP_ENV=production`、`APP_DEBUG=false`、`https://`の`APP_URL`、明示的なDBホスト・ポート・DB名・ユーザー名・パスワード・文字コード。localの既定値や`change-me`は使用できません。直接信頼できるHTTPSのみを使用し、`display_errors=Off`、`display_startup_errors=Off`、`log_errors=On`、適切な`error_reporting`、ホスト管理の`error_log`を設定します。

詳細は[`docs/deployment-checklist.md`](docs/deployment-checklist.md)、設定テンプレートは[`.env.example`](.env.example)を参照してください。

## データベース、マイグレーション、開発用データ

```sh
php bin/migrate status
php bin/migrate migrate
php bin/seed
```

マイグレーションはCLIから実行し、Webリクエストでは実行しません。`bin/seed`はlocal環境専用で、トランザクション内で冪等に開発用サンプルデータを投入します。`DB_TEST_*`には接続しません。詳細は[`docs/development-seeding.md`](docs/development-seeding.md)を参照してください。

## ローカル起動とADMIN作成

```sh
php -S localhost:8000 -t public
php bin/system-user create-admin
```

`create-admin`は管理者情報を対話入力し、既定パスワードは使用しません。

## テスト

```sh
composer test
```

DB統合テストの例:

```sh
APP_ENV=test \
DB_TEST_HOST=127.0.0.1 \
DB_TEST_PORT=3306 \
DB_TEST_DATABASE=company_employee_management_test \
DB_TEST_USERNAME=root \
DB_TEST_PASSWORD= \
DB_TEST_CHARSET=utf8mb4 \
composer test
```

開発・本番DBをテストに指定しないでください。

## 本番デプロイ

Webサーバーのドキュメントルートは`/public`にし、`.env`、ソース、マイグレーション、運用スクリプトを公開ルート外に置きます。Production用Composer依存関係をインストールし、マイグレーションを確認して適用します。デプロイ後はセキュリティヘッダー、ログイン・ログアウト、CSRF拒否、認可境界、ロケール、DB接続、安全なエラー応答を確認してください。ログのローテーション、保持期間、権限、監視、バックアップ、復旧はホスト・運用担当者の責務です。

## 設計方針

- インフラ詳細をインターフェースとRepositoryの内側に閉じ込める
- コンストラクタ注入と小さなDTOを使い、隠れたグローバル依存を避ける
- 入力検証と認可をサーバー境界で実施する
- ライフサイクル・アーカイブ状態で履歴を保持する
- DB制約でアプリケーションの不変条件を補強する
- セキュリティ判断を明示的かつテスト可能にする
- 実装している範囲を超えて説明しない
