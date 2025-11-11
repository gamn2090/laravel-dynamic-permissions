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
		Schema::create('feature_roles', function (Blueprint $table) {
			$table->id();
			$table->unsignedBigInteger('role_id');
			$table->foreignId('feature_id')->constrained()->onDelete('cascade');
			$table->boolean('can_access')->default(true)->after('feature_id');
			$table->foreignId('granted_by')->nullable()->after('can_access')->constrained('users')->onDelete('set null');
			$table->timestamp('granted_at')->useCurrent()->after('granted_by');
			$table->timestamps();

			$table->index('can_access');
			$table->index('granted_by');
			$table->index('granted_at');
			$table->unique(['role_id', 'feature_id']);
			$table->index('role_id');
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('feature_roles');
	}
};
