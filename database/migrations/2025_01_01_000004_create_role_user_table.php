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
		Schema::create(config('dynamic-permissions.tables.role_user', 'role_user'), function (Blueprint $table) {
			$table->id();
			$table->foreignId('role_id')->constrained(config('dynamic-permissions.tables.roles', 'roles'))->onDelete('cascade');
			$table->foreignId('user_id')->constrained('users')->onDelete('cascade');
			$table->timestamp('assigned_at')->nullable();
			$table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null');
			$table->timestamps();

			$table->unique(['role_id', 'user_id']);
			$table->index('role_id');
			$table->index('user_id');
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists(config('dynamic-permissions.tables.role_user', 'role_user'));
	}
};
