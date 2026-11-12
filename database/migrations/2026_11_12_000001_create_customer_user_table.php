<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_user', function (Blueprint $table) {
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['customer_id', 'user_id']);
        });

        DB::table('users')
            ->whereNotNull('customer_id')
            ->orderBy('id')
            ->chunkById(500, function ($users): void {
                $assignments = $users
                    ->map(fn ($user) => [
                        'customer_id' => $user->customer_id,
                        'user_id' => $user->id,
                    ])
                    ->all();

                if ($assignments !== []) {
                    DB::table('customer_user')->insertOrIgnore($assignments);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_user');
    }
};
