<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * The Fleet portal has no database of its own — all data lives behind the
 * ShaloTrack C# API. Nothing to seed.
 *
 * (The previous seeder referenced App\Models\User, which does not exist in
 * this project — a leftover from the Laravel Breeze starter kit.)
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        //
    }
}