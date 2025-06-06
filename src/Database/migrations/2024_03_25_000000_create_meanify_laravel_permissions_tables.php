<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @return void
     */
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('application')->nullable();
            $table->string('code')->nullable();
            $table->string('label');
            $table->string('group')->nullable();
            $table->boolean('apply')->default(false);
            $table->string('class')->nullable();
            $table->string('method')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('application')->nullable();
            $table->string('name')->nullable();
            $table->string('description')->nullable();
            $table->boolean('super_user_role')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('roles_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        if (Schema::hasTable('users')) {
            Schema::create('users_roles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('role_id')->constrained()->onDelete('cascade');
                $table->timestamps();
            });
        }


        //Seed Admin role
        \Illuminate\Support\Facades\Artisan::call('meanify:permissions', [
            '--application'      => 'admin',
            '--action'           => 'generate',
            '--force'            => true,
            '--database'         => true,
            '--path'             => 'app/Http/Controllers',
            '--file'             => 'storage/temp/permissions-{datetime}.yaml',
            '--connection'       => 'mysql',
        ]);
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('users_roles');
        Schema::dropIfExists('roles_permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
    }
};
