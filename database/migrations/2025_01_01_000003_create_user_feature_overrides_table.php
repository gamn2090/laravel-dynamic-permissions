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
		Schema::create('user_feature_overrides', function (Blueprint $table) {
			$table->id();
			$table->unsignedBigInteger('user_id');
			$table->foreignId('feature_id')->constrained()->onDelete('cascade');
			$table->boolean('can_access');
			$table->boolean('has_access');
			$table->unsignedBigInteger('granted_by')->nullable();
			$table->unsignedBigInteger('revoked_by')->nullable();
			$table->text('reason')->nullable();
			$table->timestamp('expires_at')->nullable();
			$table->timestamp('granted_at')->nullable();
			$table->timestamp('revoked_at')->nullable();
			$table->timestamps();

			$table->unique(['user_id', 'feature_id']);
			$table->index(['user_id', 'has_access']);
			$table->index('expires_at');
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('user_feature_overrides');
	}
};
