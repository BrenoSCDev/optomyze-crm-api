<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->string('message_id')->unique();
            $table->unsignedBigInteger('lead_id')->nullable(); // se quiser vincular a um lead
            $table->string('from')->nullable(); // número que enviou
            $table->string('to')->nullable();   // número que recebeu
            $table->text('message')->nullable();
            $table->enum('direction', ['inbound', 'outbound']); // recebido ou enviado
            $table->string('status')->nullable(); // enviado, entregue, lido
            $table->timestamp('timestamp')->nullable();
            $table->json('raw_payload')->nullable(); // guardar json completo se quiser
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
