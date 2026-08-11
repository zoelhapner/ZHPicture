<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Menu;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        Menu::truncate();

        $menus = [
            [
                'text' => 'Beranda',
                'icon' => 'ti ti-home',
                'url' => 'dashboard',
                'type' => 'route',
                'order' => 0,
                'is_active' => true,
            ],
            [
                'text' => 'Kustom',
                'icon' => 'ti ti-square-plus',
                'url' => '#',
                'type' => 'url',
                'order' => 1,
                'is_active' => true,
                'permission_name' => 'lihat daftar user',
                'children' => [
                    [
                        'text' => 'Daftar Menu',
                        'icon' => 'ti ti-category',
                        'url' => '/menus',
                        'type' => 'url',
                        'order' => 0,
                        'is_active' => true,
                        'permission_name' => 'lihat daftar menu',
                    ],
                    [
                        'text' => 'Manajemen Permission',
                        'icon' => 'ti ti-notebook',
                        'url' => '/permissions',
                        'type' => 'url',
                        'order' => 1,
                        'is_active' => true,
                        'permission_name' => 'lihat daftar permission',
                    ]
                ],
            ],
            [
                'text' => 'Akun',
                'icon' => 'ti ti-user-circle',
                'url' => '#',
                'type' => 'url',
                'order' => 2,
                'is_active' => true,
                'permission_name' => 'lihat daftar user',
                'children' => [
                    [
                        'text' => 'Manajemen User',
                        'icon' => 'ti ti-user-circle',
                        'url' => '/users',
                        'type' => 'url',
                        'order' => 0,
                        'is_active' => true,
                        'permission_name' => 'lihat daftar user',
                    ],
                    [
                        'text' => 'Manajemen Role',
                        'icon' => 'ti ti-user-check',
                        'url' => '/roles',
                        'type' => 'url',
                        'order' => 1,
                        'is_active' => true,
                        'permission_name' => 'lihat daftar role',
                    ],
                    [
                        'text' => 'Manajemen Akun',
                        'icon' => 'ti ti-user',
                        'url' => '/accounts',
                        'type' => 'url',
                        'order' => 2,
                        'is_active' => true,
                        'permission_name' => 'kelola akun',
                    ],
                ],
            ],
            [
                'text' => 'SDM',
                'icon' => 'ti ti-users-group',
                'url' => '#',
                'type' => 'url',
                'order' => 3,
                'is_active' => true,
                'permission_name' => 'lihat daftar karyawan|lihat data karyawan',
                'children' => [
                    [
                        'text' => 'Data Karyawan',
                        'icon' => 'ti ti-id-badge',
                        'url' => '/employees',
                        'type' => 'url',
                        'order' => 0,
                        'is_active' => true,
                        'permission_name' => 'lihat daftar karyawan|lihat data karyawan',
                    ],
                    [
                        'text' => 'Absensi',
                        'icon' => 'ti ti-article',
                        'url' => '/licenses',
                        'type' => 'url',
                        'order' => 1,
                        'is_active' => true,
                        'permission_name' => 'lihat daftar absensi',
                    ],
                    [
                        'text' => 'Pelatihan',
                        'icon' => 'ti ti-article',
                        'url' => '/license_holders',
                        'type' => 'url',
                        'order' => 2,
                        'is_active' => true,
                        'permission_name' => 'lihat daftar pelatihan',
                    ],
                    [
                        'text' => 'Penilaian Kinerja',
                        'icon' => 'ti ti-article',
                        'url' => '/accounting',
                        'type' => 'url',
                        'order' => 3,
                        'is_active' => true,
                        'permission_name' => 'lihat daftar penilaian kinerja',
                    ],
                ],
            ],
            [
                'text' => 'Recruitment',
                'icon' => 'ti ti-users',
                'url' => '#',
                'type' => 'url',
                'order' => 4,
                'is_active' => true,
                'permission_name' => 'lihat daftar recruitment',
                'children' => [
                    [
                        'text' => 'Template Lowongan Kerja',
                        'icon' => 'ti ti-article',
                        'url' => '/recruitment',
                        'type' => 'url',
                        'order' => 0,
                        'is_active' => true,
                        'permission_name' => 'lihat daftar recruitment',
                    ],
                ],
            ],
            [
                'text' => 'Customer',
                'icon' => 'ti ti-building-bank',
                'url' => '#',
                'type' => 'url',
                'order' => 5,
                'is_active' => true,
                'permission_name' => 'lihat daftar customer',
                'children' => [
                    [
                        'text' => 'Daftar Customer',
                        'icon' => 'ti ti-list',
                        'url' => '/customers',
                        'type' => 'url',
                        'order' => 0,
                        'is_active' => true,
                        'permission_name' => 'lihat daftar customer',
                    ],
                    [
                        'text' => 'Edit Profile',
                        'icon' => 'ti ti-notebook',
                        'url' => '/customer/profile',
                        'type' => 'url',
                        'order' => 1,
                        'is_active' => true,
                        'permission_name' => 'ubah data customer',
                    ],
                    [
                        'text' => 'Riwayat Transaksi',
                        'icon' => 'ti ti-notebook',
                        'url' => '/customer/history',
                        'type' => 'url',
                        'order' => 2,
                        'is_active' => true,
                        'permission_name' => 'riwayat transaksi customer',
                    ],
                ],
            ],
            [
                'text' => 'Affiliator',
                'icon' => 'ti ti-affiliate',
                'url' => '#',
                'type' => 'url',
                'order' => 6,
                'is_active' => true,
                'permission_name' => 'lihat daftar affiliator',
                'children' => [
                    [
                        'text' => 'Daftar Affiliator',
                        'icon' => 'ti ti-list',
                        'url' => '/affiliators',
                        'type' => 'url',
                        'order' => 0,
                        'is_active' => true,
                        'permission_name' => 'lihat daftar affiliator',
                    ],
                    [
                        'text' => 'Riwayat Performa',
                        'icon' => 'ti ti-notebook',
                        'url' => '/affiliator/history',
                        'type' => 'url',
                        'order' => 1,
                        'is_active' => true,
                        'permission_name' => 'riwayat performa affiliator',
                    ],
                ],
            ],
            // [
            //     'text' => 'Freelancer',
            //     'icon' => 'ti ti-building-bank',
            //     'url' => '#',
            //     'type' => 'url',
            //     'order' => 8,
            //     'is_active' => true,
            //     'permission_name' => 'lihat daftar freelancer',
            //     'children' => [
            //         [
            //             'text' => 'Daftar freelancer',
            //             'icon' => 'ti ti-list',
            //             'url' => '/freelancer',
            //             'type' => 'url',
            //             'order' => 0,
            //             'is_active' => true,
            //             'permission_name' => 'lihat daftar freelancer',
            //         ],
            //         [
            //             'text' => 'Informasi Kepemilikan',
            //             'icon' => 'ti ti-notebook',
            //             'url' => '/freelancer/modal',
            //             'type' => 'url',
            //             'order' => 1,
            //             'is_active' => true,
            //             'permission_name' => 'saham freelancer',
            //         ],
            //     ],
            // ],
            [
                'text' => 'Finance',
                'icon' => 'ti ti-brand-mastercard',
                'url' => '#',
                'type' => 'url',
                'order' => 9,
                'is_active' => true,
                'permission_name' => 'lihat akun-akuntansi',
                'children' => [
                    [
                        'text' => 'Detail Akun',
                        'icon' => 'ti ti-list',
                        'url' => '/accounting',
                        'type' => 'url',
                        'order' => 0,
                        'is_active' => true,
                        'permission_name' => 'lihat akun-akuntansi',
                    ],
                    [
                        'text' => 'Input Jurnal',
                        'icon' => 'ti ti-notebook',
                        'url' => '/journals',
                        'type' => 'url',
                        'order' => 1,
                        'is_active' => true,
                        'permission_name' => 'lihat jurnal',
                    ],
                    [
                        'text' => 'Jurnal Umum',
                        'icon' => 'ti ti-notebook',
                        'url' => '/journals/general',
                        'type' => 'url',
                        'order' => 2,
                        'is_active' => true,
                        'permission_name' => 'lihat jurnal',
                    ],
                    [
                        'text' => 'Transaksi',
                        'icon' => 'ti ti-transaction-dollar',
                        'url' => '/journals/report',
                        'type' => 'url',
                        'order' => 3,
                        'is_active' => true,
                        'permission_name' => 'lihat jurnal',
                    ],
                    [
                        'text' => 'Buku Besar',
                        'icon' => 'ti ti-notebook',
                        'url' => '/journals/ledger',
                        'type' => 'url',
                        'order' => 4,
                        'is_active' => true,
                        'permission_name' => 'lihat jurnal',
                    ],
                    [
                        'text' => 'Neraca Saldo',
                        'icon' => 'ti ti-scale',
                        'url' => '/reports/balance_sheet',
                        'type' => 'url',
                        'order' => 5,
                        'is_active' => true,
                        'permission_name' => 'lihat jurnal',
                    ],
                    [
                        'text' => 'Laba Rugi',
                        'icon' => 'ti ti-notebook',
                        'url' => '/reports/income-statemment',
                        'type' => 'url',
                        'order' => 6,
                        'is_active' => true,
                        'permission_name' => 'lihat jurnal',
                    ],
                    [
                        'text' => 'Tutup Buku',
                        'icon' => 'ti ti-notebook',
                        'url' => '/periods',
                        'type' => 'url',
                        'order' => 7,
                        'is_active' => true,
                        'permission_name' => 'lihat jurnal',
                    ],
                ],
            ],
        ];

        foreach ($menus as $menuData) {
            $children = $menuData['children'] ?? [];
            unset($menuData['children']);

            $parent = Menu::create($menuData);

            foreach ($children as $childData) {
                $childData['parent_id'] = $parent->id;
                Menu::create($childData);
            }
        }
    }
}