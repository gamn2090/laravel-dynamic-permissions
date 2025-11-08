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
		Schema::create('role_features', function (Blueprint $table) {
			$table->id();
			$table->unsignedBigInteger('role_id');
			$table->foreignId('feature_id')->constrained()->onDelete('cascade');
			$table->timestamps();

			$table->unique(['role_id', 'feature_id']);
			$table->index('role_id');
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('role_features');
	}
};
