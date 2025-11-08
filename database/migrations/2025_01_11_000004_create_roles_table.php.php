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
		Schema::create(config('dynamic-permissions.tables.roles', 'roles'), function (Blueprint $table) {
			$table->id();
			$table->string('name')->unique();
			$table->string('slug')->unique();
			$table->text('description')->nullable();
			$table->boolean('is_active')->default(true)->index();
			$table->boolean('is_default')->default(false)->index();
			$table->integer('priority')->default(0)->index();
			$table->timestamps();
			$table->softDeletes();

			// Indexes for common queries
			$table->index(['is_active', 'priority']);
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists(config('dynamic-permissions.tables.roles', 'roles'));
	}
};
