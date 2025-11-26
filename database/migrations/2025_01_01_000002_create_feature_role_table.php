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
			$table->foreignId('feature_id')->constrained('features')->onDelete('cascade');
			$table->boolean('can_access')->default(false);
			$table->unsignedBigInteger('granted_by')->nullable()->index();
			$table->timestamp('granted_at')->nullable();
			$table->timestamps();

			$table->unique(['role_id', 'feature_id']);
			$table->index(['role_id', 'feature_id', 'can_access']);
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
