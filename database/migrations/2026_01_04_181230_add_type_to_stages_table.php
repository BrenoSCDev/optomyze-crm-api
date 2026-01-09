<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stages', function (Blueprint $table) {
            // stage = real stage, model = template
            $table->enum('template_type', ['stage', 'model'])
                ->default('stage')
                ->after('settings');

            $table->index('template_type');
        });
    }

    public function down(): void
    {
        Schema::table('stages', function (Blueprint $table) {
            $table->dropIndex(['template_type']);
            $table->dropColumn('template_type');
        });
    }
};
