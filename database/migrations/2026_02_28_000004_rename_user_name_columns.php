<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add new 'name' column
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');
        });

        DB::table('users')
            ->select('id', 'first_name', 'last_name')
            ->orderBy('id')
            ->get()
            ->each(function ($user): void {
                $name = trim(implode(' ', array_filter([
                    $user->first_name,
                    $user->last_name,
                ])));

                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['name' => $name !== '' ? $name : null]);
            });

        // Drop old columns
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('first_name');
            $table->dropColumn('last_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back old columns
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('id');
            $table->string('last_name')->nullable()->after('first_name');
        });

        DB::table('users')
            ->select('id', 'name')
            ->orderBy('id')
            ->get()
            ->each(function ($user): void {
                $fullName = trim((string) $user->name);

                if ($fullName === '') {
                    return;
                }

                $parts = preg_split('/\s+/', $fullName, 2) ?: [];
                $firstName = $parts[0] ?? null;
                $lastName = $parts[1] ?? null;

                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                    ]);
            });

        // Drop the new column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};
