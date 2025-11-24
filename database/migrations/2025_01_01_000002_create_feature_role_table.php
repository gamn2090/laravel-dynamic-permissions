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
		Schema::create(config('dynamic-permissions.tables.feature_role', 'feature_role'), function (Blueprint $table) {
			$table->id();
			$table->foreignId('role_id')
				->constrained(config('dynamic-permissions.tables.roles', 'roles'))
				->onDelete('cascade');
			$table->foreignId('feature_id')->constrained()->onDelete('cascade');
			$table->boolean('can_access')->default(true);
			$table->unsignedBigInteger('granted_by')->nullable();
			$table->timestamp('granted_at')->useCurrent();
			$table->timestamps();

			$table->index('can_access');
			$table->index('granted_by');
			$table->index('granted_at');
			$table->unique(['role_id', 'feature_id']);
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists(config('dynamic-permissions.tables.feature_role', 'feature_role'));
	}
};
