<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('characters', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name')->unique();
            $table->unsignedSmallInteger('level')->default(1);
            $table->unsignedBigInteger('xp')->default(0);
            $table->bigInteger('gold')->default(0);
            $table->bigInteger('gems')->default(0);
            $table->jsonb('attributes')->default(DB::raw("'{}'::jsonb"));   // {str,agi,int,vit,...}
            $table->jsonb('proficiencies')->default(DB::raw("'{}'::jsonb")); // np. sword/bow/magic
            $table->unsignedInteger('version')->default(1); // optimistic lock
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('skills', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('code')->unique(); // sword|bow|fire_magic|alchemy|blacksmithing...
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('character_skills', function (Blueprint $table) {
            $table->id();
            $table->ulid('character_id');
            $table->unsignedSmallInteger('skill_id');
            $table->unsignedBigInteger('xp')->default(0);
            $table->unsignedSmallInteger('level')->default(1);
            $table->timestamps();

            $table->foreign('character_id')->references('id')->on('characters')->cascadeOnDelete();
            $table->foreign('skill_id')->references('id')->on('skills')->cascadeOnDelete();
            $table->unique(['character_id', 'skill_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_skills');
        Schema::dropIfExists('skills');
        Schema::dropIfExists('characters');
    }
};
