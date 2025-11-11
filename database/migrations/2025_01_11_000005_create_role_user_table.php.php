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
		Schema::create(config('dynamic-permissions.tables.role_users', 'role_users'), function (Blueprint $table) {
			$table->id();
			$table->foreignId('user_id')->constrained()->onDelete('cascade');
			$table->foreignId('role_id')
				->constrained(config('dynamic-permissions.tables.roles', 'roles'))
				->onDelete('cascade');
			$table->timestamp('assigned_at')->useCurrent();
			$table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null');
			$table->timestamps();

			// Unique constraint to prevent duplicate assignments
			$table->unique(['user_id', 'role_id']);

			// Indexes for queries
			$table->index('assigned_at');
			$table->index('assigned_by');
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
