<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** A class name ("A manhã") only needs to be unique inside its room/year. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->unique(['school_year_id', 'name']);
        });

        // Classes imported as "G2 A manhã" inside room "G2" become just "A manhã".
        $classes = DB::table('classes')->join('school_years', 'school_years.id', '=', 'classes.school_year_id')
            ->select('classes.id', 'classes.name', 'classes.school_year_id', 'school_years.name as year')->get();
        foreach ($classes as $class) {
            $prefix = $class->year.' ';
            $short = trim(mb_substr($class->name, mb_strlen($prefix)));
            $taken = DB::table('classes')->where('school_year_id', $class->school_year_id)->where('name', $short)->exists();
            if (str_starts_with($class->name, $prefix) && $short !== '' && ! $taken) {
                DB::table('classes')->where('id', $class->id)->update(['name' => $short]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropUnique(['school_year_id', 'name']);
            $table->unique('name');
        });
    }
};
