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
			$table->unsignedBigInteger('user_id')->index();
			$table->foreignId('role_id')
				->constrained(config('dynamic-permissions.tables.roles', 'roles'))
				->onDelete('cascade');
			$table->timestamp('assigned_at')->useCurrent();
			$table->unsignedBigInteger('assigned_by')->nullable()->index();
			$table->timestamps();

			// Unique constraint to prevent duplicate assignments
			$table->unique(['user_id', 'role_id']);

			// Indexes for queries
			$table->index('assigned_at');
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
