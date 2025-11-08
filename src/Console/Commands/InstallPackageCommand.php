<?php

namespace gamn2090\DynamicPermissions\Console\Commands;

use Illuminate\Console\Command;

class InstallPackageCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 */
	protected $signature = 'dynamic-permissions:install
                            {--force : Overwrite existing files}';

	/**
	 * The console command description.
	 */
	protected $description = 'Install Dynamic Permissions package';

	/**
	 * Execute the console command.
	 */
	public function handle(): int
	{
		$this->info('Installing Dynamic Permissions...');

		// Publish config
		$this->call('vendor:publish', [
			'--tag' => 'dynamic-permissions-config',
			'--force' => $this->option('force'),
		]);

		$this->info('✓ Config published');

		// Publish migrations
		$this->call('vendor:publish', [
			'--tag' => 'dynamic-permissions-migrations',
			'--force' => $this->option('force'),
		]);

		$this->info('✓ Migrations published');

		// Run migrations
		if ($this->confirm('Do you want to run migrations now?', true)) {
			$this->call('migrate');
			$this->info('✓ Migrations executed');
		}

		// Add trait to User model
		$this->info("\n⚠ Don't forget to:");
		$this->line('1. Add HasDynamicPermissions trait to your User model:');
		$this->line('   use gamn2090\DynamicPermissions\Traits\HasDynamicPermissions;');
		$this->line('');
		$this->line('2. Create a Role model or add role_id column to users table');
		$this->line('');
		$this->line('3. Run: php artisan dynamic-permissions:sync to sync features');

		$this->newLine();
		$this->info('✓ Dynamic Permissions installed successfully!');

		return self::SUCCESS;
	}
}
