<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_years', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_year_id')->constrained('school_years')->restrictOnDelete();
            $table->string('name', 80)->unique();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('responsible_email')->index();
            $table->timestamps();

            $table->unique(['responsible_email', 'name']);
        });

        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->date('date')->index();
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('duration_minutes');
            $table->unsignedSmallInteger('break_minutes')->default(0);
            $table->enum('status', ['draft', 'published', 'closed'])->default('draft')->index();
            $table->timestamps();
        });

        Schema::create('meeting_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->restrictOnDelete();

            $table->unique(['meeting_id', 'class_id']);
        });

        Schema::create('time_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->restrictOnDelete();
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('status', ['available', 'booked'])->default('available')->index();

            $table->unique(['meeting_id', 'class_id', 'start_time']);
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->restrictOnDelete();
            $table->foreignId('time_slot_id')->nullable()->constrained()->nullOnDelete();
            $table->string('responsible_email')->index();
            $table->string('responsible_name', 150);
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->string('student_name', 150);
            $table->enum('status', ['confirmed', 'cancelled'])->default('confirmed')->index();

            // Filled only while the appointment is active; NULL once cancelled.
            // Unique indexes ignore NULLs, so they enforce RB05 and RB06 at the database level.
            $table->unsignedBigInteger('active_slot_id')->nullable()->unique();
            $table->unsignedBigInteger('active_meeting_id')->nullable();
            $table->unique(['student_id', 'active_meeting_id']);

            $table->timestamps();
        });

        Schema::create('authentication_codes', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('code_hash', 64);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('actor', 190)->nullable();
            $table->string('action', 60);
            $table->string('entity', 60);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('data')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 60)->primary();
            $table->text('value')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('authentication_codes');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('time_slots');
        Schema::dropIfExists('meeting_classes');
        Schema::dropIfExists('meetings');
        Schema::dropIfExists('students');
        Schema::dropIfExists('classes');
        Schema::dropIfExists('school_years');
    }
};
