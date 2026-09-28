<?php

declare(strict_types=1);

namespace App\Domain\Organization;

final class PrefectureCatalog
{
    /** @var array<int, array{code: string, name: string, label_en: string, branch_name: string}> */
    private const ENTRIES = [
        ['code' => 'HOKKAIDO', 'name' => '北海道', 'label_en' => 'Hokkaido', 'branch_name' => '北海道支店'],
        ['code' => 'AOMORI', 'name' => '青森県', 'label_en' => 'Aomori', 'branch_name' => '青森支店'],
        ['code' => 'IWATE', 'name' => '岩手県', 'label_en' => 'Iwate', 'branch_name' => '岩手支店'],
        ['code' => 'MIYAGI', 'name' => '宮城県', 'label_en' => 'Miyagi', 'branch_name' => '宮城支店'],
        ['code' => 'AKITA', 'name' => '秋田県', 'label_en' => 'Akita', 'branch_name' => '秋田支店'],
        ['code' => 'YAMAGATA', 'name' => '山形県', 'label_en' => 'Yamagata', 'branch_name' => '山形支店'],
        ['code' => 'FUKUSHIMA', 'name' => '福島県', 'label_en' => 'Fukushima', 'branch_name' => '福島支店'],
        ['code' => 'IBARAKI', 'name' => '茨城県', 'label_en' => 'Ibaraki', 'branch_name' => '茨城支店'],
        ['code' => 'TOCHIGI', 'name' => '栃木県', 'label_en' => 'Tochigi', 'branch_name' => '栃木支店'],
        ['code' => 'GUNMA', 'name' => '群馬県', 'label_en' => 'Gunma', 'branch_name' => '群馬支店'],
        ['code' => 'SAITAMA', 'name' => '埼玉県', 'label_en' => 'Saitama', 'branch_name' => '埼玉支店'],
        ['code' => 'CHIBA', 'name' => '千葉県', 'label_en' => 'Chiba', 'branch_name' => '千葉支店'],
        ['code' => 'TOKYO', 'name' => '東京都', 'label_en' => 'Tokyo', 'branch_name' => '東京支店'],
        ['code' => 'KANAGAWA', 'name' => '神奈川県', 'label_en' => 'Kanagawa', 'branch_name' => '神奈川支店'],
        ['code' => 'NIIGATA', 'name' => '新潟県', 'label_en' => 'Niigata', 'branch_name' => '新潟支店'],
        ['code' => 'TOYAMA', 'name' => '富山県', 'label_en' => 'Toyama', 'branch_name' => '富山支店'],
        ['code' => 'ISHIKAWA', 'name' => '石川県', 'label_en' => 'Ishikawa', 'branch_name' => '石川支店'],
        ['code' => 'FUKUI', 'name' => '福井県', 'label_en' => 'Fukui', 'branch_name' => '福井支店'],
        ['code' => 'YAMANASHI', 'name' => '山梨県', 'label_en' => 'Yamanashi', 'branch_name' => '山梨支店'],
        ['code' => 'NAGANO', 'name' => '長野県', 'label_en' => 'Nagano', 'branch_name' => '長野支店'],
        ['code' => 'GIFU', 'name' => '岐阜県', 'label_en' => 'Gifu', 'branch_name' => '岐阜支店'],
        ['code' => 'SHIZUOKA', 'name' => '静岡県', 'label_en' => 'Shizuoka', 'branch_name' => '静岡支店'],
        ['code' => 'AICHI', 'name' => '愛知県', 'label_en' => 'Aichi', 'branch_name' => '愛知支店'],
        ['code' => 'MIE', 'name' => '三重県', 'label_en' => 'Mie', 'branch_name' => '三重支店'],
        ['code' => 'SHIGA', 'name' => '滋賀県', 'label_en' => 'Shiga', 'branch_name' => '滋賀支店'],
        ['code' => 'KYOTO', 'name' => '京都府', 'label_en' => 'Kyoto', 'branch_name' => '京都支店'],
        ['code' => 'OSAKA', 'name' => '大阪府', 'label_en' => 'Osaka', 'branch_name' => '大阪支店'],
        ['code' => 'HYOGO', 'name' => '兵庫県', 'label_en' => 'Hyogo', 'branch_name' => '兵庫支店'],
        ['code' => 'NARA', 'name' => '奈良県', 'label_en' => 'Nara', 'branch_name' => '奈良支店'],
        ['code' => 'WAKAYAMA', 'name' => '和歌山県', 'label_en' => 'Wakayama', 'branch_name' => '和歌山支店'],
        ['code' => 'TOTTORI', 'name' => '鳥取県', 'label_en' => 'Tottori', 'branch_name' => '鳥取支店'],
        ['code' => 'SHIMANE', 'name' => '島根県', 'label_en' => 'Shimane', 'branch_name' => '島根支店'],
        ['code' => 'OKAYAMA', 'name' => '岡山県', 'label_en' => 'Okayama', 'branch_name' => '岡山支店'],
        ['code' => 'HIROSHIMA', 'name' => '広島県', 'label_en' => 'Hiroshima', 'branch_name' => '広島支店'],
        ['code' => 'YAMAGUCHI', 'name' => '山口県', 'label_en' => 'Yamaguchi', 'branch_name' => '山口支店'],
        ['code' => 'TOKUSHIMA', 'name' => '徳島県', 'label_en' => 'Tokushima', 'branch_name' => '徳島支店'],
        ['code' => 'KAGAWA', 'name' => '香川県', 'label_en' => 'Kagawa', 'branch_name' => '香川支店'],
        ['code' => 'EHIME', 'name' => '愛媛県', 'label_en' => 'Ehime', 'branch_name' => '愛媛支店'],
        ['code' => 'KOCHI', 'name' => '高知県', 'label_en' => 'Kochi', 'branch_name' => '高知支店'],
        ['code' => 'FUKUOKA', 'name' => '福岡県', 'label_en' => 'Fukuoka', 'branch_name' => '福岡支店'],
        ['code' => 'SAGA', 'name' => '佐賀県', 'label_en' => 'Saga', 'branch_name' => '佐賀支店'],
        ['code' => 'NAGASAKI', 'name' => '長崎県', 'label_en' => 'Nagasaki', 'branch_name' => '長崎支店'],
        ['code' => 'KUMAMOTO', 'name' => '熊本県', 'label_en' => 'Kumamoto', 'branch_name' => '熊本支店'],
        ['code' => 'OITA', 'name' => '大分県', 'label_en' => 'Oita', 'branch_name' => '大分支店'],
        ['code' => 'MIYAZAKI', 'name' => '宮崎県', 'label_en' => 'Miyazaki', 'branch_name' => '宮崎支店'],
        ['code' => 'KAGOSHIMA', 'name' => '鹿児島県', 'label_en' => 'Kagoshima', 'branch_name' => '鹿児島支店'],
        ['code' => 'OKINAWA', 'name' => '沖縄県', 'label_en' => 'Okinawa', 'branch_name' => '沖縄支店'],
    ];

    /** @return array<int, array{code: string, name: string, label_en: string, branch_name: string}> */
    public function all(): array
    {
        return self::ENTRIES;
    }

    /** @return array{code: string, name: string, label_en: string, branch_name: string}|null */
    public function find(string $code): ?array
    {
        $normalized = strtoupper(trim($code));
        foreach (self::ENTRIES as $entry) {
            if ($entry['code'] === $normalized) {
                return $entry;
            }
        }

        return null;
    }

    public function contains(string $code): bool
    {
        return $this->find($code) !== null;
    }

    public function branchName(string $code): ?string
    {
        return $this->find($code)['branch_name'] ?? null;
    }

    public function branchLabel(string $code, string $locale): ?string
    {
        $entry = $this->find($code);
        if ($entry === null) {
            return null;
        }

        return $locale === 'ja' ? $entry['branch_name'] : $entry['label_en'] . ' Branch';
    }

    public function label(string $code, string $locale): ?string
    {
        $entry = $this->find($code);
        if ($entry === null) {
            return null;
        }

        return $locale === 'ja' ? $entry['name'] : $entry['label_en'];
    }
}
